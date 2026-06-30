/*
 * CerbUI.SearchQuery — a lightweight editor for Cerb search-query syntax (replaces Ace on worklists).
 *
 * It understands ONLY Cerb's quick-search grammar, so it stays small: plain fulltext (`email receipt`),
 * booleans (`email OR receipt`), filters (`status:o`), quoted literals (`subject:"a phrase"`), lists
 * (`status:[open,waiting]`), and deep/nested search (`sender:(org:(name:"Fiaflux Games"))`).
 *
 * The shared editor plumbing (overlay highlight rendering, caret measurement, the suggestion-menu lifecycle,
 * fuzzy match/filter, the Ace-snippet → `$0` converter) lives in CerbUI.editorCore; this file owns only the
 * search grammar: the tokenizer, the nested-filter scope path at the caret, the single-line autosize, and the
 * Enter-submits keyboard model.
 *
 * Two pieces share ONE small tokenizer:
 *   1. Live syntax highlighting — a textarea can't hold colored spans, so the text is rendered transparent
 *      over a mirror <div> (cerb-ui-searchquery--highlight) that carries the colored token spans.
 *   2. The nested filter path at the caret (e.g. ['sender:','org:','name:']) + the partial word being typed,
 *      computed by walking the tokens of the text before the caret. The path/prefix is handed to the
 *      onAutocomplete callback (local / Ajax / hybrid); CerbUI.editorCore.Autocomplete shows the results.
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

		// The shared suggestion-menu controller (debounce, request guard, CerbUI.Menu, $0 insertion).
		this._ac = new CerbUI.editorCore.Autocomplete({
			textarea: this.textarea,
			caretAnchor: this.caretAnchor,
			context: this.opts.context,
			editor: this,
			delay: this.opts.autocompleteDelay,
			onScope: (text, caret) => this._scopePathAt(text, caret),
			onItems: (ctx) => this.opts.onAutocomplete(ctx),
			onAfterApply: () => { this._renderHighlight(); this._autosize(); },
		});

		CerbUI.SearchQuery._instances.set(el, this);

		// ── Event binding ───────────────────────────────────────────────
		this._onInput = () => this._handleInput();
		this._onKeydown = (e) => this._handleKeydown(e);
		this._onScroll = () => this._syncScroll();
		this._onBlur = () => { this._ac.clearTimer(); };

		this.textarea.addEventListener('input', this._onInput);
		this.textarea.addEventListener('keydown', this._onKeydown);
		this.textarea.addEventListener('scroll', this._onScroll, { passive: true });
		this.textarea.addEventListener('blur', this._onBlur);

		this._renderHighlight();
		this._autosize();

		// Core editor-family hook: a caller can add extensible toolbar `sections` to any editor (opt-in via opts.toolbar).
		CerbUI.editorCore.attachToolbar(this, this.opts);
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
	openAutocomplete() { this._ac.trigger(); return this; }

	// Re-root the autocomplete context at runtime (e.g. a record-type dropdown changing what we search). Updates
	// the ctx.context handed to onAutocomplete, and re-roots a context-scoped source (queryFieldSource) so its
	// per-scope cache is dropped. Closes any open suggestions.
	setContext(context) {
		context = context || '';
		this.opts.context = context;
		if(this._ac) this._ac.opts.context = context;
		if(this.opts.onAutocomplete && typeof this.opts.onAutocomplete.setContext === 'function')
			this.opts.onAutocomplete.setContext(context);
		if(this._ac) this._ac.close();
		return this;
	}

	getSelectedText() { return this.textarea ? this.textarea.value.slice(this.textarea.selectionStart, this.textarea.selectionEnd) : ''; }

	// row/column of the caret — parity with the ScriptingEditor family (used by shared toolbar callers).
	getCursorPosition() {
		if(!this.textarea) return { row: 0, column: 0 };
		const caret = this.textarea.selectionStart;
		const before = this.textarea.value.slice(0, caret);
		return { row: (before.match(/\n/g) || []).length, column: caret - (before.lastIndexOf('\n') + 1) };
	}

	// Insert at the caret, replacing any selection. A single `$0` marks the final caret position.
	insertSnippet(text) {
		if(!this.textarea) return this;
		const ta = this.textarea;
		const s = ta.selectionStart, e = ta.selectionEnd;
		let insert = String(text == null ? '' : text);
		let off = insert.length;
		const m = insert.indexOf('$0');
		if(m !== -1) { off = m; insert = insert.slice(0, m) + insert.slice(m + 2); }
		ta.value = ta.value.slice(0, s) + insert + ta.value.slice(e);
		ta.selectionStart = ta.selectionEnd = s + off;
		this._renderHighlight();
		this._autosize();
		ta.focus();
		if(typeof this.opts.onAutocomplete === 'function') this._ac.schedule();
		return this;
	}

	// Literal insert at the caret (the `cerb.insertAtCursor` replacement); `opts.replace` clears the field first.
	insertAtCursor(content, opts) {
		opts = opts || {};
		if(opts.replace) this.setValue('');
		return this.insertSnippet(content);
	}

	destroy() {
		if(this._editorToolbar && typeof this._editorToolbar.destroy === 'function') this._editorToolbar.destroy();
		this._ac.destroy();
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
		this._ac.clearTimer();
		if(typeof this.opts.onAutocomplete !== 'function') return;
		if(this.textarea.value.length < this.opts.minChars) { this._ac.close(); return; }
		this._ac.schedule();
	}

	// Enter ALWAYS submits — autocomplete is opt-in. The menu only captures Enter once the user has arrowed
	// INTO it (or clicks); a merely-open or hover-highlighted menu does not steal Enter. Shift/Meta/Ctrl+Enter
	// always insert a newline (even over an open menu), re-evaluating the path after.
	_handleKeydown(e) {
		const menuOpen = this._ac.isOpen();

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
			this._ac.trigger();
			return;
		}

		if(menuOpen) {
			// Plain ↑/↓ move INTO/through the menu — Menu's own document keydown does the highlight/scroll
			// (handles virtualization); we just flag the opt-in and stop the textarea caret from drifting.
			if((e.key === 'ArrowDown' || e.key === 'ArrowUp') && !(e.metaKey || e.ctrlKey || e.altKey)) {
				e.preventDefault();
				e.stopPropagation();
				this._ac.moveSelection(e.key === 'ArrowDown' ? +1 : -1);
				return;
			}
			// Any other arrow is TEXT navigation, not menu navigation: a modified ↑/↓/←/→ (⌘/⌥/ctrl line/word/
			// doc skip) or a plain ←/→. Let the textarea move the caret natively and dismiss the suggestions —
			// don't let the open Menu eat it (closing it first unbinds Menu's document keydown for this event).
			if(e.key === 'ArrowDown' || e.key === 'ArrowUp' || e.key === 'ArrowLeft' || e.key === 'ArrowRight') {
				e.stopPropagation();
				this._ac.close();
				return; // no preventDefault → native caret navigation happens
			}
			if(e.key === 'Enter') {
				if(this._ac.navigated) {
					// Drive the selection directly (don't rely on the Menu's document listener — a popup's key
					// containment blocks it) and stop the textarea from also inserting a newline.
					e.preventDefault();
					e.stopPropagation();
					this._ac.acceptSelection(e);
					return; // -> selects active item -> onSelect -> _apply
				}
				// Not navigated: Enter submits. Stop Menu from selecting a hover-highlighted item.
				e.preventDefault();
				e.stopPropagation();
				this._ac.clearTimer();
				this._search();
				return;
			}
			if(e.key === 'Escape') {
				// Close the menu ourselves and swallow the event — Esc dismissing suggestions shouldn't also
				// bubble up to close a parent popup/dialog. (Menu's onClose resets navigated.)
				e.preventDefault();
				e.stopPropagation();
				this._ac.close();
				return;
			}
			// any other key falls through (types a char -> input handler re-suggests)
		}

		// Plain Enter (menu closed) — submit.
		if(e.key === 'Enter') {
			e.preventDefault();
			this._ac.clearTimer();
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
		if(typeof this.opts.onAutocomplete === 'function')
			this._ac.schedule();
	}

	_search() {
		this._ac.close();
		if(typeof this.opts.onSearch === 'function')
			this.opts.onSearch(this.textarea.value);
	}

	// ── Highlighting ────────────────────────────────────────────────────

	_renderHighlight() {
		const toks = this._tokenize(this.textarea.value);
		CerbUI.editorCore.renderTokens(this.highlight, toks, CerbUI.SearchQuery._TOK_CLASS);
		this._syncScroll();
	}

	_syncScroll() {
		CerbUI.editorCore.syncScroll(this.textarea, this.highlight);
	}

	_autosize() {
		const ta = this.textarea;
		ta.style.height = 'auto';
		const h = Math.min(ta.scrollHeight, this.opts.maxHeight);
		ta.style.height = h + 'px';
		ta.style.overflowY = (ta.scrollHeight > this.opts.maxHeight) ? 'auto' : 'hidden';
		this.highlight.style.height = ta.style.height;
	}

	// ── Tokenizer + scope-path (shared grammar) ─────────────────────────
	// The query grammar (tokenizer, token classes, the nested-filter scope walk) lives in the shared
	// CerbUI.editorCore.searchQuery module so CerbUI.DataQuery can reuse the exact same grammar. These are thin
	// delegates — see editor-core.js for the implementation + the group-key `()` convention.

	_tokenize(text) {
		return CerbUI.editorCore.searchQuery.tokenize(text);
	}

	_scopePathAt(text, caret) {
		return CerbUI.editorCore.searchQuery.scopePathAt(text, caret);
	}
};

// Token type -> CSS class for the highlight mirror (shared with DataQuery via editorCore.searchQuery).
CerbUI.SearchQuery._TOK_CLASS = CerbUI.editorCore.searchQuery.TOK_CLASS;

// Back-compat aliases — the fuzzy match/filter utilities now live on CerbUI.editorCore (shared with KataEditor).
CerbUI.SearchQuery.MATCH_MODES = CerbUI.editorCore.MATCH_MODES;
CerbUI.SearchQuery.DEFAULT_SCORE = CerbUI.editorCore.DEFAULT_SCORE;
CerbUI.SearchQuery.match = CerbUI.editorCore.match;
CerbUI.SearchQuery.filterItems = CerbUI.editorCore.filterItems;

/*
 * queryFieldSource(context, opts) — a ready-made onAutocomplete wired to Cerb's existing endpoints, so the
 * component is a drop-in for real worklists. `opts.filterMode` ('subsequence' (default)|'substring'|'prefix')
 * controls how cached field lists are filtered client-side; results keep the backend's hand-ranked `score`
 * order. It uses the same lazy-load contract the (now-retired) Ace search-query completer did:
 *   - unknown nested scope  -> GET c=ui&a=querySuggestions&context=…&expand=…   (api/uri/ui.php)
 *   - dynamic value lists   -> GET c=ui&a=dataQuery&q=…   (substituting {{term}} with the typed prefix)
 * Suggestions are cached per scope key (the colon-joined path), and a `_contexts` map lets nested paths
 * switch record context (sender: -> contact, sender:org: -> org, …) without re-querying from the root.
 */
CerbUI.SearchQuery.queryFieldSource = function(rootContext, opts) {
	opts = opts || {};
	const mode = opts.filterMode || 'subsequence';
	const cache = { '_contexts': { '': rootContext || '' } };
	const aceSnippetToCerb = CerbUI.editorCore.aceSnippetToCerb;

	// The bare field token (a name ending in ':', no parens) from a suggestion — used to test for nesting.
	function fieldTokenOf(s) {
		let c = (typeof s === 'string') ? s
			: String(s.caption != null ? s.caption : (s.snippet != null ? s.snippet : (s.value || '')));
		return c.replace(/\s*\(\s*\)?\s*$/, ''); // strip a trailing '(' or '()'
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
		// A leading type icon (cerb-icon name) + tag color (e.g. 'blue') from the backend field list.
		if(s.icon) { item.icon = s.icon; if(s.color) item.iconColor = s.color; }
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
		return CerbUI.editorCore.filterItems(all, prefix, mode);
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

	const source = function(ctx) {
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

	// Re-root the source to a new record context, dropping every cached field list + nested-context map so the
	// next suggestion lazy-loads against the new root. Lets a host swap contexts live (CerbUI.SearchQuery.setContext).
	source.setContext = function(newRoot) {
		for(const k in cache) { if(k !== '_contexts') delete cache[k]; }
		cache._contexts = { '': newRoot || '' };
	};

	return source;
};
