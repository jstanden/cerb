/*
 * CerbUI.SearchQuery — a lightweight editor for Cerb search-query syntax (replaces Ace on worklists).
 *
 * It understands ONLY Cerb's quick-search grammar, so it stays small: plain fulltext (`email receipt`),
 * booleans (`email OR receipt`), filters (`status:o`), quoted literals (`subject:"a phrase"`), lists
 * (`status:[open,waiting]`), and deep/nested search (`sender:(org:(name:"Fiaflux Games"))`).
 *
 * Three pieces share ONE small tokenizer:
 *   1. Live syntax highlighting — a textarea can't hold colored spans, so the text is rendered transparent
 *      over a mirror <div> (cerb-ui-searchquery--highlight) that carries the colored token spans.
 *   2. The nested filter path at the caret (e.g. ['sender:','org:','name:']) + the partial word being typed,
 *      computed by walking the tokens of the text before the caret (a port of the Ace-token logic in
 *      Devblocks.cerbCodeEditor.getQueryTokenPath, but over a plain string).
 *   3. Autocomplete — the path/prefix is handed to the onAutocomplete callback (local / Ajax / hybrid); the
 *      returned items are shown in a CerbUI.Menu anchored at the caret.
 *
 * Markup (progressive enhancement; the --right toolbar is arbitrary author markup):
 *   <div class="cerb-ui-searchquery" id="q">
 *     <span class="cerb-ui-searchquery--icon cerb-icons cerb-icon-search"></span>
 *     <div class="cerb-ui-searchquery--field">
 *       <div class="cerb-ui-searchquery--highlight" aria-hidden="true"></div>
 *       <textarea class="cerb-ui-searchquery--input" rows="1"></textarea>
 *       <span class="cerb-ui-searchquery--caret-anchor"></span>
 *     </div>
 *     <div class="cerb-ui-searchquery--right"><!-- icon buttons --></div>
 *   </div>
 *
 * Usage:
 *   new CerbUI.SearchQuery(document.getElementById('q'), {
 *     onSearch: (query) => { ... },                  // Enter (Shift+Enter = newline)
 *     onAutocomplete: ({path, prefix, context}) => items|Promise<items>,
 *     context: 'cerberusweb.contexts.ticket',
 *   });
 *   // ctx.path is the enclosing filter chain (['sender:','org:']); in GROUP-KEY position (caret inside an
 *   //   open `field:(…)`) its final segment is tagged with a trailing `()` (e.g. ['created:()']) so a source
 *   //   can offer that parameterized group's sub-keys, falling back to the plain key otherwise.
 *   // item = { caption, value, snippet?, hint?, icon?, suppressAutocomplete? }
 *   //   value    = text inserted at the caret (default)
 *   //   snippet  = overrides value; a single `$0` marks the caret (e.g. 'sender:($0)' for a nested context)
 *   //   suppressAutocomplete = don't re-open suggestions after this pick (terminal values)
 *
 * A ready-made onAutocomplete for real worklist contexts (lazy-loads from the existing endpoints):
 *   onAutocomplete: CerbUI.SearchQuery.queryFieldSource('cerberusweb.contexts.ticket')
 *
 * CSS lives in cerb.css (.cerb-ui-searchquery--*) — this component never injects styles.
 */
CerbUI.SearchQuery = class {
	static _instances = new WeakMap();
	static from(el) { return CerbUI.SearchQuery._instances.get(el); }

	static _DEFAULTS = {
		onSearch: null,           // (query) on Enter
		onAutocomplete: null,     // (ctx) -> Array<item> | Promise<Array<item>>; ctx={path,prefix,context,query,caret}
		context: '',              // root context alias passed through to onAutocomplete
		autocompleteDelay: 200,   // ms debounce for suggestions while typing
		minChars: 0,              // min length of the whole query before typing-triggered suggestions fire
		maxHeight: 160,           // px the textarea grows to before scrolling (auto-grow)
		placeholder: null,        // overrides the textarea's own placeholder when set
	};

	constructor(el, opts = {}) {
		el = (typeof el === 'string') ? document.querySelector(el) : el;
		if(!el) return;

		this.el = el;
		this.opts = Object.assign({}, CerbUI.SearchQuery._DEFAULTS, opts);

		this.textarea = el.querySelector('.cerb-ui-searchquery--input');
		this.field = el.querySelector('.cerb-ui-searchquery--field');
		this.highlight = el.querySelector('.cerb-ui-searchquery--highlight');
		this.caretAnchor = el.querySelector('.cerb-ui-searchquery--caret-anchor');
		if(!this.textarea || !this.field || !this.highlight || !this.caretAnchor) return;

		// A query editor isn't prose — kill native spellcheck squiggles and the browser's form autofill/
		// autocorrect/autocapitalize, which fight our own suggestions and the overlay highlighting.
		this.textarea.spellcheck = false;
		this.textarea.setAttribute('autocomplete', 'off');
		this.textarea.setAttribute('autocorrect', 'off');
		this.textarea.setAttribute('autocapitalize', 'off');

		if(this.opts.placeholder != null) this.textarea.placeholder = this.opts.placeholder;

		this._menu = null;          // current CerbUI.Menu (rebuilt whenever suggestions change)
		this._acToken = 0;          // monotonic guard so a slow onAutocomplete can't clobber a newer one
		this._acTimer = null;       // debounce timer
		this._menuNavigated = false; // true once the user arrows INTO the menu (the opt-in for Enter = select)

		CerbUI.SearchQuery._instances.set(el, this);

		// ── Event binding ───────────────────────────────────────────────
		this._onInput = () => this._handleInput();
		this._onKeydown = (e) => this._handleKeydown(e);
		this._onScroll = () => this._syncScroll();
		this._onBlur = () => { this._clearTimer(); };

		this.textarea.addEventListener('input', this._onInput);
		this.textarea.addEventListener('keydown', this._onKeydown);
		this.textarea.addEventListener('scroll', this._onScroll, { passive: true });
		this.textarea.addEventListener('blur', this._onBlur);

		this._renderHighlight();
		this._autosize();
	}

	// ── Public API ──────────────────────────────────────────────────────

	getValue() { return this.textarea ? this.textarea.value : ''; }

	setValue(str) {
		if(!this.textarea) return this;
		this.textarea.value = str ?? '';
		this._renderHighlight();
		this._autosize();
		return this;
	}

	focus() {
		if(this.textarea) {
			this.textarea.focus();
			// Put the caret at the end of any pre-filled query (so you append/edit, not overwrite from the start).
			const len = this.textarea.value.length;
			this.textarea.setSelectionRange(len, len);
		}
		return this;
	}

	// Force the suggestion menu open (meta/ctrl+space, or an author-supplied --right button).
	openAutocomplete() { this._triggerAutocomplete(); return this; }

	destroy() {
		this._clearTimer();
		this._closeMenu();
		CerbUI.SearchQuery._instances.delete(this.el);
		if(this.textarea) {
			this.textarea.removeEventListener('input', this._onInput);
			this.textarea.removeEventListener('keydown', this._onKeydown);
			this.textarea.removeEventListener('scroll', this._onScroll);
			this.textarea.removeEventListener('blur', this._onBlur);
		}
	}

	// ── Input / keyboard ────────────────────────────────────────────────

	_handleInput() {
		this._renderHighlight();
		this._autosize();
		this._clearTimer();
		if(typeof this.opts.onAutocomplete !== 'function') return;
		if(this.textarea.value.length < this.opts.minChars) { this._closeMenu(); return; }
		this._acTimer = window.setTimeout(() => this._triggerAutocomplete(), this.opts.autocompleteDelay);
	}

	// Enter ALWAYS submits — autocomplete is opt-in. The menu only captures Enter once the user has arrowed
	// INTO it (or clicks); a merely-open or hover-highlighted menu does not steal Enter. Shift/Meta/Ctrl+Enter
	// always insert a newline (even over an open menu), re-evaluating the path after.
	_handleKeydown(e) {
		const menuOpen = this._menu && this._menu.isOpen();

		// Newline: Shift+Enter or Meta/Ctrl+Enter, always. (Meta+Enter inserts nothing by default, so do it
		// manually — and do it manually for Shift too, for one consistent path.)
		if(e.key === 'Enter' && (e.shiftKey || e.metaKey || e.ctrlKey)) {
			e.preventDefault();
			if(menuOpen) e.stopPropagation(); // don't let the open Menu treat this as a selection
			this._insertText('\n');
			return;
		}

		// meta/ctrl+space — force suggestions regardless of the debounce
		if(e.code === 'Space' && (e.ctrlKey || e.metaKey)) {
			e.preventDefault();
			if(menuOpen) e.stopPropagation();
			this._triggerAutocomplete();
			return;
		}

		if(menuOpen) {
			// Plain ↑/↓ move INTO/through the menu — Menu's own document keydown does the highlight/scroll
			// (handles virtualization); we just flag the opt-in and stop the textarea caret from drifting.
			if((e.key === 'ArrowDown' || e.key === 'ArrowUp') && !(e.metaKey || e.ctrlKey || e.altKey)) {
				e.preventDefault();
				this._menuNavigated = true;
				return; // propagate to Menu
			}
			// Any other arrow is TEXT navigation, not menu navigation: a modified ↑/↓/←/→ (⌘/⌥/ctrl line/word/
			// doc skip) or a plain ←/→. Let the textarea move the caret natively and dismiss the suggestions —
			// don't let the open Menu eat it (closing it first unbinds Menu's document keydown for this event).
			if(e.key === 'ArrowDown' || e.key === 'ArrowUp' || e.key === 'ArrowLeft' || e.key === 'ArrowRight') {
				e.stopPropagation();
				this._closeMenu();
				return; // no preventDefault → native caret navigation happens
			}
			if(e.key === 'Enter') {
				if(this._menuNavigated) {
					// Selection happens in Menu's listener; just stop the textarea from also inserting a newline.
					e.preventDefault();
					return; // propagate to Menu -> selects active item -> onSelect -> _applySuggestion
				}
				// Not navigated: Enter submits. Stop Menu from selecting a hover-highlighted item.
				e.preventDefault();
				e.stopPropagation();
				this._clearTimer();
				this._search();
				return;
			}
			if(e.key === 'Escape') {
				// Close the menu ourselves and swallow the event — Esc dismissing suggestions shouldn't also
				// bubble up to close a parent popup/dialog. (Menu's onClose resets _menuNavigated.)
				e.preventDefault();
				e.stopPropagation();
				this._closeMenu();
				return;
			}
			// any other key falls through (types a char -> input handler re-suggests)
		}

		// Plain Enter (menu closed) — submit.
		if(e.key === 'Enter') {
			e.preventDefault();
			this._clearTimer();
			this._search();
		}
	}

	// Insert text at the caret (used for manual newlines), then re-highlight and re-evaluate suggestions on
	// the new path.
	_insertText(text) {
		const ta = this.textarea;
		const s = ta.selectionStart;
		const e = ta.selectionEnd;
		ta.value = ta.value.slice(0, s) + text + ta.value.slice(e);
		ta.selectionStart = ta.selectionEnd = s + text.length;
		this._renderHighlight();
		this._autosize();
		this._clearTimer();
		if(typeof this.opts.onAutocomplete === 'function')
			this._acTimer = window.setTimeout(() => this._triggerAutocomplete(), this.opts.autocompleteDelay);
	}

	_search() {
		this._closeMenu();
		if(typeof this.opts.onSearch === 'function')
			this.opts.onSearch(this.textarea.value);
	}

	// ── Highlighting ────────────────────────────────────────────────────

	_renderHighlight() {
		const toks = this._tokenize(this.textarea.value);
		let html = '';
		for(const t of toks) {
			const cls = CerbUI.SearchQuery._TOK_CLASS[t.type];
			const esc = CerbUI.SearchQuery._escape(t.value);
			html += cls ? ('<span class="' + cls + '">' + esc + '</span>') : esc;
		}
		// A trailing newline collapses the mirror's last line — pad it so heights stay in lockstep.
		if(html.endsWith('\n')) html += ' ';
		this.highlight.innerHTML = html;
		this._syncScroll();
	}

	_syncScroll() {
		this.highlight.scrollTop = this.textarea.scrollTop;
		this.highlight.scrollLeft = this.textarea.scrollLeft;
	}

	_autosize() {
		const ta = this.textarea;
		ta.style.height = 'auto';
		const h = Math.min(ta.scrollHeight, this.opts.maxHeight);
		ta.style.height = h + 'px';
		ta.style.overflowY = (ta.scrollHeight > this.opts.maxHeight) ? 'auto' : 'hidden';
		this.highlight.style.height = ta.style.height;
	}

	static _escape(s) {
		return s.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
	}

	// ── Tokenizer (shared by highlighting + scope-path) ─────────────────
	// Cerb's grammar is small, so a sticky-regex scan is enough. Order matters: the first pattern that
	// matches at the cursor wins. Returns [{type, value, start, end, inner?, terminated?}, ...] covering
	// the whole string (every char belongs to exactly one token).

	_tokenize(text) {
		const toks = [];
		let i = 0;
		const n = text.length;
		const RX = CerbUI.SearchQuery._RX;

		while(i < n) {
			let matched = null;
			for(const rule of RX) {
				rule.re.lastIndex = i;
				const m = rule.re.exec(text);
				if(m && m.index === i) { matched = { type: rule.type, value: m[0] }; break; }
			}
			if(!matched) { // safety net: consume one char as text so we never loop forever
				matched = { type: 'text', value: text[i] };
			}
			matched.start = i;
			matched.end = i + matched.value.length;
			if(matched.type === 'quoted') {
				const q = matched.value[0];
				matched.terminated = matched.value.length > 1 && matched.value[matched.value.length - 1] === q;
				matched.inner = matched.value.slice(1, matched.terminated ? -1 : undefined);
			}
			toks.push(matched);
			i = matched.end;
		}
		return toks;
	}

	// ── Scope path at the caret ─────────────────────────────────────────
	// Walk the tokens of the text BEFORE the caret, tracking the chain of filter fields that enclose it:
	//   - `field:` sets the current (innermost) field context
	//   - `(` / `!(` pushes the field that owns the group; `)` pops it
	//   - whitespace / AND / OR / `]` resets the innermost field (we've moved past a value)
	// In group-key position (the caret sits inside an open `field:(…)` ready for a sub-key, not after a value),
	// the final segment is tagged with a trailing `()` — e.g. `closed:(` → ['closed:()'] vs `closed:` →
	// ['closed:']. A source can offer that group's parameterized sub-keys under the `closed:()` key, falling
	// back to the plain `closed:` key for record contexts / value forms.
	// Returns { path:['sender:','org:','name:'], prefix:'partial', prefixRaw:'chars to replace', caret }.

	_scopePathAt(text, caret) {
		const toks = this._tokenize(text.slice(0, caret));
		const stack = [];     // field that owns each currently-open paren group (null for a bare grouping paren)
		let pending = null;   // innermost field context the caret sits under
		let prefix = '';      // partial value/word being typed (for filtering)
		let prefixRaw = '';   // the literal characters to replace on accept (includes an open quote)

		for(const t of toks) {
			switch(t.type) {
				case 'ws':       pending = null; prefix = ''; prefixRaw = ''; break;
				case 'field':    pending = t.value; prefix = ''; prefixRaw = ''; break;
				case 'lparen':
				case 'lparenNeg': stack.push(pending); pending = null; prefix = ''; prefixRaw = ''; break;
				case 'rparen':   stack.pop(); pending = null; prefix = ''; prefixRaw = ''; break;
				case 'lbrack':   prefix = ''; prefixRaw = ''; break; // array values still belong to `pending`
				case 'rbrack':   pending = null; prefix = ''; prefixRaw = ''; break;
				case 'comma':    prefix = ''; prefixRaw = ''; break;
				case 'bool':     pending = null; prefix = ''; prefixRaw = ''; break;
				case 'quoted':
					prefix = t.terminated ? '' : t.inner;
					prefixRaw = t.terminated ? '' : t.value;
					break;
				case 'number':
				case 'text':     prefix = t.value; prefixRaw = t.value; break;
			}
		}

		const path = stack.filter(Boolean);
		if(pending) {
			path.push(pending);
		} else if(path.length > 0) {
			// Group-key position: tag the innermost (final) group owner so a source can offer its sub-keys.
			path[path.length - 1] += '()';
		}
		return { path, prefix, prefixRaw, caret };
	}

	// ── Autocomplete ────────────────────────────────────────────────────

	_triggerAutocomplete() {
		this._clearTimer();
		if(typeof this.opts.onAutocomplete !== 'function') return;

		const ta = this.textarea;
		const caret = ta.selectionStart;
		const sp = this._scopePathAt(ta.value, caret);
		const ctx = {
			path: sp.path,
			prefix: sp.prefix,
			context: this.opts.context || '',
			query: ta.value,
			caret: caret,
		};

		const token = ++this._acToken;
		Promise.resolve(this.opts.onAutocomplete(ctx)).then(items => {
			if(token !== this._acToken) return; // a newer request superseded this one
			if(!Array.isArray(items) || items.length === 0) { this._closeMenu(); return; }
			this._openMenu(items, sp);
		}).catch(() => { this._closeMenu(); });
	}

	_openMenu(items, sp) {
		this._closeMenu();

		const ul = document.createElement('ul'); // detached; CerbUI.Menu only reads its <li> children
		ul.hidden = true;
		for(const it of items) {
			const li = document.createElement('li');
			li.textContent = (it.caption != null) ? it.caption : (it.value != null ? it.value : '');
			li.dataset.value = (it.value != null) ? it.value : (it.caption != null ? it.caption : '');
			// snippet = the text actually inserted, with a single `$0` marking the final caret (e.g.
			// `sender:($0)` drops the caret inside the parens). Falls back to value when absent.
			if(it.snippet != null) li.dataset.snippet = it.snippet;
			if(it.suppressAutocomplete) li.dataset.suppress = '1';
			if(it.hint) li.dataset.hint = it.hint;
			if(it.icon) li.dataset.icon = it.icon;
			ul.appendChild(li);
		}
		this._menuUl = ul;
		this._menuNavigated = false; // a fresh list isn't navigated until the user arrows into it

		this._menu = new CerbUI.Menu(ul, {
			// absolute (not fixed) so the dropdown is placed at document coords and scrolls WITH the field/page,
			// staying glued to the caret instead of locking to the viewport.
			fixed: false,
			closeOnSelect: true,
			onRenderItem: (li, src) => this._renderMenuItem(li, src),
			onClose: () => { this._menuNavigated = false; },
			onSelect: (li, src) => this._applySuggestion({
				insert: (src.dataset.snippet != null) ? src.dataset.snippet : src.dataset.value,
				suppress: src.dataset.suppress === '1',
			}),
		});

		this._positionCaretAnchor(sp.caret);
		this._menu.open(this.caretAnchor);
	}

	// onRenderItem hook: optional leading icon + a muted right-aligned hint (e.g. the field type).
	_renderMenuItem(li, src) {
		if(src.dataset.icon) {
			const ico = document.createElement('span');
			const name = src.dataset.icon;
			ico.className = (name.charAt(0) === '.')
				? name.slice(1).split('.').join(' ')
				: ('cerb-icons cerb-icon-' + name);
			ico.style.marginRight = '0.4em';
			li.insertBefore(ico, li.firstChild);
		}
		if(src.dataset.hint) {
			const hint = document.createElement('span');
			hint.className = 'cerb-ui-searchquery--menu-hint';
			hint.textContent = src.dataset.hint;
			li.appendChild(hint);
		}
	}

	// Replace the partial word at the caret with the chosen text, then re-suggest the next level
	// (unless the item asked us not to) so deep paths can be built without re-typing.
	_applySuggestion({ insert, suppress }) {
		this._closeMenu();
		this._replacePrefix(insert ?? '');
		this._renderHighlight();
		this._autosize();
		this.textarea.focus();
		if(suppress) return;
		// Re-run immediately so picking `sender:` (-> `sender:(|)`) cascades into its nested suggestions.
		this._triggerAutocomplete();
	}

	// Swap the partial word being typed for `insert`. A single `$0` in `insert` marks where the caret lands
	// (e.g. `sender:($0)` -> caret between the parens); without it, the caret goes to the end.
	_replacePrefix(insert) {
		const ta = this.textarea;
		const caret = ta.selectionStart;
		const sp = this._scopePathAt(ta.value, caret);
		const start = caret - sp.prefixRaw.length;

		let caretOffset = insert.length;
		const marker = insert.indexOf('$0');
		if(marker !== -1) {
			caretOffset = marker;
			insert = insert.slice(0, marker) + insert.slice(marker + 2);
		}

		ta.value = ta.value.slice(0, start) + insert + ta.value.slice(caret);
		ta.selectionStart = ta.selectionEnd = start + caretOffset;
	}

	_closeMenu() {
		if(this._menu) {
			this._menu.destroy();
			this._menu = null;
		}
		this._menuUl = null;
	}

	_clearTimer() {
		if(this._acTimer !== null) { clearTimeout(this._acTimer); this._acTimer = null; }
	}

	// Move the (zero-width) caret anchor to the caret's pixel position so the menu floats just below it.
	_positionCaretAnchor(caret) {
		const c = this._caretCoords(caret);
		this.caretAnchor.style.left = (c.left - this.textarea.scrollLeft) + 'px';
		this.caretAnchor.style.top = (c.top - this.textarea.scrollTop) + 'px';
		this.caretAnchor.style.height = c.height + 'px';
	}

	// Mirror-div caret measurement: clone the textarea's text-affecting styles into an offscreen div,
	// slice the text at the caret, and read the offset of a marker span. Coordinates are relative to the
	// textarea's border box (the --field is position:relative and the textarea fills it).
	_caretCoords(caret) {
		const ta = this.textarea;
		const div = document.createElement('div');
		const cs = window.getComputedStyle(ta);
		const props = CerbUI.SearchQuery._MIRROR_PROPS;
		for(const p of props) div.style[p] = cs[p];
		div.style.position = 'absolute';
		div.style.visibility = 'hidden';
		div.style.whiteSpace = 'pre-wrap';
		div.style.wordWrap = 'break-word';
		div.style.overflow = 'hidden';
		div.style.width = ta.clientWidth + 'px';
		div.style.height = 'auto';

		div.textContent = ta.value.slice(0, caret);
		const marker = document.createElement('span');
		marker.textContent = ta.value.slice(caret) || '.';
		div.appendChild(marker);

		document.body.appendChild(div);
		const left = marker.offsetLeft;
		const top = marker.offsetTop;
		const height = parseFloat(cs.lineHeight) || (parseFloat(cs.fontSize) * 1.2);
		document.body.removeChild(div);

		return { left, top, height };
	}
};

// Token type -> CSS class for the highlight mirror (text/ws/comma have no class).
CerbUI.SearchQuery._TOK_CLASS = {
	field:     'cerb-ui-searchquery--tok-field',
	quoted:    'cerb-ui-searchquery--tok-string',
	bool:      'cerb-ui-searchquery--tok-bool',
	number:    'cerb-ui-searchquery--tok-number',
	lparen:    'cerb-ui-searchquery--tok-paren',
	lparenNeg: 'cerb-ui-searchquery--tok-paren',
	rparen:    'cerb-ui-searchquery--tok-paren',
	lbrack:    'cerb-ui-searchquery--tok-paren',
	rbrack:    'cerb-ui-searchquery--tok-paren',
};

// Ordered, sticky tokenizer rules (first match at the cursor wins).
CerbUI.SearchQuery._RX = [
	{ type: 'ws',        re: /\s+/y },
	{ type: 'field',     re: /[A-Za-z0-9_.]+:/y },               // a filter name: status:, sender.org.name:
	{ type: 'quoted',    re: /"(?:\\.|[^"\\])*"?/y },            // "double" (trailing quote optional = unterminated)
	{ type: 'quoted',    re: /'(?:\\.|[^'\\])*'?/y },            // 'single'
	{ type: 'lparenNeg', re: /!\(/y },                           // negated group
	{ type: 'lparen',    re: /\(/y },
	{ type: 'rparen',    re: /\)/y },
	{ type: 'lbrack',    re: /\[/y },
	{ type: 'rbrack',    re: /\]/y },
	{ type: 'comma',     re: /,/y },
	{ type: 'bool',      re: /(?:AND|OR)(?![A-Za-z0-9_])/y },     // uppercase booleans only
	{ type: 'number',    re: /[+\-]?\.?\d[\d.eE+\-]*/y },
	{ type: 'text',      re: /[^\s()[\],"']+/y },
];

// Styles copied from the textarea into the offscreen mirror so wrapping matches exactly.
CerbUI.SearchQuery._MIRROR_PROPS = [
	'boxSizing', 'paddingTop', 'paddingRight', 'paddingBottom', 'paddingLeft',
	'borderTopWidth', 'borderRightWidth', 'borderBottomWidth', 'borderLeftWidth',
	'fontFamily', 'fontSize', 'fontWeight', 'fontStyle', 'letterSpacing',
	'lineHeight', 'textTransform', 'tabSize', 'textIndent',
];

/*
 * Match a candidate caption against the typed query, for filtering suggestion menus. Three modes:
 *   'subsequence' — the query's characters appear in the caption in order, not necessarily contiguous, so
 *                   typing `linadd` matches `links.address:` (lin…add). A loose "fuzzy" match. (DEFAULT)
 *   'substring'   — caption contains the query as a contiguous run
 *   'prefix'      — caption starts with the query (left-anchored only)
 * Case-insensitive; an empty query matches everything. A source owns its own filtering, so it can use this
 * (local lists) or ignore it (server-side search).
 */
CerbUI.SearchQuery.MATCH_MODES = ['subsequence', 'substring', 'prefix'];

CerbUI.SearchQuery.match = function(text, query, mode) {
	text = String(text == null ? '' : text).toLowerCase();
	query = String(query == null ? '' : query).toLowerCase();
	if(!query) return true;

	if(mode === 'substring')
		return text.indexOf(query) !== -1;

	if(mode === 'prefix')
		return text.slice(0, query.length) === query;

	// 'subsequence' (default)
	let qi = 0;
	for(let ti = 0; ti < text.length && qi < query.length; ti++)
		if(text[ti] === query[qi]) qi++;
	return qi === query.length;
};

// Filter an array of suggestion items (or plain strings) by query + mode, then order by `score` (higher
// first) so a source's hand-ranked order wins over the matched order. Items without a score default to
// DEFAULT_SCORE (1000, matching the legacy Ace completer), so a field scored below that sinks beneath the
// unranked baseline and one scored above floats up. `keyFn` selects the text to match against (defaults to
// the item's caption, or the string itself). The sort is stable, so equally-scored items keep their given
// order (a local list stays as authored).
CerbUI.SearchQuery.DEFAULT_SCORE = 1000;

CerbUI.SearchQuery.filterItems = function(items, query, mode, keyFn) {
	const key = keyFn || (it => (typeof it === 'string') ? it : (it && it.caption != null ? it.caption : ''));
	const scoreOf = it => (it && typeof it.score === 'number') ? it.score : CerbUI.SearchQuery.DEFAULT_SCORE;
	const matched = query ? items.filter(it => CerbUI.SearchQuery.match(key(it), query, mode)) : items.slice();
	return matched.sort((a, b) => scoreOf(b) - scoreOf(a));
};

/*
 * queryFieldSource(context, opts) — a ready-made onAutocomplete wired to Cerb's existing endpoints, so the
 * component is a drop-in for real worklists. `opts.filterMode` ('subsequence' (default)|'substring'|'prefix')
 * controls how cached field lists are filtered client-side; results keep the backend's hand-ranked `score`
 * order. It replicates the Ace completer's lazy-load (cerberus.js cerbCodeEditorAutocompleteSearchQueries):
 *   - unknown nested scope  -> GET c=ui&a=querySuggestions&context=…&expand=…   (api/uri/ui.php)
 *   - dynamic value lists   -> GET c=ui&a=dataQuery&q=…   (substituting {{term}} with the typed prefix)
 * Suggestions are cached per scope key (the colon-joined path), and a `_contexts` map lets nested paths
 * switch record context (sender: -> contact, sender:org: -> org, …) without re-querying from the root.
 */
CerbUI.SearchQuery.queryFieldSource = function(rootContext, opts) {
	opts = opts || {};
	const mode = opts.filterMode || 'subsequence';
	const cache = { '_contexts': { '': rootContext || '' } };

	// The bare field token (a name ending in ':', no parens) from a suggestion — used to test for nesting.
	function fieldTokenOf(s) {
		let c = (typeof s === 'string') ? s
			: String(s.caption != null ? s.caption : (s.snippet != null ? s.snippet : (s.value || '')));
		return c.replace(/\s*\(\s*\)?\s*$/, ''); // strip a trailing '(' or '()'
	}

	// The backend emits Ace-format snippets (`"${1}"`, `field:[${1}]`, `${1:3.14}`). Convert their tabstops to
	// our single `$0` caret marker: keep any default text, drop the numbering, mark the FIRST stop as the caret.
	function aceSnippetToCerb(snip) {
		let placed = false;
		return String(snip).replace(/\$\{(\d+):([^}]*)\}|\$\{(\d+)\}|\$(\d+)/g, function(m, _n1, def) {
			const fill = (def != null) ? def : '';
			if(!placed) { placed = true; return fill + '$0'; }
			return fill;
		});
	}

	function toItem(s, scopeKey) {
		if(typeof s === 'string') return { caption: s, value: s };
		const value = (s.snippet != null) ? aceSnippetToCerb(s.snippet) : (s.value != null ? s.value : s.caption);
		const item = {
			caption: (s.caption != null) ? s.caption : value,
			value: value,
			hint: s.hint || s.meta || null,
			suppressAutocomplete: !!s.suppress_autocomplete,
		};
		if(typeof s.score === 'number') item.score = s.score; // honored by filterItems (higher first)
		// A field that descends into another record context (sender: -> org: -> …) inserts `field:()` with
		// the caret inside the parens, then keeps suggesting the inner fields.
		const ft = fieldTokenOf(s);
		if(ft && ft.charAt(ft.length - 1) === ':' && cache._contexts[scopeKey + ft] != null) {
			item.value = ft;
			item.snippet = ft + '($0)';
			item.suppressAutocomplete = false;
		}
		return item;
	}

	function filterItems(arr, prefix, scopeKey) {
		const all = arr.map(s => toItem(s, scopeKey));
		return CerbUI.SearchQuery.filterItems(all, prefix, mode);
	}

	function get(urlargs) {
		return new Promise((resolve) => {
			genericAjaxGet('', urlargs, function(json) { resolve(json); });
		});
	}

	// Resolve a dynamic {_type:'autocomplete', query, key, min_length} descriptor against c=ui&a=dataQuery.
	function resolveDynamic(d, prefix) {
		const minLen = d.min_length || 0;
		if(minLen && prefix.length < minLen) return Promise.resolve([]);
		const query = String(d.query).replace('{{term}}', prefix);
		return get('c=ui&a=dataQuery&q=' + encodeURIComponent(query)).then(json => {
			const out = [];
			if(json && json.data) {
				for(const row of json.data) {
					const v = row[d.key];
					if(v == null || String(v).length === 0) continue;
					out.push({
						caption: v,
						value: (String(v).indexOf(' ') !== -1) ? ('"' + v + '"') : v,
						suppressAutocomplete: true, // a concrete value is terminal — don't re-list after picking
					});
				}
			}
			return out;
		});
	}

	return function(ctx) {
		const path = ctx.path.slice();
		let scopeKey = path.join('');
		const prefix = ctx.prefix || '';

		// A trailing `()` on the final segment marks group-key position (inside `field:(…)`). Prefer that key —
		// a parameterized group's sub-keys (e.g. `closed:()`); if the map has none (a record context like
		// `sender:(`, which nests via `_contexts`, or a plain value field), drop the `()` and resolve the
		// sibling key normally.
		if(scopeKey.endsWith('()') && cache[scopeKey] === undefined) {
			path[path.length - 1] = path[path.length - 1].slice(0, -2);
			scopeKey = path.join('');
		}

		const cached = cache[scopeKey];

		if(Array.isArray(cached)) return filterItems(cached, prefix, scopeKey);
		if(cached && typeof cached === 'object' && cached._type === 'autocomplete')
			return resolveDynamic(cached, prefix);

		// Lazy-load: figure out the smallest `expand` suffix we still need (mirrors the Ace completer).
		let expand = '';
		let expandPrefix = '';
		let expandContext = cache._contexts[''] || '';

		if(cache._contexts && cache._contexts[scopeKey] != null) {
			expandContext = cache._contexts[scopeKey];
			expandPrefix = scopeKey;
		} else {
			const stack = [];
			for(const seg of path) {
				stack.push(seg);
				const sk = stack.join('');
				if(cache[sk]) {
					expandPrefix += seg;
				} else if(cache._contexts && cache._contexts[sk] != null) {
					expandContext = cache._contexts[sk];
					expandPrefix += seg;
				} else {
					expand += seg;
				}
			}
		}

		if(expandContext === '') return [];

		// Guard the inherited Ace bug: a parameterized (non-context) field like `closed:` in
		// `closed:(since:… until:…)` lives in the cache as a value list, so the resolver walks PAST it but never
		// switches context — leaving `expand` non-empty against the ROOT context. Fetching that returns the root
		// field list under key '', and the prefix-merge below (`cache[expandPrefix + ''] = json['']`) would
		// then overwrite `cache['closed:']` with the root fields. There's nothing real to fetch for such a
		// sub-key, so bail without touching the cache.
		if(expand !== '' && expandContext === (cache._contexts[''] || ''))
			return [];

		return get('c=ui&a=querySuggestions&context=' + encodeURIComponent(expandContext)
				+ '&expand=' + encodeURIComponent(expand)).then(json => {
			if(json && typeof json === 'object') {
				for(const pathKey in json) {
					if(pathKey === '_contexts') {
						for(const ck in json._contexts)
							cache._contexts[expandPrefix + ck] = json._contexts[ck];
					} else {
						cache[expandPrefix + pathKey] = json[pathKey];
					}
				}
			}
			const now = cache[scopeKey];
			if(Array.isArray(now)) return filterItems(now, prefix, scopeKey);
			if(now && now._type === 'autocomplete') return resolveDynamic(now, prefix);
			return [];
		});
	};
};
