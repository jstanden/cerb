/*
 * CerbUI.SheetBuilder — visual builder for a sheet KATA (layout + columns) against a sample dataset.
 *
 * Layout: a column-type palette (left) + a document column [ dataset panel · column strip (the
 * drag/drop/select/reorder canvas) · live server-rendered sheet preview · generated-KATA pane ] + an
 * inspector (right) for the selected column (or the sheet's layout settings). The builder's model is the
 * source of truth; the KATA pane + preview are derived from it. Export is copy-to-clipboard.
 *
 * A sheet renders as a table/grid, not a list of wrapped elements, so — unlike CerbUI.FormBuilder — the
 * live preview is read-only and the selectable canvas is a separate column strip.
 *
 * The dataset has three authoring modes (manual / automation / dataQuery), narrowed per caller via
 * allowedDataSourceTypes; the resolved sample rows define the dict keys the column key-pickers offer.
 *
 * Usage:
 *   new CerbUI.SheetBuilder(el, {
 *     columnSchema, layoutSchema, dataSourceSchema,          // Cerb\Sheets\SheetBuilder::get*Schema()
 *     allowedColumnTypes, allowedDataSourceTypes,
 *     recordTypes, sheetDataAutomations,
 *   });
 *
 * Depends on CerbUI.Sidebar, CerbUI.SplitPane, CerbUI.Droppable, CerbUI.Sortable, CerbUI.Switcher,
 * CerbUI.SelectMenu, CerbUI.Toggle, CerbUI.SearchQuery, CerbUI.JsonEditor, CerbUI.KataEditor, and the
 * legacy genericAjaxPost() for the preview/sample round-trips.
 */
CerbUI.SheetBuilder = class {
	static _instances = new WeakMap();
	static from(el) { return CerbUI.SheetBuilder._instances.get(el); }

	// Palette grouping + per-category icon-square color (unknown types → "Columns").
	static _CATEGORIES = [
		{ label: 'Values',  color: '#3182ce', types: ['text','date','markdown','code','time_elapsed'] },
		{ label: 'Records', color: '#805ad5', types: ['card','link','icon'] },
		{ label: 'Actions', color: '#38a169', types: ['selection','slider','interaction','search','search_button','toolbar'] },
	];
	static _OTHER_COLOR = '#718096';
	// PHP date() format presets offered by a `date` column's Format field (tune freely).
	static _DATE_FORMATS = ['d-M-Y H:i:s T', 'd-M-Y', 'Y-m-d', 'Y-m-d H:i:s', 'D, d M Y', 'g:i A', 'M j, Y', 'r'];

	static _humanize(t) {
		return String(t).replace(/([A-Z_])/g, (m, c) => c === '_' ? ' ' : ' ' + c).replace(/^./, c => c.toUpperCase()).trim();
	}

	constructor(el, opts = {}) {
		this.el = (typeof el === 'string') ? document.querySelector(el) : el;
		if(!this.el) return;
		CerbUI.SheetBuilder._instances.set(this.el, this);

		this.opts = Object.assign({
			columnSchema: {}, layoutSchema: { fields: [] }, dataSourceSchema: {},
			allowedColumnTypes: [], allowedDataSourceTypes: [],
			recordTypes: [], sheetDataAutomations: [],
			initial: null,   // {schema, data} parsed KATA to seed the model (Form Builder round-trip)
		}, opts);

		this.columnSchema = this.opts.columnSchema || {};
		this.layoutSchema = this.opts.layoutSchema || { fields: [] };
		this.dataSourceSchema = this.opts.dataSourceSchema || {};
		this.recordTypes = this.opts.recordTypes || [];
		this.sheetDataAutomations = this.opts.sheetDataAutomations || [];
		// automation record id → its name (the `data: automation: uri:`), so a RecordChooser pick resolves to a uri.
		this._automationUriById = {};
		this.sheetDataAutomations.forEach(a => { if(a.id != null) this._automationUriById[a.id] = a.uri; });

		// Only offer column/dataset types the caller allows AND we have a descriptor for.
		this.columnTypes = (this.opts.allowedColumnTypes || []).filter(t => this.columnSchema[t]);
		this.dataSourceTypes = (this.opts.allowedDataSourceTypes || []).filter(m => this.dataSourceSchema[m]);

		this.model = {
			layout: { style: 'table', headings: true, colorPalettes: [] },
			columns: [],
			// Per-mode configs so switching modes preserves each (compare sources without losing setup).
			dataset: { mode: this.dataSourceTypes[0] || 'manual', configs: {} },
		};

		// Seed from an existing sheet (Form Builder round-trip), else a sensible starter `card/_label` column.
		if(this.opts.initial && (this.opts.initial.schema || this.opts.initial.data)) {
			this._importModel(this.opts.initial);
		} else if(this.columnTypes.indexOf('card') >= 0) {
			const seed = this._makeColumn('card');
			seed.key = '_label';
			this.model.columns.push(seed);
		}

		// Records mode always needs a record_type for a valid sample query (default Ticket + a useful expand).
		if(this.model.dataset.mode === 'records') {
			const cfg = this._dsConfig();
			if(!cfg.record_type) cfg.record_type = this._defaultRecordType();
			if(cfg.record_type === 'ticket' && !cfg.expand) cfg.expand = 'customfields,owner_';
		}

		this.selectedIndex = -1;   // selected column, or -1
		this.layoutMode = false;   // true → the inspector shows sheet layout settings
		this.dataSourceMode = true;   // open the Data source inspector by default on start
		this.sampleKeys = [];      // dict keys from the last loaded sample (drives datakey pickers)
		this.sampleData = [];
		this._previewPage = 0;     // current preview page (paging)
		this._uidCounter = 0;
		this._paletteItems = {};
		this._refreshTimer = null;

		this._build();
		this.renderColumnStrip();
		this.renderInspector();
		// Auto-load a sample so keys + preview are ready immediately — most users won't need to open Data source.
		this.loadSample();
	}

	// ── DOM scaffold ────────────────────────────────────────────────────

	_build() {
		this.el.classList.add('cerb-sb');
		this.el.innerHTML = '';

		this.el.appendChild(this._buildPalette());

		const main = document.createElement('div');
		main.className = 'cerb-sb--main';

		const doc = document.createElement('div');
		doc.className = 'cerb-sb--doc';

		doc.appendChild(this._buildColumnStrip());

		// Live server-rendered sheet preview (read-only).
		this.previewEl = document.createElement('div');
		this.previewEl.className = 'cerb-sb--preview';
		// The sheet render fires `cerb-sheet--page-changed` on a paging click (bubbles up); re-render that page.
		$(this.previewEl).on('cerb-sheet--page-changed', (e) => {
			this._previewPage = parseInt(e.page, 10) || 0;
			this.refreshPreview(true);   // keep the page
		});
		doc.appendChild(this.previewEl);

		doc.appendChild(this._buildKataPane());

		this.inspectorEl = document.createElement('div');
		this.inspectorEl.className = 'cerb-sb--inspector';

		main.appendChild(doc);
		main.appendChild(this.inspectorEl);
		this.el.appendChild(main);

		if(window.CerbUI && CerbUI.SplitPane)
			this.splitpane = new CerbUI.SplitPane(main, { orientation: 'horizontal', ratio: 0.62, min: 0.3, storageKey: 'cerb-sheet-builder' });

		this._buildDropZone();

		if(window.CerbUI && CerbUI.KataEditor)
			this._kataEditor = new CerbUI.KataEditor(this.kataEl, { readOnly: true, maxLines: 18 });
	}

	_buildPalette() {
		const aside = document.createElement('aside');
		aside.className = 'cerb-ui-sidebar cerb-sb--palette';
		const body = document.createElement('div');
		body.className = 'cerb-ui-sidebar--body';

		const seen = {};
		const groups = [];
		CerbUI.SheetBuilder._CATEGORIES.forEach(cat => {
			const types = cat.types.filter(t => this.columnTypes.indexOf(t) >= 0);
			if(types.length) { groups.push({ label: cat.label, color: cat.color, types: types }); types.forEach(t => seen[t] = true); }
		});
		const leftovers = this.columnTypes.filter(t => !seen[t]);
		if(leftovers.length) groups.push({ label: 'Columns', color: CerbUI.SheetBuilder._OTHER_COLOR, types: leftovers });

		groups.forEach(group => {
			const sec = document.createElement('div');
			sec.className = 'cerb-ui-sidebar--section';
			const lbl = document.createElement('div');
			lbl.className = 'cerb-ui-sidebar--label';
			lbl.textContent = group.label;
			sec.appendChild(lbl);
			const ul = document.createElement('ul');
			group.types.forEach(type => {
				const li = document.createElement('li');
				li.dataset.nodeType = type;
				li.dataset.icon = (this.columnSchema[type] && this.columnSchema[type].icon) || 'text';
				li.dataset.color = group.color;
				li.dataset.name = (this.columnSchema[type] && this.columnSchema[type].title) || CerbUI.SheetBuilder._humanize(type);
				ul.appendChild(li);
				this._paletteItems[type] = li;
			});
			sec.appendChild(ul);
			body.appendChild(sec);
		});

		aside.appendChild(body);
		if(window.CerbUI && CerbUI.Sidebar)
			this.sidebar = new CerbUI.Sidebar(aside, { palette: true, filter: false, onSelect: () => true });
		return aside;
	}

	// The Data source inspector view: a mode switcher + a "Load sample" action + the active mode's editor.
	_renderDataSourceInspector(host) {
		const head = document.createElement('div');
		head.className = 'cerb-sb--inspector-head';
		head.innerHTML = '<span class="cerb-icons cerb-icon-database"></span> <b>Data source</b>';
		host.appendChild(head);

		// Mode selector (only the allowed modes) — a SelectMenu with each mode's icon (roomier than a switcher).
		if(this.dataSourceTypes.length > 1) {
			const swField = this._formField('');
			const sel = document.createElement('select');
			sel.className = 'cerb-sb--dataset-select';
			this.dataSourceTypes.forEach(mode => {
				const o = document.createElement('option');
				o.value = mode;
				o.textContent = (this.dataSourceSchema[mode] && this.dataSourceSchema[mode].title) || mode;
				const icon = this.dataSourceSchema[mode] && this.dataSourceSchema[mode].icon;
				if(icon) o.setAttribute('data-cerb-ui-icon', icon);
				if(mode === this.model.dataset.mode) o.selected = true;
				sel.appendChild(o);
			});
			swField.appendChild(sel);
			host.appendChild(swField);
			this._enhance.push(() => { if(window.CerbUI && CerbUI.SelectMenu) new CerbUI.SelectMenu(sel, { onSelect: (v) => this._setDatasetMode(v) }); });
		}

		this._datasetBody = document.createElement('div');
		this._datasetBody.className = 'cerb-sb--dataset-body';
		host.appendChild(this._datasetBody);
		this._renderDatasetMode();

		const loadRow = document.createElement('div');
		loadRow.className = 'cerb-sb--dataset-loadrow';
		const load = document.createElement('button');
		load.type = 'button';
		load.className = 'cerb-ui-button cerb-ui-button--outline cerb-sb--dataset-load';
		load.innerHTML = '<span class="cerb-icons cerb-icon-refresh"></span> Load sample';
		load.addEventListener('click', () => this.loadSample());
		loadRow.appendChild(load);
		this._datasetStatus = document.createElement('span');
		this._datasetStatus.className = 'cerb-sb--dataset-status';
		this._datasetStatus.textContent = this._datasetStatusText || '';
		loadRow.appendChild(this._datasetStatus);
		host.appendChild(loadRow);

		this._renderSampleData(host);
	}

	// The first loaded sample row as a key → value table (the keys the column pickers offer, with real values).
	// Idempotent: replaces any existing sample table in the host, so it can refresh in place after a load.
	_renderSampleData(host) {
		const existing = host.querySelector('.cerb-sb--sample');
		if(existing) existing.remove();
		if(!this.sampleData || !this.sampleData.length) return;
		const row0 = this.sampleData[0] || {};
		const keys = this.sampleKeys.length ? this.sampleKeys : Object.keys(row0).filter(k => !String(k).startsWith('__'));
		if(!keys.length) return;

		const sec = document.createElement('div');
		sec.className = 'cerb-sb--sample';
		const head = document.createElement('div');
		head.className = 'cerb-sb--sample-head';
		head.textContent = 'Sample · row 1 of ' + this.sampleData.length;
		sec.appendChild(head);

		const table = document.createElement('div');
		table.className = 'cerb-sb--sample-table';
		keys.forEach(k => {
			let v = row0[k];
			if(v !== null && typeof v === 'object') v = JSON.stringify(v);
			v = (v === undefined || v === null) ? '' : String(v);
			const r = document.createElement('div');
			r.className = 'cerb-sb--sample-row';
			const kEl = document.createElement('span');
			kEl.className = 'cerb-sb--sample-key';
			kEl.textContent = k;
			kEl.title = k;
			const vEl = document.createElement('span');
			vEl.className = 'cerb-sb--sample-val';
			vEl.textContent = v;
			vEl.title = v;
			r.appendChild(kEl); r.appendChild(vEl);
			table.appendChild(r);
		});
		sec.appendChild(table);
		host.appendChild(sec);
	}

	_buildColumnStrip() {
		const wrap = document.createElement('div');
		wrap.className = 'cerb-ui-panel cerb-ui-panel--spaced cerb-sb--columns-wrap';

		const header = document.createElement('div');
		header.className = 'cerb-ui-header cerb-ui-header--tight cerb-ui-header--center';
		const title = document.createElement('div');
		title.className = 'cerb-ui-header--title-sm';
		title.textContent = 'Columns';
		header.appendChild(title);
		const right = document.createElement('div');
		right.className = 'cerb-ui-header--right';
		const dataBtn = document.createElement('button');
		dataBtn.type = 'button';
		dataBtn.className = 'cerb-ui-button cerb-ui-button--subtle cerb-sb--data-btn';
		dataBtn.innerHTML = '<span class="cerb-icons cerb-icon-database"></span> Data source';
		dataBtn.addEventListener('click', () => this.selectDataSource());
		right.appendChild(dataBtn);
		const gear = document.createElement('button');
		gear.type = 'button';
		gear.className = 'cerb-ui-button cerb-ui-button--subtle cerb-sb--layout-btn';
		gear.innerHTML = '<span class="cerb-icons cerb-icon-adjust"></span> Sheet settings';
		gear.addEventListener('click', () => this.selectLayout());
		right.appendChild(gear);
		header.appendChild(right);
		wrap.appendChild(header);

		this.columnsEl = document.createElement('div');
		this.columnsEl.className = 'cerb-sb--columns';
		this.columnsEl.addEventListener('click', (e) => {
			const chip = e.target.closest('.cerb-sb-column');
			if(chip) {
				const idx = parseInt(chip.getAttribute('data-cerb-sb-index'), 10);
				if(!isNaN(idx)) this.select(idx);
			} else {
				this.select(-1);
			}
		});
		wrap.appendChild(this.columnsEl);

		return wrap;
	}

	_buildKataPane() {
		const kata = document.createElement('div');
		kata.className = 'cerb-sb--kata';
		const bar = document.createElement('div');
		bar.className = 'cerb-sb--kata-bar';
		const toggle = document.createElement('button');
		toggle.type = 'button';
		toggle.className = 'cerb-sb--kata-toggle';
		this._kataChevron = document.createElement('span');
		this._kataChevron.className = 'cerb-icons cerb-icon-chevron-up cerb-sb--kata-chevron';
		const label = document.createElement('span');
		label.className = 'cerb-sb--kata-label';
		label.textContent = 'Generated KATA';
		toggle.appendChild(this._kataChevron);
		toggle.appendChild(label);
		toggle.addEventListener('click', () => this._toggleKata());
		const copy = document.createElement('button');
		copy.type = 'button';
		copy.className = 'cerb-ui-button cerb-ui-button--subtle cerb-sb--copy';
		copy.innerHTML = '<span class="cerb-icons cerb-icon-copy"></span> Copy';
		copy.addEventListener('click', () => this._copyKata());
		bar.appendChild(toggle);
		bar.appendChild(copy);
		this.kataEl = document.createElement('textarea');
		this.kataEl.className = 'cerb-sb--kata-text';
		this.kataEl.readOnly = true;
		this.kataEl.spellcheck = false;
		kata.appendChild(bar);
		kata.appendChild(this.kataEl);
		this.kataWrapEl = kata;

		let collapsed = true;
		try { const v = window.localStorage.getItem('cerb-sheet-builder-kata'); if(v !== null) collapsed = (v === '1'); } catch(e) {}
		// Applied after the editor is built (in _build) via _toggleKata; store initial intent.
		this._kataCollapsed = collapsed;
		if(collapsed) kata.classList.add('cerb-sb--kata--collapsed');
		this._kataChevron.className = 'cerb-icons cerb-icon-chevron-' + (collapsed ? 'up' : 'down') + ' cerb-sb--kata-chevron';

		return kata;
	}

	_toggleKata(force) {
		const collapsed = (force === undefined) ? !this.kataWrapEl.classList.contains('cerb-sb--kata--collapsed') : force;
		this.kataWrapEl.classList.toggle('cerb-sb--kata--collapsed', collapsed);
		this._kataChevron.className = 'cerb-icons cerb-icon-chevron-' + (collapsed ? 'up' : 'down') + ' cerb-sb--kata-chevron';
		try { window.localStorage.setItem('cerb-sheet-builder-kata', collapsed ? '1' : '0'); } catch(e) {}
		// An editor built inside a collapsed (display:none) pane measures zero — nudge a re-render on expand.
		if(!collapsed && this._kataEditor) this._kataEditor.setValue(this._getKataText());
	}

	_setKataText(kata) {
		if(this._kataEditor) this._kataEditor.setValue(kata);
		else if(this.kataEl) this.kataEl.value = kata;
	}
	_getKataText() {
		if(this._kataEditor) return this._kataEditor.getValue();
		return this.kataEl ? this.kataEl.value : this.serializeKata();
	}
	_copyKata() {
		const text = this.serializeKata();
		const ta = document.createElement('textarea');
		ta.value = text;
		ta.style.position = 'fixed';
		ta.style.opacity = '0';
		document.body.appendChild(ta);
		ta.select();
		try { document.execCommand('copy'); } catch(e) {}
		document.body.removeChild(ta);
		if(window.Devblocks && Devblocks.createAlert) Devblocks.createAlert('Copied to clipboard!');
	}

	_buildDropZone() {
		if(!(window.CerbUI && CerbUI.Droppable)) return;

		this._dropMarker = document.createElement('div');
		this._dropMarker.className = 'cerb-sb--drop-indicator';

		this._onDragMove = (e) => {
			const r = this.columnsEl.getBoundingClientRect();
			if(e.clientY < r.top - 40 || e.clientY > r.bottom + 40) return;
			this._showDropMarker(this._dropIndex(e.clientY));
		};

		this.dropzone = new CerbUI.Droppable(this.columnsEl, {
			accept: (item) => !!(item && item.matches && item.matches('.cerb-ui-sidebar--item')),
			onOver: (info) => {
				this._showDropMarker(this._dropIndex(info.clientY));
				document.addEventListener('pointermove', this._onDragMove, true);
			},
			onOut: () => {
				document.removeEventListener('pointermove', this._onDragMove, true);
				this._hideDropMarker();
			},
			onDrop: (info) => {
				document.removeEventListener('pointermove', this._onDragMove, true);
				this._hideDropMarker();
				const type = info.payload && info.payload.nodeType;
				if(!type || this.columnTypes.indexOf(type) < 0) return false;
				this.addColumn(type, this._dropIndex(info.clientY));
			},
		});
	}

	_dropIndex(clientY) {
		const nodes = Array.from(this.columnsEl.querySelectorAll('.cerb-sb-column'));
		for(let i = 0; i < nodes.length; i++) {
			const r = nodes[i].getBoundingClientRect();
			if(clientY < r.top + r.height / 2) return i;
		}
		return nodes.length;
	}
	_showDropMarker(index) {
		const nodes = this.columnsEl.querySelectorAll('.cerb-sb-column');
		if(index >= nodes.length) this.columnsEl.appendChild(this._dropMarker);
		else this.columnsEl.insertBefore(this._dropMarker, nodes[index]);
	}
	_hideDropMarker() {
		if(this._dropMarker && this._dropMarker.parentNode) this._dropMarker.parentNode.removeChild(this._dropMarker);
	}

	// ── Model mutation ──────────────────────────────────────────────────

	_uid() { return 'c' + (++this._uidCounter); }

	_makeColumn(type) {
		const sch = this.columnSchema[type] || {};
		// Start with a BLANK key so the inspector's Column-key chooser suggests immediately (nothing to clear first).
		const col = { type: type, key: '', fields: {}, raw: null };
		if(sch.curated && sch.fields) {
			sch.fields.forEach(f => { if(f.default !== undefined) col.fields[f.key] = f.default; });
			const nw = sch.new || {};
			Object.keys(nw).forEach(k => { col.fields[k] = nw[k]; });
		} else {
			// Action types: raw-KATA params body, seeded from `new`.
			col.raw = this._newToKata(sch.new || {});
		}
		return col;
	}

	// Serialize a `new` starter object into a simple KATA params body (used for raw/action columns).
	_newToKata(obj) {
		const lines = [];
		Object.keys(obj).forEach(k => { lines.push(k + ': ' + obj[k]); });
		return lines.join('\n');
	}

	addColumn(type, index) {
		const col = this._makeColumn(type);
		let at = (index == null) ? this.model.columns.length : Math.max(0, Math.min(index, this.model.columns.length));
		this.model.columns.splice(at, 0, col);
		this.selectedIndex = this.model.columns.indexOf(col);
		this.layoutMode = false;
		this.renderColumnStrip();
		this.refreshPreview();
		this.renderInspector();
	}

	removeColumn(index) {
		if(index < 0 || index >= this.model.columns.length) return;
		this.model.columns.splice(index, 1);
		if(this.selectedIndex === index) this.selectedIndex = -1;
		else if(this.selectedIndex > index) this.selectedIndex--;
		this.renderColumnStrip();
		this.refreshPreview();
		this.renderInspector();
	}

	select(index) {
		this.layoutMode = false;
		this.dataSourceMode = false;
		this.selectedIndex = index;
		this._markSelected();
		this.renderInspector();
	}

	selectLayout() {
		this.layoutMode = true;
		this.dataSourceMode = false;
		this.selectedIndex = -1;
		this._markSelected();
		this.renderInspector();
	}

	selectDataSource() {
		this.dataSourceMode = true;
		this.layoutMode = false;
		this.selectedIndex = -1;
		this._markSelected();
		this.renderInspector();
	}

	// ── Column strip ────────────────────────────────────────────────────

	renderColumnStrip() {
		const host = this.columnsEl;
		host.innerHTML = '';
		if(!this.model.columns.length) {
			const empty = document.createElement('div');
			empty.className = 'cerb-sb--columns-empty';
			empty.textContent = 'Drag a column type from the palette to start.';
			host.appendChild(empty);
			return;
		}
		this.model.columns.forEach((col, i) => {
			const sch = this.columnSchema[col.type] || {};
			const chip = document.createElement('div');
			chip.className = 'cerb-sb-column';
			chip.setAttribute('data-cerb-sb-index', i);
			const icon = sch.icon || 'text';
			// Left (prominent) = the identifier that distinguishes columns (`card/_label`); right (muted) = the type.
			const ident = col.type + '/' + (col.key || '…');
			const typeTitle = sch.title || col.type;
			chip.innerHTML =
				'<span class="cerb-icons cerb-icon-' + icon + ' cerb-sb-column--icon"></span>' +
				'<span class="cerb-sb-column--name">' + this._esc(ident) + '</span>' +
				'<span class="cerb-sb-column--key">' + this._esc(typeTitle) + '</span>';
			host.appendChild(chip);
		});
		this._initSortable();
		this._markSelected();
	}

	_initSortable() {
		if(this._sortable) { this._sortable.destroy(); this._sortable = null; }
		if(!(window.CerbUI && CerbUI.Sortable)) return;
		this._sortable = new CerbUI.Sortable(this.columnsEl, {
			items: '.cerb-sb-column',
			handle: '',
			onEnd: (info) => {
				const from = info.fromIndex, to = info.toIndex;
				if(from == null || to == null || from === to) return;
				const moved = this.model.columns.splice(from, 1)[0];
				this.model.columns.splice(to, 0, moved);
				this.selectedIndex = this.model.columns.indexOf(moved);
				// Rebuilding the strip (which destroys+recreates THIS sortable) synchronously inside onEnd
				// corrupts the drop mid-flight → all chips vanish. Defer to the next tick.
				setTimeout(() => {
					this.renderColumnStrip();
					this.refreshPreview();
					this.renderInspector();
				}, 0);
			},
		});
	}

	_markSelected() {
		this.columnsEl.querySelectorAll('.cerb-sb-column--selected').forEach(el => el.classList.remove('cerb-sb-column--selected'));
		if(this.selectedIndex < 0) return;
		const el = this.columnsEl.querySelector('.cerb-sb-column[data-cerb-sb-index="' + this.selectedIndex + '"]');
		if(el) el.classList.add('cerb-sb-column--selected');
	}

	_esc(s) { const d = document.createElement('div'); d.textContent = (s == null ? '' : String(s)); return d.innerHTML; }

	// ── Dataset editors ─────────────────────────────────────────────────

	// The config for the CURRENT dataset mode (created lazily). Per-mode, so switching preserves each one until
	// the whole editor is closed.
	_dsConfig() {
		const ds = this.model.dataset;
		return (ds.configs[ds.mode] = ds.configs[ds.mode] || {});
	}

	_setDatasetMode(mode) {
		if(mode === this.model.dataset.mode) return;
		this._syncDataset();   // capture the current mode's editor values into ITS config before switching
		this.model.dataset.mode = mode;   // the new mode's config is preserved (not reset)
		this.renderInspector();
		this.refreshPreview();   // preview follows the now-selected source (keys/sample stay at the last Load)
	}

	// Pull live values from the dataset editors into the CURRENT mode's config before their DOM is torn down
	// (they don't fire `input` for programmatic edits), then drop the refs so a later read falls back to config.
	_syncDataset() {
		const cfg = this._dsConfig();
		try { if(this._dsSearchQuery) cfg.query = this._dsSearchQuery.getValue(); } catch(e) {}
		try { if(this._dsDataQuery) cfg.data_query = this._dsDataQuery.getValue(); } catch(e) {}
		try { if(this._dsJsonEditor) cfg.rows = this._dsJsonEditor.getValue(); } catch(e) {}
		this._dsSearchQuery = this._dsDataQuery = this._dsJsonEditor = this._dsAutoChooser = null;
	}

	_renderDatasetMode() {
		const host = this._datasetBody;
		host.innerHTML = '';
		this._dsSearchQuery = null;
		this._dsJsonEditor = null;
		this._dsDataQuery = null;
		this._dsAutoChooser = null;

		const mode = this.model.dataset.mode;
		const cfg = this._dsConfig();

		if(mode === 'records') {
			// Record type
			const rtField = this._formField('Record type');
			const sel = document.createElement('select');
			this.recordTypes.forEach(rt => {
				const o = document.createElement('option');
				o.value = rt.alias; o.textContent = rt.label; o.setAttribute('data-cerb-ui-icon', rt.icon);
				if(rt.alias === cfg.record_type) o.selected = true;
				sel.appendChild(o);
			});
			rtField.appendChild(sel);
			host.appendChild(rtField);

			// Expand keys
			const exField = this._formField('Expand keys');
			const ex = document.createElement('input');
			ex.type = 'text'; ex.value = cfg.expand || ''; ex.placeholder = 'e.g. customfields,owner_';
			ex.addEventListener('input', () => { cfg.expand = ex.value; });
			exField.appendChild(ex);
			host.appendChild(exField);

			// Query (SearchQuery scoped to the record type)
			const qField = this._formField('Query');
			const qta = document.createElement('textarea');
			qta.className = 'cerb-sb--dataset-query';
			qta.value = cfg.query || '';
			qta.spellcheck = false;
			qField.appendChild(qta);
			host.appendChild(qField);

			if(window.CerbUI && CerbUI.SelectMenu)
				new CerbUI.SelectMenu(sel, { onSelect: (v) => {
					cfg.record_type = v;
					if(this._dsSearchQuery && this._dsSearchQuery.setContext) this._dsSearchQuery.setContext(this._contextForAlias(v));
				}});

			if(window.CerbUI && CerbUI.SearchQuery) {
				const ctx = this._contextForAlias(cfg.record_type || (this.recordTypes[0] && this.recordTypes[0].alias));
				this._dsSearchQuery = new CerbUI.SearchQuery(qta, {
					context: ctx,
					onAutocomplete: CerbUI.SearchQuery.queryFieldSource(ctx),
				});
			}
			if(!cfg.record_type) cfg.record_type = this._defaultRecordType();

		} else if(mode === 'dataQuery') {
			// A raw data query — any query whose output is format:dictionaries.
			const qField = this._formField('Data query');
			const ta = document.createElement('textarea');
			ta.className = 'cerb-sb--dataset-query';
			ta.value = cfg.data_query || 'type:worklist.records\nof:ticket\nquery:(\n  limit:10\n  sort:[id]\n)\nformat:dictionaries';
			ta.spellcheck = false;
			cfg.data_query = ta.value;
			qField.appendChild(ta);
			host.appendChild(qField);

			if(window.CerbUI && CerbUI.DataQuery)
				this._dsDataQuery = new CerbUI.DataQuery(ta, { onAutocomplete: CerbUI.DataQuery.dataQueryFieldSource() });
			else
				ta.addEventListener('input', () => { cfg.data_query = ta.value; });

		} else if(mode === 'automation') {
			const uField = this._formField('ui.sheet.data automation');
			const wrap = document.createElement('div');
			wrap.className = 'cerb-sb--auto-chooser';
			uField.appendChild(wrap);
			host.appendChild(uField);

			const iField = this._formField('Inputs (KATA)');
			const ita = document.createElement('textarea');
			ita.className = 'cerb-sb--field-kata'; ita.value = cfg.inputs || ''; ita.spellcheck = false;
			ita.addEventListener('input', () => { cfg.inputs = ita.value; });
			iField.appendChild(ita);
			host.appendChild(iField);

			if(window.CerbUI && CerbUI.RecordChooser) {
				let seedId = '';
				for(const a of this.sheetDataAutomations) { if(a.uri === cfg.uri) { seedId = a.id; break; } }
				const seed = seedId ? { context: 'cerb.contexts.automation', id: seedId, label: cfg.uri || '' } : null;
				this._dsAutoChooser = new CerbUI.RecordChooser(wrap, {
					context: 'cerb.contexts.automation',
					query: 'trigger:cerb.trigger.ui.sheet.data',   // constrain to ui.sheet.data automations
					emptyIcon: 'zap',
					searchPlaceholder: 'ui.sheet.data automation…',
					value: seed,
					onSelect: (item) => {
						cfg.uri = (item && item.id != null && this._automationUriById[item.id]) ? this._automationUriById[item.id] : ((item && item.label) || '');
						this._scheduleRefresh();
					},
				});
			} else {
				const inp = document.createElement('input');
				inp.type = 'text'; inp.value = cfg.uri || '';
				inp.addEventListener('input', () => { cfg.uri = inp.value; });
				wrap.appendChild(inp);
			}

		} else {
			// manual
			const rField = this._formField('Sample rows (JSON)');
			const ta = document.createElement('textarea');
			ta.className = 'cerb-sb--dataset-json';
			ta.value = cfg.rows || '[\n  {"id": 1, "_label": "Example one"},\n  {"id": 2, "_label": "Example two"}\n]';
			ta.spellcheck = false;
			cfg.rows = ta.value;
			rField.appendChild(ta);
			host.appendChild(rField);

			if(window.CerbUI && CerbUI.JsonEditor)
				this._dsJsonEditor = new CerbUI.JsonEditor(ta, { minLines: 4 });
			else
				ta.addEventListener('input', () => { cfg.rows = ta.value; });
		}
	}

	// A sensible starting record type — Ticket if available, else the first.
	_defaultRecordType() {
		const t = this.recordTypes.find(r => r.alias === 'ticket');
		return t ? 'ticket' : ((this.recordTypes[0] && this.recordTypes[0].alias) || '');
	}

	_contextForAlias(alias) {
		const rt = this.recordTypes.find(r => r.alias === alias);
		return rt ? rt.context : alias;
	}

	_formField(label) {
		const field = document.createElement('div');
		field.className = 'cerb-ui-form--field';
		if(label) {
			const lab = document.createElement('label');
			lab.className = 'cerb-ui-form--label';
			lab.textContent = label;
			field.appendChild(lab);
		}
		return field;
	}

	// Read the live dataset config (editors that don't fire input) into a FormData for the endpoints.
	_datasetFormData(fd) {
		const ds = this.model.dataset;
		const cfg = this._dsConfig();
		fd.set('mode', ds.mode);
		if(ds.mode === 'records') {
			if(this._dsSearchQuery) cfg.query = this._dsSearchQuery.getValue();
			fd.set('data_query', this._buildDataQuery());
		} else if(ds.mode === 'dataQuery') {
			if(this._dsDataQuery) cfg.data_query = this._dsDataQuery.getValue();
			fd.set('data_query', cfg.data_query || '');
		} else if(ds.mode === 'automation') {
			fd.set('automation_uri', cfg.uri || '');
			fd.set('automation_inputs', cfg.inputs || '');
		} else {
			if(this._dsJsonEditor) cfg.rows = this._dsJsonEditor.getValue();
			fd.set('rows', cfg.rows || '');
		}
	}

	_buildDataQuery() {
		const c = this._dsConfig();
		const of = c.record_type || '';
		const expand = c.expand || '';
		let q = (c.query || '').trim();
		// Always bound the sample so the first Load doesn't pull an unbounded set (and `query:()` isn't empty).
		if(!/(^|\s)limit:/.test(q)) q = (q ? q + '\n' : '') + 'limit:10';
		let out = 'type:worklist.records\nof:' + of + '\n';
		if(expand) out += 'expand:[' + expand + ']\n';
		out += 'query:(\n' + q + '\n)\nformat:dictionaries';
		if(this._previewPage) out += '\npage:' + this._previewPage;   // top-level page for the worklist.records provider
		return out;
	}

	loadSample() {
		this._previewPage = 0;   // a fresh sample is always page 0
		const fd = new FormData();
		fd.set('c', 'ui');
		fd.set('a', 'sheetBuilderData');
		this._datasetFormData(fd);

		this._setDatasetStatus('Loading…');

		genericAjaxPost(fd, null, null, (json) => {
			if(!json || json.error) {
				this._setDatasetStatus('');
				if(window.Devblocks && Devblocks.createAlertError) Devblocks.createAlertError((json && json.error) || 'Failed to load sample data.');
				return;
			}
			this.sampleData = json.data || [];
			this.sampleKeys = json.keys || [];
			this._setDatasetStatus(this.sampleData.length + ' rows · ' + this.sampleKeys.length + ' keys');
			// Refresh just the sample table in place — a full renderInspector would recreate the dataset editors
			// mid-view (losing focus). The column datakey pickers read this.sampleKeys when a column is selected.
			if(this.dataSourceMode) this._renderSampleData(this.inspectorEl);
			this.refreshPreview();
		});
	}

	_setDatasetStatus(text) {
		this._datasetStatusText = text;
		if(this._datasetStatus) this._datasetStatus.textContent = text;
	}

	// ── Inspector ───────────────────────────────────────────────────────

	renderInspector() {
		const host = this.inspectorEl;
		this._syncDataset();   // pull live values from the dataset editors before their DOM is torn down
		host.innerHTML = '';
		host.classList.remove('cerb-ui-form');

		const hasColumn = (!this.layoutMode && !this.dataSourceMode && this.selectedIndex >= 0 && this.selectedIndex < this.model.columns.length);
		const show = this.layoutMode || this.dataSourceMode || hasColumn;

		if(this.splitpane) {
			if(show) { if(this.splitpane.isCollapsed()) this.splitpane.expand(); }
			else if(!this.splitpane.isCollapsed()) { this.splitpane.collapse('second'); }
		}
		if(!show) return;

		host.classList.add('cerb-ui-form');
		this._enhance = [];
		this._liveSyncs = [];   // closures that pull values from overlay editors that don't fire `input`

		if(this.dataSourceMode) { this._renderDataSourceInspector(host); this._runEnhancements(); return; }
		if(this.layoutMode) { this._renderLayoutInspector(host); this._runEnhancements(); return; }

		const col = this.model.columns[this.selectedIndex];
		const sch = this.columnSchema[col.type] || {};

		const head = document.createElement('div');
		head.className = 'cerb-sb--inspector-head';
		head.innerHTML = '<span class="cerb-icons cerb-icon-' + (sch.icon || 'text') + '"></span> <b>' + this._esc(sch.title || CerbUI.SheetBuilder._humanize(col.type)) + '</b>';
		host.appendChild(head);

		// Universal: column key (TextChooser of sample keys, freeform too) + label (top-level `label:`).
		this._addField(host, 'Column key', 'datakey', col.key, (v) => {
			col.key = String(v).replace(/[^A-Za-z0-9_]/g, '_');
			this.renderColumnStrip();
			this._scheduleRefresh();
		}, null, 'column key…');
		this._addField(host, 'Heading (label)', 'text', col.fields.label, (v) => {
			col.fields.label = v;
			this.renderColumnStrip();
			this._scheduleRefresh();
		});

		if(sch.curated && sch.fields) {
			const rendered = {};
			sch.fields.forEach(f => {
				if(f.key === 'label') return;
				if(f.sgroup) {
					if(rendered[f.sgroup]) return;
					rendered[f.sgroup] = true;
					this._renderSourceGroup(host, col, sch.fields.filter(x => x.sgroup === f.sgroup));
					return;
				}
				let fval = col.fields[f.key];
				if(fval === undefined && f.default !== undefined) fval = f.default;   // e.g. underline defaults on
				this._addField(host, f.label || f.key, f.input, fval, (v) => {
					col.fields[f.key] = v;
					this._scheduleRefresh();
				}, f.options, f.placeholder);
			});
		} else {
			this._addField(host, 'Params (KATA)', 'kata', col.raw || '', (v) => {
				col.raw = v;
				this._scheduleRefresh();
			});
		}

		const remove = document.createElement('button');
		remove.type = 'button';
		remove.className = 'cerb-ui-button cerb-ui-button--outline cerb-sb--remove';
		remove.innerHTML = '<span class="cerb-icons cerb-icon-trash"></span> Remove column';
		remove.addEventListener('click', () => this.removeColumn(this.selectedIndex));
		host.appendChild(remove);

		this._runEnhancements();
	}

	// The active source role for a value group — the stored choice, else inferred (the role whose field has a
	// value), else 'key' (the sensible default), else the group's first role.
	_groupActiveRole(col, groupFields) {
		const g = groupFields[0].sgroup;
		col.sources = col.sources || {};
		if(col.sources[g]) return col.sources[g];
		for(const f of groupFields) {
			const v = col.fields ? col.fields[f.key] : undefined;
			if(!(v === undefined || v === null || v === '')) { col.sources[g] = f.srole; return f.srole; }
		}
		col.sources[g] = groupFields.some(f => f.srole === 'key') ? 'key' : groupFields[0].srole;
		return col.sources[g];
	}

	// A mutually-exclusive value source: a Literal/Key/Template switcher + the active source's input. Only the
	// active source is stored-as-emitted; the others' values are kept so toggling back restores them.
	_renderSourceGroup(host, col, groupFields) {
		const g = groupFields[0].sgroup;
		const active = this._groupActiveRole(col, groupFields);
		// literal reads as "Text" in a string context ("Icon" when it's an icon picker); key/template as-is.
		const roleLabel = (f) => f.srole === 'key' ? 'Key' : (f.srole === 'template' ? 'Template' : (f.input === 'iconpicker' ? 'Icon' : 'Text'));
		const options = groupFields.map(f => [f.srole, roleLabel(f)]);

		const field = this._formField(groupFields[0].label);
		const sw = document.createElement('div');
		sw.className = 'cerb-ui-switcher cerb-sb--source-switcher';
		options.forEach(([val, txt]) => {
			const b = document.createElement('button');
			b.type = 'button';
			b.dataset.value = val;
			b.textContent = txt;
			if(val === active) b.classList.add('cerb-ui-switcher--active');
			sw.appendChild(b);
		});
		field.appendChild(sw);
		host.appendChild(field);
		this._enhance.push(() => { if(window.CerbUI && CerbUI.Switcher) new CerbUI.Switcher(sw, { onSelect: (role) => {
			col.sources[g] = role;
			this.renderInspector();   // reveal the chosen source's input
			this._scheduleRefresh();
		}}); });

		const af = groupFields.find(f => f.srole === active) || groupFields[0];
		this._addField(host, '', af.input, col.fields ? col.fields[af.key] : '', (v) => {
			col.fields[af.key] = v;
			this._scheduleRefresh();
		}, af.options, af.placeholder);
	}

	// The palette entries (`<name>:<index>`) offered by the color pickers. Dark variants are FOLDED — each entry
	// appears once (as the light `<name>:<index>`; the runtime auto-swaps `_dark`), and its swatch/hex preview
	// reflects the current mode (the dark color when the worker is in dark mode and one is defined).
	_paletteRefItems() {
		const dark = this._isDarkMode();
		const items = [];
		(this.model.layout.colorPalettes || []).forEach(p => {
			if(!p.name) return;
			(p.colors || []).forEach((hex, i) => {
				const shown = (dark && Array.isArray(p.colorsDark) && p.colorsDark[i] != null) ? p.colorsDark[i] : hex;
				items.push({ label: p.name + ':' + i, value: p.name + ':' + i, hint: shown, swatch: shown });
			});
		});
		return items;
	}

	_isDarkMode() { return !!(document.documentElement && document.documentElement.classList.contains('dark')); }

	_renderLayoutInspector(host) {
		const head = document.createElement('div');
		head.className = 'cerb-sb--inspector-head';
		head.innerHTML = '<span class="cerb-icons cerb-icon-adjust"></span> <b>Sheet settings</b>';
		host.appendChild(head);

		(this.layoutSchema.fields || []).forEach(f => {
			let value = this.model.layout[f.key];
			if(value === undefined && f.default !== undefined) value = f.default;
			this._addField(host, f.label || f.key, f.input, value, (v) => {
				this.model.layout[f.key] = v;
				this._scheduleRefresh();
			}, f.options, f.placeholder);
		});

		this._renderColorPalettes(host);
	}

	// Structured color-palette editor (replaces a raw `colors:` KATA field). Each palette = an aliased name +
	// a row of CerbUI.ColorPicker swatches; referenced by a cell's `color`/`text_color` as `<name>:<index>`.
	_renderColorPalettes(host) {
		const palettes = this.model.layout.colorPalettes || (this.model.layout.colorPalettes = []);

		const sec = document.createElement('div');
		sec.className = 'cerb-sb--palettes';
		const secHead = document.createElement('div');
		secHead.className = 'cerb-sb--palettes-head';
		secHead.textContent = 'Color palettes';
		sec.appendChild(secHead);

		palettes.forEach((pal, pi) => {
			const card = document.createElement('div');
			card.className = 'cerb-sb--palette-card';

			const top = document.createElement('div');
			top.className = 'cerb-sb--palette-top';
			const name = document.createElement('input');
			name.type = 'text';
			name.className = 'cerb-sb--palette-name';
			name.value = pal.name || '';
			name.placeholder = 'name (e.g. statuses)';
			name.addEventListener('input', () => { pal.name = name.value.replace(/[^A-Za-z0-9_]/g, '_'); this._scheduleRefresh(); });
			top.appendChild(name);

			// Dark-variants toggle: on → a parallel `colorsDark` (emitted as `<name>_dark`, auto-swapped in dark mode).
			const hasDark = Array.isArray(pal.colorsDark);
			const darkBtn = document.createElement('button');
			darkBtn.type = 'button';
			darkBtn.className = 'cerb-ui-button cerb-ui-button--subtle cerb-sb--palette-dark' + (hasDark ? ' cerb-sb--palette-dark--on' : '');
			darkBtn.innerHTML = '<span class="cerb-icons cerb-icon-moon"></span>';
			darkBtn.title = hasDark ? 'Remove dark-mode variants' : 'Add dark-mode variants';
			darkBtn.addEventListener('click', () => {
				if(Array.isArray(pal.colorsDark)) delete pal.colorsDark;
				else pal.colorsDark = (pal.colors || []).slice();   // seed dark from the light colors
				this.renderInspector();
				this._scheduleRefresh();
			});
			top.appendChild(darkBtn);

			const del = document.createElement('button');
			del.type = 'button';
			del.className = 'cerb-ui-button cerb-ui-button--subtle cerb-sb--palette-del';
			del.innerHTML = '<span class="cerb-icons cerb-icon-trash"></span>';
			del.title = 'Remove palette';
			del.addEventListener('click', () => { palettes.splice(pi, 1); this.renderInspector(); this._scheduleRefresh(); });
			top.appendChild(del);
			card.appendChild(top);

			// Swatches: a wrapping row of index-cells. Each cell = index (drag handle) + a light well + (when dark
			// variants are on) a dark well below it. Drag reorders/renumbers BOTH colors and colorsDark together.
			const swatches = document.createElement('div');
			swatches.className = 'cerb-sb--palette-swatches';
			(pal.colors || []).forEach((hex, ci) => {
				const cell = document.createElement('div');
				cell.className = 'cerb-sb--swatch-cell';

				const num = document.createElement('span');   // index + drag handle
				num.className = 'cerb-sb--swatch-num';
				num.textContent = ci;
				num.title = (pal.name ? (pal.name + ':' + ci) : ('index ' + ci)) + ' — drag to reorder';
				cell.appendChild(num);

				const linp = document.createElement('input');   // light well
				linp.type = 'text';
				linp.value = hex;
				linp.title = 'Light';
				cell.appendChild(linp);
				this._enhance.push(() => {
					if(window.CerbUI && CerbUI.ColorPicker)
						new CerbUI.ColorPicker(linp, { showInput: false, onChange: (hx) => { pal.colors[ci] = hx; this._scheduleRefresh(); } });
					else
						linp.addEventListener('input', () => { pal.colors[ci] = linp.value; this._scheduleRefresh(); });
				});

				if(hasDark) {
					const dinp = document.createElement('input');   // dark well
					dinp.type = 'text';
					dinp.value = (pal.colorsDark[ci] != null) ? pal.colorsDark[ci] : hex;
					dinp.title = 'Dark';
					cell.appendChild(dinp);
					this._enhance.push(() => {
						if(window.CerbUI && CerbUI.ColorPicker)
							new CerbUI.ColorPicker(dinp, { showInput: false, onChange: (hx) => { pal.colorsDark[ci] = hx; this._scheduleRefresh(); } });
						else
							dinp.addEventListener('input', () => { pal.colorsDark[ci] = dinp.value; this._scheduleRefresh(); });
					});
				}

				const rm = document.createElement('span');
				rm.className = 'cerb-icons cerb-icon-circle-remove cerb-sb--swatch-rm';
				rm.title = 'Remove color';
				rm.addEventListener('click', () => {
					pal.colors.splice(ci, 1);
					if(Array.isArray(pal.colorsDark)) pal.colorsDark.splice(ci, 1);
					this.renderInspector();
					this._scheduleRefresh();
				});
				cell.appendChild(rm);

				swatches.appendChild(cell);
			});
			card.appendChild(swatches);

			// Drag-reorder the cells (handle = the number so it doesn't fight the swatch's open-popup click).
			this._enhance.push(() => {
				if(!(window.CerbUI && CerbUI.Sortable)) return;
				new CerbUI.Sortable(swatches, {
					items: '.cerb-sb--swatch-cell',
					handle: '.cerb-sb--swatch-num',
					onEnd: (info) => {
						const from = info.fromIndex, to = info.toIndex;
						if(from == null || to == null || from === to) return;
						pal.colors.splice(to, 0, pal.colors.splice(from, 1)[0]);
						if(Array.isArray(pal.colorsDark)) pal.colorsDark.splice(to, 0, pal.colorsDark.splice(from, 1)[0]);
						setTimeout(() => { this.renderInspector(); this._scheduleRefresh(); }, 0);   // renumber (defer — don't rebuild the sortable inside its own onEnd)
					},
				});
			});

			const addColor = document.createElement('button');
			addColor.type = 'button';
			addColor.className = 'cerb-ui-button cerb-ui-button--subtle cerb-sb--palette-addcolor';
			addColor.innerHTML = '<span class="cerb-icons cerb-icon-circle-plus"></span> Color';
			addColor.addEventListener('click', () => {
				pal.colors = pal.colors || [];
				pal.colors.push('#888888');
				if(Array.isArray(pal.colorsDark)) pal.colorsDark.push('#888888');
				this.renderInspector();
				this._scheduleRefresh();
			});
			card.appendChild(addColor);

			sec.appendChild(card);
		});

		const add = document.createElement('button');
		add.type = 'button';
		add.className = 'cerb-ui-button cerb-ui-button--outline cerb-sb--palette-add';
		add.innerHTML = '<span class="cerb-icons cerb-icon-circle-plus"></span> Palette';
		add.addEventListener('click', () => this._openPaletteMenu(add, palettes));
		sec.appendChild(add);

		host.appendChild(sec);
	}

	// "+ Palette" menu: a blank palette or a preset seeded from CerbUI.palettes.
	_openPaletteMenu(anchor, palettes) {
		const presets = [
			{ label: 'Blank', colors: ['#888888'] },
			{ label: 'Category10', colors: (window.CerbUI && CerbUI.palettes && CerbUI.palettes.category10) || [] },
			{ label: 'Rainbow', colors: (window.CerbUI && CerbUI.palettes && CerbUI.palettes.rainbow) || [] },
		];
		const addPreset = (p) => {
			palettes.push({ name: this._nextPaletteName(palettes), colors: p.colors.slice() });
			this.renderInspector();
			this._scheduleRefresh();
		};

		if(!(window.CerbUI && CerbUI.Menu)) { addPreset(presets[0]); return; }

		const ul = document.createElement('ul');
		presets.forEach((p, i) => { const li = document.createElement('li'); li.textContent = p.label; li.dataset.index = i; ul.appendChild(li); });
		new CerbUI.Menu(ul, { onSelect: (li, src) => { const p = presets[parseInt(src.dataset.index, 10)]; if(p) addPreset(p); } }).open(anchor);
	}

	_nextPaletteName(palettes) {
		const used = {}; (palettes || []).forEach(p => { if(p.name) used[p.name] = true; });
		let n = 1; while(used['palette' + n]) n++;
		return 'palette' + n;
	}

	// Build one labeled inspector control. datakey → SelectMenu of sample keys; columnkey → SelectMenu of
	// column keys; else mirrors the FormBuilder mapping (bool→Toggle, select→SelectMenu, kata/number/text).
	_addField(host, label, input, value, onChange, options, placeholder) {
		if(input === 'bool') {
			const row = document.createElement('div');
			row.className = 'cerb-sb--toggle-row';
			const toggle = document.createElement('label');
			toggle.className = 'cerb-ui-toggle';
			const cb = document.createElement('input');
			cb.type = 'checkbox';
			cb.checked = !!value;
			const slider = document.createElement('span');
			slider.className = 'cerb-ui-toggle--slider';
			toggle.appendChild(cb); toggle.appendChild(slider);
			const text = document.createElement('span');
			text.className = 'cerb-sb--toggle-label';
			text.textContent = label;
			row.appendChild(toggle); row.appendChild(text);
			host.appendChild(row);
			this._enhance.push(() => new CerbUI.Toggle(toggle, { onChange: (checked) => onChange(checked) }));
			return;
		}

		const field = this._formField(label);

		// A reference to a color palette entry (`<name>:<index>`) — TextChooser of the defined palettes (hex sublabel).
		if(input === 'paletteref') {
			const inp = document.createElement('input');
			inp.type = 'text';
			inp.value = (value == null) ? '' : value;
			inp.setAttribute('placeholder', 'palette:index');
			inp.addEventListener('input', () => onChange(inp.value));
			field.appendChild(inp);
			host.appendChild(field);
			this._enhance.push(() => {
				if(window.CerbUI && CerbUI.TextChooser)
					new CerbUI.TextChooser(inp, { minLength: 0, source: (term) => {
						const t = String(term || '').toLowerCase();
						return this._paletteRefItems().filter(it => it.label.toLowerCase().indexOf(t) >= 0);
					}});
			});
			return;
		}

		// A PHP date() format — free text with a few useful presets (absolute by default; no preset forced).
		if(input === 'datechooser') {
			const inp = document.createElement('input');
			inp.type = 'text';
			inp.value = (value == null) ? '' : value;
			inp.setAttribute('placeholder', 'e.g. d-M-Y H:i:s T');
			inp.addEventListener('input', () => onChange(inp.value));
			field.appendChild(inp);
			host.appendChild(field);
			this._enhance.push(() => {
				if(window.CerbUI && CerbUI.TextChooser)
					new CerbUI.TextChooser(inp, { minLength: 0, source: CerbUI.SheetBuilder._DATE_FORMATS.slice() });
			});
			return;
		}

		if(input === 'switcher') {
			const sw = document.createElement('div');
			sw.className = 'cerb-ui-switcher';
			(options || []).forEach(o => {
				const val = Array.isArray(o) ? o[0] : o;
				const txt = Array.isArray(o) ? o[1] : o;
				const b = document.createElement('button');
				b.type = 'button';
				b.dataset.value = val;
				b.textContent = txt;
				if(String(value) === String(val)) b.classList.add('cerb-ui-switcher--active');
				sw.appendChild(b);
			});
			field.appendChild(sw);
			host.appendChild(field);
			this._enhance.push(() => new CerbUI.Switcher(sw, { onSelect: (v) => onChange(v) }));
			return;
		}

		// A dict-key input: free text (override / reuse a key) with autocomplete from the sample keys, and an
		// `(auto)` placeholder — empty means "inherit the column key" (e.g. a `group_id` card).
		if(input === 'datakey') {
			const inp = document.createElement('input');
			inp.type = 'text';
			inp.value = (value == null) ? '' : value;
			inp.setAttribute('placeholder', placeholder || '(auto)');
			inp.addEventListener('input', () => onChange(inp.value));
			field.appendChild(inp);
			host.appendChild(field);
			this._enhance.push(() => {
				if(window.CerbUI && CerbUI.TextChooser)
					new CerbUI.TextChooser(inp, { source: (term) => {
						const t = String(term || '').toLowerCase();
						return this.sampleKeys.filter(k => k.toLowerCase().indexOf(t) >= 0);
					}});
			});
			return;
		}

		// A reference to one of the sheet's own columns (title_column) — a fixed SelectMenu of column keys.
		if(input === 'columnkey') {
			const source = this.model.columns.map(c => c.key);
			const sel = document.createElement('select');
			const blank = document.createElement('option'); blank.value = ''; blank.textContent = '(none)'; sel.appendChild(blank);
			const seen = {};
			source.forEach(k => { seen[k] = true; const o = document.createElement('option'); o.value = k; o.textContent = k; if(String(value) === String(k)) o.selected = true; sel.appendChild(o); });
			if(value && !seen[value]) { const o = document.createElement('option'); o.value = value; o.textContent = value; o.selected = true; sel.appendChild(o); }
			field.appendChild(sel);
			host.appendChild(field);
			this._enhance.push(() => new CerbUI.SelectMenu(sel, { onSelect: (v) => onChange(v) }));
			return;
		}

		// A per-row template — CerbUI.ScriptingEditor (Twig) with the sample keys offered as {{placeholders}}.
		if(input === 'template') {
			const ta = document.createElement('textarea');
			ta.value = (value == null) ? '' : value;
			ta.className = 'cerb-sb--field-template';
			ta.spellcheck = false;
			ta.addEventListener('input', () => onChange(ta.value));
			field.appendChild(ta);
			host.appendChild(field);
			this._enhance.push(() => {
				if(!(window.CerbUI && CerbUI.ScriptingEditor)) return;
				const ed = new CerbUI.ScriptingEditor(ta, {
					onAutocomplete: (ctx) => {
						// Bare key names (safe inside a `{{ … }}` the user is typing — no double-brace).
						const t = String((ctx && ctx.prefix) || '').toLowerCase();
						return this.sampleKeys
							.filter(k => k.toLowerCase().indexOf(t) >= 0)
							.map(k => ({ caption: k, value: k }));
					},
				});
				// Overlay editors don't reliably fire `input` for programmatic inserts — pull on refresh.
				this._liveSyncs.push(() => { try { onChange(ed.getValue()); } catch(e) {} });
			});
			return;
		}

		// An icon name — CerbUI.IconPicker (clearable to blank via the text field).
		if(input === 'iconpicker') {
			const inp = document.createElement('input');
			inp.type = 'text';
			inp.value = (value == null) ? '' : value;
			inp.setAttribute('placeholder', 'icon name…');
			inp.addEventListener('input', () => onChange(inp.value));
			field.appendChild(inp);
			host.appendChild(field);
			this._enhance.push(() => {
				if(window.CerbUI && CerbUI.IconPicker)
					new CerbUI.IconPicker(inp, { value: value || '', onChange: (name) => onChange(name) });
			});
			return;
		}

		if(input === 'select') {
			const sel = document.createElement('select');
			(options || []).forEach(o => {
				const val = Array.isArray(o) ? o[0] : o;
				const txt = Array.isArray(o) ? o[1] : o;
				const opt = document.createElement('option');
				opt.value = val; opt.textContent = txt;
				if(String(value) === String(val)) opt.selected = true;
				sel.appendChild(opt);
			});
			field.appendChild(sel);
			host.appendChild(field);
			this._enhance.push(() => new CerbUI.SelectMenu(sel, { onSelect: (v) => onChange(v) }));
			return;
		}

		if(input === 'multiline' || input === 'kata') {
			const ta = document.createElement('textarea');
			ta.value = (value == null) ? '' : value;
			if(input === 'kata') ta.className = 'cerb-sb--field-kata';
			ta.spellcheck = false;
			ta.addEventListener('input', () => onChange(ta.value));
			field.appendChild(ta);
		} else if(input === 'number') {
			const inp = document.createElement('input');
			inp.type = 'number';
			inp.value = (value == null || value === '') ? '' : value;
			inp.addEventListener('input', () => onChange(inp.value === '' ? '' : parseInt(inp.value, 10)));
			field.appendChild(inp);
		} else {
			const inp = document.createElement('input');
			inp.type = 'text';
			inp.value = (value == null) ? '' : value;
			if(placeholder) inp.setAttribute('placeholder', placeholder);
			inp.addEventListener('input', () => onChange(inp.value));
			field.appendChild(inp);
		}
		host.appendChild(field);
	}

	_runEnhancements() {
		if(!window.CerbUI) return;
		(this._enhance || []).forEach(fn => { try { fn(); } catch(e) {} });
		this._enhance = [];
	}

	// ── Preview ─────────────────────────────────────────────────────────

	_scheduleRefresh() {
		if(this._refreshTimer) clearTimeout(this._refreshTimer);
		this._refreshTimer = setTimeout(() => { this._refreshTimer = null; this.refreshPreview(); }, 300);
	}

	// keepPage: preserve the current preview page (a paging click); any other refresh resets to page 0.
	refreshPreview(keepPage) {
		if(!keepPage) this._previewPage = 0;
		(this._liveSyncs || []).forEach(fn => { try { fn(); } catch(e) {} });
		const kata = this.serializeKata();
		this._setKataText(kata);

		if(!this.model.columns.length) {
			this.previewEl.innerHTML = '<div class="cerb-sb--preview-empty">Add a column to preview the sheet.</div>';
			return;
		}

		const fd = new FormData();
		fd.set('c', 'ui');
		fd.set('a', 'sheetBuilderPreview');
		fd.set('sheet_kata', kata);
		fd.set('page', this._previewPage || 0);   // automation/manual page via resolveDataSet; records via the query
		this.columnTypes.forEach(t => fd.append('types[]', t));
		this._datasetFormData(fd);

		genericAjaxPost(fd, null, null, (html) => {
			const scrollTop = this.previewEl.scrollTop;
			$(this.previewEl).html(html);   // runs any injected <script> under the nonce
			this.previewEl.scrollTop = scrollTop;
		});
	}

	// ── Import (parsed KATA tree → model) — the inverse of the emit, for the Form Builder round-trip ─────
	// The server parses the seed data/schema KATA (client has no parser) and passes the trees as opts.initial.

	_importModel(initial) {
		if(initial.schema && typeof initial.schema === 'object') {
			if(initial.schema.layout) this._importLayout(initial.schema.layout);
			if(initial.schema.columns) this.model.columns = this._importColumns(initial.schema.columns);
		}
		if(initial.data != null) this._importDataset(initial.data);
	}

	_importLayout(layout) {
		const L = this.model.layout;
		['style', 'headings', 'paging', 'filtering', 'title_column'].forEach(k => { if(layout[k] !== undefined) L[k] = layout[k]; });
		// colors map → colorPalettes, folding `<name>` + `<name>_dark` into one palette.
		if(layout.colors && typeof layout.colors === 'object' && !Array.isArray(layout.colors)) {
			const pals = {}, order = [];
			Object.keys(layout.colors).forEach(name => {
				const dark = /_dark$/.test(name);
				const base = dark ? name.replace(/_dark$/, '') : name;
				const arr = Array.isArray(layout.colors[name]) ? layout.colors[name].slice() : [];
				if(!pals[base]) { pals[base] = { name: base }; order.push(base); }
				if(dark) pals[base].colorsDark = arr; else pals[base].colors = arr;
			});
			L.colorPalettes = order.map(b => pals[b]).filter(p => p.colors && p.colors.length);
		}
	}

	_importColumns(columns) {
		const cols = [];
		if(!columns || typeof columns !== 'object') return cols;
		// The emit form is an object keyed `type/key`; tolerate an indexed list of single-key objects too.
		const entries = Array.isArray(columns)
			? columns.map(o => { const k = Object.keys(o)[0]; return [k, o[k]]; })
			: Object.keys(columns).map(k => [k, columns[k]]);
		entries.forEach(([fullKey, def]) => {
			def = def || {};
			const slash = String(fullKey).indexOf('/');
			const type = slash >= 0 ? fullKey.slice(0, slash) : fullKey;
			const key = slash >= 0 ? fullKey.slice(slash + 1) : '';
			const sch = this.columnSchema[type];
			const col = { type: type, key: key, fields: {}, sources: {}, raw: null };
			if(def.label != null) col.fields.label = def.label;
			const params = (def.params && typeof def.params === 'object') ? def.params : {};
			if(sch && sch.curated && sch.fields) this._importParams(col, sch, params);
			else col.raw = this._valToKata(params, 0);
			cols.push(col);
		});
		return cols;
	}

	// Reverse the emit's field loop: for a source group, set the active role from whichever member is present.
	_importParams(col, sch, params) {
		const done = {};
		sch.fields.forEach(f => {
			if(f.key === 'label') return;
			if(f.sgroup) {
				if(done[f.sgroup]) return;
				done[f.sgroup] = true;
				const group = sch.fields.filter(x => x.sgroup === f.sgroup);
				const nest = group[0].snest;
				const present = (v) => v !== undefined && v !== '';
				let active = null, val;
				if(nest) {
					const obj = (params[nest] && typeof params[nest] === 'object') ? params[nest] : {};
					active = group.find(g => present(obj[g.snestkey]));
					if(active) val = obj[active.snestkey];
				} else {
					active = group.find(g => present(params[g.key]));
					if(active) val = params[active.key];
				}
				if(active) { col.sources[f.sgroup] = active.srole; col.fields[active.key] = this._importFieldValue(active, val); }
				return;
			}
			if(params[f.key] !== undefined) col.fields[f.key] = this._importFieldValue(f, params[f.key]);
		});
	}

	// A parsed param value → the builder field's stored form (kata fields hold a KATA string, not a parsed tree).
	_importFieldValue(f, v) {
		if(f.input === 'kata' && v && typeof v === 'object') return this._valToKata(v, 0);
		return v;
	}

	_importDataset(data) {
		if(data && typeof data === 'object' && !Array.isArray(data) && data.automation && typeof data.automation === 'object') {
			this.model.dataset.mode = 'automation';
			const c = this.model.dataset.configs.automation = {};
			c.uri = data.automation.uri || '';
			c.inputs = (data.automation.inputs && typeof data.automation.inputs === 'object')
				? this._valToKata(data.automation.inputs, 0) : (data.automation.inputs || '');
			return;
		}
		if(data && typeof data === 'object') {
			const rows = Array.isArray(data) ? data : Object.keys(data).map(k => data[k]);
			const rowObjs = rows.filter(r => r && typeof r === 'object');
			if(rowObjs.length) {
				this.model.dataset.mode = 'manual';
				this.model.dataset.configs.manual = { rows: JSON.stringify(rowObjs, null, 2) };
			}
		}
	}

	// Serialize a parsed structure back to KATA lines (kata-field / raw-params import). Best-effort — annotations
	// (@bool/@int/@raw) are lost; the target is a KATA text field the user can refine.
	_valToKata(obj, indent) {
		const lines = [];
		const pad = n => '  '.repeat(n);
		const walk = (o, ind) => {
			if(Array.isArray(o)) {
				o.forEach(item => {
					if(item && typeof item === 'object') {
						const keys = Object.keys(item);
						if(!keys.length) { lines.push(pad(ind) + '-'); return; }
						keys.forEach((k, i) => {
							const v = item[k];
							const prefix = (i === 0) ? (pad(ind) + '- ') : (pad(ind) + '  ');
							if(v && typeof v === 'object') { lines.push(prefix + k + ':'); walk(v, ind + 2); }
							else lines.push(prefix + k + ': ' + v);
						});
					} else {
						lines.push(pad(ind) + '- ' + item);
					}
				});
			} else if(o && typeof o === 'object') {
				Object.keys(o).forEach(k => {
					const v = o[k];
					if(v && typeof v === 'object') { lines.push(pad(ind) + k + ':'); walk(v, ind + 1); }
					else lines.push(pad(ind) + k + ': ' + v);
				});
			} else {
				lines.push(pad(ind) + o);
			}
		};
		walk(obj, indent || 0);
		return lines.join('\n');
	}

	// ── getParts: the element's two sibling KATA blocks (Form Builder writeback) ─────────────────────────

	getParts() {
		(this._liveSyncs || []).forEach(fn => { try { fn(); } catch(e) {} });
		const schema = [];
		this._emitLayout(schema, 0);
		this._emitColumns(schema, 0);
		const data = [];
		this._emitDataKata(data);
		return { schema: schema.join('\n'), data: data.join('\n') };
	}

	// The inner `data:` block — manual rows / automation / (records/dataQuery →) a synthesized sample the user
	// replaces with their real `data@key:`/automation.
	_emitDataKata(lines) {
		const ds = this.model.dataset;
		const cfg = this._dsConfig();
		if(ds.mode === 'automation' && cfg.uri) {
			lines.push('automation:');
			lines.push('  uri: ' + cfg.uri);
			if(cfg.inputs && String(cfg.inputs).trim()) { lines.push('  inputs:'); this._emitRawBlock(cfg.inputs, lines, 2); }
		} else if(ds.mode === 'manual') {
			this._parseManualRows(cfg.rows).forEach((row, i) => this._emitDataRow(row, 'row' + i, lines, 0));
		} else {
			// Synthesize a sample from the loaded rows, trimmed to the keys the columns read (skip rows with none).
			const refKeys = this._referencedKeys();
			let idx = 0;
			(this.sampleData || []).slice(0, 3).forEach(row => {
				const trimmed = {};
				refKeys.forEach(k => { if(row[k] !== undefined) trimmed[k] = row[k]; });
				if(Object.keys(trimmed).length) this._emitDataRow(trimmed, 'row' + (idx++), lines, 0);
			});
		}
	}

	// The dict keys the columns actually read (column keys + datakey field values) — to trim a synthesized sample.
	// A `card` renders from a sibling trio (`<prefix>_context`/`<prefix>id`/`<prefix>_label`) off a shared prefix,
	// so include all three — else the flattened row has only e.g. `group__label` and the card won't render.
	_referencedKeys() {
		const keys = {};
		const add = (k) => { if(k) keys[k] = true; };
		this.model.columns.forEach(col => {
			const sch = this.columnSchema[col.type] || {};
			if(col.type === 'card') {
				// Effective card targets: the column key + any explicit key sources (context/id/label).
				const targets = [col.key];
				['context_key', 'id_key', 'label_key'].forEach(fk => { if(col.fields) targets.push(col.fields[fk]); });
				targets.forEach(t => this._cardSiblingKeys(t).forEach(add));
			}
			add(col.key);
			(sch.fields || []).forEach(f => {
				if(f.input === 'datakey' && col.fields && col.fields[f.key]) add(col.fields[f.key]);
			});
		});
		return Object.keys(keys);
	}

	// The sibling keys a card cell reads off a shared prefix, mirroring _DevblocksSheetService card rendering:
	// strip a trailing `id`/`_context`/`_label` from the target key → prefix, then the trio (`group__label` →
	// `group__context`,`group_id`,`group__label`; `_label` → `_context`,`id`,`_label`). A key that doesn't end
	// in one of those suffixes (not card-shaped) is returned as-is.
	_cardSiblingKeys(key) {
		key = String(key == null ? '' : key);
		if(!key) return [];
		let prefix = null;
		for(const suf of ['id', '_context', '_label']) {
			if(key.length >= suf.length && key.slice(key.length - suf.length) === suf) { prefix = key.slice(0, key.length - suf.length); break; }
		}
		if(prefix === null) return [key];
		return [prefix + '_context', prefix + 'id', prefix + '_label'];
	}

	// ── KATA serialization (model → KATA) ───────────────────────────────

	// Canonical output: a `sheet:` node wrapping `data:` + `schema:` (`layout:` + `columns:`). A caller that
	// only needs part (a widget wants the schema; an await wants data too) greps out its subtree.
	serializeKata() {
		const lines = [];
		const ds = this.model.dataset;

		// External data sources (records / raw data query) live outside the sheet KATA (a widget's data_query).
		if(ds.mode === 'records' || ds.mode === 'dataQuery')
			lines.push('# Data source: a data query (set the data_query on the adopting widget)');

		lines.push('sheet:');
		this._emitDataset(lines, 1);
		lines.push(this._pad(1) + 'schema:');
		this._emitLayout(lines, 2);
		this._emitColumns(lines, 2);
		return lines.join('\n');
	}

	_pad(n) { return '  '.repeat(n); }
	_emitRawBlock(text, lines, indent) {
		String(text).replace(/\s+$/, '').split('\n').forEach(l => lines.push(this._pad(indent) + l));
	}
	_emitScalar(key, input, v, lines, indent) {
		if(input === 'bool') { if(v) lines.push(this._pad(indent) + key + '@bool: yes'); return; }
		if(v === undefined || v === null || v === '') return;
		if(input === 'kata') { lines.push(this._pad(indent) + key + ':'); this._emitRawBlock(v, lines, indent + 1); }
		// Templates emit as `@raw:` so `{{placeholders}}`/Twig eval per row, not at schema-parse time.
		else if(input === 'template') { lines.push(this._pad(indent) + key + '@raw:'); this._emitRawBlock(v, lines, indent + 1); }
		else if(input === 'number') lines.push(this._pad(indent) + key + '@int: ' + v);
		else if(input === 'multiline' || String(v).indexOf('\n') >= 0) { lines.push(this._pad(indent) + key + '@text:'); this._emitRawBlock(v, lines, indent + 1); }
		else lines.push(this._pad(indent) + key + ': ' + v);   // text / datakey / iconpicker / switcher
	}

	_emitDataset(lines, base) {
		const ds = this.model.dataset;
		const cfg = this._dsConfig();
		if(ds.mode === 'automation' && cfg.uri) {
			lines.push(this._pad(base) + 'data:');
			lines.push(this._pad(base + 1) + 'automation:');
			lines.push(this._pad(base + 2) + 'uri: ' + cfg.uri);
			if(cfg.inputs && String(cfg.inputs).trim()) {
				lines.push(this._pad(base + 2) + 'inputs:');
				this._emitRawBlock(cfg.inputs, lines, base + 3);
			}
		} else if(ds.mode === 'manual') {
			const rows = this._parseManualRows(cfg.rows);
			if(rows && rows.length) {
				lines.push(this._pad(base) + 'data:');
				rows.forEach((row, i) => this._emitDataRow(row, 'row' + i, lines, base + 1));
			}
		}
	}

	_parseManualRows(json) {
		if(!json) return [];
		try { const v = JSON.parse(json); return Array.isArray(v) ? v : []; } catch(e) { return []; }
	}
	// Emit one data row as a KATA-keyed object (`<rowKey>:` → `field: value`) — NOT a YAML list item. Values are
	// KATA-safe: objects/arrays and any string that would break the parse (`#`, newline, risky leading char) go
	// through `@json`; simple scalars stay inline.
	_emitDataRow(row, rowKey, lines, base) {
		if(row == null || typeof row !== 'object') return;
		lines.push(this._pad(base) + rowKey + ':');
		Object.keys(row).forEach(k => {
			if(String(k).startsWith('__')) return;   // internal keys (e.g. __index)
			lines.push(this._pad(base + 1) + this._kataScalarKV(k, row[k]));
		});
	}

	// One `key: value` (or `key@json: …`) pair, KATA-safe.
	_kataScalarKV(key, v) {
		if(v !== null && typeof v === 'object') return key + '@json: ' + JSON.stringify(v);
		const s = (v === null || v === undefined) ? '' : String(v);
		// Inline is safe unless it has a newline, a `#` (comment), or a leading char KATA would misread.
		if(s.indexOf('\n') < 0 && s.indexOf('#') < 0 && !/^[\s@\-{}\[\]"'|>&*!%]/.test(s))
			return key + ': ' + s;
		return key + '@json: ' + JSON.stringify(v === undefined ? '' : v);
	}

	_emitLayout(lines, base) {
		const L = this.model.layout || {};
		const body = [];
		(this.layoutSchema.fields || []).forEach(f => {
			let v = L[f.key];
			if(v === undefined && f.default !== undefined) v = f.default;
			// Bools are emitted EXPLICITLY (yes/no) — a false with no line would inherit the layout default
			// (headings defaults ON), so toggling off must write `headings@bool: no`.
			if(f.input === 'bool') body.push(this._pad(base + 1) + f.key + '@bool: ' + (v ? 'yes' : 'no'));
			else this._emitScalar(f.key, (f.input === 'switcher' ? 'text' : f.input), v, body, base + 1);
		});
		// Color palettes → `colors:` map. A palette with dark variants also emits `<name>_dark` (the runtime
		// auto-swaps `<name>` → `<name>_dark` in dark mode).
		const pals = (L.colorPalettes || []).filter(p => p.name && (p.colors || []).length);
		if(pals.length) {
			body.push(this._pad(base + 1) + 'colors:');
			pals.forEach(p => {
				body.push(this._pad(base + 2) + p.name + '@csv: ' + p.colors.join(', '));
				if(Array.isArray(p.colorsDark) && p.colorsDark.length)
					body.push(this._pad(base + 2) + p.name + '_dark@csv: ' + p.colorsDark.join(', '));
			});
		}
		if(body.length) { lines.push(this._pad(base) + 'layout:'); body.forEach(l => lines.push(l)); }
	}

	_emitColumns(lines, base) {
		if(!this.model.columns.length) return;
		lines.push(this._pad(base) + 'columns:');
		this.model.columns.forEach((col, ci) => {
			const sch = this.columnSchema[col.type] || {};
			lines.push(this._pad(base + 1) + col.type + '/' + (col.key || ('col' + (ci + 1))) + ':');
			if(col.fields && col.fields.label) this._emitScalar('label', 'text', col.fields.label, lines, base + 2);

			const params = [];
			if(sch.curated && sch.fields) {
				const done = {};
				sch.fields.forEach(f => {
					if(f.key === 'label') return;
					// A bool that defaults ON must emit an explicit no (else it inherits the renderer's default).
					if(f.input === 'bool' && f.default === true) {
						let bv = col.fields ? col.fields[f.key] : undefined;
						if(bv === undefined) bv = true;
						params.push(this._pad(base + 3) + f.key + '@bool: ' + (bv ? 'yes' : 'no'));
						return;
					}
					// Value source group: emit only the active role's field (nested under `snest` if set).
					if(f.sgroup) {
						if(done[f.sgroup]) return;
						done[f.sgroup] = true;
						const groupFields = sch.fields.filter(x => x.sgroup === f.sgroup);
						const active = this._groupActiveRole(col, groupFields);
						const af = groupFields.find(x => x.srole === active);
						if(!af) return;
						const v = col.fields ? col.fields[af.key] : undefined;
						if(v === undefined || v === null || v === '') return;
						if(af.snest) {
							params.push(this._pad(base + 3) + af.snest + ':');
							this._emitScalar(af.snestkey, af.input, v, params, base + 4);
						} else {
							this._emitScalar(af.key, af.input, v, params, base + 3);
						}
						return;
					}
					this._emitScalar(f.key, f.input, col.fields ? col.fields[f.key] : undefined, params, base + 3);
				});
			} else if(col.raw && String(col.raw).trim()) {
				this._emitRawBlock(col.raw, params, base + 3);
			}
			if(params.length) { lines.push(this._pad(base + 2) + 'params:'); params.forEach(l => lines.push(l)); }
		});
	}

	// ── Teardown ────────────────────────────────────────────────────────

	destroy() {
		if(this._refreshTimer) clearTimeout(this._refreshTimer);
		if(this._kataEditor && this._kataEditor.destroy) this._kataEditor.destroy();
		if(this._sortable && this._sortable.destroy) this._sortable.destroy();
		if(this.dropzone && this.dropzone.destroy) this.dropzone.destroy();
		if(this.splitpane && this.splitpane.destroy) this.splitpane.destroy();
		if(this.sidebar && this.sidebar.destroy) this.sidebar.destroy();
		if(this.el) CerbUI.SheetBuilder._instances.delete(this.el);
	}
};
