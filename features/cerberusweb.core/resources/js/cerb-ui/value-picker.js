/*
 * CerbUI.ValuePicker — a RecordChooser-style field for a SMALL, LOCAL option set (a *Picker*, not a
 * server *Chooser*). Use it wherever a "Multiple Checkbox" field would otherwise render a tall, page-
 * scrolling list of checkboxes: it shows the field with the form-input background, the selections as
 * tags (in the options' defined order), and a focus-opened dropdown to pick from.
 *
 * It enhances a list of checkbox options and OWNS the element. Selections are posted as hidden inputs
 * with the checkboxes' name (multiple → name[] per value; single → one name), so it drops into the same
 * save handlers as the original checkboxes.
 *
 * The dropdown lists EVERY option (filtered by the typed text); the already-picked rows are dimmed +
 * checked (the toggle-and-dim idea borrowed from customize_view) and clicking a row toggles it — unlike
 * a RecordChooser, which hides what's chosen. Multiple stays open to toggle several; single closes.
 *
 * Markup it enhances (the multi-checkbox shape — option order is the display order):
 *   <div class="cerb-ui-value-picker">
 *     <label><input type="checkbox" name="field_5[]" value="a" checked> Apples</label>
 *     <label data-icon="tag" data-color="green"><input type="checkbox" name="field_5[]" value="b"> Bananas</label>
 *     …
 *   </div>
 *
 * Usage:
 *   new CerbUI.ValuePicker(el, {
 *     multiple: true,                 // default true; false = single (dropdown closes on pick)
 *     name: 'field_5',                // optional; else read from the checkboxes' name (sans []) / data-name
 *     searchPlaceholder: 'Filter…',
 *     value: ['a','b'],               // optional; else the `checked` source checkboxes seed the selection
 *     onSelect: (value, picked) => {},// fired per toggle
 *   });
 *
 * API: vp.getValue(); vp.setValue([…]); vp.clear(); vp.destroy(); CerbUI.ValuePicker.from(el).
 * Reuses the chooser CSS (cerb-ui-record-chooser field + cerb-ui-chooser--panel dropdown) for parity.
 * Requires CerbUI.Avatar (monogram for image/icon-less tiles). No backend.
 */
CerbUI.ValuePicker = class {
	static _instances = new WeakMap();
	static from(el) { return CerbUI.ValuePicker._instances.get(el); }

	constructor(el, opts = {}) {
		this.el = (typeof el === 'string') ? document.querySelector(el) : el;
		if(!this.el) return;

		const ds = this.el.dataset || {};

		// Read the option set from the source checkboxes (DOM order = display order)
		this.options = this._readOptions();

		this.opts = Object.assign({
			multiple:          !(ds.single === '1' || ds.single === 'true'),
			name:              ds.name || this._readName(),
			searchPlaceholder: 'Filter…',
			value:             null,
			onSelect:          null,
		}, opts);

		// Initial selection: explicit value, else the checked source options
		const initial = (this.opts.value != null)
			? (Array.isArray(this.opts.value) ? this.opts.value : [this.opts.value]).map(String)
			: this.options.filter((o) => o.checked).map((o) => o.value);
		this.values = new Set(this.opts.multiple ? initial : initial.slice(0, 1));

		this.el.replaceChildren(); // own the element; drop the source checkbox markup
		this.el.classList.add('cerb-ui-value-picker', 'cerb-ui-record-chooser');
		if(this.opts.multiple) this.el.classList.add('cerb-ui-record-chooser--multiple');

		// ── Field (mirrors RecordChooser's markup so it inherits the field/tile/input styling) ──
		this.input = document.createElement('input');
		this.input.type = 'text';
		this.input.className = 'cerb-ui-record-chooser--input';
		this.input.setAttribute('autocomplete', 'off');
		this.input.setAttribute('placeholder', this.opts.searchPlaceholder);

		this.tilesEl = document.createElement('span');
		this.tilesEl.className = 'cerb-ui-record-chooser--tiles';

		this.fieldEl = document.createElement('span');
		this.fieldEl.className = 'cerb-ui-record-chooser--field';
		this.fieldEl.appendChild(this.tilesEl);
		this.fieldEl.appendChild(this.input);

		// Caret button — opens the dropdown (styled like the chooser's search button)
		this.caretBtn = document.createElement('button');
		this.caretBtn.type = 'button';
		this.caretBtn.className = 'cerb-ui-record-chooser--search-btn cerb-ui-value-picker--caret';
		this.caretBtn.setAttribute('aria-label', 'Choose');
		this.caretBtn.innerHTML = '<span class="cerb-icons cerb-icon-chevron-down" aria-hidden="true"></span>';

		this.el.appendChild(this.fieldEl);
		this.el.appendChild(this.caretBtn);

		this.hiddenWrap = document.createElement('span');
		this.hiddenWrap.style.display = 'none';
		this.el.appendChild(this.hiddenWrap);

		this._buildPanel();

		// ── Events ──
		this._onFocus = () => this._open();
		this._onCaretClick = (e) => { e.stopPropagation(); this._isOpen() ? this._close() : this._open(); };
		this._onElClick = (e) => {
			if(e.target.closest('.cerb-ui-record-chooser--tile')) return;
			if(e.target.closest('.cerb-ui-value-picker--caret')) return;
			if(e.target === this.input) return;
			if(!this.input.hidden) this.input.focus();
		};
		this._onInput = () => this._filter();
		this._onKey = (e) => this._onKeydown(e);

		this.input.addEventListener('focus', this._onFocus);
		this.input.addEventListener('input', this._onInput);
		this.input.addEventListener('keydown', this._onKey);
		this.caretBtn.addEventListener('click', this._onCaretClick);
		this.el.addEventListener('click', this._onElClick);

		this._syncState();
		CerbUI.ValuePicker._instances.set(this.el, this);
	}

	// ── Read the option set + name from the source checkbox markup ──
	_readOptions() {
		const out = [];
		this.el.querySelectorAll('input[type="checkbox"]').forEach((cb) => {
			const lbl = cb.closest('label');
			const host = cb.dataset && Object.keys(cb.dataset).length ? cb : (lbl || cb);
			const text = (cb.dataset.label || (lbl ? lbl.textContent : '') || cb.value || '').trim();
			out.push({
				value: cb.value,
				label: text,
				icon:  cb.dataset.icon  || (lbl && lbl.dataset.icon)  || '',
				image: cb.dataset.image || (lbl && lbl.dataset.image) || '',
				color: cb.dataset.color || (lbl && lbl.dataset.color) || '',
				checked: cb.checked,
			});
		});
		return out;
	}

	_readName() {
		const cb = this.el.querySelector('input[type="checkbox"]');
		if(!cb || !cb.name) return null;
		return cb.name.replace(/\[\]$/, '');
	}

	_optByValue(value) { return this.options.find((o) => o.value === value) || null; }

	// ── A leading avatar/icon for a tile or dropdown row (or null when the option has neither) ──
	_leadingEl(o) {
		if(o.image) {
			const av = document.createElement('span');
			av.className = 'cerb-ui-chooser--avatar';
			av.style.backgroundColor = CerbUI.chooserCore.monogramColor('value:' + o.value);
			av.textContent = CerbUI.chooserCore.initials(o.label);
			const probe = new Image();
			probe.addEventListener('load', () => {
				av.style.backgroundImage = 'url("' + probe.src + '")';
				av.style.backgroundColor = 'transparent';
				av.textContent = '';
			});
			probe.src = o.image;
			return av;
		}
		if(o.icon) {
			const g = document.createElement('span');
			g.className = 'cerb-icons cerb-icon-' + o.icon + ' cerb-ui-value-picker--icon';
			if(o.color) g.style.setProperty('--cerb-value-picker-icon-color', 'var(--cerb-color-tag-' + o.color + ')');
			return g;
		}
		return null;
	}

	// ── Dropdown panel (one row per option, built once; filtered/decorated on the fly) ──
	_buildPanel() {
		this.panel = document.createElement('div');
		this.panel.className = 'cerb-ui-chooser--panel cerb-ui-chooser--panel-inline cerb-ui-value-picker--panel';
		this.panel.setAttribute('role', 'dialog');
		this.panel.hidden = true;

		this.list = document.createElement('ul');
		this.list.className = 'cerb-ui-chooser--list';
		this.panel.appendChild(this.list);

		this.rowByValue = {};

		this.options.forEach((o) => {
			const row = document.createElement('li');
			row.className = 'cerb-ui-chooser--item cerb-ui-value-picker--option';
			row.dataset.value = o.value;

			const lead = this._leadingEl(o);
			if(lead) row.appendChild(lead);

			const text = document.createElement('span');
			text.className = 'cerb-ui-chooser--text';
			const label = document.createElement('span');
			label.className = 'cerb-ui-chooser--label';
			label.textContent = o.label;
			text.appendChild(label);
			row.appendChild(text);

			const check = document.createElement('span');
			check.className = 'cerb-ui-value-picker--check cerb-icons cerb-icon-checked';
			check.setAttribute('aria-hidden', 'true');
			row.appendChild(check);

			row.addEventListener('click', () => this._toggle(o.value));
			row.addEventListener('mousemove', () => this._setActive(row));

			this.list.appendChild(row);
			this.rowByValue[o.value] = row;
		});

		document.body.appendChild(this.panel);
		this._activeRow = null;
	}

	// ── Toggle / selection ──
	_toggle(value) {
		if(!this._optByValue(value)) return;

		if(this.opts.multiple) {
			if(this.values.has(value)) this.values.delete(value);
			else this.values.add(value);
			this._syncState();
			this.input.value = '';
			this._filter();
			requestAnimationFrame(() => this.input.focus());
		} else {
			this.values = new Set([value]);
			this._syncState();
			this._close();
		}

		if(typeof this.opts.onSelect === 'function') this.opts.onSelect(value, this.values.has(value));
	}

	_removeValue(value) {
		this.values.delete(value);
		// Drop any stale filter text — in single mode the input was hidden while filled, so removing the
		// tag would otherwise re-expose the old typed filter.
		this.input.value = '';
		this._syncState();
		this._filter();
		requestAnimationFrame(() => { if(!this.input.hidden) this.input.focus(); else if(!this.caretBtn.hidden) this.caretBtn.focus(); });
	}

	// ── Tiles (selected, in OPTION order — never click order) ──
	_buildTile(o) {
		const tile = document.createElement('span');
		tile.className = 'cerb-ui-record-chooser--tile';

		const lead = this._leadingEl(o);
		if(lead) tile.appendChild(lead);

		const name = document.createElement('span');
		name.className = 'cerb-ui-record-chooser--label';
		name.textContent = o.label;
		tile.appendChild(name);

		const clear = document.createElement('button');
		clear.type = 'button';
		clear.className = 'cerb-ui-record-chooser--clear';
		clear.setAttribute('aria-label', 'Remove');
		clear.innerHTML = '<span class="cerb-icons cerb-icon-circle-remove" aria-hidden="true"></span>';
		clear.addEventListener('click', (e) => { e.stopPropagation(); this._removeValue(o.value); });
		tile.appendChild(clear);

		return tile;
	}

	_syncState() {
		const filled = this.values.size > 0;
		const multi = this.opts.multiple;

		// Tiles — iterate options so order is the defined order, not selection order
		this.tilesEl.replaceChildren();
		this.options.forEach((o) => { if(this.values.has(o.value)) this.tilesEl.appendChild(this._buildTile(o)); });

		// Visibility (single-filled collapses to the chip only, like RecordChooser)
		this.input.hidden = !multi && filled;
		this.caretBtn.hidden = !multi && filled;
		this.el.classList.toggle('cerb-ui-record-chooser--has-tiles', filled);

		// Picked decoration on the dropdown rows
		this.options.forEach((o) => {
			const row = this.rowByValue[o.value];
			if(row) row.classList.toggle('cerb-ui-value-picker--picked', this.values.has(o.value));
		});

		// Hidden inputs (option order). Multiple → name[] per value; single → always one name ('' empty).
		this.hiddenWrap.replaceChildren();
		if(this.opts.name) {
			if(multi) {
				const name = this.opts.name + '[]';
				this.options.forEach((o) => {
					if(!this.values.has(o.value)) return;
					const h = document.createElement('input');
					h.type = 'hidden'; h.name = name; h.value = o.value;
					this.hiddenWrap.appendChild(h);
				});
			} else {
				const h = document.createElement('input');
				h.type = 'hidden'; h.name = this.opts.name;
				h.value = this.values.size ? Array.from(this.values)[0] : '';
				this.hiddenWrap.appendChild(h);
			}
		}
	}

	// ── Filter the dropdown by the typed text (winnow; never removes picked from view) ──
	_filter() {
		const q = (this.input.value || '').trim().toLowerCase();
		let anyVisible = false;
		this.options.forEach((o) => {
			const row = this.rowByValue[o.value];
			if(!row) return;
			const show = !q || o.label.toLowerCase().indexOf(q) !== -1;
			row.hidden = !show;
			if(show) anyVisible = true;
		});
		this._clearActive();
		this._emptyRow(!anyVisible);
		if(this._isOpen()) this._position();
	}

	_emptyRow(show) {
		if(show && !this._empty) {
			this._empty = document.createElement('li');
			this._empty.className = 'cerb-ui-chooser--empty';
			this._empty.textContent = 'No matches';
			this.list.appendChild(this._empty);
		} else if(!show && this._empty) {
			this._empty.remove();
			this._empty = null;
		}
	}

	// ── Keyboard nav over the VISIBLE rows ──
	_visibleRows() { return Array.from(this.list.querySelectorAll('.cerb-ui-value-picker--option:not([hidden])')); }
	_clearActive() {
		if(this._activeRow) this._activeRow.classList.remove('cerb-ui-chooser--item-active');
		this._activeRow = null;
	}
	_setActive(row) {
		if(this._activeRow === row) return;
		this._clearActive();
		this._activeRow = row;
		if(row) { row.classList.add('cerb-ui-chooser--item-active'); row.scrollIntoView({ block: 'nearest' }); }
	}
	_moveActive(delta) {
		const rows = this._visibleRows();
		if(!rows.length) return;
		let i = this._activeRow ? rows.indexOf(this._activeRow) : -1;
		i = Math.max(0, Math.min(i + delta, rows.length - 1));
		this._setActive(rows[i]);
	}
	_onKeydown(e) {
		if(e.key === 'ArrowDown')      { e.preventDefault(); if(!this._isOpen()) this._open(); else this._moveActive(1); }
		else if(e.key === 'ArrowUp')   { e.preventDefault(); this._moveActive(-1); }
		else if(e.key === 'Enter')     { e.preventDefault(); if(this._activeRow) this._toggle(this._activeRow.dataset.value); }
		else if(e.key === 'Escape')    { e.preventDefault(); this._close(); }
		else if(e.key === 'Tab')       { this._close(); } // let focus move on
	}

	// ── Open / close / position ──
	_isOpen() { return !this.panel.hidden; }
	_open() {
		if(this._isOpen()) return;
		this.panel.hidden = false;
		this._filter();
		this._position();

		this._docDown = (e) => {
			if(this.panel.contains(e.target)) return;
			if(this.el.contains(e.target)) return;
			this._close();
		};
		document.addEventListener('pointerdown', this._docDown, true);
		this._reposition = () => this._position();
		window.addEventListener('resize', this._reposition);
		window.addEventListener('scroll', this._reposition, true);
	}
	_close() {
		if(!this._isOpen()) return;
		this.panel.hidden = true;
		this._clearActive();
		if(this._docDown) { document.removeEventListener('pointerdown', this._docDown, true); this._docDown = null; }
		if(this._reposition) {
			window.removeEventListener('resize', this._reposition);
			window.removeEventListener('scroll', this._reposition, true);
			this._reposition = null;
		}
	}
	// Anchor the dropdown to the little input at the end (like RecordChooser/chooserCore), NOT the whole
	// field — so it stays a compact menu and doesn't grow tall/wide as the selected tiles wrap.
	_anchorEl() {
		if(this.input && !this.input.hidden) return this.input;
		if(this.caretBtn && !this.caretBtn.hidden) return this.caretBtn;
		return this.el;
	}
	_position() {
		const r = this._anchorEl().getBoundingClientRect();
		const h = this.panel.offsetHeight || 320;
		const w = this.panel.offsetWidth || 320;
		const below = (window.innerHeight - r.bottom >= h + 8) || (r.top < h + 8);
		const top = below ? r.bottom + 4 : r.top - h - 4;
		const left = Math.min(r.left, document.documentElement.clientWidth - w - 8);
		this.panel.style.top  = (top + window.scrollY) + 'px';
		this.panel.style.left = (Math.max(8, left) + window.scrollX) + 'px';
	}

	// ── Public API ──
	getValue() {
		const picked = this.options.filter((o) => this.values.has(o.value)).map((o) => o.value);
		return this.opts.multiple ? picked : (picked[0] || null);
	}
	setValue(value) {
		const arr = value == null ? [] : (Array.isArray(value) ? value : [value]).map(String);
		this.values = new Set(this.opts.multiple ? arr : arr.slice(0, 1));
		this._syncState();
	}
	clear() {
		this.values = new Set();
		this.input.value = '';
		this._syncState();
		this._filter();
	}

	destroy() {
		CerbUI.ValuePicker._instances.delete(this.el);
		this._close();
		this.input.removeEventListener('focus', this._onFocus);
		this.input.removeEventListener('input', this._onInput);
		this.input.removeEventListener('keydown', this._onKey);
		this.caretBtn.removeEventListener('click', this._onCaretClick);
		this.el.removeEventListener('click', this._onElClick);
		this.panel.remove();
		this.el.classList.remove('cerb-ui-value-picker', 'cerb-ui-record-chooser', 'cerb-ui-record-chooser--multiple', 'cerb-ui-record-chooser--has-tiles');
	}
};
