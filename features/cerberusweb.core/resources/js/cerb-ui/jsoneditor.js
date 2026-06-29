/*
 * CerbUI.JsonEditor — a plain-JS code editor for JSON, a sibling of CerbUI.KataEditor.
 *
 * It shares the overlay-highlight machinery with the other editors via CerbUI.editorCore (transparent textarea
 * over a colored mirror div, scroll-sync, the keyboard-shortcut matcher), and reuses KataEditor's code-folding
 * MODEL/projection design — but with a much simpler, JSON-only tokenizer and BRACKET-based folding (`{…}` /
 * `[…]`) instead of KATA's indentation-based folding. There is no autocomplete in this first version; the gutter
 * MARKER API is kept so client-side JSON validation can light up errors later.
 *
 * Like KataEditor it's a CODE editor: always multi-line, auto-growing between minLines and maxLines, with a left
 * line-number gutter and Tab-as-two-spaces / Shift+Tab dedent. It does NOT wrap (1 text line = 1 gutter row) —
 * long lines scroll horizontally.
 *
 * Markup (the gallery / template supplies it):
 *   <div class="cerb-ui-jsoneditor" id="ed">
 *     <div class="cerb-ui-jsoneditor--gutter" aria-hidden="true"></div>
 *     <div class="cerb-ui-jsoneditor--field">
 *       <div class="cerb-ui-jsoneditor--highlight" aria-hidden="true"></div>
 *       <textarea class="cerb-ui-jsoneditor--input" name="…" spellcheck="false"></textarea>
 *       <span class="cerb-ui-jsoneditor--caret-anchor"></span>
 *     </div>
 *   </div>
 *
 * Usage:
 *   new CerbUI.JsonEditor(document.getElementById('ed'), { minLines: 4, maxLines: 25 });
 *   new CerbUI.JsonEditor(el, { readOnly: true });   // a result/output viewer: highlight + fold, no editing
 *
 * Form integration: a textarea can only hold the folded PROJECTION, so when the authored <textarea> carries a
 * name= it's kept as an inert hidden VALUE CARRIER and editing happens in a nameless clone. _fireChange() keeps
 * the carrier = the full document, so native FormData(form) submit and external .val() reads always see the whole
 * doc (folded or not). To SET the value from outside, call setValue() — not .val() on the field. CSS lives in
 * cerb.css (.cerb-ui-jsoneditor--*).
 */
CerbUI.JsonEditor = class {
	static _instances = new WeakMap();
	static from(el) { return CerbUI.JsonEditor._instances.get(el); }

	static _DEFAULTS = {
		minLines: 2,              // editor never shrinks below this many rows
		maxLines: 25,             // grows to this many rows, then scrolls (data-editor-lines overrides)
		tabSize: 2,               // a Tab inserts this many spaces; Shift+Tab dedents by up to this many
		indentGuides: true,       // faint vertical rule down each indentation level (continues across blank lines)
		readOnly: false,          // highlight + fold only; disable text-mutating keys (data-editor-readonly overrides)
		placeholder: null,
		onGutterClick: null,      // (modelRow, e) when the left marker column is clicked (e.g. toggle a breakpoint)
		validate: false,          // when true, lint JSON on edit and mark the first syntax error in the gutter
		validateDelay: 300,       // ms debounce for validation while typing
		onValidate: null,         // (result) after each validate(): { valid, row?, column?, position?, message? }
	};

	constructor(el, opts = {}) {
		el = (typeof el === 'string') ? document.querySelector(el) : el;
		if(!el) return;

		this.el = el;
		this.opts = Object.assign({}, CerbUI.JsonEditor._DEFAULTS, opts);

		const input = el.querySelector('.cerb-ui-jsoneditor--input');
		const lines = input && input.getAttribute('data-editor-lines');
		if(lines) this.opts.maxLines = parseInt(lines, 10) || this.opts.maxLines;
		if(input && input.hasAttribute('data-editor-readonly')) this.opts.readOnly = true;

		this.textarea = input;
		this.field = el.querySelector('.cerb-ui-jsoneditor--field');
		this.highlight = el.querySelector('.cerb-ui-jsoneditor--highlight');
		this.gutter = el.querySelector('.cerb-ui-jsoneditor--gutter');
		this.caretAnchor = el.querySelector('.cerb-ui-jsoneditor--caret-anchor');
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
			carrier.classList.remove('cerb-ui-jsoneditor--input');
			carrier.classList.add('cerb-ui-jsoneditor--value');
			carrier.hidden = true;
			carrier.setAttribute('aria-hidden', 'true');
			this._valueField = carrier;
			this.textarea = editor;
		}

		this.tab = ' '.repeat(this.opts.tabSize);
		this._highlightRow = null;   // a MODEL row marked active in the gutter, or null
		this._markers = new Map();   // MODEL row -> gutter marker descriptor {type,icon,color,title,pip} (left of numbers)
		this._changeCbs = [];
		this._suppressInput = false; // true while _writeValue applies an edit (ignore the echoed `input` event)
		this._validationRow = null;  // MODEL row of the current validation error marker (so we own/clear just it)
		this._validateTimer = null;  // debounce timer for validate-on-edit

		// ── Code folding (model + projection) ──
		// The textarea can't hide rows, so folding keeps the FULL text in this._model (the source of truth) and
		// shows only the unfolded lines (the "projection") in the textarea. Public rows/getValue are MODEL space.
		this._model = this.textarea.value;
		this._folds = [];            // [{headerRow, startRow, endRow}] in MODEL rows; startRow===headerRow stays visible
		this._hidden = new Set();    // cached set of hidden MODEL rows (= union of every fold's startRow+1..endRow)
		this._lastProjection = this.textarea.value; // last textarea value we reconciled into the model

		// Code, not prose — disable the browser's text-assist features that fight the overlay.
		this.textarea.spellcheck = false;
		this.textarea.setAttribute('autocomplete', 'off');
		this.textarea.setAttribute('autocorrect', 'off');
		this.textarea.setAttribute('autocapitalize', 'off');
		if(this.opts.placeholder != null) this.textarea.placeholder = this.opts.placeholder;
		if(this.opts.readOnly) {
			this.textarea.readOnly = true;
			this.el.classList.add('cerb-ui-jsoneditor--readonly');
		}

		this._shortcuts = this._buildShortcuts();

		CerbUI.JsonEditor._instances.set(el, this);

		this._onInput = (e) => this._handleInput(e);
		this._onKeydown = (e) => this._handleKeydown(e);
		this._onScroll = () => this._syncScroll();
		this._onGutterClick = (e) => this._handleGutterClick(e);

		this.textarea.addEventListener('input', this._onInput);
		this.textarea.addEventListener('keydown', this._onKeydown);
		this.textarea.addEventListener('scroll', this._onScroll, { passive: true });
		if(this.gutter) this.gutter.addEventListener('click', this._onGutterClick);

		this._rebuildProjection();  // initial render (projection === model while nothing is folded)

		if(this.opts.validate) this.validate();  // surface any error in the seeded value right away

		// Core editor-family hook: a caller can add extensible toolbar `sections` to any editor (opt-in via opts.toolbar).
		CerbUI.editorCore.attachToolbar(this, this.opts);

		// Find/Replace (Mod-F) — shared controller + a folding adapter (model⇄projection offset mapping).
		this._find = new CerbUI.editorCore.FindController(this, CerbUI.editorCore.makeFindAdapter(this, 'folding'));
	}

	// ── Public API ──────────────────────────────────────────────────────

	// The full document (incl. any folded-away lines) — NOT the textarea projection.
	getValue() { return this.textarea ? this._model : ''; }

	setValue(str) {
		if(!this.textarea) return this;
		this._model = str ?? '';
		this._folds = [];                 // a fresh document drops all folds
		this._markers.clear();            // …and all row-keyed gutter markers
		this._rebuildProjection();
		// A freshly loaded document starts at the TOP. Assigning textarea.value parks the caret at the end, so
		// _rebuildProjection's scroll-caret-into-view would otherwise leave a tall result scrolled to the bottom.
		this.textarea.selectionStart = this.textarea.selectionEnd = 0;
		this.textarea.scrollTop = 0;
		this._syncScroll();
		this._fireChange();
		return this;
	}

	focus() { if(this.textarea) this.textarea.focus(); return this; }

	getSelectedText() { return this.textarea.value.slice(this.textarea.selectionStart, this.textarea.selectionEnd); }

	clearSelection() { const c = this.textarea.selectionEnd; this.textarea.setSelectionRange(c, c); return this; }

	// {row, column} both 0-based. Row is MODEL space.
	getCursorPosition() {
		const caret = this.textarea.selectionStart;
		const before = this.textarea.value.slice(0, caret);
		const viewRow = (before.match(/\n/g) || []).length;
		const column = caret - (before.lastIndexOf('\n') + 1);
		return { row: this._viewRowToModelRow(viewRow), column: column };
	}

	// 1-based MODEL row, 0-based column. Auto-reveals any fold hiding the target.
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
		if(this.opts.readOnly) return this;
		const ta = this.textarea;
		const s = ta.selectionStart, e = ta.selectionEnd;
		// Accept Ace-format snippets: flatten numbered tab-stops to defaults + mark the first as `$0` (idempotent).
		let insert = CerbUI.editorCore.aceSnippetToCerb(String(text == null ? '' : text));
		let off = insert.length;
		const m = insert.indexOf('$0');
		if(m !== -1) { off = m; insert = insert.slice(0, m) + insert.slice(m + 2); }
		this._setValueAndCaret(ta.value.slice(0, s) + insert + ta.value.slice(e), s + off);
		ta.focus();
		return this;
	}

	// Highlight a MODEL row in the gutter. Reveals the row if it's hidden inside a fold so the mark is visible.
	highlightLine(row) { this._highlightRow = row; this._revealModelRow(row); this._renderGutter(); return this; }
	clearHighlight() { this._highlightRow = null; this._renderGutter(); return this; }

	// ── Gutter markers (LEFT of the line numbers; MODEL-space) ──
	// Host-driven per-line marks: JSON validation errors/warnings, a breakpoint, etc. A marker is an icon
	// (`cerb-icon-<icon>`) or a colored `pip` dot. `type` picks a default icon+color (overridable); `title` is the
	// hover tooltip. One marker per row.
	setMarker(modelRow, desc) {
		desc = desc || {};
		const preset = CerbUI.JsonEditor._MARKER_TYPES[desc.type] || {};
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

	onChange(cb) { if(typeof cb === 'function') this._changeCbs.push(cb); return this; }

	// ── JSON validation ─────────────────────────────────────────────────
	// Lint the full document and surface the FIRST syntax error as an `error` gutter marker on its line (the note
	// is the marker's hover title). Returns the lint result. Callable manually; when the `validate` option is on it
	// also runs debounced on every edit. Validation owns a single marker row (this._validationRow) so it clears
	// only its own marker — a host marker on the same row is superseded while an error stands.
	validate() {
		const r = CerbUI.JsonEditor.lint(this._model);
		if(this._validationRow !== null) { this.clearMarker(this._validationRow); this._validationRow = null; }
		if(!r.valid) {
			this._revealModelRow(r.row);              // make the line visible if it's folded away
			this.setMarker(r.row, { type: 'error', title: r.message });
			this._validationRow = r.row;
		}
		if(typeof this.opts.onValidate === 'function') { try { this.opts.onValidate(r); } catch(_) {} }
		return r;
	}

	_scheduleValidate() {
		if(!this.opts.validate) return;
		if(this._validateTimer !== null) clearTimeout(this._validateTimer);
		this._validateTimer = window.setTimeout(() => { this._validateTimer = null; this.validate(); }, this.opts.validateDelay);
	}

	// The enumerable shortcut list with OS-appropriate labels — for a future keyboard-shortcuts hint popup.
	getShortcuts() {
		const keys = CerbUI.editorCore.keys;
		return this._shortcuts.map(sc => ({ id: sc.id, label: sc.label, keys: sc.keys.map(k => keys.label(k)) }));
	}

	// ── Code folding (public API; all rows are MODEL space) ──────────────
	// A fold collapses an object/array: the line with the opening bracket stays visible, the body rows are hidden,
	// and the closing-bracket line stays visible. Fold ops don't change the document text, so they DON'T fire
	// onChange.

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

	getLine(row) { const l = this._modelLines(); return (row >= 0 && row < l.length) ? l[row] : ''; }

	destroy() {
		if(this._editorToolbar && typeof this._editorToolbar.destroy === 'function') this._editorToolbar.destroy();
		if(this._find) this._find.destroy();
		if(this._validateTimer !== null) { clearTimeout(this._validateTimer); this._validateTimer = null; }
		CerbUI.JsonEditor._instances.delete(this.el);
		if(this.textarea) {
			this.textarea.removeEventListener('input', this._onInput);
			this.textarea.removeEventListener('keydown', this._onKeydown);
			this.textarea.removeEventListener('scroll', this._onScroll);
		}
		if(this.gutter && this._onGutterClick) this.gutter.removeEventListener('click', this._onGutterClick);
		if(this._revealDisposer) { this._revealDisposer(); this._revealDisposer = null; }
	}

	// ── Keyboard shortcuts (abstract, enumerable registry) ──────────────
	// Each descriptor: { id, keys:[spec…], label, run(e) }. `keys` are editorCore.keys binding specs ('Mod' = Cmd
	// OR Ctrl). In readOnly mode only the non-mutating commands (fold/unfold/grow/shrink) are registered.

	_buildShortcuts() {
		const keys = CerbUI.editorCore.keys;
		const fold = [
			{ id:'fold',         keys:['Mod-BracketLeft'],  label:'Fold',          run:() => this._foldAtCaret() },
			{ id:'unfold',       keys:['Mod-BracketRight'], label:'Unfold',        run:() => this._unfoldAtCaret() },
			{ id:'growEditor',   keys:['Mod-Shift-ArrowDown'], label:'Taller editor',  run:() => this._resizeMaxLines(1) },
			{ id:'shrinkEditor', keys:['Mod-Shift-ArrowUp'],   label:'Shorter editor', run:() => this._resizeMaxLines(-1) },
		];
		const edit = [
			{ id:'deleteLine',   keys:['Mod-D','Alt-D'],  label:'Delete line',     run:() => this._deleteLine() },
			{ id:'moveLineUp',   keys:['Alt-ArrowUp'],    label:'Move line up',    run:() => this._moveLine(-1) },
			{ id:'moveLineDown', keys:['Alt-ArrowDown'],  label:'Move line down',  run:() => this._moveLine(1) },
			{ id:'indent',       keys:['Tab'],            label:'Indent',          run:() => this._indent() },
			{ id:'dedent',       keys:['Shift-Tab'],      label:'Dedent',          run:() => this._dedent() },
		];
		const find = [
			{ id:'find', keys:['Mod-F'], label:'Find', run:() => this._find.open() },
		];
		const list = (this.opts.readOnly ? fold : edit.concat(fold)).concat(find);
		for(const sc of list) sc._parsed = sc.keys.map(k => keys.parse(k));
		return list;
	}

	// Run the first matching shortcut; returns true if one handled the event (caller returns early).
	_dispatchShortcut(e) {
		const keys = CerbUI.editorCore.keys;
		for(const sc of this._shortcuts) {
			if(!sc._parsed.some(p => keys.matchEvent(p, e))) continue;
			e.preventDefault();
			sc.run(e);
			return true;
		}
		return false;
	}

	// Click a gutter chevron to toggle the fold on that header row (one delegated listener).
	_handleGutterClick(e) {
		const chev = e.target.closest('.cerb-ui-jsoneditor--gutter-fold');
		if(chev) {
			const mr = parseInt(chev.getAttribute('data-fold-row'), 10);
			if(!isNaN(mr)) this.toggleFold(mr);
			return;
		}
		// Clicking the LEFT marker column fires onGutterClick (e.g. toggle a breakpoint), empty slots included.
		const mk = e.target.closest('.cerb-ui-jsoneditor--gutter-marker');
		if(mk && typeof this.opts.onGutterClick === 'function') {
			const mr = parseInt(mk.getAttribute('data-model-row'), 10);
			if(!isNaN(mr)) this.opts.onGutterClick(mr, e);
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

	_handleInput(e) {
		// Our own programmatic writes (_writeValue) re-emit an `input` event via execCommand; ignore it — the
		// command that called _setValueAndCaret already drives the re-render.
		if(this._suppressInput) return;
		// Convert any pasted tabs to spaces so the stored value is always spaces (_sanitizeTabs fully refreshes).
		if(this.textarea.value.indexOf('\t') !== -1) { this._sanitizeTabs(); return; }
		this._applyProjectionEditToModel();  // fold the edit back into the full-text model
		this._renderHighlight();
		this._autosize();
		this._renderGutter();
		this._scrollCaretIntoView();
		this._fireChange();
		this._scheduleValidate();
	}

	_handleKeydown(e) {
		// Registry shortcuts run first (delete/move line, indent/dedent, fold/unfold, grow/shrink).
		if(this._dispatchShortcut(e)) return;

		// Enter — newline with JSON auto-indent (editable mode only).
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
	// wipes that stack. We instead replace only the changed span via execCommand, which records a proper undo
	// entry. Falls back to a direct write if execCommand is unavailable or refuses.
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

		// execCommand('insertText') is pathologically slow on large spans (a Replace-All across a huge doc
		// hangs). Above this threshold, write directly — fast, at the cost of native undo for this one bulk op.
		// The caller sets the caret + calls _refresh(), so no input event / render is needed here.
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
		this._applyProjectionEditToModel();  // any projection change (programmatic edit) -> model
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

	// Tab key: insert tabSize spaces at the caret, or indent every line touched by the selection.
	_indent() {
		this._revealForEdit();
		const ta = this.textarea, v = ta.value, s = ta.selectionStart, e = ta.selectionEnd;
		if(s === e) {
			this._setValueAndCaret(v.slice(0, s) + this.tab + v.slice(e), s + this.tab.length);
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

	// Enter: newline, copying the current line's indent (and one extra level if it ends with an open `{`/`[`).
	_insertNewline() {
		this._revealForEdit();
		const ta = this.textarea, v = ta.value, s = ta.selectionStart, e = ta.selectionEnd;
		const lineStart = v.lastIndexOf('\n', s - 1) + 1;
		const curBeforeCaret = v.slice(lineStart, s);
		let indent = (curBeforeCaret.match(/^[ ]*/) || [''])[0];
		if(/[\[{]\s*$/.test(curBeforeCaret)) indent += this.tab; // opened an object/array -> indent its body
		const ins = '\n' + indent;
		this._setValueAndCaret(v.slice(0, s) + ins + v.slice(e), s + ins.length);
	}

	// ⌘/Ctrl/⌥+D — delete the whole line the caret sits on, regardless of column.
	_deleteLine() {
		this._revealForEdit();
		const ta = this.textarea, v = ta.value, s = ta.selectionStart;
		const lineStart = v.lastIndexOf('\n', s - 1) + 1;
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

	// ⌥+↑ / ↓ — move the current line (or selected line-block) up/down, keeping the selection on it.
	_moveLine(dir) {
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
		let toks = this._tokenize(this.textarea.value);
		toks = this._injectIndentGuides(this._injectFoldMarks(toks));
		CerbUI.editorCore.renderTokens(this.highlight, toks, CerbUI.JsonEditor._TOK_CLASS);
		if(this._find) this._find.repaintBands();   // re-add find-match bands (the mirror was just wiped)
		this._syncScroll();
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
	// paints a faint vertical rule down each indentation level. The guide span holds the actual spaces and draws
	// its line via inset box-shadow — no width change, so glyph advances (and the caret) stay aligned. Blank lines
	// get PHANTOM guide spaces at the surrounding depth (mirror-only) so the guides continue across gaps.
	_injectIndentGuides(toks) {
		const tab = this.opts.tabSize;
		if(!this.opts.indentGuides || tab <= 0) return toks;

		const indents = this.textarea.value.split('\n').map(s => {
			let i = 0; while(i < s.length && s[i] === ' ') i++;
			return (i === s.length) ? -1 : i;          // all-spaces or empty -> blank
		});
		const blankDepth = (row) => {
			let p = 0, n = 0;
			for(let r = row - 1; r >= 0; r--) if(indents[r] >= 0) { p = indents[r]; break; }
			for(let r = row + 1; r < indents.length; r++) if(indents[r] >= 0) { n = indents[r]; break; }
			return Math.max(p, n);
		};
		const guides = (depth) => {
			const out = [];
			const levels = Math.floor(depth / tab);
			for(let i = 0; i < levels; i++) out.push({ type: 'indent-guide', value: ' '.repeat(tab) });
			const rem = depth - levels * tab;
			if(rem > 0) out.push({ type: 'text', value: ' '.repeat(rem) });
			return out;
		};

		const out = [];
		let row = 0, atLineStart = true;
		const closeBlankLine = () => { const d = blankDepth(row); if(d >= tab) out.push(...guides(d)); };

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
					out.push(...guides(sp));
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
	}

	_renderGutter() {
		if(!this.gutter) return;
		const hidden = this._hidden;
		// Map each foldable header row -> collapsed? (a detected range that's also in _folds is collapsed).
		const headerState = new Map();
		for(const r of this._foldableRanges()) headerState.set(r.headerRow, false);
		for(const f of this._folds) headerState.set(f.startRow, true);
		const anyFoldable = headerState.size > 0;             // reserve the chevron column only when needed
		// Reserve the LEFT marker column when any marker exists, or whenever a gutter-click handler is wired.
		const anyMarker = this._markers.size > 0 || typeof this.opts.onGutterClick === 'function';
		const esc = CerbUI.editorCore.escapeHtml;
		const mCount = this._modelLines().length;
		let html = '';
		for(let mr = 0; mr < mCount; mr++) {
			if(hidden.has(mr)) continue;                       // collapsed-away rows have no gutter line
			const num = mr + 1;                                // MODEL number — jumps across folds (1,2,6…)
			const active = (this._highlightRow === mr) ? ' cerb-ui-jsoneditor--gutter-line-active' : '';
			const isHeader = headerState.has(mr);
			// Marker slot, LEFT of the numbers (icon or pip). A reserved empty slot keeps the column aligned and
			// stays clickable; markers on rows hidden inside a fold simply don't render (the row is skipped above).
			let marker = '';
			if(anyMarker) {
				const mk = this._markers.get(mr);
				let cls = 'cerb-ui-jsoneditor--gutter-marker', style = '', attrs = '';
				if(mk) {
					cls += mk.pip ? ' cerb-ui-jsoneditor--gutter-marker-pip' : (mk.icon ? (' cerb-icons cerb-icon-' + mk.icon) : '');
					if(mk.type) cls += ' cerb-ui-jsoneditor--gutter-marker-' + mk.type;
					if(mk.color) style = ' style="color:var(--cerb-color-tag-' + mk.color + ')"';
					// esc() handles & < > ; also escape " for the attribute context (a validation message can carry
					// user-derived text). Defense-in-depth: authored lint messages are quote-free anyway.
					if(mk.title) attrs = ' title="' + esc(mk.title).replace(/"/g, '&quot;') + '"';
				}
				marker = '<span class="' + cls + '" data-model-row="' + mr + '"' + style + attrs + '></span>';
			}
			// Fold chevron sits to the RIGHT of the right-aligned number; an empty slot keeps the column aligned.
			const slot = !anyFoldable ? '' :
				('<span class="cerb-ui-jsoneditor--gutter-fold' +
					(isHeader ? (' cerb-icons cerb-icon-' + (headerState.get(mr) ? 'chevron-right' : 'chevron-down')) : '') +
					'"' + (isHeader ? (' data-fold-row="' + mr + '"') : '') + '></span>');
			const foldable = isHeader ? ' cerb-ui-jsoneditor--gutter-line-foldable' : '';
			html += '<div class="cerb-ui-jsoneditor--gutter-line' + active + foldable + '">' +
				marker + '<span class="cerb-ui-jsoneditor--gutter-num">' + num + '</span>' + slot + '</div>';
		}
		this.gutter.innerHTML = html;
		this.gutter.scrollTop = this.textarea.scrollTop;
	}

	// ⌘/Ctrl+Shift+↓ / ↑ — grow/shrink the editor's max visible rows (in-memory for this session). Capped at the
	// number of currently-visible lines: growing past the content does nothing.
	_resizeMaxLines(delta) {
		const docLines = this.textarea.value.split('\n').length;       // visible (projection) line count
		const ceiling = Math.max(this.opts.minLines, Math.min(docLines, 100));
		const n = Math.max(this.opts.minLines, Math.min((this.opts.maxLines || 20) + delta, ceiling));
		if(n === this.opts.maxLines) return;
		this.opts.maxLines = n;
		this._autosize();
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

	// Recompute the textarea from this._model + this._folds and re-render. A fold toggle is NOT an undoable text
	// edit, so we write the value directly. Optionally restores the caret to a model offset (mapped into the new
	// projection).
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

	// Reconcile a projection edit (typing, programmatic edit) back into this._model and remap active folds. The
	// replaced span lives entirely in visible text, so it maps cleanly to the model.
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

	// Foldable bracket ranges in MODEL space: each `{`/`[` and its matching `}`/`]` that span at least one body
	// line. Scan string-aware (brackets inside "…" don't count, honoring \ escapes; JSON strings can't cross
	// lines, so the in-string flag resets each line). endRow is the line BEFORE the closer, so the closing-bracket
	// line stays visible (and trailing commas survive in the folded projection). When several brackets open on one
	// line (e.g. `"a": [{`), keep only the widest range for that header row (one chevron per row).
	_foldableRanges() {
		const lines = this._modelLines();
		const stack = [], ranges = [];
		let inStr = false;
		for(let r = 0; r < lines.length; r++) {
			const line = lines[r];
			for(let c = 0; c < line.length; c++) {
				const ch = line[c];
				if(inStr) { if(ch === '\\') c++; else if(ch === '"') inStr = false; continue; }
				if(ch === '"') { inStr = true; continue; }
				if(ch === '{' || ch === '[') stack.push(r);
				else if(ch === '}' || ch === ']') {
					const openRow = stack.pop();
					if(openRow != null && (r - 1) > openRow) ranges.push({ headerRow: openRow, startRow: openRow, endRow: r - 1 });
				}
			}
			inStr = false; // JSON strings don't span lines
		}
		const byHeader = new Map();
		for(const rg of ranges) { const ex = byHeader.get(rg.headerRow); if(!ex || rg.endRow > ex.endRow) byHeader.set(rg.headerRow, rg); }
		return [...byHeader.values()].sort((a, b) => a.startRow - b.startRow);
	}

	// ── JSON tokenizer (drives the highlight mirror) ────────────────────
	// Line-oriented: returns a flat token list covering every character (newlines included), so
	// editorCore.renderTokens can build the colored mirror spans. Strings, numbers, and the keywords
	// true/false/null are colored; a string immediately before a `:` is a property name. Everything else
	// (structural punctuation {}[],:, whitespace) is default-colored `text`.

	_tokenize(text) {
		const lines = text.split('\n');
		const toks = [];
		for(let li = 0; li < lines.length; li++) {
			if(li > 0) toks.push({ type: 'text', value: '\n' });
			this._tokenizeLine(toks, lines[li]);
		}
		return toks;
	}

	_tokenizeLine(toks, line) {
		const RX = /"(?:\\.|[^"\\])*"?|-?\d+(?:\.\d+)?(?:[eE][+-]?\d+)?|\b(?:true|false|null)\b/g;
		let last = 0, m;
		while((m = RX.exec(line)) !== null) {
			if(m.index > last) toks.push({ type: 'text', value: line.slice(last, m.index) });
			const v = m[0], c = v.charAt(0);
			let type;
			if(c === '"') {
				let k = m.index + v.length;                       // a string before a `:` is a property name
				while(k < line.length && (line[k] === ' ' || line[k] === '\t')) k++;
				type = (line[k] === ':') ? 'property' : 'string';
			} else if(c === 't' || c === 'f' || c === 'n') {
				type = 'keyword';
			} else {
				type = 'number';
			}
			toks.push({ type: type, value: v });
			last = m.index + v.length;
		}
		if(last < line.length) toks.push({ type: 'text', value: line.slice(last) });
	}

	// ── JSON linter (pure; no DOM) ──────────────────────────────────────
	// Returns { valid:true } or { valid:false, row, column, position, message } for the FIRST syntax error
	// (row/column 0-based, row = MODEL row since it runs on the model text). Hybrid: JSON.parse is the AUTHORITY on
	// validity (so we never false-reject valid JSON across engines), and _locate() — a small recursive-descent
	// scanner — pinpoints WHERE, because engine error messages don't reliably carry a location. Empty/whitespace
	// is valid (a host enforces "required" separately). Messages are fixed, quote-free phrases (never a `"`), so
	// they're safe in the gutter title even before the attribute's own quote-escaping.
	static lint(text) {
		const s = (text == null) ? '' : String(text);
		if(s.trim() === '') return { valid: true };
		try { JSON.parse(s); return { valid: true }; } catch(_) {}

		let loc = null;
		try { loc = CerbUI.JsonEditor._locate(s); } catch(_) { loc = null; }
		const pos = (loc && typeof loc.position === 'number') ? Math.max(0, Math.min(loc.position, s.length)) : 0;
		const before = s.slice(0, pos);
		const row = (before.match(/\n/g) || []).length;
		const column = pos - (before.lastIndexOf('\n') + 1);
		return { valid: false, row, column, position: pos, message: (loc && loc.message) || 'Invalid JSON' };
	}

	// Walk the JSON grammar over `s`, tracking an index, and return { position, message } at the first place it
	// can't proceed (or null if it parses clean — shouldn't happen once JSON.parse has failed, but lint() falls
	// back gracefully). Intentionally lenient where JSON.parse is stricter (raw control chars, leading zeros) —
	// it's only a LOCATOR; JSON.parse already decided the text is invalid.
	static _locate(s) {
		let i = 0;
		const n = s.length;
		const isWs = (c) => c === ' ' || c === '\t' || c === '\n' || c === '\r';
		const fail = (pos, message) => { throw { position: pos, message }; };
		const skipWs = () => { while(i < n && isWs(s[i])) i++; };

		const parseString = () => {                                  // assumes s[i] === '"'
			const start = i; i++;
			while(i < n) {
				const c = s[i];
				if(c === '\\') {
					i++;
					if(i >= n) fail(start, 'Unterminated string');
					const e = s[i];
					if('"\\/bfnrt'.indexOf(e) !== -1) { i++; continue; }
					if(e === 'u') {
						for(let k = 1; k <= 4; k++) { if(!/[0-9a-fA-F]/.test(s[i + k] || '')) fail(i, 'Invalid unicode escape'); }
						i += 5; continue;
					}
					fail(i, 'Invalid string escape');
				}
				if(c === '"') { i++; return; }
				if(c === '\n') fail(start, 'Unterminated string');   // JSON strings can't span lines
				i++;
			}
			fail(start, 'Unterminated string');
		};

		const parseNumber = () => {
			const re = /-?(?:0|[1-9]\d*)(?:\.\d+)?(?:[eE][+-]?\d+)?/y;
			re.lastIndex = i;
			const m = re.exec(s);
			if(!m || m[0].length === 0) fail(i, 'Invalid number');
			i += m[0].length;
		};

		const parseValue = () => {
			skipWs();
			if(i >= n) fail(i, 'Unexpected end of input');
			const c = s[i];
			if(c === '"') return parseString();
			if(c === '{') return parseObject();
			if(c === '[') return parseArray();
			if(c === '-' || (c >= '0' && c <= '9')) return parseNumber();
			if(s.startsWith('true', i)) { i += 4; return; }
			if(s.startsWith('false', i)) { i += 5; return; }
			if(s.startsWith('null', i)) { i += 4; return; }
			fail(i, 'Unexpected character');
		};

		const parseObject = () => {
			i++;                                                     // consume {
			skipWs();
			if(s[i] === '}') { i++; return; }
			while(true) {
				skipWs();
				if(i >= n) fail(i, 'Unexpected end of input');
				if(s[i] !== '"') fail(i, 'Expected a property name');
				parseString();
				skipWs();
				if(s[i] !== ':') fail(i, "Expected ':' after a property name");
				i++;
				parseValue();
				skipWs();
				if(s[i] === ',') { i++; continue; }
				if(s[i] === '}') { i++; return; }
				fail(i, "Expected ',' or '}'");
			}
		};

		const parseArray = () => {
			i++;                                                     // consume [
			skipWs();
			if(s[i] === ']') { i++; return; }
			while(true) {
				parseValue();
				skipWs();
				if(s[i] === ',') { i++; continue; }
				if(s[i] === ']') { i++; return; }
				fail(i, "Expected ',' or ']'");
			}
		};

		try {
			parseValue();
			skipWs();
			if(i < n) return { position: i, message: 'Trailing characters after the JSON value' };
			return null;
		} catch(e) {
			if(e && typeof e.position === 'number') return { position: e.position, message: e.message };
			return null;
		}
	}
};

// Token type -> CSS class for the highlight mirror (text has no class = default literal color). JSON colors
// reuse the shared editor syntax palette: property names take the KATA key color (teal), strings/numbers their
// own colors, and true/false/null the type color (purple).
CerbUI.JsonEditor._TOK_CLASS = {
	property:        'cerb-ui-jsoneditor--tok-property',
	string:          'cerb-ui-jsoneditor--tok-string',
	number:          'cerb-ui-jsoneditor--tok-number',
	keyword:         'cerb-ui-jsoneditor--tok-keyword',
	foldmark:        'cerb-ui-jsoneditor--fold-indicator cerb-icons cerb-icon-move-horizontal',
	'indent-guide':  'cerb-ui-jsoneditor--indent-guide',
};

// Gutter marker type presets: a default icon (a `cerb-icon-<name>`) or `pip` (a colored dot) + a tag color.
// `setMarker` lets the caller override any of icon/pip/color/title. The hook for client-side JSON validation.
CerbUI.JsonEditor._MARKER_TYPES = {
	error:      { icon: 'circle-exclamation-mark', color: 'red' },
	warning:    { icon: 'alert',                    color: 'orange' },
	info:       { icon: 'circle-info',              color: 'blue' },
	breakpoint: { pip: true,                        color: 'red' },
};
