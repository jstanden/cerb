/*
 * CerbUI.TagInput — a freeform tag field (the *freeform* sibling of CerbUI.ValuePicker, which picks from
 * a fixed local set). Use it wherever a "List" field would otherwise render a tall stack of repeating
 * text inputs: you type arbitrary text, press Enter, and it becomes a removable chip. There are NO
 * presets and NO dropdown — every value is whatever the user typed.
 *
 * It enhances a container and OWNS it. Selections post as hidden inputs with the field's name
 * (name[] per tag, in entry order), so it drops into the same save handlers as the original text inputs
 * (e.g. a LIST custom field read as field_{id}[]).
 *
 * Markup it enhances — an empty container, or one seeded with the existing values as text inputs (the
 * legacy repeating-input shape, which also doubles as a no-JS fallback):
 *   <div class="cerb-ui-tag-input" data-name="field_5">
 *     <input type="text" name="field_5[]" value="alpha">
 *     <input type="text" name="field_5[]" value="beta">
 *   </div>
 *
 * Usage:
 *   new CerbUI.TagInput(el, {
 *     name: 'field_5',                 // optional; else data-name / the child inputs' name (sans [])
 *     placeholder: 'Add a tag…',
 *     value: ['alpha','beta'],         // optional; else read from the seed text inputs
 *     separators: ['comma'],           // optional; also commit on ',' (default: Enter only)
 *     allowDuplicates: false,          // default false (skip an exact-duplicate tag)
 *     editable: true,                  // default true; double-click a chip to rename it (data-editable="false" opts out)
 *     onChange: (values) => {},        // fired per add/remove/rename
 *   });
 *
 * Behavior: Enter (and comma when enabled) commits the typed text; pasting splits on newline/tab (and
 * comma when enabled) to add several at once; Backspace on an empty input removes the last tag; blur
 * commits a pending word so it isn't lost. Double-click a chip to edit it in place (Enter/blur commit,
 * Esc cancels; emptying it removes the chip, renaming onto an existing tag collapses them). Order is
 * entry order (a list is ordered).
 *
 * API: ti.getValue(); ti.setValue([…]); ti.addTag(text); ti.clear(); ti.destroy(); CerbUI.TagInput.from(el).
 * Reuses the record-chooser CSS (field + tile + input) for visual parity. No backend.
 */
CerbUI.TagInput = class {
	static _instances = new WeakMap();
	static from(el) { return CerbUI.TagInput._instances.get(el); }

	constructor(el, opts = {}) {
		this.el = (typeof el === 'string') ? document.querySelector(el) : el;
		if(!this.el) return;

		const ds = this.el.dataset || {};

		// Read seed values + name from the source markup BEFORE we own the element
		const seedValues = this._readSeedValues();
		const seedName = this._readName();

		const seps = (opts.separators != null) ? opts.separators
			: (ds.separators ? ds.separators.split(',').map((s) => s.trim()).filter(Boolean) : []);

		this.opts = Object.assign({
			name:            ds.name || seedName,
			placeholder:     ds.placeholder || 'Add a tag…',
			value:           null,
			allowDuplicates: (ds.allowDuplicates === '1' || ds.allowDuplicates === 'true'),
			editable:        !(ds.editable === '0' || ds.editable === 'false'), // double-click a chip to rename it
			onChange:        null,
		}, opts);
		this.opts.separators = seps;
		this._commaSep = seps.indexOf('comma') !== -1;

		// Initial values: explicit option, else the seed text inputs. Entry order is preserved.
		const initial = (this.opts.value != null)
			? (Array.isArray(this.opts.value) ? this.opts.value : [this.opts.value]).map(String)
			: seedValues;
		this.values = this.opts.allowDuplicates ? initial.slice() : initial.filter((v, i) => initial.indexOf(v) === i);

		this.el.replaceChildren(); // own the element; drop the seed markup
		this.el.classList.add('cerb-ui-tag-input', 'cerb-ui-record-chooser', 'cerb-ui-record-chooser--multiple');

		// ── Field (mirrors RecordChooser's markup so it inherits the field/tile/input styling) ──
		this.input = document.createElement('input');
		this.input.type = 'text';
		this.input.className = 'cerb-ui-record-chooser--input';
		this.input.setAttribute('autocomplete', 'off');
		this.input.setAttribute('placeholder', this.opts.placeholder);

		this.tilesEl = document.createElement('span');
		this.tilesEl.className = 'cerb-ui-record-chooser--tiles';

		this.fieldEl = document.createElement('span');
		this.fieldEl.className = 'cerb-ui-record-chooser--field';
		this.fieldEl.appendChild(this.tilesEl);
		this.fieldEl.appendChild(this.input);

		this.el.appendChild(this.fieldEl);

		this.hiddenWrap = document.createElement('span');
		this.hiddenWrap.style.display = 'none';
		this.el.appendChild(this.hiddenWrap);

		// ── Events ──
		this._onKey = (e) => this._onKeydown(e);
		this._onPaste = (e) => this._onPasteEvent(e);
		this._onBlur = () => this._commitPending();
		this._onElClick = (e) => {
			if(e.target.closest('.cerb-ui-record-chooser--tile')) return;
			if(e.target === this.input) return;
			this.input.focus();
		};

		this.input.addEventListener('keydown', this._onKey);
		this.input.addEventListener('paste', this._onPaste);
		this.input.addEventListener('blur', this._onBlur);
		this.el.addEventListener('click', this._onElClick);

		this._syncState();
		CerbUI.TagInput._instances.set(this.el, this);
	}

	// ── Read seed values + name from the source markup (text inputs or [data-value] nodes) ──
	_readSeedValues() {
		const out = [];
		this.el.querySelectorAll('input[type="text"], input:not([type])').forEach((node) => {
			const v = (node.value || '').trim();
			if(v !== '') out.push(v);
		});
		this.el.querySelectorAll('[data-value]').forEach((node) => {
			const v = (node.getAttribute('data-value') || '').trim();
			if(v !== '') out.push(v);
		});
		return out;
	}

	_readName() {
		const node = this.el.querySelector('input[name]');
		if(!node || !node.name) return null;
		return node.name.replace(/\[\]$/, '');
	}

	// ── Add / remove ──
	_addTag(text) {
		const value = (text || '').trim();
		if(value === '') return false;
		if(!this.opts.allowDuplicates && this.values.indexOf(value) !== -1) return false;
		this.values.push(value);
		this._syncState();
		if(typeof this.opts.onChange === 'function') this.opts.onChange(this.getValue());
		return true;
	}

	_removeAt(i) {
		if(i < 0 || i >= this.values.length) return;
		this.values.splice(i, 1);
		this._syncState();
		requestAnimationFrame(() => this.input.focus());
		if(typeof this.opts.onChange === 'function') this.opts.onChange(this.getValue());
	}

	// Commit whatever's typed but not yet entered (Enter/blur) so it isn't silently lost
	_commitPending() {
		if(this.input.value.trim() === '') return;
		if(this._addTag(this.input.value)) this.input.value = '';
	}

	// Split a pasted/typed blob on the active delimiters (newline/tab always; comma when enabled)
	_split(text) {
		const re = this._commaSep ? /[\n\r\t,]+/ : /[\n\r\t]+/;
		return text.split(re).map((s) => s.trim()).filter(Boolean);
	}

	_onKeydown(e) {
		if(e.key === 'Enter') {
			e.preventDefault();
			this._commitPending();
		} else if(this._commaSep && e.key === ',') {
			e.preventDefault();
			this._commitPending();
		} else if(e.key === 'Backspace' && this.input.value === '' && this.values.length) {
			e.preventDefault();
			this._removeAt(this.values.length - 1);
		}
	}

	_onPasteEvent(e) {
		const text = (e.clipboardData || window.clipboardData).getData('text');
		if(!text) return;
		const parts = this._split(text);
		// Only intercept when the paste actually spans multiple tags; a single token pastes normally so it
		// can still be edited before committing.
		if(parts.length <= 1) return;
		e.preventDefault();
		parts.forEach((p) => this._addTag(p));
		this.input.value = '';
	}

	// ── Tile (label + × remove); plain text, no avatar ──
	_buildTile(value, i) {
		const tile = document.createElement('span');
		tile.className = 'cerb-ui-record-chooser--tile';

		const name = document.createElement('span');
		name.className = 'cerb-ui-record-chooser--label';
		name.textContent = value;
		tile.appendChild(name);

		const clear = document.createElement('button');
		clear.type = 'button';
		clear.className = 'cerb-ui-record-chooser--clear';
		clear.setAttribute('aria-label', 'Remove');
		clear.innerHTML = '<span class="cerb-icons cerb-icon-circle-remove" aria-hidden="true"></span>';
		clear.addEventListener('click', (e) => { e.stopPropagation(); this._removeAt(i); });
		tile.appendChild(clear);

		// Double-click the chip (anywhere but ×) to rename it in place
		if(this.opts.editable) {
			tile.addEventListener('dblclick', (e) => {
				if(e.target.closest('.cerb-ui-record-chooser--clear')) return;
				e.preventDefault();
				e.stopPropagation();
				this._beginEdit(tile, i);
			});
		}

		return tile;
	}

	// Swap a tile's label for a seamless inline input pre-filled with the value (all text selected).
	// Commit on Enter/blur, cancel on Esc. _syncState() rebuilds the tile either way, so the input is
	// throwaway and its listeners die with it (same lifecycle as the per-tile clear button).
	_beginEdit(tile, i) {
		if(this._editing) return;
		const oldValue = this.values[i];
		const label = tile.querySelector('.cerb-ui-record-chooser--label');
		if(label == null || oldValue == null) return;

		this._editing = true;

		const input = document.createElement('input');
		input.type = 'text';
		input.className = 'cerb-ui-record-chooser--tile-edit';
		input.setAttribute('autocomplete', 'off');
		input.value = oldValue;
		input.size = Math.max(oldValue.length, 1);

		let done = false;
		const finish = (commit) => {
			if(done) return;
			done = true;
			this._editing = false;
			if(commit) this._applyEdit(i, input.value);
			else this._syncState(); // cancel — rebuild the original tile
		};

		input.addEventListener('keydown', (e) => {
			e.stopPropagation();
			if(e.key === 'Enter') { e.preventDefault(); finish(true); }
			else if(e.key === 'Escape') { e.preventDefault(); finish(false); }
		});
		input.addEventListener('input', () => { input.size = Math.max(input.value.length, 1); });
		input.addEventListener('blur', () => finish(true));

		tile.replaceChild(input, label);
		requestAnimationFrame(() => { input.focus(); input.select(); });
	}

	// Resolve a committed inline edit. Empties remove the chip; a value that collides with another
	// existing chip (when duplicates are off) collapses onto it; otherwise rename in place.
	_applyEdit(i, text) {
		const value = (text || '').trim();
		const oldValue = this.values[i];

		if(value === oldValue) { this._syncState(); return; }
		if(value === '') { this._removeAt(i); return; }
		if(!this.opts.allowDuplicates && this.values.indexOf(value) !== -1) { this._removeAt(i); return; }

		this.values[i] = value;
		this._syncState();
		if(typeof this.opts.onChange === 'function') this.opts.onChange(this.getValue());
	}

	_syncState() {
		const filled = this.values.length > 0;

		// Tiles — entry order
		this.tilesEl.replaceChildren();
		this.values.forEach((v, i) => this.tilesEl.appendChild(this._buildTile(v, i)));
		this.el.classList.toggle('cerb-ui-record-chooser--has-tiles', filled);

		// Hidden inputs — one name[] per tag, in entry order
		this.hiddenWrap.replaceChildren();
		if(this.opts.name) {
			const name = this.opts.name + '[]';
			this.values.forEach((v) => {
				const h = document.createElement('input');
				h.type = 'hidden'; h.name = name; h.value = v;
				this.hiddenWrap.appendChild(h);
			});
		}
	}

	// ── Public API ──
	getValue() { return this.values.slice(); }
	setValue(value) {
		const arr = value == null ? [] : (Array.isArray(value) ? value : [value]).map(String);
		this.values = this.opts.allowDuplicates ? arr : arr.filter((v, i) => arr.indexOf(v) === i);
		this._syncState();
	}
	addTag(text) { if(this._addTag(text)) this.input.value = ''; }
	clear() {
		this.values = [];
		this.input.value = '';
		this._syncState();
		requestAnimationFrame(() => this.input.focus());
	}

	destroy() {
		CerbUI.TagInput._instances.delete(this.el);
		this.input.removeEventListener('keydown', this._onKey);
		this.input.removeEventListener('paste', this._onPaste);
		this.input.removeEventListener('blur', this._onBlur);
		this.el.removeEventListener('click', this._onElClick);
		this.el.classList.remove('cerb-ui-tag-input', 'cerb-ui-record-chooser', 'cerb-ui-record-chooser--multiple', 'cerb-ui-record-chooser--has-tiles');
	}
};
