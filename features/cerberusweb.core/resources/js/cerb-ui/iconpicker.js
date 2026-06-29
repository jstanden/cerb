/*
 * CerbUI.IconPicker — a zero-dependency icon picker bound to a text <input>, modeled on CerbUI.ColorPicker.
 *
 * Usage:
 *   new CerbUI.IconPicker(inputEl, { value: 'rocket', emptyIcon: 'picture', onChange: (name) => { ... } });
 *
 * Enhances a text <input>: wraps it in a small "well" (a button showing the chosen cerb-icon glyph) next to
 * the (hidden) input that holds the icon name, and builds a floating panel appended to <body> — a filter box
 * over a scrollable grid of every cerb-icon + its name. The <input> keeps holding the value so forms submit
 * it; every change fires input + change on the input plus a `cerb-ui-iconpicker:change` CustomEvent
 * (detail = { name }). The icon list is fetched once from `c=ui&a=iconsJson` and cached across instances.
 *
 * API: ip.getValue(); ip.setValue(name); ip.open(); ip.close(); ip.destroy(); static CerbUI.IconPicker.from(el).
 * Requires genericAjaxGet + the cerb-icons CSS.
 */
CerbUI.IconPicker = class {
	static _uid = 0;
	static _instances = new WeakMap();
	static from(el) { return CerbUI.IconPicker._instances.get(el); }

	static _icons = null;        // cached name list
	static _iconsPromise = null; // in-flight fetch (shared by all instances)

	// Fetch (once) the full icon-name list from the /ui endpoint; resolves to string[].
	static loadIcons() {
		if(CerbUI.IconPicker._icons) return Promise.resolve(CerbUI.IconPicker._icons);
		if(CerbUI.IconPicker._iconsPromise) return CerbUI.IconPicker._iconsPromise;
		CerbUI.IconPicker._iconsPromise = new Promise((resolve) => {
			if(typeof genericAjaxGet !== 'function') { resolve([]); return; }
			genericAjaxGet('', 'c=ui&a=iconsJson', (json) => {
				CerbUI.IconPicker._icons = Array.isArray(json) ? json : [];
				resolve(CerbUI.IconPicker._icons);
			}, { error: () => resolve([]) });
		});
		return CerbUI.IconPicker._iconsPromise;
	}

	constructor(inputEl, opts = {}) {
		this.inputEl = (typeof inputEl === 'string') ? document.querySelector(inputEl) : inputEl;
		if(!this.inputEl) return;
		this.uid = ++CerbUI.IconPicker._uid;

		this.opts = Object.assign({
			emptyIcon: 'picture', // placeholder glyph when there's no value
			allowClear: true,
			onChange:  null,
			onOpen:    null,
			onClose:   null,
		}, opts);

		this.value = (opts.value != null) ? opts.value : (this.inputEl.value || '');

		this.docClick = null;
		this.docKeydown = null;
		this._active = null; // currently highlighted grid cell (keyboard navigation)
		this._onButtonClick = this._onButtonClick.bind(this);
		this._onKeydown = this._onKeydown.bind(this);

		this._buildWell();
		this._buildPanel();
		this._render();

		this.inputEl.setAttribute('autocomplete', 'off');
		CerbUI.IconPicker._instances.set(this.inputEl, this);
		CerbUI.IconPicker._instances.set(this.well, this);
	}

	// ── DOM ─────────────────────────────────────────────────────────────────────
	_buildWell() {
		this.well = document.createElement('span');
		this.well.className = 'cerb-ui-iconpicker';

		this.button = document.createElement('button');
		this.button.type = 'button';
		this.button.className = 'cerb-ui-iconpicker--button';
		this.button.setAttribute('aria-haspopup', 'dialog');
		this.button.setAttribute('aria-expanded', 'false');
		this.button.setAttribute('aria-label', 'Choose an icon');
		this.glyph = document.createElement('span');
		this.glyph.className = 'cerb-icons';
		this.glyph.setAttribute('aria-hidden', 'true');
		this.button.appendChild(this.glyph);
		this.button.addEventListener('click', this._onButtonClick);

		this.inputEl.parentNode.insertBefore(this.well, this.inputEl);
		this.inputEl.classList.add('cerb-ui-iconpicker--input');
		this.well.appendChild(this.button);
		this.well.appendChild(this.inputEl);
	}

	_buildPanel() {
		this.el = document.createElement('div');
		this.el.className = 'cerb-ui-iconpicker--panel';
		this.el.setAttribute('role', 'dialog');
		this.el.setAttribute('aria-label', 'Icon picker');
		this.el.setAttribute('hidden', '');
		// One handler drives keyboard nav for the whole panel: keys bubble here whether focus is
		// in the filter box (the common case) or on a cell.
		this.el.addEventListener('keydown', this._onKeydown);

		this.gridId = 'cerb-ui-iconpicker-grid-' + this.uid;

		this.search = document.createElement('input');
		this.search.type = 'search';
		this.search.className = 'cerb-ui-iconpicker--search';
		this.search.placeholder = 'Filter icons…';
		this.search.setAttribute('autocomplete', 'off');
		// Combobox over the grid: Tab/ArrowDown moves real focus into the grid (roving tabindex).
		this.search.setAttribute('role', 'combobox');
		this.search.setAttribute('aria-expanded', 'true');
		this.search.setAttribute('aria-autocomplete', 'list');
		this.search.setAttribute('aria-controls', this.gridId);
		this.search.addEventListener('input', () => this._filter());
		this.el.appendChild(this.search);

		this.grid = document.createElement('div');
		this.grid.className = 'cerb-ui-iconpicker--grid';
		this.grid.id = this.gridId;
		this.grid.setAttribute('role', 'listbox');
		this.grid.setAttribute('aria-label', 'Icons');
		this.el.appendChild(this.grid);

		if(this.opts.allowClear) {
			this.clearBtn = document.createElement('button');
			this.clearBtn.type = 'button';
			this.clearBtn.className = 'cerb-ui-iconpicker--clear cerb-ui-button cerb-ui-button--subtle';
			this.clearBtn.textContent = 'Clear';
			this.clearBtn.addEventListener('click', () => { this.setValue(''); this.close(true); });
			this.el.appendChild(this.clearBtn);
		}

		document.body.appendChild(this.el);
		this._gridBuilt = false;
	}

	_buildGrid() {
		this._gridBuilt = true;
		CerbUI.IconPicker.loadIcons().then((names) => {
			const frag = document.createDocumentFragment();
			names.forEach((name, i) => {
				const cell = document.createElement('button');
				cell.type = 'button';
				cell.className = 'cerb-ui-iconpicker--cell';
				cell.title = name;
				cell.id = this.gridId + '-' + i;
				cell.tabIndex = -1; // reached via arrow keys, not Tab
				cell.setAttribute('role', 'option');
				cell.setAttribute('aria-selected', 'false');
				cell.setAttribute('data-name', name);
				// name is a server-provided cerb-icons token ([a-z0-9-]) — safe to interpolate
				cell.innerHTML = '<span class="cerb-icons cerb-icon-' + name + '" aria-hidden="true"></span>'
					+ '<span class="cerb-ui-iconpicker--cell-name">' + name + '</span>';
				// Mouse select mirrors keyboard select: commit, close, and return focus to the well
				// button so the user's Tab journey continues from the control.
				cell.addEventListener('click', () => { this.setValue(name); this.close(true); });
				frag.appendChild(cell);
			});
			this.grid.replaceChildren(frag);
			this._filter();
			// Pre-highlight the current value so Enter re-selects it and arrows start from there.
			if(this.value) {
				const current = this.grid.querySelector('[data-name="' + (window.CSS && CSS.escape ? CSS.escape(this.value) : this.value) + '"]');
				if(current && !current.hidden) this._setActive(current);
			}
		});
	}

	_filter() {
		const q = (this.search.value || '').trim().toLowerCase();
		let firstVisible = null;
		this.grid.querySelectorAll('.cerb-ui-iconpicker--cell').forEach((c) => {
			c.hidden = !!q && c.getAttribute('data-name').indexOf(q) === -1;
			if(!c.hidden && !firstVisible) firstVisible = c;
		});
		// Keep a valid active cell: hold the current one if still visible, else fall back to the first.
		if(!this._active || this._active.hidden) this._setActive(firstVisible);
	}

	// ── Keyboard navigation ─────────────────────────────────────────────────────
	_visibleCells() {
		return Array.prototype.filter.call(
			this.grid.querySelectorAll('.cerb-ui-iconpicker--cell'),
			(c) => !c.hidden
		);
	}

	// Number of cells in the first rendered row (the grid wraps, so columns are layout-derived).
	_columnCount(cells) {
		if(cells.length < 2) return 1;
		const top0 = cells[0].offsetTop;
		let n = 1;
		while(n < cells.length && cells[n].offsetTop === top0) n++;
		return n;
	}

	// Roving tabindex: the active cell is the grid's single tab stop (tabindex 0); all others are -1.
	// focus=true moves real DOM focus to it (keyboard navigation inside the grid).
	_setActive(cell, focus) {
		if(this._active && this._active !== cell) {
			this._active.classList.remove('cerb-ui-iconpicker--cell--active');
			this._active.setAttribute('aria-selected', 'false');
			this._active.tabIndex = -1;
		}
		this._active = cell || null;
		if(this._active) {
			this._active.classList.add('cerb-ui-iconpicker--cell--active');
			this._active.setAttribute('aria-selected', 'true');
			this._active.tabIndex = 0;
			this.search.setAttribute('aria-activedescendant', this._active.id);
			this._active.scrollIntoView({ block: 'nearest' });
			if(focus) this._active.focus();
		} else {
			this.search.removeAttribute('aria-activedescendant');
		}
	}

	// Move the active cell by (dx) columns and (dy) rows, clamped to the visible set.
	_moveActive(dx, dy, focus) {
		const cells = this._visibleCells();
		if(!cells.length) return;
		const cols = this._columnCount(cells);
		let idx = this._active ? cells.indexOf(this._active) : -1;
		if(idx < 0) idx = (dx < 0 || dy < 0) ? cells.length - 1 : 0;
		else idx = Math.max(0, Math.min(cells.length - 1, idx + dx + dy * cols));
		this._setActive(cells[idx], focus);
	}

	// Move focus from the filter box into the grid (lands on the active cell, or the first visible).
	_enterGrid() {
		const cells = this._visibleCells();
		if(!cells.length) return;
		this._setActive((this._active && !this._active.hidden) ? this._active : cells[0], true);
	}

	// The panel's tab cycle, in DOM order: filter box → active grid cell → Clear button.
	_tabbables() {
		return [this.search, (this._active && !this._active.hidden) ? this._active : null, this.clearBtn].filter(Boolean);
	}

	// Trap Tab within the panel so focus cycles instead of escaping to the browser chrome.
	_trapTab(e) {
		const order = this._tabbables();
		if(order.length < 2) { e.preventDefault(); return; }
		const first = order[0], last = order[order.length - 1];
		if(!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
		else if(e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
		// Otherwise let native Tab move between the (few) tabbable stops.
	}

	_onKeydown(e) {
		if(e.key === 'Tab') { this._trapTab(e); return; }

		// In the filter box: Down/Up enter the grid; Enter picks the active/first match. Left/Right/Home/End
		// stay as normal text editing so filtering isn't disrupted.
		if(e.target === this.search) {
			if(e.key === 'ArrowDown') { e.preventDefault(); this._enterGrid(); }
			else if(e.key === 'ArrowUp') { e.preventDefault(); const c = this._visibleCells(); if(c.length) this._setActive(c[c.length - 1], true); }
			else if(e.key === 'Enter') {
				const cells = this._visibleCells();
				const pick = (this._active && !this._active.hidden) ? this._active : cells[0];
				if(pick) { e.preventDefault(); this.setValue(pick.getAttribute('data-name')); this.close(true); }
			}
			return;
		}

		// On a grid cell: arrows navigate (with focus), Enter/Space selects, printable keys jump back to
		// the filter box and keep typing.
		if(e.target.classList && e.target.classList.contains('cerb-ui-iconpicker--cell')) {
			switch(e.key) {
				case 'ArrowRight': e.preventDefault(); this._moveActive(+1, 0, true); break;
				case 'ArrowLeft':  e.preventDefault(); this._moveActive(-1, 0, true); break;
				case 'ArrowDown':  e.preventDefault(); this._moveActive(0, +1, true); break;
				case 'ArrowUp': {
					e.preventDefault();
					const cells = this._visibleCells(), cols = this._columnCount(cells), idx = cells.indexOf(this._active);
					if(idx >= 0 && idx < cols) this.search.focus(); // top row → back to the filter box
					else this._moveActive(0, -1, true);
					break;
				}
				case 'Home': e.preventDefault(); { const c = this._visibleCells(); if(c.length) this._setActive(c[0], true); } break;
				case 'End':  e.preventDefault(); { const c = this._visibleCells(); if(c.length) this._setActive(c[c.length - 1], true); } break;
				case 'Enter':
				case ' ':    e.preventDefault(); this.setValue(this._active.getAttribute('data-name')); this.close(true); break;
				case 'Backspace': e.preventDefault(); this.search.value = this.search.value.slice(0, -1); this.search.focus(); this._filter(); break;
				default:
					if(e.key.length === 1 && !e.ctrlKey && !e.metaKey && !e.altKey) {
						e.preventDefault(); this.search.value += e.key; this.search.focus(); this._filter();
					}
			}
		}
	}

	_render() {
		const name = this.value;
		this.glyph.className = 'cerb-icons cerb-icon-' + (name || this.opts.emptyIcon);
		this.well.classList.toggle('cerb-ui-iconpicker--empty', !name);
	}

	// ── Value ───────────────────────────────────────────────────────────────────
	getValue() { return this.value; }

	setValue(name) {
		this.value = name || '';
		this.inputEl.value = this.value;
		this._render();
		this.inputEl.dispatchEvent(new Event('input', { bubbles: true }));
		this.inputEl.dispatchEvent(new Event('change', { bubbles: true }));
		if(typeof this.opts.onChange === 'function') this.opts.onChange(this.value);
		this.inputEl.dispatchEvent(new CustomEvent('cerb-ui-iconpicker:change', { detail: { name: this.value }, bubbles: true }));
		return this;
	}

	// ── Open / close ──────────────────────────────────────────────────────────────
	_onButtonClick(e) { e.preventDefault(); e.stopPropagation(); this.isOpen() ? this.close() : this.open(); }

	isOpen() { return !this.el.hasAttribute('hidden'); }

	open() {
		if(this.isOpen()) return;
		if(!this._gridBuilt) this._buildGrid();
		this.el.removeAttribute('hidden');
		this.button.setAttribute('aria-expanded', 'true');
		this.position();
		this.attachDocListeners();
		requestAnimationFrame(() => this.search.focus());
		if(typeof this.opts.onOpen === 'function') this.opts.onOpen();
	}

	// returnFocus=true puts focus back on the well button (the control's tab stop) so the user can
	// keep tabbing — used after an explicit select/clear or Escape, but NOT on outside-click dismissal.
	close(returnFocus) {
		if(!this.isOpen()) return;
		this.el.setAttribute('hidden', '');
		this.button.setAttribute('aria-expanded', 'false');
		if(this.docClick) { document.removeEventListener('click', this.docClick); this.docClick = null; }
		if(this.docKeydown) { document.removeEventListener('keydown', this.docKeydown); this.docKeydown = null; }
		if(returnFocus) this.button.focus();
		if(typeof this.opts.onClose === 'function') this.opts.onClose();
	}

	position() {
		const rect = this.well.getBoundingClientRect();
		const ph = this.el.offsetHeight || 320;
		const pw = this.el.offsetWidth || 300;
		const topViewport = (window.innerHeight - rect.bottom >= ph + 8 || rect.top < ph + 8)
			? rect.bottom + 4
			: rect.top - ph - 4;
		const leftViewport = Math.min(rect.left, document.documentElement.clientWidth - pw - 8);
		this.el.style.top = `${topViewport + window.scrollY}px`;
		this.el.style.left = `${Math.max(8, leftViewport) + window.scrollX}px`;
	}

	attachDocListeners() {
		this.docClick = (e) => {
			const t = e.target;
			if(!this.el.contains(t) && !this.well.contains(t)) this.close();
		};
		this.docKeydown = (e) => { if(e.key === 'Escape') { e.preventDefault(); this.close(true); } };
		requestAnimationFrame(() => {
			document.addEventListener('click', this.docClick);
			document.addEventListener('keydown', this.docKeydown);
		});
	}

	// ── Teardown ──────────────────────────────────────────────────────────────────
	destroy() {
		CerbUI.IconPicker._instances.delete(this.inputEl);
		CerbUI.IconPicker._instances.delete(this.well);
		this.close();
		this.button.removeEventListener('click', this._onButtonClick);
		this.inputEl.classList.remove('cerb-ui-iconpicker--input');
		if(this.well.parentNode) this.well.parentNode.insertBefore(this.inputEl, this.well);
		this.well.remove();
		this.el.remove();
	}
};
