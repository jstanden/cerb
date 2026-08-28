/*
 * CerbUI.FormBuilder — visual builder for an interaction `await:form:` block.
 *
 * A palette of the trigger's form components (left) + a live, server-rendered preview of the form (center)
 * + a config inspector for the selected field (right), with the generated KATA shown below the preview.
 * Drag a component from the palette into the preview to add it; drag placed fields to reorder; click a field
 * to edit it. The preview is rendered by the server at 100% fidelity (the real `await/<type>.tpl` templates
 * in simulated mode) — no interaction is ever run. The builder's model is the source of truth; the KATA pane
 * and preview are derived from it. Export is copy-to-clipboard (import KATA→model is a later phase).
 *
 * Usage:
 *   new CerbUI.FormBuilder(el, {
 *     extensionId: 'cerb.trigger.interaction.worker',
 *     components:  { text: { icon: 'text' }, … },   // getFormComponentMeta()
 *     schema:      { text: { title, has_var, fields, new }, … },   // getFormComponentSchema()
 *   });
 *
 * Depends on CerbUI.Sidebar (palette), CerbUI.SplitPane, CerbUI.Droppable, CerbUI.Sortable, and the legacy
 * genericAjaxPost() for the preview round-trip.
 */
CerbUI.FormBuilder = class {
	static _instances = new WeakMap();
	static from(el) { return CerbUI.FormBuilder._instances.get(el); }

	// Palette grouping + per-category icon-square color + friendly labels (unknown types → "Components").
	static _CATEGORIES = [
		{ label: 'Inputs',  color: '#3182ce', types: ['text','textarea','chooser','sheet','fileUpload','agentPrompt','editor','query'] },
		{ label: 'Display', color: '#805ad5', types: ['say','chart','map','audio','llmTranscript','fileDownload'] },
		{ label: 'Flow',    color: '#38a169', types: ['submit','end'] },
	];
	static _OTHER_COLOR = '#718096';
	static _HIDDEN_TYPES = ['end'];   // internal-only; not offered in the palette
	static _LABELS = { agentPrompt:'Agent prompt', fileUpload:'File upload', fileDownload:'File download', llmTranscript:'Transcript' };
	// A fixed sample session id (real UUID shape) SHARED by the llmTranscript + agentPrompt seeds, so one
	// find/replace in the generated KATA updates both (agentPrompt mints the session, the transcript shows it).
	static _SAMPLE_SESSION_ID = '4117ed1d-f48f-44e3-9720-f11f7c7b53c7';

	static _humanize(t) {
		if(CerbUI.FormBuilder._LABELS[t]) return CerbUI.FormBuilder._LABELS[t];
		return String(t).replace(/([A-Z])/g, ' $1').replace(/^./, c => c.toUpperCase());
	}

	constructor(el, opts = {}) {
		this.el = (typeof el === 'string') ? document.querySelector(el) : el;
		if(!this.el) return;
		CerbUI.FormBuilder._instances.set(this.el, this);

		this.opts = Object.assign({ extensionId: '', components: {}, schema: {}, recordTypes: [], mapResources: [], agentModels: [], previewChrome: 'dialog' }, opts);
		this.components = this.opts.components || {};
		this.schema = this.opts.schema || {};
		this.recordTypes = this.opts.recordTypes || [];
		this.mapResources = this.opts.mapResources || [];
		this.agentModels = this.opts.agentModels || [];         // [{name,provider,model,icon,vision,context_window}] — agentPrompt model references
		this.accountUris = this.opts.accountUris || {};         // connected_account id → uri (for readable auth cerb-uris)
		this._recordTypeByAlias = {};
		this.recordTypes.forEach(rt => { this._recordTypeByAlias[rt.alias] = rt; });

		this.model = { title: 'Form', elements: [] };
		this.selectedIndex = -1;
		this._varCounter = 0;
		this._paletteItems = {};

		this._refreshTimer = null;

		this._build();

		// A form always ends with exactly one submit (mirrors the runtime, which synthesizes one when absent).
		this._ensureSubmit();
		this._updatePalette();

		this.refreshPreview();
		this.renderInspector();
	}

	// ── DOM scaffold ────────────────────────────────────────────────────

	_build() {
		this.el.classList.add('cerb-fb');
		this.el.innerHTML = '';

		this.el.appendChild(this._buildPalette());

		// Main = SplitPane [ document column | inspector ].
		const main = document.createElement('div');
		main.className = 'cerb-fb--main';

		const doc = document.createElement('div');
		doc.className = 'cerb-fb--doc';

		// Preview (dropzone) — an inert facsimile of the real interaction popup: a STATIC `.cerb-ui-dialog` shell
		// (the classes only — CerbUI.Dialog's JS is never activated) supplies the rounded border, titlebar, and
		// content areas so the preview reads as an actual interaction. The form's title is the dialog's own
		// contentEditable `--title` (no more "Title: [input]" breaking the illusion). Inside sits the real
		// `.cerb-form-builder` form shell so the rendered elements' scripts (which look for
		// `.closest('form.cerb-form-builder')`) resolve, while submit/reset/end are swallowed.
		// Portal chrome (interaction.website) renders the preview as the customer-facing portal popup; otherwise
		// the Cerb dialog facsimile. Either way the form's title is an editable field in the faux titlebar and the
		// elements live in a real `.cerb-form-builder` form shell (so their scripts' `.closest('form.cerb-form-builder')`
		// resolves while submit/reset/end are swallowed).
		const portal = (this.opts.previewChrome === 'portal');

		// Enter in any single-line field must never implicitly submit the enclosing form — this popup can live
		// inside the page's <form>, so a stray Enter (in an inspector field or a preview input) would POST the
		// whole page. We only cancel the default submit: textareas/contenteditable are untouched, and component
		// Enter handlers (TagInput add, RecordChooser/SearchQuery select) already ran on the target first.
		this.el.addEventListener('keydown', (e) => {
			if(e.key !== 'Enter') return;
			const t = e.target;
			if(!t || t.tagName !== 'INPUT') return;
			const type = (t.getAttribute('type') || 'text').toLowerCase();
			if(type === 'checkbox' || type === 'radio' || type === 'button' || type === 'submit') return;
			e.preventDefault();
		});

		this.previewEl = document.createElement('div');
		this.previewEl.className = 'cerb-fb--preview' + (portal ? ' cerb-fb--preview--portal' : '');

		this.titleEl = document.createElement('span');
		this.titleEl.className = 'cerb-fb--preview-title ' + (portal ? 'cerb-interaction-popup--title' : 'cerb-ui-dialog--title');
		this.titleEl.contentEditable = 'true';
		this.titleEl.spellcheck = false;
		this.titleEl.setAttribute('role', 'textbox');
		this.titleEl.setAttribute('aria-label', 'Form title');
		this.titleEl.setAttribute('data-placeholder', 'Untitled form');
		this.titleEl.textContent = this.model.title;
		this.titleEl.addEventListener('input', () => {
			this.model.title = this.titleEl.textContent.trim();
			this._updateKata();   // title isn't rendered among the elements — just keep the KATA pane in sync
		});
		// A title is single-line: Enter commits (blur) rather than inserting a newline into the KATA value.
		this.titleEl.addEventListener('keydown', (e) => {
			if(e.key === 'Enter') { e.preventDefault(); this.titleEl.blur(); }
		});

		const form = document.createElement('form');
		form.className = 'cerb-form-builder cerb-fb--preview-form' + (portal ? ' cerb-interaction-popup--form' : '');
		form.addEventListener('submit', e => { e.preventDefault(); e.stopPropagation(); return false; });
		this.previewBodyEl = document.createElement('div');
		this.previewBodyEl.className = 'cerb-form-data cerb-fb--preview-body' + (portal ? ' cerb-interaction-popup--form-elements' : '');
		form.appendChild(this.previewBodyEl);

		if(portal) {
			// Facsimile of the public portal interaction popup (structure mirrors public/popup.tpl).
			const win = document.createElement('div');
			win.className = 'cerb-interaction-popup cerb-interaction-popup--style-embed cerb-fb--preview-dialog';
			const container = document.createElement('div');
			container.className = 'cerb-interaction-popup--container';
			const header = document.createElement('div');
			header.className = 'cerb-interaction-popup--header';
			const close = document.createElement('div');   // decorative close (portal chrome)
			close.className = 'cerb-interaction-popup--close';
			close.setAttribute('aria-hidden', 'true');
			header.appendChild(close);
			header.appendChild(this.titleEl);
			container.appendChild(header);
			container.appendChild(form);
			win.appendChild(container);
			this.previewEl.appendChild(win);

		} else {
			// Facsimile of the Cerb dialog (classes only — CerbUI.Dialog's JS is never activated).
			const dialog = document.createElement('div');
			dialog.className = 'cerb-ui-dialog cerb-ui-dialog--header-bar cerb-fb--preview-dialog';
			const titlebar = document.createElement('div');
			titlebar.className = 'cerb-ui-dialog--titlebar';
			titlebar.appendChild(this.titleEl);
			// Decorative window controls (inert) — sell the "this is a real popup" illusion; never wired.
			const controls = document.createElement('div');
			controls.className = 'cerb-ui-dialog--controls cerb-fb--preview-controls';
			controls.setAttribute('aria-hidden', 'true');
			controls.innerHTML = '<span class="cerb-ui-dialog--btn"><span class="cerb-icons cerb-icon-remove"></span></span>';
			titlebar.appendChild(controls);
			dialog.appendChild(titlebar);
			const content = document.createElement('div');
			content.className = 'cerb-ui-dialog--content cerb-fb--preview-content';
			content.appendChild(form);
			dialog.appendChild(content);
			this.previewEl.appendChild(dialog);
		}

		doc.appendChild(this.previewEl);

		this._$form = $(form);
		this._$form.on('cerb-form-builder-submit cerb-form-builder-reset cerb-form-builder-end', e => {
			e.stopPropagation(); return false;
		});

		// Click a placed element → select it; click bare canvas → deselect.
		$(this.previewBodyEl).on('click', '.cerb-fb-element', e => {
			e.stopPropagation();
			const idx = parseInt(e.currentTarget.getAttribute('data-cerb-fb-index'), 10);
			if(!isNaN(idx)) this.select(idx);
		});
		this.previewEl.addEventListener('click', e => {
			if(!e.target.closest('.cerb-fb-element')) this.select(-1);
		});

		// KATA pane (read-only generated output + copy) — collapsible; the bar's left half toggles it.
		this.kataEl_wrap = document.createElement('div');
		const kata = this.kataEl_wrap;
		kata.className = 'cerb-fb--kata';
		const kataBar = document.createElement('div');
		kataBar.className = 'cerb-fb--kata-bar';
		const kataToggle = document.createElement('button');
		kataToggle.type = 'button';
		kataToggle.className = 'cerb-fb--kata-toggle';
		this._kataChevron = document.createElement('span');
		this._kataChevron.className = 'cerb-icons cerb-icon-chevron-up cerb-fb--kata-chevron';
		const kataLabel = document.createElement('span');
		kataLabel.className = 'cerb-fb--kata-label';
		kataLabel.textContent = 'Generated KATA';
		kataToggle.appendChild(this._kataChevron);
		kataToggle.appendChild(kataLabel);
		kataToggle.addEventListener('click', () => this._toggleKata());
		const copyBtn = document.createElement('button');
		copyBtn.type = 'button';
		copyBtn.className = 'cerb-ui-button cerb-ui-button--subtle cerb-fb--copy';
		copyBtn.innerHTML = '<span class="cerb-icons cerb-icon-copy"></span> Copy';
		copyBtn.addEventListener('click', () => this._copyKata());
		kataBar.appendChild(kataToggle);
		kataBar.appendChild(copyBtn);
		this.kataEl = document.createElement('textarea');
		this.kataEl.className = 'cerb-fb--kata-text';
		this.kataEl.readOnly = true;
		this.kataEl.spellcheck = false;
		kata.appendChild(kataBar);
		kata.appendChild(this.kataEl);
		doc.appendChild(kata);

		// Collapsed by default (the KATA isn't usually needed until export); remembered per-worker.
		let collapsed = true;
		try { const v = window.localStorage.getItem('cerb-form-builder-kata'); if(v !== null) collapsed = (v === '1'); } catch(e) {}
		this._toggleKata(collapsed);

		// Inspector
		this.inspectorEl = document.createElement('div');
		this.inspectorEl.className = 'cerb-fb--inspector';

		main.appendChild(doc);
		main.appendChild(this.inspectorEl);
		this.el.appendChild(main);

		if(window.CerbUI && CerbUI.SplitPane)
			this.splitpane = new CerbUI.SplitPane(main, { orientation: 'horizontal', ratio: 0.6, min: 0.3, storageKey: 'cerb-form-builder' });

		this._buildDropZone();

		// Enhance the read-only Generated-KATA textarea into a KataEditor (syntax highlighting + folding). Built last
		// so the element is attached; caps at ~18 lines then scrolls. Set/read via _setKataText/_getKataText.
		if(window.CerbUI && CerbUI.KataEditor)
			this._kataEditor = new CerbUI.KataEditor(this.kataEl, { readOnly: true, maxLines: 18 });
	}

	// The Generated-KATA content lives in a read-only KataEditor when available (falls back to the raw textarea).
	_setKataText(kata) {
		if(this._kataEditor) this._kataEditor.setValue(kata);
		else if(this.kataEl) this.kataEl.value = kata;
	}
	_getKataText() {
		if(this._kataEditor) return this._kataEditor.getValue();
		return this.kataEl ? this.kataEl.value : this.serializeKata();
	}

	_buildPalette() {
		const aside = document.createElement('aside');
		aside.className = 'cerb-ui-sidebar cerb-fb--palette';

		const body = document.createElement('div');
		body.className = 'cerb-ui-sidebar--body';

		const hidden = CerbUI.FormBuilder._HIDDEN_TYPES;
		const seen = {};
		const groups = [];
		CerbUI.FormBuilder._CATEGORIES.forEach(cat => {
			const types = cat.types.filter(t => this.components[t] && hidden.indexOf(t) < 0);
			if(types.length) { groups.push({ label: cat.label, color: cat.color, types: types }); types.forEach(t => seen[t] = true); }
		});
		const leftovers = Object.keys(this.components).filter(t => !seen[t] && hidden.indexOf(t) < 0);
		if(leftovers.length) groups.push({ label: 'Components', color: CerbUI.FormBuilder._OTHER_COLOR, types: leftovers });

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
				const icon = (this.components[type] && this.components[type].icon) || 'form';
				li.dataset.icon = icon;
				li.dataset.color = group.color;   // icon-square background (by category)
				li.dataset.name = CerbUI.FormBuilder._humanize(type);
				li.dataset.kind = group.label;
				ul.appendChild(li);
				this._paletteItems[type] = li;
			});
			sec.appendChild(ul);
			body.appendChild(sec);
		});

		aside.appendChild(body);

		// Palette items are drag-only — swallow the click so nothing is "selected" (return truthy = handled).
		if(window.CerbUI && CerbUI.Sidebar)
			this.sidebar = new CerbUI.Sidebar(aside, { palette: true, filter: false, onSelect: () => true });

		return aside;
	}

	_buildDropZone() {
		if(!(window.CerbUI && CerbUI.Droppable)) return;

		this._dropMarker = document.createElement('div');
		this._dropMarker.className = 'cerb-fb--drop-indicator';

		// Droppable.onOver only fires on ENTER, so track the pointer ourselves to move the marker live.
		this._onDragMove = (e) => {
			const r = this.previewEl.getBoundingClientRect();
			if(e.clientY < r.top || e.clientY > r.bottom || e.clientX < r.left || e.clientX > r.right) return;
			this._showDropMarker(this._dropIndex(e.clientY));
		};

		this.dropzone = new CerbUI.Droppable(this.previewEl, {
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
				if(!type || !this.components[type]) return false;
				this.addElement(type, this._dropIndex(info.clientY));
				this.refreshPreview();
				this.renderInspector();
			},
		});
	}

	// Insertion index for a drop at clientY — before the first placed element whose midpoint is below it.
	_dropIndex(clientY) {
		const nodes = Array.from(this.previewBodyEl.querySelectorAll('.cerb-fb-element'));
		for(let i = 0; i < nodes.length; i++) {
			const r = nodes[i].getBoundingClientRect();
			if(clientY < r.top + r.height / 2) return i;
		}
		return nodes.length;
	}

	// Show the insertion line before the element at `index` (or at the end).
	_showDropMarker(index) {
		const nodes = this.previewBodyEl.querySelectorAll('.cerb-fb-element');
		if(index >= nodes.length) this.previewBodyEl.appendChild(this._dropMarker);
		else this.previewBodyEl.insertBefore(this._dropMarker, nodes[index]);
	}

	_hideDropMarker() {
		if(this._dropMarker && this._dropMarker.parentNode) this._dropMarker.parentNode.removeChild(this._dropMarker);
	}

	// ── Model mutation ──────────────────────────────────────────────────

	_uid() { return 'e' + (++this._varCounter) + Math.floor(this._varCounter * 97 % 89).toString(36); }

	_uuid() {
		if(window.crypto && crypto.randomUUID) return crypto.randomUUID();
		return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, c => {
			const r = Math.random() * 16 | 0;
			return (c === 'x' ? r : (r & 0x3 | 0x8)).toString(16);
		});
	}

	// Default output var for a new component — `prompt_<type><n>` (e.g. `prompt_text1`), numbered per type so the
	// name is meaningful and usually needs no renaming.
	_nextVar(type) {
		const used = {};
		this.model.elements.forEach(e => { if(e.key) used[e.key] = true; });
		const base = 'prompt_' + (type || 'field');
		let n = 1;
		while(used[base + n]) n++;
		return base + n;
	}

	_hasSubmit() { return this.model.elements.some(e => e.type === 'submit'); }

	_makeElement(type) {
		const sch = this.schema[type];
		const hasVar = sch ? !!sch.has_var : true;   // unknown (raw) types default to carrying a var
		const elm = { type: type, key: hasVar ? this._nextVar(type) : this._uid(), fields: {}, raw: null };

		if(sch && sch.fields) {
			(sch.fields || []).forEach(f => { if(f.default !== undefined) elm.fields[f.key] = f.default; });
			const nw = sch.new || {};
			Object.keys(nw).forEach(k => { elm.fields[k] = nw[k]; });
		} else {
			// Raw-KATA fallback for types without a curated field set.
			elm.raw = '';
		}

		// Per-instance seeds that can't live in a static schema.
		if(type === 'say' && elm.fields.format == null) {
			elm.fields.format = 'markdown';
			if(elm.fields.content == null) elm.fields.content = 'Sample message text.';
		}
		// A fixed sample session id (UUID shape), shared by llmTranscript + agentPrompt so one find/replace updates
		// both. It never collides with a real session (reads as mock); a real resolved `{{session_id}}` (simulator)
		// still renders live.
		if(type === 'llmTranscript') elm.fields.session_id = CerbUI.FormBuilder._SAMPLE_SESSION_ID;
		if(type === 'agentPrompt') {
			elm.fields.session_id = CerbUI.FormBuilder._SAMPLE_SESSION_ID;
			// Seed with a reference to the first configured agent_model (if any).
			elm.fields.modelList = this.agentModels.length ? [{ name: this.agentModels[0].name }] : [];
		}

		return elm;
	}

	// A model card's `id` is the KATA alias (`models:<id>:`) — editable, must be a valid key. `provider` is fixed
	// at creation; `model` is the free-text model id (TextChooser-assisted); `endpoint` overrides api_endpoint_url.
	_modelFromPreset(p) {
		return { id: this._uniqueModelAlias(p.id || p.provider), provider: p.provider, model: p.model, vision: p.vision !== false, context_window: p.context_window || 200000, authentication: '', endpoint: '' };
	}

	_sanitizeAlias(s) { return String(s || '').trim().replace(/[^A-Za-z0-9_]/g, '_').replace(/^_+|_+$/g, '') || 'model'; }

	// Ensure the alias is unique within the current agentPrompt's model list (append -2, -3, … on collision).
	_uniqueModelAlias(base) {
		base = this._sanitizeAlias(base);
		const elm = (this.selectedIndex >= 0) ? this.model.elements[this.selectedIndex] : null;
		const used = {};
		if(elm && Array.isArray(elm.fields.modelList)) elm.fields.modelList.forEach(m => { if(m.id) used[m.id] = true; });
		if(!used[base]) return base;
		let n = 2, name;
		do { name = base + '_' + (n++); } while(used[name]);
		return name;
	}

	// Guarantee exactly one submit, always last (mirrors the runtime's synthesized trailing submit).
	_ensureSubmit() {
		const submits = this.model.elements.filter(e => e.type === 'submit');
		this.model.elements = this.model.elements.filter(e => e.type !== 'submit');
		this.model.elements.push(submits.length ? submits[0] : this._makeElement('submit'));
	}

	addElement(type, index) {
		if(type === 'submit') return;   // submit is a singleton, always present + last

		const elm = this._makeElement(type);

		// Everything inserts before the trailing submit.
		const lastSubmitAt = this.model.elements.length - 1;
		let at = (index == null) ? lastSubmitAt : index;
		at = Math.max(0, Math.min(at, lastSubmitAt));

		this.model.elements.splice(at, 0, elm);
		this._ensureSubmit();
		this.selectedIndex = this.model.elements.indexOf(elm);
		this._updatePalette();
	}

	removeElement(index) {
		if(index < 0 || index >= this.model.elements.length) return;
		if(this.model.elements[index].type === 'submit') return;   // the submit is pinned
		this.model.elements.splice(index, 1);
		if(this.selectedIndex === index) this.selectedIndex = -1;
		else if(this.selectedIndex > index) this.selectedIndex--;
		this._ensureSubmit();
		this._updatePalette();
		this.refreshPreview();
		this.renderInspector();
	}

	// Hatch out the submit tile while one is on the canvas (it's a singleton).
	_updatePalette() {
		const li = this._paletteItems['submit'];
		if(li) li.classList.toggle('cerb-fb--tile-used', this._hasSubmit());
	}

	select(index) {
		this.selectedIndex = index;
		this._markSelected();
		this.renderInspector();
	}

	// ── Preview ─────────────────────────────────────────────────────────

	// Debounced refresh. With no argument (an inspector edit) it re-renders ONLY the selected element in place,
	// leaving the rest of the preview — and its scroll position — untouched. Structural changes (add/remove/
	// reorder) call refreshPreview() directly for a full re-render.
	_scheduleRefresh(index) {
		if(index === undefined) index = this.selectedIndex;
		this._pendingRefreshIndex = index;
		if(this._refreshTimer) clearTimeout(this._refreshTimer);
		this._refreshTimer = setTimeout(() => {
			this._refreshTimer = null;
			const idx = this._pendingRefreshIndex;
			if(idx != null && idx >= 0 && idx < this.model.elements.length)
				this.refreshElement(idx);
			else
				this.refreshPreview();
		}, 300);
	}

	// Pull live values from embedded editors (SearchQuery/RecordChooser) — their programmatic edits
	// (autocomplete inserts, chip selections) don't fire `input`, so we read them before every serialize.
	_syncLive() { (this._liveSyncs || []).forEach(fn => { try { fn(); } catch(e) {} }); if(this._modelPicker) { try { this._modelPicker.sync(); } catch(e) {} } }

	// Refresh just the KATA pane (used when the change — e.g. the title — isn't reflected in the preview).
	_updateKata() {
		this._syncLive();
		this._setKataText(this.serializeKata());
	}

	refreshPreview() {
		this._syncLive();

		const kata = this.serializeKata();
		this._setKataText(kata);

		if(!this.model.elements.length) {
			this._renderPreviewHtml('');
			return;
		}

		const fd = new FormData();
		fd.set('c', 'profiles');
		fd.set('a', 'invoke');
		fd.set('module', 'automation');
		fd.set('action', 'formBuilderPreview');
		fd.set('extension_id', this.opts.extensionId);
		fd.set('kata', kata);

		genericAjaxPost(fd, null, null, (html) => this._renderPreviewHtml(html));
	}

	// Re-render a single element and swap it in place — no full-body rebuild, so scroll/focus elsewhere is kept.
	refreshElement(index) {
		this._syncLive();

		const kata = this.serializeKata();
		this._setKataText(kata);

		// If the target node isn't in the DOM yet (e.g. first render), fall back to a full render.
		const exists = this.previewBodyEl.querySelector('.cerb-fb-element[data-cerb-fb-index="' + index + '"]');
		if(!exists) { this.refreshPreview(); return; }

		const fd = new FormData();
		fd.set('c', 'profiles');
		fd.set('a', 'invoke');
		fd.set('module', 'automation');
		fd.set('action', 'formBuilderPreview');
		fd.set('extension_id', this.opts.extensionId);
		fd.set('kata', kata);
		fd.set('only_index', index);

		genericAjaxPost(fd, null, null, (html) => this._swapElementHtml(index, html));
	}

	_swapElementHtml(index, html) {
		const $old = $(this.previewBodyEl).find('.cerb-fb-element[data-cerb-fb-index="' + index + '"]');
		if(!$old.length || !html || !String(html).trim()) { this.refreshPreview(); return; }

		$old.replaceWith(html);   // jQuery parses the fragment and runs its injected <script> blocks under the nonce

		this._initSortable();     // rebind the reorder handles to the new node (doesn't touch scroll)
		this._markSelected();
	}

	_renderPreviewHtml(html) {
		const $body = $(this.previewBodyEl);

		// Preserve the scroll position across a full re-render so editing a field low in a long form stays put.
		const scroller = this.previewEl;
		const scrollTop = scroller ? scroller.scrollTop : 0;

		if(!html || !String(html).trim()) {
			$body.html('<div class="cerb-fb--empty">Drag a component from the palette to start building your form.</div>');
			return;
		}

		$body.html(html);   // jQuery runs the injected <script> blocks under the CSP nonce

		this._initSortable();
		this._markSelected();

		if(scroller) scroller.scrollTop = scrollTop;
	}

	_initSortable() {
		if(this._sortable) { this._sortable.destroy(); this._sortable = null; }
		if(!(window.CerbUI && CerbUI.Sortable)) return;

		this._sortable = new CerbUI.Sortable(this.previewBodyEl, {
			items: '.cerb-fb-element',
			handle: '',
			onEnd: (info) => {
				const from = info.fromIndex, to = info.toIndex;
				if(from == null || to == null || from === to) return;
				const moved = this.model.elements.splice(from, 1)[0];
				this.model.elements.splice(to, 0, moved);
				this._ensureSubmit();   // submit stays last even if dragged past it
				this.selectedIndex = this.model.elements.indexOf(moved);
				this.refreshPreview();
				this.renderInspector();
			},
		});
	}

	_markSelected() {
		this.previewBodyEl.querySelectorAll('.cerb-fb-element--selected').forEach(el => el.classList.remove('cerb-fb-element--selected'));
		if(this.selectedIndex < 0) return;
		const el = this.previewBodyEl.querySelector('.cerb-fb-element[data-cerb-fb-index="' + this.selectedIndex + '"]');
		if(el) el.classList.add('cerb-fb-element--selected');
	}

	// ── Inspector ───────────────────────────────────────────────────────

	renderInspector() {
		const host = this.inspectorEl;
		host.innerHTML = '';
		host.classList.remove('cerb-ui-form');   // NB: don't reset className — that would strip SplitPane's --pane class (kills flex:1 → pane collapses to content width)
		this._chooserSearchQuery = null;

		const hasSelection = (this.selectedIndex >= 0 && this.selectedIndex < this.model.elements.length);

		// Collapse the inspector pane entirely when nothing is selected — the document reclaims the full width;
		// expand it back (at the preserved ratio) when a field is selected.
		if(this.splitpane) {
			if(hasSelection) { if(this.splitpane.isCollapsed()) this.splitpane.expand(); }
			else if(!this.splitpane.isCollapsed()) { this.splitpane.collapse('second'); }
		}

		if(!hasSelection)
			return;   // pane collapsed; nothing to render

		host.classList.add('cerb-ui-form');
		this._enhance = [];
		this._liveSyncs = [];
		this._modelPicker = null;   // set only while an agentPrompt inspector is shown

		const elm = this.model.elements[this.selectedIndex];
		const sch = this.schema[elm.type] || {};

		const head = document.createElement('div');
		head.className = 'cerb-fb--inspector-head';
		const icon = (this.components[elm.type] && this.components[elm.type].icon) || 'form';
		head.innerHTML = '<span class="cerb-icons cerb-icon-' + icon + '"></span> <b>' + (sch.title || CerbUI.FormBuilder._humanize(elm.type)) + '</b>';
		host.appendChild(head);

		// `say` is fully custom (no variable, no label — plaintext/markdown body only).
		if(elm.type === 'say') {
			this._renderSayInspector(host, elm);
			this._addRemove(host, elm);
			this._runEnhancements();
			return;
		}

		// Variable name (output binding) for components that produce a value.
		const hasVar = sch.fields ? !!sch.has_var : true;
		if(hasVar) {
			this._addField(host, 'Variable', 'text', elm.key, (v) => {
				elm.key = v.replace(/[^A-Za-z0-9_]/g, '_');
				this._scheduleRefresh();
			});
		}

		// Every component carries a top-level `label:` — always show it right after the variable.
		this._addField(host, 'Label', 'text', elm.fields.label, (v) => {
			elm.fields.label = v;
			this._scheduleRefresh();
		});

		if(elm.type === 'submit') {
			this._renderSubmitInspector(host, elm);
		} else if(elm.type === 'chooser') {
			this._renderChooserInspector(host, elm, sch);
		} else if(elm.type === 'query') {
			this._addRecordTypeSelect(host, elm);
			this._renderRestFields(host, elm, sch, ['label', 'record_type']);
		} else if(elm.type === 'map') {
			this._renderMapInspector(host, elm);
		} else if(elm.type === 'agentPrompt') {
			this._renderAgentPromptInspector(host, elm, sch);
		} else if(elm.type === 'sheet') {
			this._renderRestFields(host, elm, sch, ['label']);   // keep the raw Data/Schema textareas
			this._renderSheetDesignButton(host, elm);            // + a visual "Design sheet…" launcher
		} else if(sch.fields) {
			this._renderRestFields(host, elm, sch, ['label']);
		} else {
			// Raw-KATA fallback — edit the element body directly.
			this._addField(host, 'Body (KATA)', 'kata', elm.raw || '', (v) => {
				elm.raw = v;
				this._scheduleRefresh();
			});
		}

		this._addRemove(host, elm);
		this._runEnhancements();
	}

	// A "Design sheet…" button that opens CerbUI.SheetBuilder (in a nested CerbUI.Dialog) seeded from this sheet
	// element's data/schema; on Apply it writes the built parts back into elm.fields.data / elm.fields.schema.
	_renderSheetDesignButton(host, elm) {
		if(!(window.CerbUI && CerbUI.Dialog && CerbUI.SheetBuilder)) return;
		const btn = document.createElement('button');
		btn.type = 'button';
		btn.className = 'cerb-ui-button cerb-fb--design-sheet';
		btn.innerHTML = '<span class="cerb-icons cerb-icon-table"></span> Design sheet…';
		btn.addEventListener('click', () => this._openSheetBuilder(elm));
		host.appendChild(btn);
	}

	_openSheetBuilder(elm) {
		const fd = new FormData();
		fd.set('c', 'profiles');
		fd.set('a', 'invoke');
		fd.set('module', 'automation');
		fd.set('action', 'showSheetBuilderPopup');
		fd.set('extension_id', this.opts.extensionId);
		fd.set('data', elm.fields.data || '');
		fd.set('schema', elm.fields.schema || '');

		const dlg = CerbUI.Dialog.fromAjax(fd, {
			namespace: 'sheetBuilder',
			title: 'Design sheet',
			width: '92%',
			onLoad: (content) => {
				$(content).on('cerb-sheet-builder-apply', (e, parts) => {
					if(parts) {
						elm.fields.data = parts.data || '';
						elm.fields.schema = parts.schema || '';
						this.renderInspector();   // refresh the Data/Schema textareas to the applied KATA
						this._scheduleRefresh();
					}
					if(dlg && dlg.close) dlg.close();
				});
			},
		});
	}

	// The submit is pinned (always present, always last) — no remove control.
	_addRemove(host, elm) {
		if(elm.type === 'submit') return;
		const remove = document.createElement('button');
		remove.type = 'button';
		remove.className = 'cerb-ui-button cerb-ui-button--outline cerb-fb--remove';
		remove.innerHTML = '<span class="cerb-icons cerb-icon-trash"></span> Remove field';
		remove.addEventListener('click', () => this.removeElement(this.selectedIndex));
		host.appendChild(remove);
	}

	// Render a type's remaining schema fields, skipping the given keys (rendered elsewhere).
	_renderRestFields(host, elm, sch, skip) {
		(sch.fields || []).forEach(f => {
			if(skip.indexOf(f.key) >= 0) return;
			this._addField(host, f.label || f.key, f.input, elm.fields[f.key], (v) => {
				elm.fields[f.key] = v;
				this._scheduleRefresh();
			}, f.options);
		});
	}

	// A record-type SelectMenu (icons) bound to elm.fields.record_type. Optionally re-scopes a chooser query.
	_addRecordTypeSelect(host, elm, onChangeExtra) {
		const field = document.createElement('div');
		field.className = 'cerb-ui-form--field';
		const lab = document.createElement('label');
		lab.className = 'cerb-ui-form--label';
		lab.textContent = 'Record type';
		const sel = document.createElement('select');
		this.recordTypes.forEach(rt => {
			const o = document.createElement('option');
			o.value = rt.alias;
			o.textContent = rt.label;
			o.setAttribute('data-cerb-ui-icon', rt.icon);
			if(rt.alias === elm.fields.record_type) o.selected = true;
			sel.appendChild(o);
		});
		field.appendChild(lab);
		field.appendChild(sel);
		host.appendChild(field);
		this._enhance.push(() => new CerbUI.SelectMenu(sel, {
			onSelect: (v) => {
				elm.fields.record_type = v;
				if(onChangeExtra) onChangeExtra(v);
				this._scheduleRefresh();
			},
		}));
	}

	// say: a single CerbUI.MarkdownEditor — its built-in toolbar has the Markdown/Plaintext toggle. Same body
	// text either way; only the KATA key changes (markdown → `content@text:`, plaintext → `message@text:`).
	// No variable, no label.
	_renderSayInspector(host, elm) {
		const field = document.createElement('div');
		field.className = 'cerb-ui-form--field';
		const lab = document.createElement('label');
		lab.className = 'cerb-ui-form--label';
		lab.textContent = 'Content';
		const ta = document.createElement('textarea');
		ta.value = elm.fields.content || '';
		field.appendChild(lab);
		field.appendChild(ta);
		host.appendChild(field);

		this._enhance.push(() => new CerbUI.MarkdownEditor(ta, {
			mode: elm.fields.format === 'plaintext' ? 'plaintext' : 'markdown',
			toolbar: {
				mode: true,
				onMode: (mode) => { elm.fields.format = mode; this._scheduleRefresh(); },
			},
			onChange: (v) => { elm.fields.content = v; this._scheduleRefresh(); },
		}));
	}

	// map: a RecordChooser for the geometry resource + the common projection/center/zoom knobs + points
	// (resource or manual lat/long). Emits `resource:`/`projection:`/`points:` under the element body. This is a
	// stopgap so a map is usable/previewable from the builder until a dedicated Map Builder exists.
	_renderMapInspector(host, elm) {
		// Base region geometry — a RecordChooser over `cerb.resource.map` resources.
		this._addMapResourceChooser(host, elm, {
			label: 'Resource', field: 'resource_uri', type: 'cerb.resource.map',
			icon: 'map', placeholder: 'Map resource…',
		});

		// Projection — re-render on change so the Scale placeholder reflects the type's default.
		const projType = elm.fields.projection_type || 'mercator';
		this._addField(host, 'Projection', 'select', projType, (v) => {
			elm.fields.projection_type = v;
			this.renderInspector();
			this._scheduleRefresh();
		}, [['mercator', 'Mercator'], ['albersUsa', 'Albers USA']]);

		// Capture scale + center from the current preview map (pan/zoom, then click) — beats typing each by hand.
		this._addMapCaptureButton(host, elm);

		// Scale default is projection-aware (the map service defaults it when omitted); hint it as a placeholder.
		this._addNumberField(host, 'Scale', elm, 'projection_scale', (projType === 'albersUsa' ? '670' : '90') + ' (default)');
		this._addNumberField(host, 'Center latitude', elm, 'center_latitude');
		this._addNumberField(host, 'Center longitude', elm, 'center_longitude');
		this._addNumberField(host, 'Zoom latitude', elm, 'zoom_latitude');
		this._addNumberField(host, 'Zoom longitude', elm, 'zoom_longitude');
		this._addNumberField(host, 'Zoom scale', elm, 'zoom_scale');

		// Points — none / resource / manual (lat-long). The chosen mode is stored explicitly (points_mode) so
		// selecting "Resource" shows the chooser BEFORE a resource is picked — deriving the mode from the field
		// values alone would snap back to "None" while points_uri is still empty.
		const pointsMode = elm.fields.points_mode || (elm.fields.points_data ? 'manual' : (elm.fields.points_uri ? 'resource' : 'none'));
		this._addField(host, 'Points', 'select', pointsMode, (v) => {
			elm.fields.points_mode = v;
			if(v !== 'resource') elm.fields.points_uri = '';
			if(v !== 'manual') elm.fields.points_data = '';
			this.renderInspector();
			this._scheduleRefresh();
		}, [['none', 'None'], ['resource', 'Resource'], ['manual', 'Manual (lat/long)']]);

		if(pointsMode === 'resource') {
			this._addMapResourceChooser(host, elm, {
				label: 'Points resource', field: 'points_uri', type: 'cerb.resource.map.points',
				icon: 'map-pin', placeholder: 'Points resource…',
			});
		} else if(pointsMode === 'manual') {
			this._addField(host, 'Points (KATA)', 'kata', elm.fields.points_data || '', (v) => {
				elm.fields.points_data = v;
				this._scheduleRefresh();
			});
		}

		// Region filter + fill, then points filter.
		this._renderMapFilter(host, elm, 'regions', 'Region Filter');
		this._renderRegionFill(host, elm);
		this._renderMapFilter(host, elm, 'points', 'Points Filter');
	}

	// Region fill via `color_map`: a property + value→color rows (each color a CerbUI.ColorPicker swatch).
	_renderRegionFill(host, elm) {
		const head = document.createElement('div');
		head.className = 'cerb-fb--map-section';
		head.textContent = 'Region Fill';
		host.appendChild(head);

		const rows = Array.isArray(elm.fields.regions_fill_map) ? elm.fields.regions_fill_map : null;
		const active = !!elm.fields.regions_fill_active || !!elm.fields.regions_fill_property || (rows && rows.length > 0);

		if(!active) {
			const add = document.createElement('button');
			add.type = 'button';
			add.className = 'cerb-ui-button cerb-ui-button--outline cerb-fb--map-add-filter';
			add.innerHTML = '<span class="cerb-icons cerb-icon-plus"></span> Color map';
			add.addEventListener('click', () => {
				elm.fields.regions_fill_active = true;
				elm.fields.regions_fill_map = [{ value: '', color: '#3182ce' }];
				this.renderInspector();
			});
			host.appendChild(add);
			return;
		}

		this._addField(host, 'Property', 'text', elm.fields.regions_fill_property || '', (v) => {
			elm.fields.regions_fill_property = v;
			this._scheduleRefresh();
		});

		if(!Array.isArray(elm.fields.regions_fill_map)) elm.fields.regions_fill_map = [];
		elm.fields.regions_fill_map.forEach((row) => this._buildColorMapRow(host, elm, row));

		const addVal = document.createElement('button');
		addVal.type = 'button';
		addVal.className = 'cerb-ui-button cerb-ui-button--outline cerb-fb--map-add-filter';
		addVal.innerHTML = '<span class="cerb-icons cerb-icon-plus"></span> Value';
		addVal.addEventListener('click', () => {
			elm.fields.regions_fill_map.push({ value: '', color: '#3182ce' });
			this.renderInspector();
		});
		host.appendChild(addVal);

		const rm = document.createElement('button');
		rm.type = 'button';
		rm.className = 'cerb-ui-button cerb-ui-button--outline cerb-fb--map-remove-filter';
		rm.innerHTML = '<span class="cerb-icons cerb-icon-trash"></span> Remove fill';
		rm.addEventListener('click', () => {
			elm.fields.regions_fill_active = false;
			elm.fields.regions_fill_property = '';
			elm.fields.regions_fill_map = [];
			this.renderInspector();
			this._scheduleRefresh();
		});
		host.appendChild(rm);
	}

	// One value→color row: a value text input + a ColorPicker swatch + a remove button.
	_buildColorMapRow(host, elm, row) {
		const field = document.createElement('div');
		field.className = 'cerb-ui-form--field';
		const line = document.createElement('div');
		line.className = 'cerb-fb--colormap-rowline';

		const val = document.createElement('input');
		val.type = 'text';
		val.placeholder = 'Value (e.g. California)';
		val.value = row.value || '';
		val.addEventListener('input', () => { row.value = val.value; this._scheduleRefresh(); });

		const color = document.createElement('input');
		color.type = 'text';
		color.className = 'cerb-fb--colormap-color';
		color.value = row.color || '#3182ce';

		const rm = document.createElement('button');
		rm.type = 'button';
		rm.className = 'cerb-ui-button cerb-ui-button--outline cerb-fb--colormap-remove';
		rm.innerHTML = '<span class="cerb-icons cerb-icon-circle-minus"></span>';
		rm.title = 'Remove value';
		rm.addEventListener('click', () => {
			const i = elm.fields.regions_fill_map.indexOf(row);
			if(i >= 0) elm.fields.regions_fill_map.splice(i, 1);
			this.renderInspector();
			this._scheduleRefresh();
		});

		line.appendChild(val);
		line.appendChild(color);
		line.appendChild(rm);
		field.appendChild(line);
		host.appendChild(field);

		this._enhance.push(() => {
			if(window.CerbUI && CerbUI.ColorPicker)
				new CerbUI.ColorPicker(color, { showInput: false, onChange: (hx) => { row.color = hx; this._scheduleRefresh(); } });
		});
	}

	// A single map filter section (regions or points): a property + is/not Switcher + a TagInput value list,
	// gated behind a "(+) Property" button so an unfiltered map stays clean. The map renderer supports ONE filter
	// object per section (`filter: {property, is|not}`), so this is one property per section.
	_renderMapFilter(host, elm, prefix, heading) {
		const propKey = prefix + '_filter_property';
		const modeKey = prefix + '_filter_mode';
		const valsKey = prefix + '_filter_values';

		const head = document.createElement('div');
		head.className = 'cerb-fb--map-section';
		head.textContent = heading;
		host.appendChild(head);

		const active = !!elm.fields[prefix + '_filter_active'] || !!elm.fields[propKey]
			|| (Array.isArray(elm.fields[valsKey]) && elm.fields[valsKey].length > 0);

		if(!active) {
			const add = document.createElement('button');
			add.type = 'button';
			add.className = 'cerb-ui-button cerb-ui-button--outline cerb-fb--map-add-filter';
			add.innerHTML = '<span class="cerb-icons cerb-icon-plus"></span> Property';
			add.addEventListener('click', () => {
				elm.fields[prefix + '_filter_active'] = true;
				if(!elm.fields[modeKey]) elm.fields[modeKey] = 'is';
				this.renderInspector();
			});
			host.appendChild(add);
			return;
		}

		this._addField(host, 'Property', 'text', elm.fields[propKey] || '', (v) => {
			elm.fields[propKey] = v;
			this._scheduleRefresh();
		});

		// is / not — CerbUI.Switcher enhances pre-built <button data-value> markup.
		const swField = document.createElement('div');
		swField.className = 'cerb-ui-form--field';
		const swLab = document.createElement('label');
		swLab.className = 'cerb-ui-form--label';
		swLab.textContent = 'Match';
		swField.appendChild(swLab);
		const sw = document.createElement('div');
		sw.className = 'cerb-ui-switcher cerb-fb--map-switcher';
		const mode = elm.fields[modeKey] || 'is';
		[['is', 'is'], ['not', 'is not']].forEach(([val, label]) => {
			const b = document.createElement('button');
			b.type = 'button';
			b.setAttribute('data-value', val);
			b.textContent = label;
			if(mode === val) b.className = 'cerb-ui-switcher--active';
			sw.appendChild(b);
		});
		swField.appendChild(sw);
		host.appendChild(swField);
		this._enhance.push(() => new CerbUI.Switcher(sw, {
			value: mode,
			onSelect: (v) => { elm.fields[modeKey] = v; this._scheduleRefresh(); },
		}));

		// Values — CerbUI.TagInput (always a list; one value is just a single tag).
		const tiField = document.createElement('div');
		tiField.className = 'cerb-ui-form--field';
		const tiLab = document.createElement('label');
		tiLab.className = 'cerb-ui-form--label';
		tiLab.textContent = 'Values';
		tiField.appendChild(tiLab);
		const ti = document.createElement('div');
		ti.className = 'cerb-fb--taginput';
		tiField.appendChild(ti);
		host.appendChild(tiField);
		this._enhance.push(() => {
			const tag = new CerbUI.TagInput(ti, {
				value: Array.isArray(elm.fields[valsKey]) ? elm.fields[valsKey] : [],
				placeholder: 'Add value…',
				onChange: (vals) => { elm.fields[valsKey] = vals; this._scheduleRefresh(); },
			});
			// Tag edits don't fire input on the field — pull the live value before each serialize.
			this._liveSyncs.push(() => { if(tag && tag.getValue) elm.fields[valsKey] = tag.getValue(); });
		});

		const rm = document.createElement('button');
		rm.type = 'button';
		rm.className = 'cerb-ui-button cerb-ui-button--outline cerb-fb--map-remove-filter';
		rm.innerHTML = '<span class="cerb-icons cerb-icon-trash"></span> Remove filter';
		rm.addEventListener('click', () => {
			elm.fields[prefix + '_filter_active'] = false;
			elm.fields[propKey] = '';
			elm.fields[valsKey] = [];
			this.renderInspector();
			this._scheduleRefresh();
		});
		host.appendChild(rm);
	}

	// A RecordChooser (autocomplete-only) over Resource records of one map resource-type. The map URI is
	// name-based (`cerb:resource:<name>`) and Context_Resource::autocomplete returns the name as the value, so
	// the chosen item's id IS the resource name — no worklist popup (searchButton:false) to keep the value a name.
	_addMapResourceChooser(host, elm, cfg) {
		const field = document.createElement('div');
		field.className = 'cerb-ui-form--field';
		const lab = document.createElement('label');
		lab.className = 'cerb-ui-form--label';
		lab.textContent = cfg.label;
		const wrap = document.createElement('div');
		wrap.className = 'cerb-ui-record-chooser cerb-fb--map-chooser';
		field.appendChild(lab);
		field.appendChild(wrap);
		host.appendChild(field);

		const currentName = this._resourceUriToName(elm.fields[cfg.field]);

		this._enhance.push(() => {
			if(!(window.CerbUI && CerbUI.RecordChooser)) return;
			const seed = currentName ? { context: 'resource', id: currentName, label: currentName } : null;
			const rc = new CerbUI.RecordChooser(wrap, {
				context: 'resource',
				query: 'type:' + cfg.type,
				emptyIcon: cfg.icon || 'map',
				searchButton: false,   // autocomplete-only; value is the resource NAME (URIs are name-based)
				searchPlaceholder: cfg.placeholder || 'Resource…',
				value: seed,
				onSelect: (item) => {
					elm.fields[cfg.field] = (item && item.id) ? ('cerb:resource:' + item.id) : '';
					this._scheduleRefresh();
				},
			});
			// A chooser CLEAR mutates without firing onSelect — pull the live value before each serialize.
			this._liveSyncs.push(() => {
				const v = rc.getValue ? rc.getValue() : null;
				const item = Array.isArray(v) ? v[0] : v;
				elm.fields[cfg.field] = (item && item.id) ? ('cerb:resource:' + item.id) : '';
			});
		});
	}

	// A float-friendly numeric field. The generic 'number' input parseInts on change, which corrupts decimal
	// lat/long — so use a text input and keep the raw string (emitted bare; KATA/JS coerce numerically).
	_addNumberField(host, label, elm, field, placeholder) {
		this._addField(host, label, 'text', (elm.fields[field] == null ? '' : elm.fields[field]), (v) => {
			elm.fields[field] = String(v).trim();
			this._scheduleRefresh();
		}, null, placeholder);
	}

	_resourceUriToName(uri) {
		const m = String(uri || '').match(/^cerb:resource:(.+)$/);
		return m ? m[1] : '';
	}

	// A crosshairs button that reads the current framing from the live preview map and fills scale + center.
	_addMapCaptureButton(host, elm) {
		const btn = document.createElement('button');
		btn.type = 'button';
		btn.className = 'cerb-ui-button cerb-ui-button--outline cerb-fb--map-capture';
		btn.innerHTML = '<span class="cerb-icons cerb-icon-crosshairs"></span> Capture view from preview';
		btn.title = 'Set scale + center from the current preview map — pan/zoom to frame it, then click';
		btn.addEventListener('click', () => this._captureMapView(elm));
		host.appendChild(btn);
	}

	_captureMapView(elm) {
		const wrap = this.previewBodyEl && this.previewBodyEl.querySelector('.cerb-fb-element[data-cerb-fb-index="' + this.selectedIndex + '"]');
		const mapEl = wrap && wrap.querySelector('.cerb-ui-map');
		const map = (mapEl && window.CerbUI && CerbUI.Map && CerbUI.Map.from) ? CerbUI.Map.from(mapEl) : null;
		const view = (map && map.getView) ? map.getView() : null;
		if(!view || !view.center) return;   // preview not rendered / not interactive yet

		elm.fields.center_latitude = view.center.latitude.toFixed(4);
		elm.fields.center_longitude = view.center.longitude.toFixed(4);
		elm.fields.projection_scale = String(Math.round(view.scale));
		// Center + scale bake in the current framing, so drop any separate zoom (it would re-apply on top).
		elm.fields.zoom_latitude = '';
		elm.fields.zoom_longitude = '';
		elm.fields.zoom_scale = '';

		this.renderInspector();   // reflect the captured values
		this._scheduleRefresh();  // re-render the preview at the captured framing
	}

	// agentPrompt: an ordered list of models (preset-seeded) + per-model props; the guided core of this component.
	// The model-card editor is the shared CerbUI.AgentPrompt.ModelPicker (also used by the Automation Builder).
	_renderAgentPromptInspector(host, elm, sch) {
		this._renderRestFields(host, elm, sch, ['label']);   // placeholder

		const sessField = document.createElement('div');
		sessField.className = 'cerb-ui-form--field';
		const sessLabel = document.createElement('label');
		sessLabel.className = 'cerb-ui-form--label';
		sessLabel.textContent = 'Session ID';
		const sessInput = document.createElement('input');
		sessInput.type = 'text';
		sessInput.value = elm.fields.session_id || '';
		sessInput.readOnly = true;
		sessField.appendChild(sessLabel);
		sessField.appendChild(sessInput);
		host.appendChild(sessField);

		if(!Array.isArray(elm.fields.modelList)) elm.fields.modelList = [];

		const container = document.createElement('div');
		host.appendChild(container);

		// Built after the inspector is attached (its TextChooser/RecordChooser enhance on construct). We alias
		// elm.fields.modelList to the picker's internal objects so `_emitAgentPrompt` reads live values (a
		// chooser CLEAR mutates them without firing onChange; `_syncLive()` pulls those in before emit).
		this._enhance.push(() => {
			this._modelPicker = new CerbUI.AgentPrompt.ModelPicker(container, {
				agentModels: this.agentModels,
				models: elm.fields.modelList,
				onChange: () => { this._scheduleRefresh(); },
			});
			elm.fields.modelList = this._modelPicker.models;
		});
	}

	// Submit: a mutually-exclusive Default / Automatic / Custom-buttons mode, plus a `Hidden` property. `hidden`
	// is NOT a mode — it's a modifier on a configured button set (Default or Custom): the buttons stay configured
	// and `hidden@bool: yes` hides the whole submit at runtime (dimmed in the builder preview), so an
	// automation-driven flow can pre-configure buttons that are only conditionally shown. Only the active mode's
	// params are written to KATA (default → explicit continue/reset bools; auto → is_automatic; custom → a
	// `buttons:` block, which overrides the default continue/reset entirely).
	_renderSubmitInspector(host, elm) {
		const hasButtons = Array.isArray(elm.fields.buttonList) && elm.fields.buttonList.length;
		const mode = elm.fields.is_automatic ? 'auto' : (hasButtons ? 'custom' : 'default');

		this._addField(host, 'Behavior', 'select', mode, (v) => {
			elm.fields.is_automatic = (v === 'auto');
			if(v === 'custom') {
				if(!Array.isArray(elm.fields.buttonList) || !elm.fields.buttonList.length) elm.fields.buttonList = this._defaultButtons();
			} else {
				elm.fields.buttonList = [];
			}
			this.renderInspector();
			this._scheduleRefresh();
		}, [['default', 'Default buttons'], ['auto', 'Automatic submit'], ['custom', 'Custom buttons']]);

		if(mode === 'default') {
			this._addField(host, 'Continue button', 'bool', elm.fields.continue !== false, (v) => {
				elm.fields.continue = v;
				this._scheduleRefresh();
			});
			this._addField(host, 'Reset button', 'bool', !!elm.fields.reset, (v) => {
				elm.fields.reset = v;
				this._scheduleRefresh();
			});
		} else if(mode === 'custom') {
			(elm.fields.buttonList || []).forEach((b, i) => host.appendChild(this._buildButtonCard(elm, b, i)));
			const add = document.createElement('button');
			add.type = 'button';
			add.className = 'cerb-ui-button cerb-ui-button--subtle cerb-fb--add-model';
			add.innerHTML = '<span class="cerb-icons cerb-icon-plus"></span> Button';
			add.addEventListener('click', () => {
				elm.fields.buttonList.push({ type: 'continue', label: 'Continue', icon: 'circle-ok', icon_at: 'start', value: 'yes' });
				this.renderInspector();
				this._scheduleRefresh();
			});
			host.appendChild(add);
		}

		// 'Hidden' — a property of the configured button set (kept, but the whole submit is hidden at runtime;
		// dimmed in the preview). N/A to Automatic submit (it has no buttons to keep).
		if(mode !== 'auto') {
			this._addField(host, 'Hidden', 'bool', !!elm.fields.hidden, (v) => {
				elm.fields.hidden = v;
				this._scheduleRefresh();
			});
		}
	}

	_defaultButtons() {
		return [
			{ type: 'continue', label: 'Continue', icon: 'circle-ok', icon_at: 'start', value: 'yes' },
			{ type: 'reset', label: 'Reset', icon: 'refresh', icon_at: 'start', value: '' },
		];
	}

	_buildButtonCard(elm, b, index) {
		const card = document.createElement('div');
		card.className = 'cerb-fb--model-card';

		const header = document.createElement('div');
		header.className = 'cerb-fb--model-card-head';
		header.innerHTML = '<b>' + (b.label || 'Button') + '</b>';
		const del = document.createElement('button');
		del.type = 'button';
		del.className = 'cerb-ui-button cerb-ui-button--subtle';
		del.innerHTML = '<span class="cerb-icons cerb-icon-circle-remove"></span>';
		del.addEventListener('click', () => { elm.fields.buttonList.splice(index, 1); this.renderInspector(); this._scheduleRefresh(); });
		header.appendChild(del);
		card.appendChild(header);

		this._addField(card, 'Type', 'select', b.type, (v) => { b.type = v; this._scheduleRefresh(); }, [['continue', 'Continue'], ['reset', 'Reset']]);
		this._addField(card, 'Label', 'text', b.label, (v) => { b.label = v; this._scheduleRefresh(); });

		// Icon — CerbUI.IconPicker.
		const iconField = document.createElement('div');
		iconField.className = 'cerb-ui-form--field';
		const iconLabel = document.createElement('label');
		iconLabel.className = 'cerb-ui-form--label';
		iconLabel.textContent = 'Icon';
		const iconInput = document.createElement('input');
		iconInput.type = 'text';
		iconInput.value = b.icon || '';
		iconField.appendChild(iconLabel);
		iconField.appendChild(iconInput);
		card.appendChild(iconField);
		this._enhance.push(() => {
			if(window.CerbUI && CerbUI.IconPicker)
				new CerbUI.IconPicker(iconInput, { value: b.icon || '', onChange: (name) => { b.icon = name; this._scheduleRefresh(); } });
		});

		this._addField(card, 'Icon position', 'select', b.icon_at || 'start', (v) => { b.icon_at = v; this._scheduleRefresh(); }, [['start', 'Start'], ['end', 'End']]);
		this._addField(card, 'Value', 'text', b.value, (v) => { b.value = v; this._scheduleRefresh(); });

		return card;
	}

	// Chooser: a record-type picker (SelectMenu of record types w/ icons) that scopes the query editor
	// (CerbUI.SearchQuery) it feeds — changing the type re-roots the query's autocomplete.
	_renderChooserInspector(host, elm, sch) {
		// Record type — SelectMenu; changing it re-scopes the query editor below.
		this._addRecordTypeSelect(host, elm, (v) => {
			const rt = this._recordTypeByAlias[v];
			if(this._chooserSearchQuery && rt) this._chooserSearchQuery.setContext(rt.context);
		});

		// Query — a SearchQuery scoped to the chosen record type's context.
		const qField = document.createElement('div');
		qField.className = 'cerb-ui-form--field';
		const qLabel = document.createElement('label');
		qLabel.className = 'cerb-ui-form--label';
		qLabel.textContent = 'Query';
		const ta = document.createElement('textarea');
		ta.value = elm.fields.query || '';
		qField.appendChild(qLabel);
		qField.appendChild(ta);
		host.appendChild(qField);
		this._enhance.push(() => {
			const rt = this._recordTypeByAlias[elm.fields.record_type];
			const ctx = rt ? rt.context : '';
			const source = (ctx && CerbUI.SearchQuery.queryFieldSource) ? CerbUI.SearchQuery.queryFieldSource(ctx) : null;
			const sq = new CerbUI.SearchQuery(ta, { context: ctx, onAutocomplete: source, placeholder: 'Filter results…' });
			this._chooserSearchQuery = sq;
			// Read-on-refresh (autocomplete inserts don't fire input) + trigger a refresh on any interaction.
			this._liveSyncs.push(() => { elm.fields.query = sq.getValue(); });
			if(sq.textarea) ['input', 'keyup', 'blur'].forEach(ev => sq.textarea.addEventListener(ev, () => this._scheduleRefresh()));
		});

		// The remaining chooser fields (default / multiple / autocomplete / required / hidden).
		(sch.fields || []).forEach(f => {
			if(['label', 'record_type', 'query'].indexOf(f.key) >= 0) return;
			this._addField(host, f.label || f.key, f.input, elm.fields[f.key], (v) => {
				elm.fields[f.key] = v;
				this._scheduleRefresh();
			}, f.options);
		});
	}

	// Build one labeled inspector control in cerb-ui-form style; bools → CerbUI.Toggle, selects → CerbUI.SelectMenu.
	_addField(host, label, input, value, onChange, options, placeholder) {
		if(input === 'bool') {
			const row = document.createElement('div');
			row.className = 'cerb-fb--toggle-row';
			const toggle = document.createElement('label');
			toggle.className = 'cerb-ui-toggle';
			const cb = document.createElement('input');
			cb.type = 'checkbox';
			cb.checked = !!value;
			const slider = document.createElement('span');
			slider.className = 'cerb-ui-toggle--slider';
			toggle.appendChild(cb);
			toggle.appendChild(slider);
			const text = document.createElement('span');
			text.className = 'cerb-fb--toggle-label';
			text.textContent = label;
			row.appendChild(toggle);
			row.appendChild(text);
			host.appendChild(row);
			this._enhance.push(() => new CerbUI.Toggle(toggle, { onChange: (checked) => onChange(checked) }));
			return;
		}

		const field = document.createElement('div');
		field.className = 'cerb-ui-form--field';
		const lab = document.createElement('label');
		lab.className = 'cerb-ui-form--label';
		lab.textContent = label;
		field.appendChild(lab);

		if(input === 'select') {
			const sel = document.createElement('select');
			(options || []).forEach(o => {
				const val = Array.isArray(o) ? o[0] : o;
				const txt = Array.isArray(o) ? o[1] : o;
				const opt = document.createElement('option');
				opt.value = val;
				opt.textContent = txt;
				if(String(value) === String(val)) opt.selected = true;
				sel.appendChild(opt);
			});
			field.appendChild(sel);
			this._enhance.push(() => new CerbUI.SelectMenu(sel, { onSelect: (v) => onChange(v) }));
		} else if(input === 'multiline' || input === 'kata') {
			const ta = document.createElement('textarea');
			ta.value = (value == null) ? '' : value;
			if(input === 'kata') ta.className = 'cerb-fb--field-kata';
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

	// Enhance selects/toggles once they're in the DOM (SelectMenu/Toggle need an attached element).
	_runEnhancements() {
		if(!window.CerbUI) return;
		(this._enhance || []).forEach(fn => { try { fn(); } catch(e) {} });
		this._enhance = [];
	}

	// ── KATA serialization (model → KATA) ───────────────────────────────

	serializeKata() {
		const lines = ['await:', '  form:'];
		lines.push('    title: ' + (this.model.title || 'Form'));
		lines.push('    elements:');

		this.model.elements.forEach(elm => {
			lines.push('      ' + elm.type + '/' + (elm.key || 'field') + ':');
			this._emitElementBody(elm, lines, 4);
		});

		return lines.join('\n');
	}

	_pad(n) { return '  '.repeat(n); }

	_emitRawBlock(text, lines, indent) {
		String(text).replace(/\s+$/, '').split('\n').forEach(l => lines.push(this._pad(indent) + l));
	}

	_emitScalar(key, input, v, lines, indent) {
		if(input === 'bool') {
			if(v) lines.push(this._pad(indent) + key + '@bool: yes');
			return;
		}
		if(v === undefined || v === null || v === '') return;

		if(input === 'kata') {
			lines.push(this._pad(indent) + key + ':');
			this._emitRawBlock(v, lines, indent + 1);
		} else if(input === 'number') {
			lines.push(this._pad(indent) + key + '@int: ' + v);
		} else if(input === 'multiline' || String(v).indexOf('\n') >= 0) {
			lines.push(this._pad(indent) + key + '@text:');
			this._emitRawBlock(v, lines, indent + 1);
		} else {
			lines.push(this._pad(indent) + key + ': ' + v);
		}
	}

	_emitElementBody(elm, lines, indent) {
		// say has no label — plaintext/markdown body only.
		if(elm.type === 'say') {
			this._emitSay(elm, lines, indent);
			return;
		}

		// Every element carries a top-level `label:` — emit it first, uniformly (curated and raw types alike).
		if(elm.fields && elm.fields.label)
			this._emitScalar('label', 'text', elm.fields.label, lines, indent);

		if(elm.type === 'submit') {
			this._emitSubmit(elm, lines, indent);
			return;
		}
		if(elm.type === 'map') {
			this._emitMap(elm, lines, indent);
			return;
		}
		if(elm.type === 'agentPrompt') {
			this._emitAgentPrompt(elm, lines, indent);
			return;
		}

		if(elm.raw != null && elm.raw !== '') {
			this._emitRawBlock(elm.raw, lines, indent);
			return;
		}

		const sch = this.schema[elm.type] || {};
		(sch.fields || []).forEach(f => {
			if(f.key === 'label') return;   // emitted above
			this._emitScalar(f.key, f.input, elm.fields ? elm.fields[f.key] : undefined, lines, indent);
		});
	}

	// say → same body, keyed by format: markdown → `content@text:`, plaintext → `message@text:`.
	_emitSay(elm, lines, indent) {
		const f = elm.fields || {};
		const key = (f.format === 'plaintext') ? 'message' : 'content';
		if(f.content) { lines.push(this._pad(indent) + key + '@text:'); this._emitRawBlock(f.content, lines, indent + 1); }
		if(f.hidden) lines.push(this._pad(indent) + 'hidden@bool: yes');
	}

	// map → `resource:` + optional `projection:`/`points:`. Projection sub-keys are only emitted when set (the
	// map service defaults the rest); numbers are emitted bare, matching existing shipped map KATA.
	_emitMap(elm, lines, indent) {
		const f = elm.fields || {};

		if(f.resource_uri) {
			lines.push(this._pad(indent) + 'resource:');
			lines.push(this._pad(indent + 1) + 'uri: ' + f.resource_uri);
		}

		const proj = [];
		if(f.projection_type) proj.push(this._pad(indent + 1) + 'type: ' + f.projection_type);
		if(this._numSet(f.projection_scale)) proj.push(this._pad(indent + 1) + 'scale: ' + f.projection_scale);
		const center = [];
		if(this._numSet(f.center_latitude)) center.push(this._pad(indent + 2) + 'latitude: ' + f.center_latitude);
		if(this._numSet(f.center_longitude)) center.push(this._pad(indent + 2) + 'longitude: ' + f.center_longitude);
		const zoom = [];
		if(this._numSet(f.zoom_latitude)) zoom.push(this._pad(indent + 2) + 'latitude: ' + f.zoom_latitude);
		if(this._numSet(f.zoom_longitude)) zoom.push(this._pad(indent + 2) + 'longitude: ' + f.zoom_longitude);
		if(this._numSet(f.zoom_scale)) zoom.push(this._pad(indent + 2) + 'scale: ' + f.zoom_scale);

		if(proj.length || center.length || zoom.length) {
			lines.push(this._pad(indent) + 'projection:');
			proj.forEach(l => lines.push(l));
			if(center.length) { lines.push(this._pad(indent + 1) + 'center:'); center.forEach(l => lines.push(l)); }
			if(zoom.length) { lines.push(this._pad(indent + 1) + 'zoom:'); zoom.forEach(l => lines.push(l)); }
		}

		// Regions — filter + color_map fill.
		const regionInner = [];
		this._emitMapFilter(f, 'regions', indent + 1).forEach(l => regionInner.push(l));
		this._emitRegionFill(f, indent + 1).forEach(l => regionInner.push(l));
		if(regionInner.length) {
			lines.push(this._pad(indent) + 'regions:');
			regionInner.forEach(l => lines.push(l));
		}

		// Points — resource OR manual data, plus an optional filter (all under one `points:` block).
		const pts = [];
		if(f.points_uri) {
			pts.push(this._pad(indent + 1) + 'resource:');
			pts.push(this._pad(indent + 2) + 'uri: ' + f.points_uri);
		} else if(f.points_data && String(f.points_data).trim() !== '') {
			pts.push(this._pad(indent + 1) + 'data:');
			this._emitRawBlock(f.points_data, pts, indent + 2);
		}
		this._emitMapFilter(f, 'points', indent + 1).forEach(l => pts.push(l));
		if(pts.length) {
			lines.push(this._pad(indent) + 'points:');
			pts.forEach(l => lines.push(l));
		}
	}

	// A `filter: {property, is|not@list}` block for the regions/points section, or [] if incomplete.
	_emitMapFilter(f, prefix, indent) {
		const prop = f[prefix + '_filter_property'];
		const vals = f[prefix + '_filter_values'];
		if(!prop || !Array.isArray(vals) || !vals.length) return [];
		const mode = (f[prefix + '_filter_mode'] === 'not') ? 'not' : 'is';
		const out = [];
		out.push(this._pad(indent) + 'filter:');
		out.push(this._pad(indent + 1) + 'property: ' + prop);
		out.push(this._pad(indent + 1) + mode + '@list:');
		vals.forEach(v => out.push(this._pad(indent + 2) + v));
		return out;
	}

	// A `fill: color_map: {property, colors:{value:color}}` block, or [] if incomplete.
	_emitRegionFill(f, indent) {
		const prop = f.regions_fill_property;
		const rows = Array.isArray(f.regions_fill_map)
			? f.regions_fill_map.filter(r => r && String(r.value).trim() !== '') : [];
		if(!prop || !rows.length) return [];
		const out = [];
		out.push(this._pad(indent) + 'fill:');
		out.push(this._pad(indent + 1) + 'color_map:');
		out.push(this._pad(indent + 2) + 'property: ' + prop);
		out.push(this._pad(indent + 2) + 'colors:');
		rows.forEach(r => out.push(this._pad(indent + 3) + r.value + ': ' + (r.color || '')));
		return out;
	}

	_numSet(v) { return v !== undefined && v !== null && String(v).trim() !== ''; }

	// Emit a STRING scalar with an explicit `@text:` annotation. A bare `key:` with no value parses as an empty
	// OBJECT in KATA, not an empty string — so any field expected to be a string is always typecast, even empty.
	_emitStr(key, v, lines, indent) {
		v = (v == null) ? '' : String(v);
		if(v.indexOf('\n') >= 0) {
			lines.push(this._pad(indent) + key + '@text:');
			this._emitRawBlock(v, lines, indent + 1);
		} else {
			lines.push(this._pad(indent) + key + '@text:' + (v !== '' ? ' ' + v : ''));
		}
	}

	// Prefer the readable connected-account uri over the numeric id in the emitted cerb-uri (model stays id-based
	// internally so the auth RecordChooser can always re-seed by id). Falls back to the id when there's no uri.
	_authKataUri(v) {
		const m = String(v || '').match(/^cerb:connected_account:(.+)$/);
		if(!m) return v;
		const id = m[1];
		return 'cerb:connected_account:' + (this.accountUris[id] || id);
	}

	// agentPrompt → placeholder/session_id + the `models:` catalog: each key REFERENCES an agent_model record
	// by name; only non-empty overrides are written under it (a bare `<name>:` inherits from the record).
	_emitAgentPrompt(elm, lines, indent) {
		const f = elm.fields || {};
		if(f.placeholder) this._emitStr('placeholder', f.placeholder, lines, indent);
		if(f.session_id) lines.push(this._pad(indent) + 'session_id: ' + f.session_id);

		const models = f.modelList || [];
		if(models.length) {
			lines.push(this._pad(indent) + 'models:');
			models.forEach(m => {
				const name = String(m.name || '').trim();
				if(!name) return;
				lines.push(this._pad(indent + 1) + name + ':');
				if(m.context_window != null && m.context_window !== '')
					lines.push(this._pad(indent + 2) + 'context_window@int: ' + (parseInt(m.context_window, 10) || 0));
				if(m.effort_choices)
					lines.push(this._pad(indent + 2) + 'effort_choices: ' + m.effort_choices);
				if(m.disabled) {
					const expr = String(m.disabled).trim();
					lines.push(this._pad(indent + 2) + 'disabled@bool: ' + (expr.indexOf('{{') === 0 ? expr : ('{{' + expr + '}}')));
				}
			});
		}
	}

	// Only the active submit mode's params — continue defaults ON at runtime, so hiding it needs an explicit `no`.
	// `hidden` is orthogonal to the mode: emit it alongside the (still-configured) button set so a hidden submit
	// keeps its buttons for when it's later shown. Automatic submit has no buttons, so it stays a clean early-out.
	_emitSubmit(elm, lines, indent) {
		const f = elm.fields || {};

		if(f.is_automatic) { lines.push(this._pad(indent) + 'is_automatic@bool: yes'); return; }

		if(f.hidden) lines.push(this._pad(indent) + 'hidden@bool: yes');

		if(Array.isArray(f.buttonList) && f.buttonList.length) {
			lines.push(this._pad(indent) + 'buttons:');
			f.buttonList.forEach((b, i) => {
				const type = (b.type === 'reset') ? 'reset' : 'continue';
				lines.push(this._pad(indent + 1) + type + '/b' + i + ':');
				if(b.label) lines.push(this._pad(indent + 2) + 'label: ' + b.label);
				if(b.icon) lines.push(this._pad(indent + 2) + 'icon: ' + b.icon);
				if(b.icon_at) lines.push(this._pad(indent + 2) + 'icon_at: ' + b.icon_at);
				if(b.value) lines.push(this._pad(indent + 2) + 'value: ' + b.value);
			});
			return;
		}

		lines.push(this._pad(indent) + 'continue@bool: ' + (f.continue !== false ? 'yes' : 'no'));
		lines.push(this._pad(indent) + 'reset@bool: ' + (f.reset ? 'yes' : 'no'));
	}

	// Collapse/expand the Generated KATA pane. With no arg it flips the current state; state is remembered.
	_toggleKata(collapsed) {
		if(collapsed === undefined) collapsed = !this._kataCollapsed;
		this._kataCollapsed = collapsed;
		if(this.kataEl_wrap) this.kataEl_wrap.classList.toggle('cerb-fb--kata--collapsed', collapsed);
		if(this._kataChevron) {
			// Collapsed → up (click to expand upward); expanded → down (click to collapse the pane down).
			this._kataChevron.classList.toggle('cerb-icon-chevron-up', collapsed);
			this._kataChevron.classList.toggle('cerb-icon-chevron-down', !collapsed);
		}
		// Re-render on expand: the editor may have been built while the pane was hidden (zero-size), so nudge it.
		if(!collapsed && this._kataEditor) this._kataEditor.setValue(this._kataEditor.getValue());
		try { window.localStorage.setItem('cerb-form-builder-kata', collapsed ? '1' : '0'); } catch(e) {}
	}

	_copyKata() {
		const text = this._getKataText();
		const done = () => { if(window.Devblocks && Devblocks.createAlert) Devblocks.createAlert('Copied to clipboard!'); };
		// Copy via a throwaway textarea so we never steal focus from / mangle the read-only KataEditor.
		const fallback = () => {
			const ta = document.createElement('textarea');
			ta.value = text;
			ta.style.cssText = 'position:fixed;opacity:0;';
			document.body.appendChild(ta);
			ta.select();
			try { document.execCommand('copy'); } catch(e) {}
			document.body.removeChild(ta);
			done();
		};
		if(navigator.clipboard && navigator.clipboard.writeText)
			navigator.clipboard.writeText(text).then(done, fallback);
		else
			fallback();
	}

	destroy() {
		if(this._refreshTimer) clearTimeout(this._refreshTimer);
		if(this._kataEditor && this._kataEditor.destroy) this._kataEditor.destroy();
		if(this._sortable) this._sortable.destroy();
		if(this.dropzone) this.dropzone.destroy();
		if(this.splitpane) this.splitpane.destroy();
		if(this.sidebar && this.sidebar.destroy) this.sidebar.destroy();
		CerbUI.FormBuilder._instances.delete(this.el);
	}
};
