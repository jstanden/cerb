/*
 * CerbUI.KataEditor — a plain-JS code editor for Cerb's KATA syntax (the eventual replacement for Ace in the
 * automation editor and the `.cerbCodeEditor()` plugin).
 *
 * It shares the overlay-highlight + caret-anchored autocomplete machinery with CerbUI.SearchQuery via
 * CerbUI.editorCore, but is a CODE editor: always multi-line, auto-growing between minLines and maxLines, with
 * a left line-number gutter, KATA-aware syntax highlighting, and tab-as-two-spaces / Shift+Tab dedent. It does
 * NOT wrap (1 text line = 1 gutter row) — long lines scroll horizontally, like Ace's cerb_kata mode.
 *
 * KATA grammar highlighted (see /docs/kata): `key:` (optionally `key/identifier:` and a `key@annotation,csv:`
 * run), the value after a key, indented `@text`/`@json`/… blocks, `{{ twig }}` / `{% twig %}` tags, `# comment`
 * lines, and `cerb:context:id` URIs.
 *
 * Markup (the gallery / template supplies it):
 *   <div class="cerb-ui-kataeditor" id="ed">
 *     <div class="cerb-ui-kataeditor--gutter" aria-hidden="true"></div>
 *     <div class="cerb-ui-kataeditor--field">
 *       <div class="cerb-ui-kataeditor--highlight" aria-hidden="true"></div>
 *       <textarea class="cerb-ui-kataeditor--input" name="…" spellcheck="false"></textarea>
 *       <span class="cerb-ui-kataeditor--caret-anchor"></span>
 *     </div>
 *   </div>
 *
 * Usage:
 *   new CerbUI.KataEditor(document.getElementById('ed'), {
 *     minLines: 4, maxLines: 25,
 *     onAutocomplete: CerbUI.KataEditor.kataFieldSource(CerbUI.editorCore.autocompleteSchemas.kataAutomationPolicy),
 *   });
 *
 * Form integration: a textarea can only hold the folded PROJECTION, so when the authored <textarea> carries a
 * name= it's kept as an inert hidden VALUE CARRIER and editing happens in a nameless clone. _fireChange() keeps
 * the carrier = the full document, so native FormData(form) submit and external .val() reads always see the whole
 * doc (folded or not). To SET the value from outside, call setValue() — not .val() on the field. CSS lives in
 * cerb.css (.cerb-ui-kataeditor--*).
 */
CerbUI.KataEditor = class {
	static _instances = new WeakMap();
	static from(el) { return CerbUI.KataEditor._instances.get(el); }
	static _NS = 'kataeditor';   // element-class namespace -> `.cerb-ui-kataeditor--input`, etc.

	// Wrap a BARE <textarea> in the editor shell and construct, so a template only authors the textarea.
	static enhance(field, opts = {}) { return CerbUI.editorCore.enhanceEditor(this, field, opts); }

	static _DEFAULTS = {
		onAutocomplete: null,     // (ctx) -> Array<item> | Promise<...>; ctx={path,prefix,context,query,caret,editor}
		context: '',              // passed through to onAutocomplete
		autocompleteDelay: 200,   // ms debounce for suggestions while typing
		minLines: 2,              // editor never shrinks below this many rows
		maxLines: 25,             // grows to this many rows, then scrolls (data-editor-lines overrides)
		tabSize: 2,               // a Tab inserts this many spaces; Shift+Tab dedents by up to this many
		indentGuides: true,       // faint vertical rule down each indentation level (continues across blank lines)
		placeholder: null,
		onGutterClick: null,      // (modelRow, e) when the left marker column is clicked (e.g. toggle a breakpoint)
		onOpenUri: null,          // (uri) override for the hover "Open" action on a cerb: URI (default: open its peek)
		readOnly: false,          // highlight + fold only; disable text-mutating keys (data-editor-readonly overrides)
		folding: true,            // false = never foldable (no chevrons); keeps 1 model row = 1 view row (e.g. a diff pane)
	};

	// Viewport virtualization (large docs): at or below this many VIEW rows we paint the whole mirror + gutter
	// (today's exact path — zero alignment risk for the common small doc); above it we paint only the visible
	// window + OVERSCAN buffer rows, with block spacer <div>s above/below so scroll-sync still lines up.
	static _VIRTUALIZE_MIN_ROWS = 200;
	static _OVERSCAN = 12;

	constructor(el, opts = {}) {
		el = (typeof el === 'string') ? document.querySelector(el) : el;
		// Polymorphic: a bare <textarea> self-builds the editor shell so callers can skip the boilerplate.
		el = CerbUI.editorCore.resolveEditorEl(el, CerbUI.KataEditor._NS, opts);
		if(!el) return;

		this.el = el;
		this.opts = Object.assign({}, CerbUI.KataEditor._DEFAULTS, opts);

		const input0 = el.querySelector('.cerb-ui-kataeditor--input');
		const lines = input0 && input0.getAttribute('data-editor-lines');
		if(lines) this.opts.maxLines = parseInt(lines, 10) || this.opts.maxLines;
		if(input0 && input0.hasAttribute('data-editor-readonly')) this.opts.readOnly = true;

		this.textarea = el.querySelector('.cerb-ui-kataeditor--input');
		this.field = el.querySelector('.cerb-ui-kataeditor--field');
		this.highlight = el.querySelector('.cerb-ui-kataeditor--highlight');
		this.gutter = el.querySelector('.cerb-ui-kataeditor--gutter');
		this.caretAnchor = el.querySelector('.cerb-ui-kataeditor--caret-anchor');
		if(!this.textarea || !this.field || !this.highlight || !this.caretAnchor) return;

		// Form integration: a textarea can only hold the folded PROJECTION, so the authored field can't double as
		// the form value — a submit while folded would post a truncated document. When it carries a name=, keep the
		// authored textarea as an inert hidden VALUE CARRIER and edit in a nameless clone; _fireChange() keeps the
		// carrier = this._model, so native FormData(form) always serializes the full document (folded or not).
		this._valueField = null;
		if(this.textarea.name) {
			const carrier = this.textarea;
			const editor = carrier.cloneNode(false);   // attributes only (incl. data-editor-lines); textarea text isn't cloned
			editor.removeAttribute('name');             // …the clone must NOT be serialized
			editor.removeAttribute('id');               // …nor duplicate the carrier's id
			editor.value = carrier.value;               // shallow clone has no text — copy the initial value across
			carrier.parentNode.insertBefore(editor, carrier);
			carrier.classList.remove('cerb-ui-kataeditor--input');
			carrier.classList.add('cerb-ui-kataeditor--value');
			carrier.hidden = true;                      // still serialized — submission is gated by `disabled`, not visibility
			carrier.setAttribute('aria-hidden', 'true');
			this._valueField = carrier;
			this.textarea = editor;
		}

		this.tab = ' '.repeat(this.opts.tabSize);
		this._highlightRow = null;   // a MODEL row marked active (line band + gutter cell), or null
		this._highlightColor = null; // optional Cerb tag color name for the active line (e.g. 'red'); null = default
		this._markers = new Map();   // MODEL row -> gutter marker descriptor {type,icon,color,title,pip} (left of numbers)
		this._lineDecos = new Map(); // MODEL row -> CSS class for a full-width body band (e.g. a diff add/remove tint)
		this._changeCbs = [];
		this._suppressInput = false; // true while _writeValue applies an edit (ignore the echoed `input` event)

		// ── Code folding (model + projection) ──
		// The textarea can't hide rows, so folding keeps the FULL text in this._model (the source of truth) and
		// shows only the unfolded lines (the "projection") in the textarea. Public rows/getValue are MODEL space.
		this._model = this.textarea.value;
		this._folds = [];            // [{headerRow, startRow, endRow}] in MODEL rows; startRow===headerRow stays visible
		this._hidden = new Set();    // cached set of hidden MODEL rows (= union of every fold's startRow+1..endRow)
		this._lastProjection = this.textarea.value; // last textarea value we reconciled into the model

		// ── Viewport-virtualization render model (rebuilt only when the projection text changes) ──
		this._renderModelKey = null; // projection text the cached render model was built from (single-slot)
		this._lineToks = null;       // per-VIEW-line token arrays (grouped from _tokenize's flat output)
		this._indents = null;        // per-VIEW-line leading-space count (-1 blank), for indent-guide context
		this._viewToModel = null;    // view-row -> model-row map (for the windowed gutter)
		this._foldMarkRows = new Set(); // VIEW rows that are collapsed fold headers (paint a fold indicator)
		this._renderedFirst = 0;     // currently-painted window [first,last] (view rows), for the scroll moved-check
		this._renderedLast = -1;
		this._painting = false;      // re-entry guard: _paintHighlightWindow ends in _syncScroll
		this._scrollRaf = 0;         // rAF handle coalescing the scroll-driven window repaint
		this._lineHeight = 0;        // cached lineHeight px (refreshed in _autosize / paint), avoids per-scroll getComputedStyle

		// Code, not prose — disable the browser's text-assist features that fight the overlay + suggestions.
		this.textarea.spellcheck = false;
		this.textarea.setAttribute('autocomplete', 'off');
		this.textarea.setAttribute('autocorrect', 'off');
		this.textarea.setAttribute('autocapitalize', 'off');
		if(this.opts.placeholder != null) this.textarea.placeholder = this.opts.placeholder;
		if(this.opts.readOnly) {
			this.textarea.readOnly = true;
			this.el.classList.add('cerb-ui-kataeditor--readonly');
		}

		this._ac = new CerbUI.editorCore.Autocomplete({
			textarea: this.textarea,
			caretAnchor: this.caretAnchor,
			context: this.opts.context,
			editor: this,
			delay: this.opts.autocompleteDelay,
			onScope: (text, caret) => this._scopePathAt(text, caret),
			onItems: (ctx) => {
				const v = this.textarea.value, c = this.textarea.selectionStart;
				// A script tag wins wherever one is open (incl. inside a @text block — scripting is live there).
				const t = CerbUI.editorCore.kataScript.contextAt(v, c);
				if(t) return (t.sub === 'args') ? CerbUI.editorCore.kataScript.suggestArgs(t) : CerbUI.editorCore.kataScript.suggest(t);
				// Otherwise: no suggestions inside a @annotation text block or on a comment line.
				if(this._autocompleteSuppressed(v, c)) return [];
				return (typeof this.opts.onAutocomplete === 'function') ? this.opts.onAutocomplete(ctx) : [];
			},
			onAfterApply: () => this._refresh(),
		});

		this._shortcuts = this._buildShortcuts();

		CerbUI.KataEditor._instances.set(el, this);

		this._onInput = (e) => this._handleInput(e);
		this._onKeydown = (e) => this._handleKeydown(e);
		this._onScroll = () => this._handleScroll();
		this._onBlur = () => { this._ac.clearTimer(); };
		this._onGutterClick = (e) => this._handleGutterClick(e);
		this._onPaste = (e) => this._handlePaste(e);

		this.textarea.addEventListener('input', this._onInput);
		this.textarea.addEventListener('keydown', this._onKeydown);
		this.textarea.addEventListener('paste', this._onPaste);
		this.textarea.addEventListener('scroll', this._onScroll, { passive: true });
		this.textarea.addEventListener('blur', this._onBlur);
		if(this.gutter) this.gutter.addEventListener('click', this._onGutterClick);

		this._rebuildProjection();  // initial render (projection === model while nothing is folded)

		// Core editor-family hook: a caller can add extensible toolbar `sections` to any editor (opt-in via opts.toolbar).
		CerbUI.editorCore.attachToolbar(this, this.opts);

		// Find/Replace (Mod-F) — shared controller + a folding adapter (model⇄projection offset mapping).
		this._find = new CerbUI.editorCore.FindController(this, CerbUI.editorCore.makeFindAdapter(this, 'folding'));
	}

	// ── Public API (mirrors the Ace surface the automation editor depends on) ──

	// The full document (incl. any folded-away lines) — NOT the textarea projection.
	getValue() { return this.textarea ? this._model : ''; }

	setValue(str) {
		if(!this.textarea) return this;
		this._model = str ?? '';
		this._folds = [];                 // a fresh document drops all folds
		this._markers.clear();            // …and all row-keyed gutter markers
		this._lineDecos.clear();          // …and all row-keyed line decorations (diff bands)
		this._rebuildProjection(0);       // a fresh document starts at the top (caret + scroll), like the editor family
		this._fireChange();
		return this;
	}

	focus() { if(this.textarea) this.textarea.focus(); return this; }

	getSelectedText() { return this.textarea.value.slice(this.textarea.selectionStart, this.textarea.selectionEnd); }

	clearSelection() { const c = this.textarea.selectionEnd; this.textarea.setSelectionRange(c, c); return this; }

	// {row, column} both 0-based (like Ace's getCursorPosition). Row is MODEL space.
	getCursorPosition() {
		const caret = this.textarea.selectionStart;
		const before = this.textarea.value.slice(0, caret);
		const viewRow = (before.match(/\n/g) || []).length;
		const column = caret - (before.lastIndexOf('\n') + 1);
		return { row: this._viewRowToModelRow(viewRow), column: column };
	}

	// Ace's gotoLine is 1-based MODEL row, 0-based column. Auto-reveals any fold hiding the target.
	gotoLine(line, column) {
		const mLines = this._modelLines();
		const r = Math.max(0, Math.min((line || 1) - 1, mLines.length - 1));
		this._revealModelRow(r);
		let mOff = 0;
		for(let i = 0; i < r; i++) mOff += mLines[i].length + 1;
		mOff += Math.min(column || 0, mLines[r].length);
		this.textarea.focus();
		const vo = this._modelOffsetToViewOffset(mOff);
		this.textarea.setSelectionRange(vo, vo);
		this.scrollToLine(r);
		return this;
	}

	setCursorPosition(row, column) { return this.gotoLine((row || 0) + 1, column || 0); }

	// row is a MODEL row; reveals it if folded, then scrolls its view row into the top region.
	scrollToLine(row) {
		this._revealModelRow(row);
		const vr = this._modelRowToViewRow(row);
		const cs = window.getComputedStyle(this.textarea);
		const lh = parseFloat(cs.lineHeight) || (parseFloat(cs.fontSize) * 1.5);
		this.textarea.scrollTop = Math.max(0, (vr < 0 ? 0 : vr) * lh);
		this._syncScroll();
		return this;
	}

	// Insert a snippet at the caret; a single `$0` marks where the caret lands (else it goes to the end).
	insertSnippet(text) {
		const ta = this.textarea;
		const s = ta.selectionStart, e = ta.selectionEnd;
		// Accept Ace-format snippets: flatten numbered tab-stops (`${1:default}`/`${1}`/`$1`) to their default
		// text and mark the first as our `$0` caret. Idempotent for callers that already pass `$0`.
		let insert = CerbUI.editorCore.aceSnippetToCerb(String(text == null ? '' : text));
		// Multi-line snippets nest under the caret's current line: shift continuation lines by its indent.
		const lineStart = ta.value.lastIndexOf('\n', s - 1) + 1;
		const indent = (ta.value.slice(lineStart, s).match(/^[ \t]*/) || [''])[0];
		insert = CerbUI.editorCore.indentSnippet(insert, indent);
		let off = insert.length;
		const m = insert.indexOf('$0');
		if(m !== -1) { off = m; insert = insert.slice(0, m) + insert.slice(m + 2); }
		this._setValueAndCaret(ta.value.slice(0, s) + insert + ta.value.slice(e), s + off);
		ta.focus();
		return this;
	}

	// Highlight a MODEL row in the gutter — the hook the run-step line marker / phase-2 error callouts use.
	// Reveals the row if it's hidden inside a fold so the marker is actually visible.
	// Mark a MODEL row "active": a full-width line band in the editor body + a tinted gutter cell. `opts.color` is a
	// Cerb tag color name ('red'|'green'|'blue'|'orange'|'purple'|'gray'); omitted = the component default accent.
	highlightLine(row, opts) { this._highlightRow = row; this._highlightColor = (opts && opts.color) || null; this._revealModelRow(row); this._renderActiveLineBand(); this._renderGutter(); return this; }
	clearHighlight() { this._highlightRow = null; this._highlightColor = null; this._renderActiveLineBand(); this._renderGutter(); return this; }

	// The active-line background band, painted BEHIND the colored mirror text (z-index:-1 inside --highlight, which
	// is the scroll-synced overlay, so the band tracks both axes of scroll). Recreated on each highlight repaint
	// because _renderHighlight() replaces the mirror's innerHTML. Positioned by VIEW row (folds shift rows).
	_renderActiveLineBand() {
		if(!this.highlight) return;
		const prev = this.highlight.querySelector('.cerb-ui-kataeditor--active-line');
		if(prev) prev.remove();
		if(this._highlightRow == null) return;
		const vr = this._modelRowToViewRow(this._highlightRow);
		if(vr < 0) return;                                  // hidden inside a collapsed fold
		const cs = window.getComputedStyle(this.textarea);
		const lh = parseFloat(cs.lineHeight) || (parseFloat(cs.fontSize) * 1.5);
		const padTop = parseFloat(cs.paddingTop) || 0;
		const band = document.createElement('div');
		band.className = 'cerb-ui-kataeditor--active-line';
		band.style.top = (padTop + vr * lh) + 'px';
		band.style.height = lh + 'px';
		// Span the full scrollable content width, not just the client width (the CSS right:0 clamps to the
		// padding box, so a horizontally scrolled long line would run past the band's tint).
		band.style.width = 'max(100%, ' + this.textarea.scrollWidth + 'px)';
		if(this._highlightColor)
			band.style.setProperty('--cerb-ui-kataeditor-active-accent', 'var(--cerb-color-tag-' + this._highlightColor + ')');
		this.highlight.appendChild(band);
	}

	// ── Gutter markers (LEFT of the line numbers; MODEL-space) ──
	// Host-driven per-line marks: parse errors/warnings (KATA `_line` metadata), a run cursor / `await:`
	// continuation, a debugging breakpoint, etc. A marker is an icon (`cerb-icon-<icon>`) or a colored `pip`
	// dot. `type` picks a default icon+color (overridable); `title` is the hover tooltip. One marker per row.
	setMarker(modelRow, desc) {
		desc = desc || {};
		const preset = CerbUI.KataEditor._MARKER_TYPES[desc.type] || {};
		this._markers.set(modelRow, {
			type:  desc.type || null,
			pip:   (desc.pip != null) ? !!desc.pip : !!preset.pip,
			icon:  desc.icon || preset.icon || null,
			color: desc.color || preset.color || null,
			title: desc.title || '',
		});
		this._renderGutter();
		return this;
	}
	clearMarker(modelRow) { if(this._markers.delete(modelRow)) this._renderGutter(); return this; }
	clearMarkers() { if(this._markers.size) { this._markers.clear(); this._renderGutter(); } return this; }
	getMarkers() { return new Map(this._markers); }

	// ── Line decorations (full-width body bands; MODEL rows) ──
	// Replace the whole set of line bands in one shot: a Map or plain object of MODEL row -> CSS class. Each row
	// gets a tinted full-width band behind its text (the class supplies the color). CerbUI.DiffViewer drives this
	// to paint add/remove lines; it's deliberately generic (any host could tint matched/error lines).
	setLineDecorations(map) {
		this._lineDecos = new Map();
		if(map instanceof Map) { for(const [k, v] of map) this._lineDecos.set(k | 0, v); }
		else if(map && typeof map === 'object') { for(const k in map) this._lineDecos.set(parseInt(k, 10), map[k]); }
		this._renderLineDecorations();
		return this;
	}
	clearLineDecorations() { if(this._lineDecos.size) { this._lineDecos.clear(); this._renderLineDecorations(); } return this; }

	onChange(cb) { if(typeof cb === 'function') this._changeCbs.push(cb); return this; }

	openAutocomplete() { this._ac.trigger(); return this; }

	// The enumerable shortcut list with OS-appropriate labels — for a future keyboard-shortcuts hint popup.
	getShortcuts() {
		const keys = CerbUI.editorCore.keys;
		return this._shortcuts.map(sc => ({ id: sc.id, label: sc.label, keys: sc.keys.map(k => keys.label(k)) }));
	}

	// ── Code folding (public API; all rows are MODEL space) ──────────────
	// A fold collapses a `key:` header's indented subtree: the header row stays visible, rows startRow+1..endRow
	// are hidden. Fold ops don't change the document text, so they DON'T fire onChange.

	isFolded(modelRow) { return this._folds.some(f => f.startRow === modelRow); }

	fold(modelRow) {
		if(this.isFolded(modelRow)) return this;
		const range = this._foldableRanges().find(r => r.headerRow === modelRow);
		if(!range) return this;
		const caretM = this._viewOffsetToModelOffset(this.textarea.value, this.textarea.selectionStart);
		this._folds.push({ headerRow: range.headerRow, startRow: range.startRow, endRow: range.endRow });
		this._folds.sort((a, b) => a.startRow - b.startRow);
		this._rebuildProjection(caretM);
		return this;
	}

	unfold(modelRow) {
		const before = this._folds.length;
		this._folds = this._folds.filter(f => !(f.startRow === modelRow || (modelRow > f.startRow && modelRow <= f.endRow)));
		if(this._folds.length === before) return this;
		const caretM = this._viewOffsetToModelOffset(this.textarea.value, this.textarea.selectionStart);
		this._rebuildProjection(caretM);
		return this;
	}

	toggleFold(modelRow) { return this.isFolded(modelRow) ? this.unfold(modelRow) : this.fold(modelRow); }

	foldAll() {
		const caretM = this._viewOffsetToModelOffset(this.textarea.value, this.textarea.selectionStart);
		this._folds = this._foldableRanges().map(r => ({ headerRow: r.headerRow, startRow: r.startRow, endRow: r.endRow }));
		this._folds.sort((a, b) => a.startRow - b.startRow);
		this._rebuildProjection(caretM);
		return this;
	}

	unfoldAll() {
		if(!this._folds.length) return this;
		const caretM = this._viewOffsetToModelOffset(this.textarea.value, this.textarea.selectionStart);
		this._folds = [];
		this._rebuildProjection(caretM);
		return this;
	}

	// The raw KATA key-path at the caret (segments include their trailing ':', and any /id or @annotations) —
	// a plain-string port of Devblocks.cerbCodeEditor.getKataTokenPath. Runs over the projection: all ancestors
	// of a VISIBLE caret are themselves visible (a fold only hides a header's deeper descendants), so the
	// projection yields the same ancestor chain as the model would.
	getTokenPath() { return this._scopePathAt(this.textarea.value, this.textarea.selectionStart).path; }

	// 0-based row of a colon-joined path (e.g. 'series/s0:metric:'), or false — port of getKataRowByPath.
	getRowByPath(pathStr) {
		if(typeof pathStr !== 'string') return false;
		let p = pathStr;
		if(p.endsWith(':')) p = p.slice(0, -1);
		const path = p.split(':');
		const lines = this._modelLines();
		const stack = [];
		let depth = 0, indent = '', matches = 0;

		for(let row = 0; row < lines.length; row++) {
			const m = lines[row].match(/^(\s*)([^\s#][^:]*):/);
			if(!m) continue;

			let tag = m[2];
			const tagIndent = m[1];
			const annPos = tag.indexOf('@');
			if(annPos !== -1) tag = tag.slice(0, annPos);

			if(tagIndent.length > indent.length) {
				depth++; stack.push(tagIndent); indent = tagIndent;
			} else if(tagIndent.length < indent.length) {
				while(stack.length > 0 && tagIndent.length < indent.length) {
					indent = stack.pop(); depth--;
					if(indent.length === tagIndent.length) { stack.push(tagIndent); depth++; }
					if(depth === 0) indent = '';
				}
			}

			if(path.hasOwnProperty(depth) && tag === path[depth]) {
				if(matches === depth) {
					matches++;
					if(path.length === matches) return row;
				}
			}
		}
		return false;
	}

	getLine(row) { const l = this._modelLines(); return (row >= 0 && row < l.length) ? l[row] : ''; }

	destroy() {
		if(this._editorToolbar && typeof this._editorToolbar.destroy === 'function') this._editorToolbar.destroy();
		if(this._find) this._find.destroy();
		this._ac.destroy();
		CerbUI.KataEditor._instances.delete(this.el);
		if(this.textarea) {
			this.textarea.removeEventListener('input', this._onInput);
			this.textarea.removeEventListener('keydown', this._onKeydown);
			this.textarea.removeEventListener('paste', this._onPaste);
			this.textarea.removeEventListener('scroll', this._onScroll);
			this.textarea.removeEventListener('blur', this._onBlur);
		}
		if(this.gutter && this._onGutterClick) this.gutter.removeEventListener('click', this._onGutterClick);
		if(this._revealDisposer) { this._revealDisposer(); this._revealDisposer = null; }
		if(this._scrollRaf) { cancelAnimationFrame(this._scrollRaf); this._scrollRaf = 0; }
	}

	// ── Keyboard shortcuts (abstract, enumerable registry) ──────────────
	// Each descriptor: { id, keys:[spec…], label, menu, run(e) }. `keys` are editorCore.keys binding specs
	// ('Mod' = Cmd OR Ctrl). `menu` decides how it coordinates with an open autocomplete menu: 'close' dismisses
	// it first (structural edits), 'open' leaves the controller to react (autocomplete). Menu-navigation keys
	// (plain ↑/↓, Enter, Escape) stay imperative in _handleKeydown — they branch on menu state, not commands.

	_buildShortcuts() {
		// Read-only editors only fold/resize — never mutate text or pop autocomplete.
		const fold = [
			{ id:'fold',         keys:['Mod-BracketLeft'],  label:'Fold',          menu:'close', run:() => this._foldAtCaret() },
			{ id:'unfold',       keys:['Mod-BracketRight'], label:'Unfold',        menu:'close', run:() => this._unfoldAtCaret() },
			{ id:'growEditor',   keys:['Mod-Shift-ArrowDown'], label:'Taller editor',  menu:'close', run:() => this._resizeMaxLines(1) },
			{ id:'shrinkEditor', keys:['Mod-Shift-ArrowUp'],   label:'Shorter editor', menu:'close', run:() => this._resizeMaxLines(-1) },
		];
		const edit = [
			{ id:'deleteLine',   keys:['Mod-D','Alt-D'],  label:'Delete line',     menu:'close', run:() => this._deleteLine() },
			{ id:'moveLineUp',   keys:['Alt-ArrowUp'],    label:'Move line up',    menu:'close', run:() => this._moveLine(-1) },
			{ id:'moveLineDown', keys:['Alt-ArrowDown'],  label:'Move line down',  menu:'close', run:() => this._moveLine(1) },
			{ id:'indent',       keys:['Tab'],            label:'Indent',          menu:'close', run:() => this._indent() },
			{ id:'dedent',       keys:['Shift-Tab'],      label:'Dedent',          menu:'close', run:() => this._dedent() },
			{ id:'toggleComment',keys:['Mod-Slash'],      label:'Toggle comment',  menu:'close', run:() => this._toggleComment() },
			{ id:'autocomplete', keys:['Mod-Space'],      label:'Show suggestions',menu:'open',  run:() => this._ac.trigger() },
		];
		// Find is available even in readOnly editors (replace is gated separately).
		const find = [
			{ id:'find', keys:['Mod-F'], label:'Find', menu:'close', run:() => this._find.open() },
		];
		const list = (this.opts.readOnly ? fold : edit.concat(fold)).concat(find);
		const keys = CerbUI.editorCore.keys;
		for(const sc of list) sc._parsed = sc.keys.map(k => keys.parse(k));
		return list;
	}

	// Run the first matching shortcut; returns true if one handled the event (caller returns early).
	_dispatchShortcut(e) {
		const keys = CerbUI.editorCore.keys;
		for(const sc of this._shortcuts) {
			if(!sc._parsed.some(p => keys.matchEvent(p, e))) continue;
			e.preventDefault();
			if(this._ac.isOpen()) {
				e.stopPropagation();           // never let the open Menu also act on this key
				if(sc.menu === 'close') this._ac.close();
			}
			sc.run(e);
			return true;
		}
		return false;
	}

	// Click a gutter chevron to toggle the fold on that header row (one delegated listener).
	_handleGutterClick(e) {
		const chev = e.target.closest('.cerb-ui-kataeditor--gutter-fold');
		if(chev) {
			const mr = parseInt(chev.getAttribute('data-fold-row'), 10);
			if(!isNaN(mr)) this.toggleFold(mr);
			return;
		}
		// Clicking the LEFT marker column: a cerb: URI marker opens that record's peek; otherwise it fires
		// onGutterClick (e.g. toggle a breakpoint), empty slots included.
		const mk = e.target.closest('.cerb-ui-kataeditor--gutter-marker');
		if(mk) {
			const uri = mk.getAttribute('data-uri');
			if(uri) { this._openUri(uri); return; }
			if(typeof this.opts.onGutterClick === 'function') {
				const mr = parseInt(mk.getAttribute('data-model-row'), 10);
				if(!isNaN(mr)) this.opts.onGutterClick(mr, e);
			}
		}
	}

	// ⌘/Ctrl+[ — fold the innermost foldable block at/containing the caret.
	_foldAtCaret() {
		const mr = this.getCursorPosition().row;
		const ranges = this._foldableRanges();
		let target = ranges.find(r => r.headerRow === mr);
		if(!target) {
			const containing = ranges.filter(r => mr > r.startRow && mr <= r.endRow);
			if(containing.length) target = containing.reduce((a, b) => (b.startRow > a.startRow ? b : a)); // innermost
		}
		if(target) this.fold(target.headerRow);
	}

	// ⌘/Ctrl+] — unfold the collapsed block at the caret row.
	_unfoldAtCaret() {
		const mr = this.getCursorPosition().row;
		if(this.isFolded(mr)) this.unfold(mr);
	}

	// ── Input / keyboard ────────────────────────────────────────────────

	// A multi-line paste is a block of content, not authoring a token — flag it so the following `input` event
	// doesn't pop suggestions at the end of the pasted block. Single-line pastes still suggest (like typing).
	_handlePaste(e) {
		const text = (e.clipboardData || window.clipboardData) ? (e.clipboardData || window.clipboardData).getData('text') : '';
		this._pasteIsBlock = /[\r\n]/.test(text);
	}

	_handleInput(e) {
		// Our own programmatic writes (_writeValue) re-emit an `input` event via execCommand; ignore it — the
		// command that called _setValueAndCaret already drives the re-render (and decides about suggestions).
		if(this._suppressInput) return;
		// Convert any pasted tabs to spaces so the stored value is always spaces (_sanitizeTabs fully refreshes).
		if(this.textarea.value.indexOf('\t') !== -1) { this._sanitizeTabs(); return; }
		this._applyProjectionEditToModel();  // fold the edit back into the full-text model
		this._renderHighlight();
		this._autosize();
		this._renderGutter();
		this._scrollCaretIntoView();
		this._fireChange();
		this._ac.clearTimer();
		// Undo/redo reverts text — the user isn't authoring, so don't pop suggestions (but still re-render above).
		const it = e && e.inputType;
		if(it === 'historyUndo' || it === 'historyRedo') return;
		// Backspace/Delete shouldn't *pop* a fresh menu (esp. when deleting indentation whitespace). But if a menu
		// is already open, re-evaluate it at the new caret — schedule()→trigger() recomputes items and auto-closes
		// when nothing matches, so a stale suggestion can't linger. ⌘/Ctrl+Space still forces the menu.
		if(it === 'deleteContentBackward' || it === 'deleteContentForward') {
			if(this._ac.isOpen()) this._ac.schedule();
			return;
		}
		// Pasting a multi-line block isn't authoring a token — don't pop suggestions at the end of it.
		if(it === 'insertFromPaste' && this._pasteIsBlock) { this._pasteIsBlock = false; return; }
		// Otherwise always schedule — KataScript autocomplete is built in even without a KATA `onAutocomplete`
		// source; the controller's onItems decides whether there's anything to show.
		this._ac.schedule();
	}

	_handleKeydown(e) {
		const menuOpen = this._ac.isOpen();

		// Registry shortcuts run first (delete/move line, indent/dedent, ⌘/Ctrl+Space). Each is modifier-bearing
		// or Tab, so none collides with the menu's PLAIN ↑/↓ navigation handled below; ⌥+↑/↓ thus wins over the
		// "modified arrow closes menu + native caret move" branch — the precedence move-line needs.
		if(this._dispatchShortcut(e)) return;

		if(menuOpen) {
			const plain = !(e.metaKey || e.ctrlKey || e.altKey);
			// ↓ engages the menu (and, once engaged, steps down it). ↑ only steps the menu if the user has already
			// arrowed in — otherwise an unsolicited menu would hijack the first ↑; instead it's a normal "up a line"
			// that dismisses the suggestions (handled by the text-navigation branch below).
			if(plain && e.key === 'ArrowDown') {
				e.preventDefault();
				e.stopPropagation();
				this._ac.moveSelection(+1);
				return;
			}
			if(plain && e.key === 'ArrowUp' && this._ac.navigated) {
				e.preventDefault();
				e.stopPropagation();
				this._ac.moveSelection(-1);
				return;
			}
			// Any other arrow (modified ↑/↓, plain ←/→, or an un-engaged plain ↑) is text navigation — dismiss +
			// native caret move.
			if(e.key === 'ArrowDown' || e.key === 'ArrowUp' || e.key === 'ArrowLeft' || e.key === 'ArrowRight') {
				e.stopPropagation();
				this._ac.close();
				return;
			}
			if(e.key === 'Enter') {
				if(this._ac.navigated) { e.preventDefault(); e.stopPropagation(); this._ac.acceptSelection(e); return; }
				// Not navigated: Enter inserts a newline. Stop the Menu from selecting a hover-highlighted item.
				e.preventDefault();
				e.stopPropagation();
				this._ac.close();
				this._insertNewline();
				return;
			}
			if(e.key === 'Escape') {
				e.preventDefault();
				e.stopPropagation();
				this._ac.close();
				return;
			}
			// other keys fall through (type a char -> input handler re-suggests)
		}

		// Enter (menu closed) — newline with KATA auto-indent.
		if(!this.opts.readOnly && e.key === 'Enter') {
			e.preventDefault();
			this._insertNewline();
		}
	}

	_setValueAndCaret(value, selStart, selEnd) {
		this._writeValue(value);
		const ta = this.textarea;
		ta.selectionStart = selStart;
		ta.selectionEnd = (selEnd == null) ? selStart : selEnd;
		this._refresh();
	}

	// Apply a new full value while PRESERVING the textarea's native undo/redo stack, so ⌘/Ctrl+Z (and redo)
	// keep working after our programmatic edits (move/delete line, indent, snippet…). A direct `ta.value = …`
	// wipes that stack — leaving Cmd+Z to fall through to the browser. We instead replace only the changed span
	// via execCommand, which records a proper undo entry. Falls back to a direct write if execCommand is
	// unavailable or refuses (e.g. the textarea isn't focused).
	_writeValue(value) {
		const ta = this.textarea;
		const old = ta.value;
		if(old === value) return;

		// Minimal diff: shared prefix p, shared suffix; replace old[p..so) with value[p..sn).
		let p = 0; const max = Math.min(old.length, value.length);
		while(p < max && old[p] === value[p]) p++;
		let so = old.length, sn = value.length;
		while(so > p && sn > p && old[so - 1] === value[sn - 1]) { so--; sn--; }
		const insert = value.slice(p, sn);

		// execCommand('insertText') is pathologically slow on large spans (a Replace-All across a 9k-line doc
		// hangs for a minute). Above this threshold, write directly — fast, at the cost of native undo for this
		// one bulk op. The caller sets the caret + calls _refresh(), so no input event / render is needed here.
		const BIG_EDIT = 10000;
		if(Math.max(so - p, insert.length) > BIG_EDIT) { ta.value = value; return; }

		let ok = false;
		this._suppressInput = true;   // execCommand re-emits `input` synchronously; the caller drives the refresh
		try {
			ta.focus();
			ta.setSelectionRange(p, so);
			ok = (insert.length === 0)
				? document.execCommand('delete')          // pure deletion (selection -> removed)
				: document.execCommand('insertText', false, insert);
		} catch(_) {}
		this._suppressInput = false;
		if(!ok || ta.value !== value) ta.value = value;   // fallback: correctness over undo
	}

	_refresh() {
		this._applyProjectionEditToModel();  // any projection change (programmatic edit, autocomplete apply) -> model
		this._renderHighlight();
		this._autosize();
		this._renderGutter();
		this._scrollCaretIntoView();
		this._fireChange();
	}

	// Keep the caret line visible once the editor is in scroll mode (content past maxLines). Needed because we
	// edit by setting textarea.value programmatically (newlines, snippet inserts) — which gets no native
	// caret-into-view scroll — and _autosize's `height:auto` measuring pass resets scrollTop to 0.
	_scrollCaretIntoView() {
		const ta = this.textarea;
		const cs = window.getComputedStyle(ta);
		const lh = parseFloat(cs.lineHeight) || (parseFloat(cs.fontSize) * 1.5);
		const padTop = parseFloat(cs.paddingTop) || 0;
		const padBottom = parseFloat(cs.paddingBottom) || 0;
		const before = ta.value.slice(0, ta.selectionStart);
		const row = (before.match(/\n/g) || []).length;  // VIEW row (pixel math is on the projection)
		const lineTop = padTop + row * lh;
		const lineBottom = lineTop + lh;
		if(lineTop < ta.scrollTop)
			ta.scrollTop = lineTop - padTop;
		else if(lineBottom > ta.scrollTop + ta.clientHeight)
			ta.scrollTop = lineBottom - ta.clientHeight + padBottom;
		this._syncScroll();
	}

	_fireChange() {
		if(this._valueField) this._valueField.value = this._model;   // keep the hidden form carrier = full document
		for(const cb of this._changeCbs) { try { cb(this.getValue()); } catch(_) {} }
	}

	// Tab key: insert tabSize spaces at the caret, or indent every line touched by the selection. As a special
	// case, when nothing is selected and the caret sits at end-of-line with text behind it to complete, Tab asks
	// for autocomplete suggestions instead of indenting (Tab-to-complete).
	_indent() {
		this._revealForEdit();
		const ta = this.textarea, v = ta.value, s = ta.selectionStart, e = ta.selectionEnd;
		if(s === e) {
			const lineStart = v.lastIndexOf('\n', s - 1) + 1;
			const nl = v.indexOf('\n', s);
			const atEol = (nl === -1) ? (s === v.length) : (s === nl);
			if(atEol && v.slice(lineStart, s).trim().length > 0) { this._ac.trigger(); return; }
			this._setValueAndCaret(v.slice(0, s) + this.tab + v.slice(e), s + this.tab.length);
			this._ac.clearTimer();
			return;
		}
		const lineStart = v.lastIndexOf('\n', s - 1) + 1;
		const block = v.slice(lineStart, e);
		const newBlock = block.replace(/^/gm, this.tab);
		const delta = newBlock.length - block.length;
		this._setValueAndCaret(v.slice(0, lineStart) + newBlock + v.slice(e), s + this.tab.length, e + delta);
	}

	// Shift+Tab: strip up to tabSize leading spaces (or one tab) from each line touched by the selection.
	_dedent() {
		this._revealForEdit();
		const ta = this.textarea, v = ta.value, s = ta.selectionStart, e = ta.selectionEnd;
		const lineStart = v.lastIndexOf('\n', s - 1) + 1;
		let endPos = e;
		if(s === e) { const nl = v.indexOf('\n', s); endPos = (nl === -1) ? v.length : nl; }
		const before = v.slice(0, lineStart);
		const block = v.slice(lineStart, endPos);
		const after = v.slice(endPos);
		const re = new RegExp('^( {1,' + this.opts.tabSize + '}|\\t)');
		let firstRemoved = 0, totalRemoved = 0;
		const out = block.split('\n').map((ln, i) => {
			const m = ln.match(re);
			if(m) { const r = m[0].length; if(i === 0) firstRemoved = r; totalRemoved += r; return ln.slice(r); }
			return ln;
		}).join('\n');
		const newS = Math.max(lineStart, s - firstRemoved);
		this._setValueAndCaret(before + out + after, newS, (s === e) ? newS : (e - totalRemoved));
	}

	// ⌘/ (Ctrl+/): block-comment toggle over the lines the selection touches. Comments are KATA lines whose first
	// non-space char is '#' (see the tokenizer). If every non-blank line is already commented we uncomment, else we
	// comment; blank lines are left alone. The whole affected line range is re-selected so a repeated ⌘/ keeps
	// toggling the same block.
	_toggleComment() {
		this._ac.clearTimer();
		this._revealForEdit();
		const ta = this.textarea, v = ta.value, s = ta.selectionStart, e = ta.selectionEnd;
		const lineStart = v.lastIndexOf('\n', s - 1) + 1;
		const nl = v.indexOf('\n', e);
		const endPos = (nl === -1) ? v.length : nl;
		const lines = v.slice(lineStart, endPos).split('\n');

		const nonBlank = lines.filter(ln => ln.trim().length > 0);
		if(nonBlank.length === 0) return;
		const uncomment = nonBlank.every(ln => ln.trimStart().charAt(0) === '#');

		const out = lines.map(ln => {
			if(ln.trim().length === 0) return ln;                      // leave blank lines untouched
			const indentLen = ln.length - ln.trimStart().length;
			const indent = ln.slice(0, indentLen), rest = ln.slice(indentLen);
			if(uncomment) return indent + rest.replace(/^#[ ]?/, '');  // drop '#' + at most one space
			return indent + '# ' + rest;
		}).join('\n');

		const next = v.slice(0, lineStart) + out + v.slice(endPos);

		if(s === e) {
			// No selection: keep a collapsed caret on the same text instead of selecting the line. The edit is at
			// the line's indent column, so a caret in the content shifts by the line's length delta; one sitting in
			// the leading whitespace stays put.
			const oldLine = lines[0], newLine = out.split('\n', 1)[0];
			const indentLen = oldLine.length - oldLine.trimStart().length;
			const col = s - lineStart;
			const newCol = (col <= indentLen) ? col : Math.max(indentLen, col + (newLine.length - oldLine.length));
			this._setValueAndCaret(next, lineStart + newCol);
			return;
		}
		// Selection: re-select the whole affected line range so a repeated ⌘/ keeps toggling the same block.
		this._setValueAndCaret(next, lineStart, lineStart + out.length);
	}

	// Enter: newline, copying the current line's indent (and one extra level if it's a childless `key:`).
	_insertNewline() {
		this._ac.clearTimer(); // a queued (pre-newline) suggestion must not pop a menu after we move lines
		this._revealForEdit();
		const ta = this.textarea, v = ta.value, s = ta.selectionStart, e = ta.selectionEnd;
		const lineStart = v.lastIndexOf('\n', s - 1) + 1;
		const curBeforeCaret = v.slice(lineStart, s);
		let indent = (curBeforeCaret.match(/^[ ]*/) || [''])[0];
		if(/:\s*$/.test(curBeforeCaret)) indent += this.tab; // a key with no inline value -> indent its child
		const ins = '\n' + indent;
		this._setValueAndCaret(v.slice(0, s) + ins + v.slice(e), s + ins.length);
		if(typeof this.opts.onAutocomplete === 'function') this._ac.schedule();
	}

	// ⌘/Ctrl/⌥+D — delete the whole line the caret sits on, regardless of column.
	_deleteLine() {
		this._ac.clearTimer();
		this._revealForEdit();
		const ta = this.textarea, v = ta.value, s = ta.selectionStart;
		// s>0 guard: lastIndexOf('\n', -1) clamps to index 0 and matches a leading '\n', mis-placing
		// lineStart on an empty first line (caret at 0) and making the delete a no-op.
		const lineStart = s > 0 ? v.lastIndexOf('\n', s - 1) + 1 : 0;
		const lineEnd = v.indexOf('\n', s);
		let cutStart, cutEnd;
		if(lineEnd === -1) { cutStart = lineStart > 0 ? lineStart - 1 : 0; cutEnd = v.length; } // last line: eat preceding \n
		else { cutStart = lineStart; cutEnd = lineEnd + 1; }                                    // else: line + trailing \n
		const next = v.slice(0, cutStart) + v.slice(cutEnd);
		// Keep the column on the line that slides into the slot (clamped to its length).
		const newLineStart = (cutStart === lineStart) ? lineStart : (v.lastIndexOf('\n', cutStart - 1) + 1);
		const col = s - lineStart;
		const afterNl = next.indexOf('\n', newLineStart);
		const lineLen = (afterNl === -1 ? next.length : afterNl) - newLineStart;
		this._setValueAndCaret(next, newLineStart + Math.min(col, lineLen));
	}

	// ⌘/Ctrl+↑ / ↓ — move the current line (or selected line-block) up/down, keeping the selection on it.
	_moveLine(dir) {
		this._ac.clearTimer();
		this._revealForEdit();
		const ta = this.textarea, v = ta.value, s = ta.selectionStart, e = ta.selectionEnd;
		const rows = v.split('\n');

		// Row range [r0, r1] the selection touches; a selection ending at column 0 doesn't pull in that row.
		const r0 = (v.slice(0, s).match(/\n/g) || []).length;
		let r1 = (v.slice(0, e).match(/\n/g) || []).length;
		if(e > s && e === (v.lastIndexOf('\n', e - 1) + 1)) r1--;
		if(r1 < r0) r1 = r0;

		if(dir < 0 && r0 === 0) return;                  // first line can't move up
		if(dir > 0 && r1 === rows.length - 1) return;    // last line can't move down

		const block = rows.splice(r0, r1 - r0 + 1);
		rows.splice(r0 + dir, 0, ...block);
		const next = rows.join('\n');

		const offsetOfRow = (arr, row) => { let o = 0; for(let i = 0; i < row; i++) o += arr[i].length + 1; return o; };
		const shift = offsetOfRow(rows, r0 + dir) - offsetOfRow(v.split('\n'), r0);
		this._setValueAndCaret(next, s + shift, e + shift);
	}

	_sanitizeTabs() {
		const ta = this.textarea, v = ta.value, caret = ta.selectionStart;
		const tabsBefore = (v.slice(0, caret).match(/\t/g) || []).length;
		const nc = caret + tabsBefore * (this.opts.tabSize - 1);
		this._setValueAndCaret(v.replace(/\t/g, this.tab), nc);
	}

	// ── Highlighting + gutter + sizing ──────────────────────────────────

	_renderHighlight() {
		this._buildRenderModel();
		this._paintHighlightWindow();
	}

	// Build (+cache) the per-view-line render model from the current projection text: grouped line tokens, leading
	// indent depths, the view→model row map, and the collapsed-header view-row set. O(doc) but cheap (no DOM);
	// rebuilt only when the projection text changes, so scrolling reuses it. Tokenizing the WHOLE doc here is what
	// keeps multi-line state (open {{ }}/{% %} tags, @text blocks) correct no matter which rows we later paint.
	_buildRenderModel() {
		const proj = this.textarea.value;
		if(this._renderModelKey === proj && this._lineToks) return;
		this._renderModelKey = proj;

		const flat = this._tokenize(proj);
		const lineToks = [[]];                              // group the flat stream on its standalone '\n' tokens
		for(const t of flat) {
			if(t.type === 'text' && t.value === '\n') { lineToks.push([]); continue; }
			lineToks[lineToks.length - 1].push(t);
		}
		this._lineToks = lineToks;

		this._indents = proj.split('\n').map(s => { let i = 0; while(i < s.length && s[i] === ' ') i++; return (i === s.length) ? -1 : i; });

		const v2m = [], total = this._modelLines().length;
		for(let mr = 0; mr < total; mr++) if(!this._hidden.has(mr)) v2m.push(mr);
		this._viewToModel = v2m;

		this._foldMarkRows = new Set();
		for(const f of this._folds) { const vr = this._modelRowToViewRow(f.startRow); if(vr >= 0) this._foldMarkRows.add(vr); }
	}

	// Reconstruct the flat token stream (with '\n' separators) from the cached per-line groups — equals
	// _tokenize(proj) exactly, so the legacy fold-mark + indent-guide passes produce identical output.
	_flatTokens() {
		const out = [];
		for(let r = 0; r < this._lineToks.length; r++) {
			if(r > 0) out.push({ type: 'text', value: '\n' });
			for(const t of this._lineToks[r]) out.push(t);
		}
		return out;
	}

	// The tokens for one VIEW row WITH indent guides injected (the per-line equivalent of _injectIndentGuides):
	// blank lines get phantom guides at the surrounding depth; indented lines peel their leading whitespace into
	// guide tokens. Used by the windowed painter (the full-render path keeps using _injectIndentGuides).
	_lineTokensWithGuides(row) {
		const base = this._lineToks[row] || [];
		const tab = this.opts.tabSize;
		if(!this.opts.indentGuides || tab <= 0) return base;
		if(base.length === 0) {                             // truly-empty line — phantom guides at surrounding depth
			const d = this._blankDepth(row, this._indents);
			return (d >= tab) ? this._guides(d) : base;
		}
		// Peel leading whitespace from the first token (matches _injectIndentGuides: drives off the token's own
		// spaces, so an all-spaces line peels its real width rather than a phantom neighbor depth).
		const v = base[0].value;
		let sp = 0; while(sp < v.length && v[sp] === ' ') sp++;
		if(sp >= tab) {
			const out = this._guides(sp);
			const rest = v.slice(sp);
			if(rest.length) out.push({ type: base[0].type, value: rest });
			for(let i = 1; i < base.length; i++) out.push(base[i]);
			return out;
		}
		return base;
	}

	// Shared indent-guide helpers (single source for the full + windowed paths).
	_blankDepth(row, indents) {
		let p = 0, n = 0;
		for(let r = row - 1; r >= 0; r--) if(indents[r] >= 0) { p = indents[r]; break; }
		for(let r = row + 1; r < indents.length; r++) if(indents[r] >= 0) { n = indents[r]; break; }
		return Math.max(p, n);
	}
	_guides(depth) {
		const tab = this.opts.tabSize, out = [];
		const levels = Math.floor(depth / tab);
		for(let i = 0; i < levels; i++) out.push({ type: 'indent-guide', value: ' '.repeat(tab) });
		const rem = depth - levels * tab;
		if(rem > 0) out.push({ type: 'text', value: ' '.repeat(rem) });
		return out;
	}

	// The visible viewport height, CLAMPED to maxLines so a not-yet-autosized (momentarily full-height) textarea
	// doesn't compute a window spanning the whole doc on first paint.
	_viewportHeight(lh) {
		const ch = this.textarea.clientHeight || 0;
		return Math.min(ch, this.opts.maxLines * lh);
	}

	// Paint the mirror. Small docs (<= _VIRTUALIZE_MIN_ROWS): render every row (today's exact path — legacy
	// fold-marks + indent-guides over the flat stream). Large docs: render only the [first,last] view-row window
	// + OVERSCAN, framed by block spacer <div>s so the mirror's content height stays = full-doc height and
	// `highlight.scrollTop = textarea.scrollTop` keeps the window aligned. Re-adds the active-line / line-deco /
	// find bands (absolute children, unaffected by the spacers).
	_paintHighlightWindow() {
		const ta = this.textarea, hl = this.highlight;
		const cs = window.getComputedStyle(ta);
		const lh = parseFloat(cs.lineHeight) || (parseFloat(cs.fontSize) * 1.5);
		this._lineHeight = lh;
		const vrc = this._lineToks.length;
		const TOK = CerbUI.KataEditor._TOK_CLASS;
		this._painting = true;

		if(vrc <= CerbUI.KataEditor._VIRTUALIZE_MIN_ROWS) {
			const toks = this._injectIndentGuides(this._injectFoldMarks(this._flatTokens()));
			hl.innerHTML = CerbUI.editorCore.tokensToHtml(toks, TOK);
			this._renderedFirst = 0; this._renderedLast = vrc - 1;
		} else {
			const win = CerbUI.editorCore.computeWindow(ta.scrollTop, this._viewportHeight(lh), lh, vrc, CerbUI.KataEditor._OVERSCAN);
			const winToks = [];
			for(let r = win.first; r <= win.last; r++) {
				if(r > win.first) winToks.push({ type: 'text', value: '\n' });
				const lt = this._lineTokensWithGuides(r);
				for(const t of lt) winToks.push(t);
				if(this._foldMarkRows.has(r)) winToks.push({ type: 'foldmark', value: '' });
			}
			const top = win.first * lh, bottom = (vrc - 1 - win.last) * lh;
			let html = '';
			if(top > 0) html += '<div class="cerb-ui-kataeditor--vspace" style="height:' + top + 'px"></div>';
			html += CerbUI.editorCore.tokensToHtml(winToks, TOK);
			if(bottom > 0) html += '<div class="cerb-ui-kataeditor--vspace" style="height:' + bottom + 'px"></div>';
			hl.innerHTML = html;
			this._renderedFirst = win.first; this._renderedLast = win.last;
		}

		this._renderActiveLineBand();    // re-add the band (the innerHTML write wiped the mirror)
		this._renderLineDecorations();   // …and any full-width line decorations (diff add/remove tints)
		if(this._find) this._find.repaintBands();   // …and any find-match bands (guarded: runs during construction too)
		this._syncScroll();
		this._painting = false;
	}

	// Full-width body bands for a set of MODEL rows, each carrying a caller-supplied CSS class — painted BEHIND
	// the mirror text like the active-line band (z-index:-1 inside --highlight, so they track scroll). Re-applied
	// on every _renderHighlight (renderTokens wipes the mirror). Used by CerbUI.DiffViewer for add/remove tints.
	_renderLineDecorations() {
		if(!this.highlight) return;
		this.highlight.querySelectorAll('.cerb-ui-kataeditor--line-deco').forEach(n => n.remove());
		if(!this._lineDecos || !this._lineDecos.size) return;
		const cs = window.getComputedStyle(this.textarea);
		const lh = parseFloat(cs.lineHeight) || (parseFloat(cs.fontSize) * 1.5);
		const padTop = parseFloat(cs.paddingTop) || 0;
		const width = 'max(100%, ' + this.textarea.scrollWidth + 'px)';   // full content width, not just the client width
		for(const [mr, cls] of this._lineDecos) {
			const vr = this._modelRowToViewRow(mr);
			if(vr < 0) continue;                              // hidden inside a fold (n/a when folding is off)
			const band = document.createElement('div');
			band.className = 'cerb-ui-kataeditor--line-deco' + (cls ? (' ' + cls) : '');
			band.style.top = (padTop + vr * lh) + 'px';
			band.style.height = lh + 'px';
			band.style.width = width;
			this.highlight.appendChild(band);
		}
	}

	// Append a zero-text `foldmark` token at the end of each collapsed header's VIEW row so the mirror paints a
	// collapse indicator there (a CSS-mask icon). Newlines are standalone tokens, so we count them to find rows.
	_injectFoldMarks(toks) {
		if(!this._folds.length) return toks;
		const rows = new Set();
		for(const f of this._folds) { const vr = this._modelRowToViewRow(f.startRow); if(vr >= 0) rows.add(vr); }
		if(!rows.size) return toks;
		const out = [];
		let row = 0;
		for(const t of toks) {
			if(t.type === 'text' && t.value === '\n') {
				if(rows.has(row)) out.push({ type: 'foldmark', value: '' });
				out.push(t); row++;
				continue;
			}
			out.push(t);
		}
		if(rows.has(row)) out.push({ type: 'foldmark', value: '' });  // last row (no trailing newline)
		return out;
	}

	// Split each VIEW line's leading whitespace into one `indent-guide` token per tabSize columns, so the mirror
	// paints a faint vertical rule down each indentation level (parity with Ace's indent guides). The guide span
	// holds the actual spaces and draws its line via inset box-shadow — no width change, so glyph advances (and the
	// caret) stay aligned. Truly-blank lines have no whitespace to carry guides, so we inject PHANTOM guide spaces
	// at the surrounding depth (max of the nearest non-blank neighbors) — mirror-only, never in the textarea — so
	// the guides visually continue across gaps. Leading whitespace is always spaces (tabs are sanitized away).
	_injectIndentGuides(toks) {
		const tab = this.opts.tabSize;
		if(!this.opts.indentGuides || tab <= 0) return toks;

		// Per view-line leading-space count (-1 = blank), for the blank-line contextual depth. (The windowed
		// painter uses the cached this._indents + the same _blankDepth/_guides helpers, so the two paths agree.)
		const indents = this.textarea.value.split('\n').map(s => {
			let i = 0; while(i < s.length && s[i] === ' ') i++;
			return (i === s.length) ? -1 : i;          // all-spaces or empty -> blank
		});

		const out = [];
		let row = 0, atLineStart = true;
		const closeBlankLine = () => { const d = this._blankDepth(row, indents); if(d >= tab) out.push(...this._guides(d)); };

		for(const t of toks) {
			if(t.type === 'text' && t.value === '\n') {
				if(atLineStart) closeBlankLine();          // empty line — phantom guides at the surrounding depth
				out.push(t); row++; atLineStart = true;
				continue;
			}
			if(atLineStart) {
				atLineStart = false;
				const v = t.value;
				let sp = 0; while(sp < v.length && v[sp] === ' ') sp++;
				if(sp >= tab) {                            // at least one full indent level — peel it into guides
					out.push(...this._guides(sp));
					const rest = v.slice(sp);
					if(rest.length) out.push({ type: t.type, value: rest });
					continue;
				}
			}
			out.push(t);
		}
		if(atLineStart) closeBlankLine();                  // last line, no trailing newline
		return out;
	}

	_syncScroll() {
		CerbUI.editorCore.syncScroll(this.textarea, this.highlight);
		if(this.gutter) this.gutter.scrollTop = this.textarea.scrollTop;
		this._repaintWindowIfMoved();
	}

	// Native textarea scroll: mirror the decorative layers synchronously (cheap), and coalesce the heavier window
	// repaint to one per frame so a fast fling doesn't rebuild the mirror on every scroll event.
	_handleScroll() {
		CerbUI.editorCore.syncScroll(this.textarea, this.highlight);
		if(this.gutter) this.gutter.scrollTop = this.textarea.scrollTop;
		if(this._scrollRaf) return;
		this._scrollRaf = requestAnimationFrame(() => { this._scrollRaf = 0; this._repaintWindowIfMoved(); });
	}

	// Repaint the windowed mirror + gutter when the viewport has scrolled out of the currently-painted overscan
	// band. No-op while painting (paint ends in _syncScroll), for small docs (never windowed), or when still in band.
	_repaintWindowIfMoved() {
		if(this._painting || !this._lineToks) return;
		const vrc = this._lineToks.length;
		if(vrc <= CerbUI.KataEditor._VIRTUALIZE_MIN_ROWS) return;
		const lh = this._lineHeight || (parseFloat(window.getComputedStyle(this.textarea).lineHeight) || 0);
		if(!(lh > 0)) return;
		const win = CerbUI.editorCore.computeWindow(this.textarea.scrollTop, this._viewportHeight(lh), lh, vrc, CerbUI.KataEditor._OVERSCAN);
		if(win.first >= this._renderedFirst && win.last <= this._renderedLast) return;   // still inside the painted band
		this._paintHighlightWindow();
		this._renderGutter();
	}

	// MODEL row -> the first cerb: URI on that line, for the gutter "open record" marker. Same regex the tokenizer
	// uses for the 'uri' token; only complete `cerb:<context>:<id>` URIs (3 colon-parts) get a marker.
	// Memoized by the model text (a whole-doc regex scan) so scroll-driven gutter repaints don't recompute it.
	_uriRowsMap() {
		if(this._uriRowsKey !== this._model) { this._uriRowsCache = this._computeUriRowsMap(); this._uriRowsKey = this._model; }
		return this._uriRowsCache;
	}
	_computeUriRowsMap() {
		const map = new Map(), RX = /cerb:[^\s)\]]+/, lines = this._modelLines();
		for(let i = 0; i < lines.length; i++) {
			const m = lines[i].match(RX);
			const p = m ? m[0].split(':') : null;             // only a complete cerb:<context>:<id> is openable
			if(p && p.length === 3 && p[1] && p[2]) map.set(i, m[0]);
		}
		return map;
	}

	// Open the record a cerb: URI points at. Replicates the old Ace ⌘-click: pop the record's peek via the global
	// cerbPeekTrigger jQuery plugin. Format is `cerb:<context>:<id>`; anything else is ignored.
	_openUri(uri) {
		if(typeof this.opts.onOpenUri === 'function') { this.opts.onOpenUri(uri); return; }
		const parts = String(uri).split(':');
		if(parts.length !== 3 || parts[0] !== 'cerb') return;
		const $ = window.jQuery;
		if(typeof $ !== 'function' || typeof $.fn.cerbPeekTrigger !== 'function') return;
		$('<div/>')
			.attr('data-context', parts[1])
			.attr('data-context-id', parts[2])
			.cerbPeekTrigger()
			.on('cerb-peek-saved cerb-peek-deleted cerb-peek-closed', function() { $(this).remove(); })
			.click()
		;
	}

	_renderGutter() {
		if(!this.gutter) return;
		this._buildRenderModel();
		// Map each foldable header row -> collapsed? (a detected range that's also in _folds is collapsed).
		const headerState = new Map();
		for(const r of this._foldableRanges()) headerState.set(r.headerRow, false);
		for(const f of this._folds) headerState.set(f.startRow, true);
		const uriRows = this._uriRowsMap();
		const ctx = {
			headerState: headerState,
			uriRows: uriRows,
			anyFoldable: headerState.size > 0,             // reserve the chevron column only when needed
			// Reserve the LEFT marker column when any marker (host or URI) exists, or a gutter-click handler is wired.
			anyMarker: this._markers.size > 0 || uriRows.size > 0 || typeof this.opts.onGutterClick === 'function',
			esc: CerbUI.editorCore.escapeHtml,
		};
		const v2m = this._viewToModel, vrc = v2m.length;

		if(vrc <= CerbUI.KataEditor._VIRTUALIZE_MIN_ROWS) {
			let html = '';
			for(let vr = 0; vr < vrc; vr++) html += this._gutterRowHtml(v2m[vr], ctx);
			this.gutter.style.minWidth = '';               // small docs size to content (today's behavior)
			this.gutter.innerHTML = html;
		} else {
			const lh = this._lineHeight || (parseFloat(window.getComputedStyle(this.textarea).lineHeight) || 0);
			const win = CerbUI.editorCore.computeWindow(this.textarea.scrollTop, this._viewportHeight(lh), lh, vrc, CerbUI.KataEditor._OVERSCAN);
			const top = win.first * lh, bottom = (vrc - 1 - win.last) * lh;
			// Stable gutter width: reserve the widest model line-number + the (whole-doc-constant) marker/chevron
			// columns so scrolling from 1-digit to N-digit numbers doesn't shift the editor horizontally.
			const digits = String(this._modelLines().length).length;
			const extraEm = 1 + (ctx.anyMarker ? 1.25 : 0) + (ctx.anyFoldable ? 1.25 : 0);
			this.gutter.style.minWidth = 'calc(' + digits + 'ch + ' + extraEm + 'em)';
			let html = '';
			if(top > 0) html += '<div class="cerb-ui-kataeditor--vspace" style="height:' + top + 'px"></div>';
			for(let vr = win.first; vr <= win.last; vr++) html += this._gutterRowHtml(v2m[vr], ctx);
			if(bottom > 0) html += '<div class="cerb-ui-kataeditor--vspace" style="height:' + bottom + 'px"></div>';
			this.gutter.innerHTML = html;
		}
		this.gutter.scrollTop = this.textarea.scrollTop;
	}

	// One gutter row's HTML for MODEL row `mr` (number jumps across folds: 1,2,6…). Shared by the full + windowed
	// gutter paths; `ctx` carries the whole-doc-constant header/marker maps so every row reserves the same columns.
	_gutterRowHtml(mr, ctx) {
		const num = mr + 1;
		const isActive = (this._highlightRow === mr);
		const active = isActive ? ' cerb-ui-kataeditor--gutter-line-active' : '';
		// Tint the active gutter cell with the caller's tag color (if any), matching the line band.
		const activeStyle = (isActive && this._highlightColor)
			? ' style="--cerb-ui-kataeditor-active-accent:var(--cerb-color-tag-' + this._highlightColor + ')"' : '';
		const isHeader = ctx.headerState.has(mr);
		// Marker slot, LEFT of the numbers (icon or pip). A reserved empty slot keeps the column aligned and clickable.
		let marker = '';
		if(ctx.anyMarker) {
			const mk = this._markers.get(mr);
			const uri = mk ? null : ctx.uriRows.get(mr);   // a host marker wins the slot; the URI marker fills the rest
			let cls = 'cerb-ui-kataeditor--gutter-marker', style = '', attrs = '';
			if(mk) {
				cls += mk.pip ? ' cerb-ui-kataeditor--gutter-marker-pip' : (mk.icon ? (' cerb-icons cerb-icon-' + mk.icon) : '');
				if(mk.type) cls += ' cerb-ui-kataeditor--gutter-marker-' + mk.type;
				if(mk.color) style = ' style="color:var(--cerb-color-tag-' + mk.color + ')"';
				if(mk.title) attrs = ' title="' + ctx.esc(mk.title) + '"';
			} else if(uri) {
				cls += ' cerb-icons cerb-icon-search cerb-ui-kataeditor--gutter-marker-uri';
				attrs = ' title="' + ctx.esc('Open ' + uri) + '" data-uri="' + ctx.esc(uri) + '"';
			}
			marker = '<span class="' + cls + '" data-model-row="' + mr + '"' + style + attrs + '></span>';
		}
		// Fold chevron sits to the RIGHT of the right-aligned number; an empty slot keeps the column aligned.
		const slot = !ctx.anyFoldable ? '' :
			('<span class="cerb-ui-kataeditor--gutter-fold' +
				(isHeader ? (' cerb-icons cerb-icon-' + (ctx.headerState.get(mr) ? 'chevron-right' : 'chevron-down')) : '') +
				'"' + (isHeader ? (' data-fold-row="' + mr + '"') : '') + '></span>');
		const foldable = isHeader ? ' cerb-ui-kataeditor--gutter-line-foldable' : '';
		return '<div class="cerb-ui-kataeditor--gutter-line' + active + foldable + '"' + activeStyle + '>' +
			marker + '<span class="cerb-ui-kataeditor--gutter-num">' + num + '</span>' + slot + '</div>';
	}

	// ⌘/Ctrl+Shift+↓ / ↑ — grow/shrink the editor's max visible rows (in-memory for this session). Capped at the
	// number of currently-visible lines: growing past the content does nothing (the editor only renders content
	// height), and that dead zone is exactly why shrinking from above it felt unresponsive.
	_resizeMaxLines(delta) {
		const docLines = this.textarea.value.split('\n').length;       // visible (projection) line count
		const ceiling = Math.max(this.opts.minLines, Math.min(docLines, 100));
		const n = Math.max(this.opts.minLines, Math.min((this.opts.maxLines || 20) + delta, ceiling));
		if(n === this.opts.maxLines) return;
		this.opts.maxLines = n;
		this._autosize();
		// (future) a hook could persist this / notify the host here.
	}

	_autosize() {
		const ta = this.textarea;
		if(!ta.getClientRects().length) { // hidden (e.g. a display:none preview panel) — defer the measure
			if(!this._revealDisposer)      // until revealed, else scrollHeight 0 would clamp us to minLines
				this._revealDisposer = CerbUI.editorCore.onFirstReveal(this.el, () => { this._revealDisposer = null; this._autosize(); });
			return;
		}
		const cs = window.getComputedStyle(ta);
		const lh = parseFloat(cs.lineHeight) || (parseFloat(cs.fontSize) * 1.5);
		this._lineHeight = lh;
		const padY = (parseFloat(cs.paddingTop) || 0) + (parseFloat(cs.paddingBottom) || 0);
		const minH = this.opts.minLines * lh + padY;
		const maxH = this.opts.maxLines * lh + padY;
		ta.style.height = 'auto';
		const h = Math.max(minH, Math.min(ta.scrollHeight, maxH));
		ta.style.height = h + 'px';
		ta.style.overflowY = (ta.scrollHeight > maxH + 1) ? 'auto' : 'hidden';
		this.highlight.style.height = h + 'px';
		if(this.gutter) this.gutter.style.height = h + 'px';
		this._syncScroll();
	}

	_lines() { return this.textarea.value.split('\n'); }       // projection (view) lines
	_modelLines() { return this._model.split('\n'); }          // full-document lines

	// ── Code folding internals (model ⇄ projection) ─────────────────────

	_hiddenModelRows() {
		const s = new Set();
		for(const f of this._folds) for(let r = f.startRow + 1; r <= f.endRow; r++) s.add(r);
		return s;
	}

	// view row -> model row (skips hidden rows); model row -> view row (-1 if the row is hidden).
	_viewRowToModelRow(vr) {
		const total = this._modelLines().length;
		let v = 0;
		for(let mr = 0; mr < total; mr++) {
			if(this._hidden.has(mr)) continue;
			if(v === vr) return mr;
			v++;
		}
		return Math.max(0, total - 1);
	}
	_modelRowToViewRow(mr) {
		if(this._hidden.has(mr)) return -1;
		let v = 0;
		for(let r = 0; r < mr; r++) if(!this._hidden.has(r)) v++;
		return v;
	}

	// char offset in a projection string -> char offset in this._model (a column maps 1:1 since folds never
	// split a line). `projText` is passed explicitly because the live textarea may already hold the new value.
	_viewOffsetToModelOffset(projText, off) {
		const before = projText.slice(0, off);
		const vRow = (before.match(/\n/g) || []).length;
		const vCol = off - (before.lastIndexOf('\n') + 1);
		const mRow = this._viewRowToModelRow(vRow);
		const mLines = this._modelLines();
		let mOff = 0;
		for(let r = 0; r < mRow; r++) mOff += mLines[r].length + 1;
		return mOff + Math.min(vCol, mLines[mRow] != null ? mLines[mRow].length : vCol);
	}

	// char offset in this._model -> char offset in the current textarea projection. A hidden target snaps to
	// the end of its enclosing fold header (the nearest visible spot).
	_modelOffsetToViewOffset(mOff) {
		const mLines = this._modelLines();
		let acc = 0, mRow = 0;
		for(; mRow < mLines.length; mRow++) { if(mOff <= acc + mLines[mRow].length) break; acc += mLines[mRow].length + 1; }
		if(mRow >= mLines.length) mRow = mLines.length - 1;
		let col = mOff - acc;
		let vRow = this._modelRowToViewRow(mRow);
		if(vRow === -1) {
			const f = this._folds.find(f => mRow > f.startRow && mRow <= f.endRow);
			const hdr = f ? f.startRow : mRow;
			vRow = this._modelRowToViewRow(hdr);
			col = mLines[hdr].length;
		}
		const view = this.textarea.value.split('\n');
		let vo = 0;
		for(let i = 0; i < vRow; i++) vo += view[i].length + 1;
		return vo + Math.min(col, view[vRow] != null ? view[vRow].length : col);
	}

	_projectedText() {
		if(!this._folds.length) return this._model;
		const mLines = this._modelLines();
		const out = [];
		for(let r = 0; r < mLines.length; r++) if(!this._hidden.has(r)) out.push(mLines[r]);
		return out.join('\n');
	}

	// Recompute the textarea from this._model + this._folds and re-render. A fold toggle is NOT an undoable
	// text edit, so we write the value directly (execCommand would push an undo entry). Optionally restores the
	// caret to a model offset (mapped into the new projection).
	_rebuildProjection(caretModelOffset) {
		this._hidden = this._hiddenModelRows();
		const proj = this._projectedText();
		const ta = this.textarea;
		this._suppressInput = true;
		ta.value = proj;
		this._suppressInput = false;
		this._lastProjection = proj;
		if(caretModelOffset != null) {
			const vo = this._modelOffsetToViewOffset(caretModelOffset);
			ta.selectionStart = ta.selectionEnd = vo;
		}
		this._renderHighlight();
		this._autosize();
		this._renderGutter();
		this._scrollCaretIntoView();
	}

	// Expand every fold hiding a given model row (handles nested folds), then rebuild.
	_revealModelRow(mr) {
		const before = this._folds.length;
		this._folds = this._folds.filter(f => !(mr > f.startRow && mr <= f.endRow));
		if(this._folds.length !== before) this._rebuildProjection();
	}

	// Before a structural line edit (indent/dedent/newline/delete/move): if the caret/selection sits on a
	// collapsed header, expand it first so the op acts on full text (a header must never drift from its body).
	_revealForEdit() {
		if(!this._folds.length) return;
		const ta = this.textarea, v = ta.value, s = ta.selectionStart, e = ta.selectionEnd;
		const vr0 = (v.slice(0, s).match(/\n/g) || []).length;
		const vr1 = (v.slice(0, e).match(/\n/g) || []).length;
		const touched = new Set();
		for(let vr = vr0; vr <= vr1; vr++) touched.add(this._viewRowToModelRow(vr));
		if(!this._folds.some(f => touched.has(f.startRow))) return;
		const mS = this._viewOffsetToModelOffset(v, s), mE = this._viewOffsetToModelOffset(v, e);
		this._folds = this._folds.filter(f => !touched.has(f.startRow));
		this._hidden = this._hiddenModelRows();
		const proj = this._projectedText();
		this._suppressInput = true; ta.value = proj; this._suppressInput = false;
		this._lastProjection = proj;
		ta.selectionStart = this._modelOffsetToViewOffset(mS);
		ta.selectionEnd = this._modelOffsetToViewOffset(mE);
	}

	// Reconcile a projection edit (typing, programmatic edit, autocomplete apply) back into this._model and
	// remap active folds. The replaced span lives entirely in visible text, so it maps cleanly to the model.
	_applyProjectionEditToModel() {
		const newProj = this.textarea.value;
		const oldProj = this._lastProjection;
		if(oldProj === newProj) return;

		if(!this._folds.length) { this._model = newProj; this._lastProjection = newProj; return; } // fast path

		// minimal diff in projection space (same algorithm as _writeValue)
		let p = 0; const max = Math.min(oldProj.length, newProj.length);
		while(p < max && oldProj[p] === newProj[p]) p++;
		let so = oldProj.length, sn = newProj.length;
		while(so > p && sn > p && oldProj[so - 1] === newProj[sn - 1]) { so--; sn--; }
		const removed = oldProj.slice(p, so), inserted = newProj.slice(p, sn);

		const oldModel = this._model;
		const mStart = this._viewOffsetToModelOffset(oldProj, p);
		const mEnd = this._viewOffsetToModelOffset(oldProj, so);
		this._model = oldModel.slice(0, mStart) + inserted + oldModel.slice(mEnd);
		this._remapFolds(mStart, mEnd, inserted, removed, oldModel);
		this._hidden = this._hiddenModelRows();
		this._lastProjection = newProj;
	}

	// Shift folds the edit was strictly above; keep folds it was strictly below or a pure in-line header edit;
	// dissolve folds whose header/body the edit destabilized (so the body reappears).
	_remapFolds(mStart, mEnd, inserted, removed, oldModel) {
		const lineDelta = (inserted.match(/\n/g) || []).length - (removed.match(/\n/g) || []).length;
		const structural = lineDelta !== 0 || removed.indexOf('\n') !== -1;
		const oldLines = oldModel.split('\n');
		const rowOffset = (row) => { let o = 0; for(let i = 0; i < row; i++) o += oldLines[i].length + 1; return o; };
		const kept = [];
		for(const f of this._folds) {
			const hdrStart = rowOffset(f.startRow);
			const hdrEnd = hdrStart + (oldLines[f.startRow] != null ? oldLines[f.startRow].length : 0);
			const bodyEnd = rowOffset(f.endRow) + (oldLines[f.endRow] != null ? oldLines[f.endRow].length : 0);
			if(mStart >= bodyEnd) { kept.push(f); continue; }                       // entirely below
			if(mEnd <= hdrStart) {                                                  // entirely above the header
				f.startRow += lineDelta; f.endRow += lineDelta; f.headerRow = f.startRow; kept.push(f); continue;
			}
			if(mStart >= hdrStart && mEnd <= hdrEnd && !structural) { kept.push(f); continue; } // in-line header edit
			// otherwise dissolve (drop f)
		}
		this._folds = kept.sort((a, b) => a.startRow - b.startRow);
	}

	// Foldable indentation subtrees in MODEL space: a `key:` header with at least one deeper-indented child.
	// Reuses the tokenizer's key-line regex; blanks belong to the subtree, trailing blanks are trimmed off.
	// Memoized by the model text (depends only on _model + opts.folding, NOT on which folds are collapsed) so
	// scroll-driven gutter repaints reuse it.
	_foldableRanges() {
		if(this._foldableKey !== this._model) { this._foldableCache = this._computeFoldableRanges(); this._foldableKey = this._model; }
		return this._foldableCache;
	}
	_computeFoldableRanges() {
		if(this.opts.folding === false) return [];   // folding disabled (e.g. a diff pane) — nothing is ever foldable
		const lines = this._modelLines();
		const KEY = /^(\s*)([\w.]+)(\/[^\s:@]+)?((?:@[A-Za-z0-9_]+)(?:,[A-Za-z0-9_]+)*)?:/;
		const out = [];
		for(let r = 0; r < lines.length; r++) {
			const m = lines[r].match(KEY);
			if(!m) continue;
			const headerIndent = m[1].length;
			let end = r;
			for(let k = r + 1; k < lines.length; k++) {
				const t = lines[k].trimStart();
				if(t.length === 0) { end = k; continue; }                  // blank: tentatively part of the subtree
				if(lines[k].length - t.length > headerIndent) end = k; else break;
			}
			while(end > r && lines[end].trim().length === 0) end--;        // don't fold trailing blank lines
			if(end > r) out.push({ headerRow: r, startRow: r, endRow: end });
		}
		return out;
	}

	// ── KATA tokenizer (drives the highlight mirror) ────────────────────
	// Line-oriented: KATA structure is indentation + line based. Returns a flat token list covering every
	// character (newlines included), so editorCore.renderTokens can build the colored mirror spans.

	_tokenize(text) {
		const lines = text.split('\n');
		const toks = [];
		let blockIndent = null; // indent (length) of the key owning an open @annotation text block, or null
		let scriptOpen = null;  // a '{{'/'{%' tag left open by a previous line (KataScript spans lines)

		for(let li = 0; li < lines.length; li++) {
			if(li > 0) toks.push({ type: 'text', value: '\n' });
			const line = lines[li];

			// Continuation of a multi-line script tag — the whole line is tag content until it closes.
			if(scriptOpen) { scriptOpen = this._pushValueTokens(toks, line, 'value', false, scriptOpen); continue; }

			const trimmed = line.trimStart();
			const indentLen = line.length - trimmed.length;

			// Inside an open text block: deeper-or-blank lines are literal content (default color; only script
			// tags are highlighted — not '#' comments, not cerb: URIs). A dedent to <= the key's indent ends it.
			if(blockIndent !== null) {
				if(trimmed.length === 0) { toks.push({ type: 'text', value: line }); continue; }
				if(indentLen > blockIndent) { scriptOpen = this._pushValueTokens(toks, line, 'text', true, null); continue; }
				blockIndent = null; // dedented — fall through and parse normally
			}

			// Comment: a line whose first non-space char is '#'.
			if(trimmed.charAt(0) === '#') { toks.push({ type: 'comment', value: line }); continue; }

			// Key line: indent, name, optional /identifier, optional @annotation,csv run, then ':'.
			const m = line.match(/^(\s*)([\w.]+)(\/[^\s:@]+)?((?:@[A-Za-z0-9_]+)(?:,[A-Za-z0-9_]+)*)?:/);
			if(m) {
				const indent = m[1], name = m[2], slash = m[3] || '', ann = m[4] || '';
				if(indent) toks.push({ type: 'text', value: indent });
				toks.push({ type: 'key', value: name });
				if(slash) toks.push({ type: 'keyslash', value: slash });
				if(ann) toks.push({ type: 'annotation', value: ann });
				toks.push({ type: 'colon', value: ':' });
				const rest = line.slice(m[0].length);
				if(rest.length) scriptOpen = this._pushValueTokens(toks, rest, 'value', false, null);
				// An annotated key with no inline value opens a text block for its deeper-indented lines.
				if(ann && rest.trim().length === 0) blockIndent = indent.length;
				continue;
			}

			// Anything else: a bare value/continuation line.
			scriptOpen = this._pushValueTokens(toks, line, 'value', false, null);
		}
		return toks;
	}

	// Tokenize a value/block string: KataScript tags via the shared module (opens `{{`/`{%` immediately), and
	// non-tag runs as `baseType` — with `cerb:` URIs detected unless `noUris` (literal inside a text block).
	// `startOpen` continues a tag from the previous line; returns the opener still in effect (or null).
	_pushValueTokens(toks, str, baseType, noUris, startOpen) {
		const plain = noUris
			? function(t, s, bt) { if(s) t.push({ type: bt, value: s }); }
			: function(t, s, bt) {
				if(!s) return;
				const RX = /cerb:[^\s)\]]+/g;
				let last = 0, mm;
				while((mm = RX.exec(s)) !== null) {
					if(mm.index > last) t.push({ type: bt, value: s.slice(last, mm.index) });
					t.push({ type: 'uri', value: mm[0] });
					last = mm.index + mm[0].length;
				}
				if(last < s.length) t.push({ type: bt, value: s.slice(last) });
			};
		return CerbUI.editorCore.kataScript.tokenize(toks, str, baseType, startOpen, plain);
	}

	// ── KATA key-path at the caret (port of getKataTokenPath over a plain string) ──
	// Returns { path:['automation:','inputs:'], prefix:'partial', prefixRaw:'chars to replace', caret }.
	// In VALUE position (a `key:` precedes the caret on this line) the current key is the final path segment and
	// the prefix is the partial value. In KEY position (typing a key) the path is the ancestor chain and the
	// prefix is the partial key. Ancestors are found by walking up to lines with strictly smaller indent.

	_scopePathAt(text, caret) {
		// Inside a script tag the "path" is meaningless; return the partial script word so an accepted
		// suggestion replaces it (not the surrounding KATA value).
		const tctx = CerbUI.editorCore.kataScript.contextAt(text, caret);
		if(tctx) return { path: [], prefix: tctx.prefix, prefixRaw: tctx.prefixRaw, caret };

		const before = text.slice(0, caret);
		const lineStart = before.lastIndexOf('\n') + 1;
		const col = caret - lineStart;
		const nlAfter = text.indexOf('\n', caret);
		const curLine = text.slice(lineStart, nlAfter === -1 ? undefined : nlAfter);
		const indentLen = curLine.length - curLine.trimStart().length;
		const KEY = /^(\s*)((?:[\w.]+)(?:\/[^\s:@]+)?(?:@[A-Za-z0-9_,]+)?):/;

		const path = [];
		let prefix = '', prefixRaw = '', walkIndent = indentLen;

		const km = curLine.match(KEY);
		if(km && km[0].length <= col) {
			// Value position: a key precedes the caret on this line.
			path.push(km[2] + ':');
			const valStr = before.slice(lineStart + km[0].length); // text after the key, up to the caret
			const wm = valStr.match(/(\S*)$/);
			prefix = wm ? wm[1] : '';
			prefixRaw = prefix;
		} else {
			// Key position: the partial key being typed is the prefix.
			prefix = before.slice(lineStart + indentLen);
			prefixRaw = prefix;
			// On a blank/whitespace line nothing fixes the indent yet, so the caret's column is the intended
			// one — dedenting (backspacing) to the parent's level makes the path follow, back out to (root).
			if(curLine.trim().length === 0) walkIndent = col;
		}

		// Walk up to each ancestor line with strictly smaller indent, prepending its key.
		let idx = lineStart - 1; // index of the '\n' ending the previous line (or -1)
		while(idx >= 0 && walkIndent > 0) {
			const prevNl = text.lastIndexOf('\n', idx - 1);
			const pStart = prevNl + 1;
			const pLine = text.slice(pStart, idx);
			idx = prevNl;
			if(pLine.trim().length === 0) continue;
			const pIndent = pLine.length - pLine.trimStart().length;
			if(pIndent < walkIndent) {
				const pm = pLine.match(KEY);
				if(pm) path.unshift(pm[2] + ':');
				walkIndent = pIndent;
			}
		}

		return { path, prefix, prefixRaw, caret };
	}

	// True when the caret sits where autocomplete must stay quiet: immediately after a completed inline key
	// (`key@anno:`) before its separating whitespace; inside an @annotation text block (an annotated, value-less
	// key opens a block; every deeper-or-blank line is literal content until the indent returns to <= the key's
	// indent); or on a `#` comment line. Mirrors the tokenizer's block tracking so the two agree exactly — a CRLF
	// after `field@text:` makes the lines below it a block, not new keys.
	_autocompleteSuppressed(text, caret) {
		const caretLineIdx = (text.slice(0, caret).match(/\n/g) || []).length;
		const lines = text.split('\n');
		const KEY = /^(\s*)([\w.]+)(\/[^\s:@]+)?((?:@[A-Za-z0-9_]+)(?:,[A-Za-z0-9_]+)*)?:/;
		let blockIndent = null;

		// A just-completed key is still the field tag (it ends in `:`), not a value slot — KATA writes an inline
		// value as `key: value` (a space after the colon) and an indented value as `key:` + CRLF + indent. Until
		// one of those whitespace separators exists, stay quiet rather than popping suggestions glued to the colon.
		// The CRLF+indent case drops the caret onto a fresh line below (no same-line key here), so it's allowed;
		// only the no-whitespace, same-line position (caret right after the colon, or `key:val` typed without a
		// space) is suppressed.
		const lineStart = text.lastIndexOf('\n', caret - 1) + 1;
		const beforeOnLine = text.slice(lineStart, caret);
		const km = beforeOnLine.match(KEY);
		if(km && !/^\s/.test(beforeOnLine.slice(km[0].length))) return true;

		for(let li = 0; li <= caretLineIdx; li++) {
			const line = lines[li];
			const trimmed = line.trimStart();
			const indentLen = line.length - trimmed.length;

			if(li === caretLineIdx) {
				if(blockIndent !== null) {
					// On a blank/whitespace caret line the caret's column is the intended indent, so dedenting
					// (backspacing) out to the key's level escapes the block even before a key is typed.
					const caretCol = caret - (text.lastIndexOf('\n', caret - 1) + 1);
					const eff = (trimmed.length === 0) ? caretCol : indentLen;
					if(eff > blockIndent) return true;          // still deeper than the key — block content
					// at/above the key's indent -> out of the block; fall through to the comment check
				}
				return trimmed.charAt(0) === '#';               // a comment line suppresses too
			}

			if(blockIndent !== null) {
				if(trimmed.length === 0) continue;              // blank stays in the block
				if(indentLen > blockIndent) continue;           // still block content
				blockIndent = null;                             // dedented — parse as a key below
			}
			if(trimmed.charAt(0) === '#') continue;             // comments don't open blocks
			const m = line.match(KEY);
			if(m && (m[4] || '') && line.slice(m[0].length).trim().length === 0)
				blockIndent = m[1].length;                      // annotated, value-less key opens a block
		}
		return false;
	}
};

// Token type -> CSS class for the highlight mirror (value/text have no class = default literal color).
// The whole key — name, /identifier, @annotations, and the trailing colon — shares the field color, matching
// the legacy Ace cerb_kata theme. Text-block content is the default literal color. Script-tag tokens
// (delimiters/strings/numbers/functions) are colored by the SHARED editorCore.kataScript classes, merged in
// here so a future TemplateEditor uses the same palette.
CerbUI.KataEditor._TOK_CLASS = Object.assign({
	comment:    'cerb-ui-kataeditor--tok-comment',
	key:        'cerb-ui-kataeditor--tok-key',
	keyslash:   'cerb-ui-kataeditor--tok-key',
	annotation: 'cerb-ui-kataeditor--tok-key',
	colon:      'cerb-ui-kataeditor--tok-key',
	uri:        'cerb-ui-kataeditor--tok-uri',
	foldmark:   'cerb-ui-kataeditor--fold-indicator cerb-icons cerb-icon-move-horizontal',
	'indent-guide': 'cerb-ui-kataeditor--indent-guide',
}, CerbUI.editorCore.kataScript.TOK_CLASS);

// Gutter marker type presets: a default icon (a `cerb-icon-<name>`) or `pip` (a colored dot) + a tag color.
// `setMarker` lets the caller override any of icon/pip/color/title. Hosts can also pass a bare icon name (e.g.
// `stop` for where a script stopped, `stopwatch` for an `await:` continuation).
CerbUI.KataEditor._MARKER_TYPES = {
	error:      { icon: 'circle-exclamation-mark', color: 'red' },
	warning:    { icon: 'alert',                    color: 'orange' },
	info:       { icon: 'circle-info',              color: 'blue' },
	breakpoint: { pip: true,                        color: 'red' },
};

/*
 * kataFieldSource(suggestionMap, opts) — a ready-made onAutocomplete that drives the KATA editor from Cerb's
 * existing autocomplete data, a port of cerberus.js `autocompleterKata` (getCompletions + parseCompletions).
 *
 * `suggestionMap` is a path-keyed object: each key is a normalized KATA scope (colon-joined, e.g.
 * `automation:inputs:`) and each value is either a static Array of suggestions, or a dynamic descriptor
 * `{ type, params? }`. It's typically one of the global `CerbUI.editorCore.autocompleteSchemas.*` schemas (e.g.
 * `kataAutomationPolicy`, `kataSchemaMetricsExplorerSeries`, `kataToolbar`) or a per-trigger JSON blob. A `'*'`
 * key may hold a { regexPattern -> suggestions } bucket for variable paths.
 *
 * Static arrays are filtered client-side; dynamic descriptors POST to the existing
 * `c=ui&a=kataSuggestions<Type>Json` endpoints (unchanged), reading sibling key values back out of the editor
 * via getTokenPath()/getRowByPath()/getLine(). `opts.filterMode` controls client-side filtering of static lists.
 */
CerbUI.KataEditor.kataFieldSource = function(suggestionMap, opts) {
	opts = opts || {};
	const mode = opts.filterMode || 'subsequence';
	const typeDefaults = opts.autocomplete_type_defaults || opts.typeDefaults || {};

	// Strip /identifiers and @annotations from each segment so `series/s0:metric@int:` keys as `series:metric:`.
	function normalizePath(path) {
		return path.map(function(v) {
			let p = v.indexOf('@'); if(p !== -1) v = v.slice(0, p) + ':';
			p = v.indexOf('/'); if(p !== -1) v = v.slice(0, p) + ':';
			return v;
		});
	}

	function toItem(s) {
		if(typeof s === 'string') s = { caption: s, snippet: s };
		const value = (s.snippet != null) ? CerbUI.editorCore.aceSnippetToCerb(s.snippet)
			: (s.value != null ? s.value : s.caption);
		const item = {
			caption: (s.caption != null) ? s.caption : value,
			value: value,
			hint: s.hint || s.meta || null,
		};
		if(typeof s.score === 'number') item.score = s.score;
		// Re-open suggestions after a pick only when the inserted text introduces a new key (contains ':') —
		// matches the legacy completer's insertMatchAndAutocomplete. A terminal value doesn't cascade.
		const ins = (s.snippet != null) ? s.snippet : value;
		item.suppressAutocomplete = s.suppress_autocomplete ? true : (String(ins).indexOf(':') === -1);
		// Interaction-backed suggestion: instead of inserting the caption, the editor-core accept path runs this
		// named automation and inserts its `return: snippet:` output. The interaction supplies the terminal value.
		if(s.interaction != null) {
			item.interaction = s.interaction;
			item.interaction_params = s.interaction_params || '';
			item.suppressAutocomplete = true;
		}
		return item;
	}

	function staticList(arr, prefix) {
		return CerbUI.editorCore.filterItems(arr.map(toItem), prefix, mode);
	}

	function post(action, params) {
		return new Promise(function(resolve) {
			const fd = new FormData();
			fd.set('c', 'ui');
			fd.set('a', action);
			for(const k in params) if(params[k] != null) fd.set(k, params[k]);
			genericAjaxPost(fd, '', '', function(json) { resolve(Array.isArray(json) ? json.map(toItem) : []); });
		});
	}

	// Read the inline value of a sibling key (the `value` in `key: value`) via the editor buffer.
	function siblingValue(editor, pathArr) {
		const row = editor.getRowByPath(pathArr.join(''));
		if(row === false) return null;
		const m = editor.getLine(row).match(/[^:]*:\s*(.*)/);
		return (m && m.length === 2) ? m[1] : null;
	}

	function resolveDynamic(desc, ctx) {
		const editor = ctx.editor, prefix = ctx.prefix || '', type = desc.type;
		let params = Object.assign({}, desc.params || {});
		if(typeDefaults[type] && typeof typeDefaults[type] === 'object')
			params = Object.assign(params, typeDefaults[type]);
		if(!editor) return Promise.resolve([]);

		switch(type) {
			case 'cerb-uri':
				return post('kataSuggestionsCerbUriJson', { prefix: prefix, params: $.param(params) }).then(function(items) {
					// A record-type segment ends with ':' (e.g. `cerb:automation:`) and should cascade to the next
					// level; a concrete record URI (e.g. `cerb:automation:my.name`) is terminal — but it carries
					// colons too, so toItem's "has-colon → re-open" default mis-fires. Suppress on non-colon ends.
					return items.map(function(it) {
						const v = String(it.value != null ? it.value : '').replace('$0', '').replace(/\s+$/, '');
						it.suppressAutocomplete = !v.endsWith(':');
						return it;
					});
				});
			case 'record-type':
				return post('kataSuggestionsRecordTypeJson', { prefix: prefix });
			case 'icon':
				return post('kataSuggestionsIconJson', { prefix: prefix });
			case 'metric-names':
				return post('kataSuggestionsMetricNamesJson', { prefix: prefix });
			case 'record-field': {
				const p = { prefix: prefix };
				if(typeof params.record_type === 'string') p['params[record_type]'] = params.record_type;
				if(typeof params.field_key === 'string') p['params[field_key]'] = params.field_key;
				return post('kataSuggestionsRecordFieldJson', p);
			}
			case 'record-fields': {
				let record_type = '';
				if(params.record_type) {
					record_type = params.record_type;
				} else if(params.parent_key) {
					const rp = editor.getTokenPath(); rp.pop();
					const pk = rp.pop() || '';
					const mm = pk.match(/([^:]*):/);
					if(mm && mm.length === 2) record_type = mm[1].split('/')[0];
				} else {
					const rp = editor.getTokenPath(); rp.pop(); rp.push('record_type:');
					record_type = siblingValue(editor, rp) || '';
				}
				return post('kataSuggestionsRecordFieldsJson', { prefix: prefix, 'params[record_type]': record_type });
			}
			case 'record-fields-value': {
				const rp = editor.getTokenPath();
				const field = rp.pop();
				let record_type;
				if(params.record_type) {
					record_type = params.record_type;
				} else {
					rp.pop(); rp.push('record_type:');
					record_type = siblingValue(editor, rp) || '';
				}
				if(record_type && field)
					return post('kataSuggestionsRecordFieldsValueJson', {
						prefix: prefix,
						'params[record_type]': record_type,
						'params[field_name]': field.split(':')[0].split('@')[0],
					});
				return Promise.resolve([]);
			}
			case 'automation-inputs': {
				const up = editor.getTokenPath(); up.pop(); up.push('uri:');
				const uri = siblingValue(editor, up);
				if(uri != null)
					return post('kataSuggestionsAutomationInputsJson', { prefix: prefix, 'params[uri]': uri });
				return Promise.resolve([]);
			}
			case 'automation-command-params': {
				const kp = editor.getTokenPath();
				const ip = kp.slice();
				while(ip.length && ip[ip.length - 1] !== 'inputs:') ip.pop();
				if(ip[ip.length - 1] === 'inputs:') {
					const np = ip.slice(); np.push('name:');
					const name = siblingValue(editor, np);
					const rel = kp.slice(ip.length + 1);
					if(name != null)
						return post('kataSuggestionsAutomationCommandParamsJson', {
							prefix: prefix,
							'params[name]': name,
							'params[prefix]': prefix,
							'params[key_path]': rel.join(''),
							'params[key_fullpath]': kp.join(''),
							'params[script]': editor.getValue(),
						});
				}
				return Promise.resolve([]);
			}
			case 'metric-dimensions': {
				const kp = editor.getTokenPath(); kp.pop(); kp.push('metric_name:');
				const metric = siblingValue(editor, kp);
				if(metric != null)
					return post('kataSuggestionsMetricDimensionJson', { prefix: prefix, 'params[metric]': metric });
				return Promise.resolve([]);
			}
			case 'metric-dimensions-series': {
				const kp = editor.getTokenPath();
				while(kp.length && !/^series\//.test(kp[kp.length - 1])) kp.pop();
				if(kp.length) {
					kp.push('metric:');
					const metric = siblingValue(editor, kp);
					if(metric != null && metric.indexOf('{{') === -1)
						return post('kataSuggestionsMetricDimensionJson', { prefix: prefix, 'params[metric]': metric.trim() });
				}
				return Promise.resolve([]);
			}
		}
		return Promise.resolve([]);
	}

	return function(ctx) {
		const scopeKey = normalizePath(ctx.path).join('');
		const prefix = ctx.prefix || '';
		let completions = suggestionMap[scopeKey];

		// Fall back to the '*' regex-pattern bucket for variable (identifier-bearing) paths. Match anchored
		// (^…$, like the legacy completer) and try the LONGEST pattern first, so the most specific leaf scope
		// wins over a shallower ancestor — e.g. `(.*):await:form:elements:` beats `(.*):await:`.
		if(completions === undefined && suggestionMap['*'] && typeof suggestionMap['*'] === 'object') {
			const pats = Object.keys(suggestionMap['*']).sort((a, b) => b.length - a.length);
			for(const pat of pats) {
				try { if(new RegExp('^' + pat + '$').test(scopeKey)) { completions = suggestionMap['*'][pat]; break; } } catch(_) {}
			}
		}

		if(Array.isArray(completions)) return staticList(completions, prefix);
		if(completions && typeof completions === 'object' && completions.type)
			return resolveDynamic(completions, ctx);
		return [];
	};
};
