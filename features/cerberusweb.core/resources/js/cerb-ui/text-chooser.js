/*
 * CerbUI.TextChooser — a plain <input type=text> with optional endpoint-backed suggestions. The freeform
 * sibling of RecordChooser/ContextChooser: it never makes a tile/chip — accepting a suggestion just writes
 * text into the field, and typing anything is always allowed (suggestions are hints, NOT a forced match,
 * unlike Menu/SelectMenu). The input keeps its own name/value, so the surrounding form still posts whatever
 * the user typed. Reuses CerbUI.chooserCore (its inline-input mode + plain, avatar-free rows).
 *
 *   new CerbUI.TextChooser(inputEl, {
 *     icon: 'globe',                                  // optional leading hint glyph (cerb-icons name)
 *     source: array | 'c=…&a=…' | (term) => items,    // items: string | {label, value?, icon?, sublabel?, meta?}
 *                                                     //   array  → filtered client-side by substring
 *                                                     //   string → bare ajax args; fetched via genericAjaxGet
 *                                                     //            with &term=… appended (returns a string array
 *                                                     //            OR [{label,value,…}])
 *                                                     //   fn     → returns items (or a Promise of items)
 *                                                     // (any source may mix bare strings and objects)
 *     minLength: 0,                                   // min term length before suggesting; 0 (default) also
 *                                                     //   suggests on empty focus, letting the endpoint recommend
 *                                                     //   common options
 *     getTerm: (value) => value,                      // what to search for; token modes override (e.g. last word)
 *     onSelect: (item, input) => {…},                 // how a pick is applied; default writes item.value ?? item.label
 *                                                     //   (input + change events fire after). Override for token
 *                                                     //   modes or side effects.
 *     avatars: false,                                 // true = lazy-loaded avatar thumbnail per row (monogram
 *                                                     //   fallback); maps the endpoint's `icon` (avatar URL) to
 *                                                     //   the row image. Pair with `context` for the seed.
 *     context: '',                                    // monogram seed context for avatar mode ('address', 'org', …)
 *   });
 *
 * Ajax `source` contract (when source is a bare-args string):
 *   REQUEST   GET ajax.php?<your args>&term=<search term>   (via genericAjaxGet; `term` is the current term,
 *             already narrowed by getTerm — the whole field by default, the last word/comma token in a token mode)
 *   RESPONSE  a JSON array, either of plain strings  ["Afghanistan","Albania",…]
 *             or of objects  [{ "label": "...",          // shown + the default text written on select
 *                                "value": "...",          // optional; written instead of label if present
 *                                "sublabel": "...",        // optional muted second line
 *                                "icon": "globe",          // optional leading cerb-icons glyph (a NAME, not a URL)
 *                                "meta": {…} }]            // optional; values joined with " · " into the sublabel
 *   The endpoint should filter by `term` server-side and cap its own result count.
 *
 * API: tc.getValue(); tc.setValue(text); tc.destroy(); CerbUI.TextChooser.from(input).
 * Requires CerbUI.chooserCore + (for string/function sources that hit the server) genericAjaxGet.
 */
CerbUI.TextChooser = class {
	static _instances = new WeakMap();
	static from(el) { return CerbUI.TextChooser._instances.get(el); }

	constructor(el, opts = {}) {
		this.input = (el && el.jquery) ? el[0] : ((typeof el === 'string') ? document.querySelector(el) : el);
		if(!this.input || !window.CerbUI || !CerbUI.chooserCore) return;

		this.opts = Object.assign({
			icon:      null,
			source:    null,
			minLength: 0, // 0 = also suggest on empty focus (endpoint can recommend common options)
			getTerm:   (value) => value,
			onSelect:  (item, input) => { input.value = (item.value != null ? item.value : item.label); },
			avatars:   false, // true = a lazy-loaded avatar thumbnail per suggestion (instant monogram fallback,
			                  //        real image loads only for visible rows). Maps the endpoint's `icon` to the
			                  //        row image; pair with `context` (the monogram seed). Reuses chooserCore.
			context:   '',    // monogram seed context for avatar mode (e.g. 'address', 'org', 'worker')
		}, opts);

		this.input.setAttribute('autocomplete', 'off');

		// Optional leading icon: wrap the input so the glyph floats inside its left padding. Self-contained
		// (doesn't rely on a .cerb-ui-form ancestor, which our targets may not have).
		this.wrap = null;
		if(this.opts.icon) {
			this.wrap = document.createElement('span');
			this.wrap.className = 'cerb-ui-text-chooser cerb-ui-text-chooser--has-icon';
			const icon = document.createElement('span');
			icon.className = 'cerb-icons cerb-icon-' + this.opts.icon + ' cerb-ui-text-chooser--icon';
			icon.setAttribute('aria-hidden', 'true');
			this.input.parentNode.insertBefore(this.wrap, this.input);
			this.wrap.appendChild(icon);
			this.wrap.appendChild(this.input);
		}

		this.core = CerbUI.chooserCore.create({
			inputEl:       this.input,
			anchor:        this.input,
			plain:         !this.opts.avatars, // avatars:true → chooserCore's lazy-load monogram/image rows
			closeOnSelect: true,
			search:        (q) => this._search(q),
			onSelect:      (item) => this._choose(item),
		});

		// Open on focus only when the field is EMPTY (so the endpoint can recommend common options). With an
		// existing value, focusing alone doesn't pop the menu — but typing (editing) does.
		this._onFocus = () => { if(!this.input.value) this.core.open(); };
		this._onInput = () => { if(!this.core.isOpen()) this.core.open(); };
		this.input.addEventListener('focus', this._onFocus);
		this.input.addEventListener('input', this._onInput);

		CerbUI.TextChooser._instances.set(this.input, this);
	}

	// chooserCore hands us the full (trimmed) field value as the query; getTerm narrows it to the part we
	// actually search on (whole value by default; last word/comma token in token modes). Below minLength we
	// return nothing so the dropdown stays hidden (and no server hit for short/empty terms).
	_search(query) {
		const term = (this.opts.getTerm(query == null ? '' : query) || '');
		if(term.length < this.opts.minLength)
			return { results: [], more: false };

		const src = this.opts.source;

		if(Array.isArray(src)) {
			const needle = term.toLowerCase();
			const results = src.map((it) => this._normalizeItem(it)).filter((it) => it && it.label.toLowerCase().indexOf(needle) !== -1);
			return { results: results, more: false };
		}

		if(typeof src === 'string')
			return this._searchUrl(src, term);

		if(typeof src === 'function')
			return Promise.resolve(src(term)).then((items) => ({ results: (items || []).map((it) => this._normalizeItem(it)).filter(Boolean), more: false }));

		return { results: [], more: false };
	}

	// Bare ajax args (e.g. 'c=profiles&a=invoke&module=org&action=autocomplete') + &term=…, via genericAjaxGet.
	// Rows may be bare strings (org/country autocomplete return string arrays) OR objects [{label, value, meta?}].
	_searchUrl(args, term) {
		return new Promise((resolve) => {
			if(typeof genericAjaxGet !== 'function') { resolve({ results: [], more: false }); return; }
			const sep = args.indexOf('term=') === -1 ? (args + '&term=' + encodeURIComponent(term)) : args;
			genericAjaxGet('', sep, (json) => {
				const rows = Array.isArray(json) ? json : [];
				resolve({ results: rows.map((it) => this._normalizeItem(it)).filter(Boolean), more: false });
			}, { error: () => resolve({ results: [], more: false }) });
		});
	}

	// Normalize a row from any source into a chooser item. A bare string is its own label+value (many Cerb
	// endpoints — org/country autocomplete — return string arrays). An object uses label (falling back to
	// value) and collapses a `meta` map into a `sublabel` if one isn't given. In avatar mode the endpoint's
	// `icon` (an avatar URL) becomes the row image, seeded by context:id for the instant monogram.
	_normalizeItem(item) {
		if(item == null) return null;
		if(typeof item !== 'object')
			item = { label: String(item), value: String(item) };
		const label = (item.label != null) ? item.label : (item.value != null ? String(item.value) : null);
		if(label == null) return null;
		const out = {
			label:    label,
			value:    (item.value != null) ? item.value : label,
			sublabel: (item.sublabel != null) ? item.sublabel
			          : (item.meta ? Object.values(item.meta).filter(Boolean).join(' · ') : undefined),
		};
		if(this.opts.avatars) {
			out.context   = this.opts.context || '';
			out.id        = (item.value != null) ? item.value : label;
			out.image_url = item.image_url || item.icon || ''; // record-autocomplete returns the avatar URL in `icon`
		} else {
			out.icon = item.icon; // plain mode: a cerb-icons glyph NAME
		}
		return out;
	}

	_choose(item) {
		this.opts.onSelect(item, this.input); // default writes the text; token modes / callers override
		// Let any listeners (validators, change-trackers) see the programmatic update.
		this.input.dispatchEvent(new Event('input', { bubbles: true }));
		this.input.dispatchEvent(new Event('change', { bubbles: true }));
	}

	// ── Public API ──
	getValue() { return this.input.value; }
	setValue(text) { this.input.value = (text == null ? '' : String(text)); }
	close() { if(this.core) this.core.close(); }      // dismiss the suggestion dropdown
	isOpen() { return !!(this.core && this.core.isOpen()); }

	destroy() {
		CerbUI.TextChooser._instances.delete(this.input);
		if(this.core) this.core.destroy();
		this.input.removeEventListener('focus', this._onFocus);
		this.input.removeEventListener('input', this._onInput);
		// Unwrap the icon wrapper (restore the input to its original parent position)
		if(this.wrap && this.wrap.parentNode) {
			this.wrap.parentNode.insertBefore(this.input, this.wrap);
			this.wrap.remove();
		}
	}
};
