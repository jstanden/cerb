/*
 * CerbUI.RecordChooser — pick record(s) of a single context (large-set safe). A *Chooser*: popup-search
 * based, with inline autocomplete as a time-saver. Reuses Cerb's ACL-filtered record autocomplete + the
 * legacy chooserOpen worklist popup; avatars come from the dictionary _image_url (real pic or monogram).
 *
 *   - Single (default): no value → leading icon + autocomplete input + magnifier (opens chooserOpen).
 *     A value → a chip (click = card peek) with only × to clear; no search until cleared.
 *   - Multiple (`multiple:true`): a tag/token input. Selections (autocomplete OR popup) stack as tiles;
 *     the magnifier stays at the right and the autocomplete input sits at the end. Click empty space to
 *     type; click a tile for its card peek; × removes it.
 *
 * Usage:
 *   new CerbUI.RecordChooser(el, {
 *     context: 'worker', multiple: false,
 *     searchPlaceholder: 'Search workers…',
 *     emptyIcon: 'file',                 // empty-state glyph (cerb-icons name)
 *     name: 'worker_id',                 // hidden input (single) / name[] (multiple) so it posts
 *     value: {id,label,image_url} | [ … ],  // PREFER server-rendered [data-context-id] seed markup over
 *                                           // this JSON option — see _readMarkupValues (the <li> approach)
 *     onSelect: (item) => { ... },       // fired per add
 *   });
 *
 * API: rc.getValue(); rc.setValue(item|array|null); rc.clear(); rc.openSearch(); rc.destroy().
 * Requires chooserCore + genericAjaxGet + (for the popup/peek) genericAjaxPopup + jQuery.
 */
CerbUI.RecordChooser = class {
	static _instances = new WeakMap();
	static from(el) { return CerbUI.RecordChooser._instances.get(el); }

	constructor(el, opts = {}) {
		this.el = (typeof el === 'string') ? document.querySelector(el) : el;
		if(!this.el) return;

		this.opts = Object.assign({
			context:           '',
			multiple:          false,
			searchPlaceholder: 'Search…',
			emptyIcon:         'file',
			name:              null,
			value:             null,
			onSelect:          null,
			exclude:           null, // () => [ids] — extra ids to hide from autocomplete (e.g. an adder whose
			                         // selections live elsewhere, so this.values doesn't reflect them)
		}, opts);

		// Initial value(s): an explicit `value` option (an item object, a "context:id" string, or an array
		// of either), else enhance authored `[data-context-id]` seed markup inside the element (the old
		// <ul><li> idea — server-render the resolved label/avatar into data-* attributes).
		this.values = (this.opts.value != null)
			? this._normalizeValues(this.opts.value)
			: this._readMarkupValues();

		this.el.replaceChildren(); // the component owns the element's content; drop the read seed markup

		this.el.classList.add('cerb-ui-record-chooser');
		if(this.opts.multiple) this.el.classList.add('cerb-ui-record-chooser--multiple');

		// Leading icon (single empty state only)
		this.iconEl = document.createElement('span');
		this.iconEl.className = 'cerb-icons cerb-icon-' + this.opts.emptyIcon + ' cerb-ui-record-chooser--empty-icon';
		this.iconEl.setAttribute('aria-hidden', 'true');

		this.input = document.createElement('input');
		this.input.type = 'text';
		this.input.className = 'cerb-ui-record-chooser--input';
		this.input.setAttribute('autocomplete', 'off');
		this.input.setAttribute('placeholder', this.opts.searchPlaceholder);

		// Tiles flow before the input via display:contents
		this.tilesEl = document.createElement('span');
		this.tilesEl.className = 'cerb-ui-record-chooser--tiles';

		// Wrapping field region: icon + tiles + input (the input trails at the end, so the autocomplete
		// menu anchors there). The search button lives outside it so it stays pinned right as tiles wrap.
		this.fieldEl = document.createElement('span');
		this.fieldEl.className = 'cerb-ui-record-chooser--field';
		this.fieldEl.appendChild(this.iconEl);
		this.fieldEl.appendChild(this.tilesEl);
		this.fieldEl.appendChild(this.input);

		this.searchBtn = document.createElement('button');
		this.searchBtn.type = 'button';
		this.searchBtn.className = 'cerb-ui-record-chooser--search-btn';
		this.searchBtn.setAttribute('aria-label', 'Search');
		this.searchBtn.innerHTML = '<span class="cerb-icons cerb-icon-search" aria-hidden="true"></span>';

		this.el.appendChild(this.fieldEl);
		this.el.appendChild(this.searchBtn);

		// Hidden form fields — kept INSIDE the chooser element (display:none, no layout effect) so the
		// passthrough input travels with the component and is unambiguously inside the surrounding form.
		this.hiddenWrap = document.createElement('span');
		this.hiddenWrap.style.display = 'none';
		this.el.appendChild(this.hiddenWrap);

		this.core = CerbUI.chooserCore.create({
			inputEl:       this.input,
			anchor:        this.input,          // anchor results to the input so they stay connected as tiles grow
			closeOnSelect: !this.opts.multiple, // multi: stay open to add several
			search:        (q, page) => this._search(q, page),
			onSelect:      (item) => this._choose(item),
		});

		this._onFocus = () => { if(this.opts.multiple || !this.values.length) this.core.open(); };
		this._onSearchClick = (e) => { e.stopPropagation(); this.openSearch(); };
		// Clicking empty space (not a tile/×/search) focuses the input to start searching
		this._onElClick = (e) => {
			if(e.target.closest('.cerb-ui-record-chooser--tile')) return;
			if(e.target.closest('.cerb-ui-record-chooser--search-btn')) return;
			if(e.target === this.input) return;
			if(!this.input.hidden) this.input.focus();
		};
		this.input.addEventListener('focus', this._onFocus);
		this.searchBtn.addEventListener('click', this._onSearchClick);
		this.el.addEventListener('click', this._onElClick);

		this._syncState();
		CerbUI.RecordChooser._instances.set(this.el, this);
	}

	// ── Seams (CerbUI.ContextChooser overrides these to go multi-record-type) ──
	_context() { return this.opts.context; }      // the context to search / link within
	_valueKey(item) { return String(item.id); }   // selection identity (dedupe + already-chosen filter)
	_hiddenValue(item) { return item.id; }         // the value posted in the hidden input

	// ── Initial-value normalization (option) + markup enhancement (data-* seed) ──
	_normalizeValues(v) {
		if(v == null) return [];
		return (Array.isArray(v) ? v : [v]).map((item) => this._normalizeValue(item)).filter(Boolean);
	}
	// An item object is used as-is; a scalar is a bare id for this chooser's fixed context.
	// (ContextChooser overrides this to also parse a "context:id" string.)
	_normalizeValue(item) {
		if(item == null) return null;
		return (typeof item === 'object') ? item : { context: this.opts.context, id: item };
	}
	// Read seed values from authored `[data-context-id]` elements inside the chooser (data-context for a
	// multi-type chooser, data-label + data-image for the chip's name/avatar). Server-render these.
	_readMarkupValues() {
		const out = [];
		this.el.querySelectorAll('[data-context-id]').forEach((node) => {
			out.push({
				context:   node.getAttribute('data-context') || this.opts.context,
				id:        node.getAttribute('data-context-id'),
				label:     node.getAttribute('data-label') || '',
				image_url: node.getAttribute('data-image') || '',
			});
		});
		return out;
	}

	// ── Inline autocomplete (ACL-filtered record autocomplete) ──
	_search(query) {
		return new Promise((resolve) => {
			if(typeof genericAjaxGet !== 'function') { resolve({ results: [], more: false }); return; }
			const context = this._context();
			const args = 'c=internal&a=invoke&module=records&action=autocomplete'
				+ '&context=' + encodeURIComponent(context)
				+ '&term=' + encodeURIComponent(query || '');
			genericAjaxGet('', args, (json) => {
				const rows = Array.isArray(json) ? json : [];
				// Hide already-chosen records: this.values + any ids the caller's exclude() reports (for
				// adder-style usage where selections are held outside the chooser).
				const selected = new Set(this.values.map((v) => this._valueKey(v)));
				if(typeof this.opts.exclude === 'function')
					(this.opts.exclude() || []).forEach((id) => selected.add(this._valueKey({ context: context, id: id })));
				resolve({
					// Drop the "(no X)" sentinel AND records already chosen (don't render repeats)
					results: rows.filter((r) => r.value && r.value !== '0' && !selected.has(this._valueKey({ context: context, id: r.value }))).map((r) => ({
						context:   context,
						id:        r.value,
						label:     r.label,
						image_url: r.icon || '',
						sublabel:  r.meta ? Object.values(r.meta).filter(Boolean).join(' · ') : '',
					})),
					more: false,
				});
			}, { error: () => resolve({ results: [], more: false }) });
		});
	}

	// ── Full search popup (legacy chooserOpen worklist) ──
	openSearch() {
		if(!this.opts.multiple && this.values.length) return; // single: clear first
		this.core.close();
		if(typeof genericAjaxPopup !== 'function' || !window.jQuery) return;
		const context = this._context();
		const uid = 'recordchooser' + (window.Devblocks && Devblocks.uniqueId ? Devblocks.uniqueId() : '');
		const url = 'c=internal&a=invoke&module=records&action=chooserOpen'
			+ '&context=' + encodeURIComponent(context)
			+ '&single=' + (this.opts.multiple ? '0' : '1');
		const $chooser = genericAjaxPopup(uid, url, null, true, '90%');
		// The legacy chooser is a jQuery-UI dialog (z ~100). If it was spawned from inside a CerbUI.Dialog
		// (z 9000+), lift it above so it isn't hidden behind the dialog.
		if(window.CerbUI && CerbUI.Dialog) {
			const z = (CerbUI.Dialog._zTop || 9000) + 1;
			requestAnimationFrame(() => { $chooser.closest('.ui-dialog').css('z-index', z); });
		}
		$chooser.one('chooser_save', (event) => {
			const vals = event.values || [], labels = event.labels || [], images = event.images || [];
			for(let i = 0; i < vals.length; i++) {
				this._choose({ context: context, id: vals[i], label: labels[i] || ('#' + vals[i]), image_url: images[i] || '' });
				if(!this.opts.multiple) break;
			}
		});
	}

	_choose(item) {
		if(!item || item.id == null || item.id === '') return; // allow id 0 (e.g. the app:0 "Global" actor)
		if(this.opts.multiple) {
			if(this.values.some((v) => this._valueKey(v) === this._valueKey(item))) return; // dedupe
			this.values.push(item);
			this._syncState();
			this.input.value = '';
			this.core.refresh();              // keep the popup open with a fresh list
			requestAnimationFrame(() => this.input.focus());
		} else {
			this.values = [item];
			this._syncState();
		}
		if(typeof this.opts.onSelect === 'function') this.opts.onSelect(item);
	}

	_removeValue(item) {
		this.values = this.values.filter((v) => this._valueKey(v) !== this._valueKey(item));
		this._syncState();
		// Keep the field editable: focus the autocomplete input if it's available, else the search button
		requestAnimationFrame(() => {
			if(!this.input.hidden) this.input.focus();
			else if(!this.searchBtn.hidden) this.searchBtn.focus();
		});
	}

	// ── Tile (avatar + peek label + remove); used for the single chip and multi tokens ──
	_buildTile(item) {
		const tile = document.createElement('span');
		tile.className = 'cerb-ui-record-chooser--tile';

		const av = document.createElement('span');
		av.className = 'cerb-ui-chooser--avatar cerb-ui-record-chooser--avatar';
		av.style.backgroundColor = CerbUI.chooserCore.monogramColor((item.context || '') + ':' + item.id);
		av.textContent = CerbUI.chooserCore.initials(item.label);
		if(item.image_url) {
			const probe = new Image();
			probe.addEventListener('load', () => {
				av.style.backgroundImage = 'url("' + probe.src + '")';
				av.style.backgroundColor = 'transparent';
				av.textContent = '';
			});
			probe.src = item.image_url;
		}
		tile.appendChild(av);

		// id 0 = a forced/singleton actor (e.g. app:0 "Everyone") — no record to peek, so a plain label
		let name;
		if(String(item.id) === '0') {
			name = document.createElement('span');
			name.className = 'cerb-ui-record-chooser--label';
			name.textContent = item.label;
		} else {
			name = document.createElement('a');
			name.className = 'cerb-peek-trigger no-underline cerb-ui-record-chooser--label';
			name.setAttribute('data-context', item.context);
			name.setAttribute('data-context-id', item.id);
			name.textContent = item.label;
			if(window.jQuery && jQuery.fn.cerbPeekTrigger) jQuery(name).cerbPeekTrigger();
		}
		tile.appendChild(name);

		const clear = document.createElement('button');
		clear.type = 'button';
		clear.className = 'cerb-ui-record-chooser--clear';
		clear.setAttribute('aria-label', 'Remove');
		clear.innerHTML = '<span class="cerb-icons cerb-icon-circle-remove" aria-hidden="true"></span>';
		clear.addEventListener('click', (e) => { e.stopPropagation(); this._removeValue(item); });
		tile.appendChild(clear);

		return tile;
	}

	// ── Render state + hidden fields ──
	_syncState() {
		const filled = this.values.length > 0;
		const multi = this.opts.multiple;

		// Tiles
		this.tilesEl.replaceChildren();
		this.values.forEach((item) => this.tilesEl.appendChild(this._buildTile(item)));

		// Visibility
		this.iconEl.hidden = multi || filled;            // leading icon only for single-empty
		this.input.hidden = !multi && filled;            // single-filled hides the input
		this.searchBtn.hidden = !multi && filled;        // …and the search button
		this.el.classList.toggle('cerb-ui-record-chooser--has-tiles', filled); // collapse the idle input when tiles exist

		// Hidden form fields. Multi → one `name[]` per value (none when empty). Single → ALWAYS one hidden
		// `name` field (so it passes through a form even when cleared): its value, or '' when empty.
		this.hiddenWrap.replaceChildren();
		if(this.opts.name) {
			if(multi) {
				const name = this.opts.name + '[]';
				this.values.forEach((item) => {
					const h = document.createElement('input');
					h.type = 'hidden';
					h.name = name;
					h.value = this._hiddenValue(item);
					this.hiddenWrap.appendChild(h);
				});
			} else {
				const h = document.createElement('input');
				h.type = 'hidden';
				h.name = this.opts.name;
				h.value = this.values.length ? this._hiddenValue(this.values[0]) : '';
				this.hiddenWrap.appendChild(h);
			}
		}
	}

	// ── Public API ──
	getValue() { return this.opts.multiple ? this.values.slice() : (this.values[0] || null); }

	setValue(value) {
		this.values = value ? (Array.isArray(value) ? value.slice() : [value]) : [];
		this._syncState();
	}

	// focus=true (default) lands the caret back in the field — right for the user clicking × to re-pick. Pass
	// false for programmatic clears (e.g. a coupled "Type" select re-scoping several choosers at once) so they
	// don't each grab focus and pop their autocomplete open.
	clear(focus = true) {
		this.values = [];
		this.input.value = '';
		this._syncState();
		if(focus) requestAnimationFrame(() => this.input.focus());
	}

	openSearchPopup() { this.openSearch(); }

	destroy() {
		CerbUI.RecordChooser._instances.delete(this.el);
		this.core.destroy();
		this.input.removeEventListener('focus', this._onFocus);
		this.searchBtn.removeEventListener('click', this._onSearchClick);
		this.el.removeEventListener('click', this._onElClick);
		this.hiddenWrap.remove();
		this.el.classList.remove('cerb-ui-record-chooser', 'cerb-ui-record-chooser--multiple');
	}
};
