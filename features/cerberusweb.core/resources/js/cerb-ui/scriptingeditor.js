/*
 * CerbUI.ScriptingEditor — a plain-JS editor for Cerb's Twig/"KataScript" scripting + template documents
 * (the eventual replacement for the Ace `ace/mode/twig` + `.placeholders` editors: email signatures, HTML /
 * visualization widget templates, bot scripting, etc.).
 *
 * Unlike KataEditor the whole document is ONE big value — free text with embedded `{{ … }}` / `{% … %}` tags,
 * no KATA key hierarchy and (for now) no code folding. So there's no model/projection and no hidden value-carrier:
 * the textarea IS the value. It shares the overlay-highlight + caret-anchored autocomplete machinery with the
 * rest of the family via CerbUI.editorCore, and reuses editorCore.kataScript for BOTH the tag tokenizer and the
 * scripting autocomplete (commands/functions/filters/args) — the same brain KataEditor uses inside a value.
 *
 * Markup (the gallery / template supplies it):
 *   <div class="cerb-ui-scriptingeditor" id="ed">
 *     <div class="cerb-ui-scriptingeditor--gutter" aria-hidden="true"></div>   (optional)
 *     <div class="cerb-ui-scriptingeditor--field">
 *       <div class="cerb-ui-scriptingeditor--highlight" aria-hidden="true"></div>
 *       <textarea class="cerb-ui-scriptingeditor--input" name="…" spellcheck="false"></textarea>
 *       <span class="cerb-ui-scriptingeditor--caret-anchor"></span>
 *     </div>
 *   </div>
 *
 * Usage:
 *   new CerbUI.ScriptingEditor(document.getElementById('ed'), { minLines: 4, maxLines: 25 });
 *   new CerbUI.ScriptingEditor(el, { readOnly: true });
 *
 * Form integration: the textarea keeps its name= and holds the full value directly (no folding), so native
 * FormData(form) submit and external .val() reads just work. CSS lives in cerb.css (.cerb-ui-scriptingeditor--*).
 */
CerbUI.ScriptingEditor = class {
	static _instances = new WeakMap();
	static from(el) { return this._instances.get(el); }

	// Wrap a BARE <textarea> or <input type=text> in the editor shell and construct — for migrating fields that
	// only exist as plain controls (e.g. the legacy-bot `.placeholders` fields scattered across action templates),
	// with no per-template markup change. A <textarea> becomes the --input directly. An <input> spawns a shadow
	// textarea as the visible editing surface while the ORIGINAL named control stays (hidden) as the value carrier,
	// mirroring the editor value back on every change — so the form POST is unchanged and the value stays one line
	// (singleLine prevents newlines; the input's native newline-stripping is a backstop). Idempotent.
	static enhance(field, opts = {}) {
		return CerbUI.editorCore.enhanceEditor(this, field, opts, { gutter: opts.gutter, singleLine: opts.singleLine });
	}

	// Element-class namespace (`.cerb-ui-<_NS>--input`, …). A subclass (e.g. CerbUI.DataQuery) overrides this +
	// its own `_instances`/`_TOK_CLASS` to reuse this multi-line shell under a different class prefix.
	static _NS = 'scriptingeditor';

	static _DEFAULTS = {
		onAutocomplete: null,     // (ctx) -> Array<item> | Promise<...>; reserved for Phase 2 variable scope
		context: '',              // passed through to onAutocomplete
		autocompleteDelay: 200,   // ms debounce for suggestions while typing
		minLines: 2,              // editor never shrinks below this many rows
		maxLines: 25,             // grows to this many rows, then scrolls (data-editor-lines overrides)
		tabSize: 2,               // a Tab inserts this many spaces; Shift+Tab dedents by up to this many
		indentGuides: false,      // templates aren't hierarchically indented — off by default
		gutter: true,             // show the left line-number gutter (set false, or omit the element, to hide)
		diffGutter: false,        // mark added/modified/deleted lines in the gutter vs a checkpoint baseline (captured
		                          //   on open; re-capture via resetDiffBaseline()). Needs a gutter. See getDiffState().
		readOnly: false,          // highlight only; disable text-mutating keys (data-editor-readonly overrides)
		singleLine: false,        // VALUE is one line: Enter suppressed, pasted newlines -> spaces, wrap not scroll, no gutter
		placeholder: null,
		onGutterClick: null,      // (modelRow, e) when the left marker column is clicked
	};

	constructor(el, opts = {}) {
		el = (typeof el === 'string') ? document.querySelector(el) : el;
		// Polymorphic: a bare <textarea>/<input> self-builds the editor shell so callers can skip the boilerplate.
		el = CerbUI.editorCore.resolveEditorEl(el, this.constructor._NS, { gutter: opts.gutter, singleLine: opts.singleLine });
		if(!el) return;

		this.el = el;
		this.opts = Object.assign({}, CerbUI.ScriptingEditor._DEFAULTS, opts);

		// Single-line (wrap) mode: no gutter (one logical line), default to a 1-row floor, and tag the wrapper so
		// the SCSS switches both text layers to pre-wrap. Enter-suppression + newline-stripping live in the handlers.
		if(this.opts.singleLine) {
			this.opts.gutter = false;
			if(opts.minLines == null) this.opts.minLines = 1;
			el.classList.add('cerb-ui-' + this.constructor._NS + '--singleline');
		}

		const ns = '.cerb-ui-' + this.constructor._NS + '--'; // outer element selectors (a subclass overrides _NS)
		const input = el.querySelector(ns + 'input');
		const lines = input && input.getAttribute('data-editor-lines');
		if(lines) this.opts.maxLines = parseInt(lines, 10) || this.opts.maxLines;
		if(input && input.hasAttribute('data-editor-readonly')) this.opts.readOnly = true;

		this.textarea = input;
		this.field = el.querySelector(ns + 'field');
		this.highlight = el.querySelector(ns + 'highlight');
		this.gutter = this.opts.gutter ? el.querySelector(ns + 'gutter') : null;
		this._gutterPadBottom = CerbUI.editorCore.gutterPadBottom(this.gutter);  // read BEFORE _autosize writes it
		this.caretAnchor = el.querySelector(ns + 'caret-anchor');
		if(!this.textarea || !this.field || !this.highlight || !this.caretAnchor) return;

		this.tab = ' '.repeat(this.opts.tabSize);
		this._highlightRow = null;   // a MODEL row marked active in the gutter, or null
		this._markers = new Map();   // row -> gutter marker descriptor {type,icon,color,title,pip}
		this._lineDecos = new Map(); // row -> CSS class for a full-width tinted body band (edit flash / highlightLine)
		// Gutter diff vs a checkpoint baseline (opt-in diffGutter). The baseline is NOT derived from content, so it
		// survives setValue(); editorCore.diff diffs the live value against it and colors the gutter rows. Captured
		// at the end of the constructor (after the textarea holds its initial value). See editorCore.diff.
		this._diffBaseline = null;       // normalized checkpoint text, or null when diffGutter is off
		this._diffRows = new Map();      // row -> 'added'|'modified'
		this._diffDeletions = new Set(); // rows with a deletion boundary ABOVE them
		this._diffAtEnd = false;         // a deletion sits past the last row
		this._diffRaf = 0;               // rAF handle coalescing recompute-on-change
		this._changeCbs = [];
		this._suppressInput = false; // true while _writeValue applies an edit (ignore the echoed `input` event)

		// Code, not prose — disable the browser's text-assist features that fight the overlay + suggestions.
		this.textarea.spellcheck = false;
		this.textarea.setAttribute('autocomplete', 'off');
		this.textarea.setAttribute('autocorrect', 'off');
		this.textarea.setAttribute('autocapitalize', 'off');
		if(this.opts.placeholder != null) this.textarea.placeholder = this.opts.placeholder;
		if(this.opts.readOnly) {
			this.textarea.readOnly = true;
			this.el.classList.add('cerb-ui-' + this.constructor._NS + '--readonly');
		}

		this._ac = new CerbUI.editorCore.Autocomplete({
			textarea: this.textarea,
			caretAnchor: this.caretAnchor,
			context: this.opts.context,
			editor: this,
			delay: this.opts.autocompleteDelay,
			onScope: (text, caret) => this._scopeAt(text, caret),
			onItems: (ctx) => this._autocompleteItems(ctx),
			onAfterApply: () => this._refresh(),
		});

		this._shortcuts = this._buildShortcuts();

		this.constructor._instances.set(el, this);

		this._onInput = (e) => this._handleInput(e);
		this._onKeydown = (e) => this._handleKeydown(e);
		this._onScroll = () => this._syncScroll();
		this._onBlur = () => { this._ac.clearTimer(); };
		this._onGutterClick = (e) => this._handleGutterClick(e);

		this.textarea.addEventListener('input', this._onInput);
		this.textarea.addEventListener('keydown', this._onKeydown);
		this.textarea.addEventListener('scroll', this._onScroll, { passive: true });
		this.textarea.addEventListener('blur', this._onBlur);
		if(this.gutter) this.gutter.addEventListener('click', this._onGutterClick);

		// Re-autosize on width changes (narrowing toggles the horizontal scrollbar syncOverlayHeight compensates for).
		this._resizeDisposer = CerbUI.editorCore.observeWidth(this.field, () => this._autosize());

		this._renderHighlight();
		this._autosize();
		this._renderGutter();

		// The diff baseline = the document as it stands now (the initial "checkpoint"). Captured after the textarea
		// holds its value; re-captured via resetDiffBaseline() (e.g. on run). No-op unless diffGutter is enabled.
		if(this.opts.diffGutter)
			this._diffBaseline = CerbUI.editorCore.lineDiff.normalize(this.getValue());

		// Core editor-family hook: a caller can add extensible toolbar `sections` to any editor (opt-in via opts.toolbar).
		// Inherited by DataQuery. No-op unless opts.toolbar is set.
		CerbUI.editorCore.attachToolbar(this, this.opts);

		// Placeholder scope (inherited by DataQuery): a wrapper tagged `.placeholders` opts into the FULL floating
		// strip (placeholders + test + help) on focus, instead of just the inline insert button in its toolbar.
		if(CerbUI.placeholders && CerbUI.placeholders.hasScope(el) && el.classList.contains('placeholders')) {
			const placement = el.getAttribute('data-cerb-placeholders-placement') || 'auto';
			this.textarea.addEventListener('focus', () => {
				CerbUI.placeholders.attach(el, this.textarea, { placement: placement });
			});
		}

		// Find/Replace (Mod-F) — shared controller + a 1:1 (no folding) adapter. Inherited by DataQuery.
		this._find = new CerbUI.editorCore.FindController(this, CerbUI.editorCore.makeFindAdapter(this, 'linear'));
	}

	// ── Public API ──────────────────────────────────────────────────────

	getValue() { return this.textarea ? this.textarea.value : ''; }

	setValue(str) {
		if(!this.textarea) return this;
		this._suppressInput = true;
		this.textarea.value = str ?? '';
		this._suppressInput = false;
		this._renderHighlight();
		this._autosize();
		this._renderGutter();
		this._fireChange();
		return this;
	}

	focus() { if(this.textarea) this.textarea.focus(); return this; }

	getSelectedText() { return this.textarea.value.slice(this.textarea.selectionStart, this.textarea.selectionEnd); }

	clearSelection() { const c = this.textarea.selectionEnd; this.textarea.setSelectionRange(c, c); return this; }

	getCursorPosition() {
		const caret = this.textarea.selectionStart;
		const before = this.textarea.value.slice(0, caret);
		return { row: (before.match(/\n/g) || []).length, column: caret - (before.lastIndexOf('\n') + 1) };
	}

	gotoLine(line, column) {
		const rows = this.textarea.value.split('\n');
		const r = Math.max(0, Math.min((line || 1) - 1, rows.length - 1));
		let off = 0;
		for(let i = 0; i < r; i++) off += rows[i].length + 1;
		off += Math.min(column || 0, rows[r].length);
		this.textarea.focus();
		this.textarea.setSelectionRange(off, off);
		this.scrollToLine(r);
		return this;
	}

	setCursorPosition(row, column) { return this.gotoLine((row || 0) + 1, column || 0); }

	scrollToLine(row) {
		const cs = window.getComputedStyle(this.textarea);
		const lh = parseFloat(cs.lineHeight) || (parseFloat(cs.fontSize) * 1.5);
		this.textarea.scrollTop = Math.max(0, row * lh);
		this._syncScroll();
		return this;
	}

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

	// Wrap the current selection in `before`/`after` (after defaults to before). With no selection, inserts the pair
	// with the caret between them. The primitive toolbar formatting actions use this (e.g. <b>…</b>).
	wrapSelection(before, after) {
		if(this.opts.readOnly) return this;
		after = (after == null) ? before : after;
		const sel = this.getSelectedText();
		this.insertSnippet(String(before) + (sel || '$0') + String(after));
		return this;
	}

	// Insert text at the caret (the `cerb.insertAtCursor` replacement — the hook the placeholder-insert menus
	// use). `opts.replace` first clears the whole document (matches the old Ace handler's `e.replace`). Content is
	// literal (e.g. `{{ticket_id}}`); a single `$0` still marks the caret if present.
	insertAtCursor(content, opts) {
		if(this.opts.readOnly) return this;
		opts = opts || {};
		if(opts.replace) this.setValue('');
		this.insertSnippet(content);
		return this;
	}

	// Append text at the end of the document (the `cerb.appendText` replacement), padding with a blank line first
	// if the document is non-empty and doesn't already end in a newline.
	appendText(content) {
		if(this.opts.readOnly) return this;
		let v = this.getValue();
		if(v.length > 0 && v.charAt(v.length - 1) !== '\n') v += '\n\n';
		this._setValueAndCaret(v, v.length);
		this.insertSnippet(content);
		return this;
	}

	highlightLine(row) { this._highlightRow = row; this._renderGutter(); return this; }
	clearHighlight() { this._highlightRow = null; this._renderGutter(); return this; }

	// ── Line decorations (full-width tinted body bands; linear so MODEL row == VIEW row) ──
	// Replace the whole set of bands in one shot: a Map or plain object of row -> CSS class (the class supplies the
	// color). Drives the agent edit-flash (CerbUI.editorCore.flashEditRange feature-detects setLineDecorations for
	// the green/red diff bands) and flashLine below. Geometry is shared with KataEditor via editorCore.
	setLineDecorations(map) {
		this._lineDecos = new Map();
		if(map instanceof Map) { for(const [k, v] of map) this._lineDecos.set(k | 0, v); }
		else if(map && typeof map === 'object') { for(const k in map) this._lineDecos.set(parseInt(k, 10), map[k]); }
		this._renderLineDecorations();
		return this;
	}
	clearLineDecorations() { if(this._lineDecos.size) { this._lineDecos.clear(); this._renderLineDecorations(); } return this; }
	_renderLineDecorations() {
		CerbUI.editorCore.renderLineDecorations(this, 'cerb-ui-scriptingeditor--line-deco');
	}

	// Briefly tint a row (the highlightLine agent command), token-guarded so a rapid second flash owns the cue.
	// Distinct from highlightLine/clearHighlight (the gutter active marker).
	flashLine(row, opts) {
		if(row == null) return this;
		this.setLineDecorations({ [row]: 'cerb-ui-scriptingeditor--line-flash' });
		this.scrollToLine(row);
		const token = (this._flashToken = (this._flashToken || 0) + 1);
		setTimeout(() => { if(this._flashToken === token) this.clearLineDecorations(); }, (opts && opts.duration) || 1300);
		return this;
	}

	// ── Gutter diff vs a checkpoint baseline (opt-in diffGutter; logic shared with KataEditor via editorCore.diff) ──
	// setDiffBaseline sets the checkpoint the gutter diffs against (defaults to the current value); resetDiffBaseline
	// re-baselines to the current value (clears the marks — the host calls this on run/save). getDiffState exposes the
	// same hunks to an editor agent (the getDiff command). All no-ops unless diffGutter is enabled.
	setDiffBaseline(text) { return CerbUI.editorCore.diff.setBaseline(this, text); }
	resetDiffBaseline() { return this.setDiffBaseline(this.getValue()); }
	getDiffBaseline() { return this._diffBaseline; }
	getDiffState() { return CerbUI.editorCore.diff.getState(this); }

	// ── Gutter markers (LEFT of the line numbers) ──
	setMarker(row, desc) {
		desc = desc || {};
		const preset = CerbUI.ScriptingEditor._MARKER_TYPES[desc.type] || {};
		this._markers.set(row, {
			type:  desc.type || null,
			pip:   (desc.pip != null) ? !!desc.pip : !!preset.pip,
			icon:  desc.icon || preset.icon || null,
			color: desc.color || preset.color || null,
			title: desc.title || '',
		});
		this._renderGutter();
		return this;
	}
	clearMarker(row) { if(this._markers.delete(row)) this._renderGutter(); return this; }
	clearMarkers() { if(this._markers.size) { this._markers.clear(); this._renderGutter(); } return this; }
	getMarkers() { return new Map(this._markers); }

	onChange(cb) { if(typeof cb === 'function') this._changeCbs.push(cb); return this; }

	openAutocomplete() { this._ac.trigger(); return this; }

	getShortcuts() {
		const keys = CerbUI.editorCore.keys;
		return this._shortcuts.map(sc => ({ id: sc.id, label: sc.label, keys: sc.keys.map(k => keys.label(k)) }));
	}

	destroy() {
		if(this._editorToolbar && typeof this._editorToolbar.destroy === 'function') this._editorToolbar.destroy();
		if(this._find) this._find.destroy();
		this._ac.destroy();
		this.constructor._instances.delete(this.el);
		if(this.textarea) {
			this.textarea.removeEventListener('input', this._onInput);
			this.textarea.removeEventListener('keydown', this._onKeydown);
			this.textarea.removeEventListener('scroll', this._onScroll);
			this.textarea.removeEventListener('blur', this._onBlur);
		}
		if(this.gutter && this._onGutterClick) this.gutter.removeEventListener('click', this._onGutterClick);
		if(this._diffRaf) { cancelAnimationFrame(this._diffRaf); this._diffRaf = 0; }
		CerbUI.editorCore.diff.closePopover(this);
		if(this._resizeDisposer) { this._resizeDisposer(); this._resizeDisposer = null; }
		if(this._revealDisposer) { this._revealDisposer(); this._revealDisposer = null; }
	}

	// ── Keyboard shortcuts ──────────────────────────────────────────────

	_buildShortcuts() {
		const view = [
			{ id:'growEditor',   keys:['Mod-Shift-ArrowDown'], label:'Taller editor',  menu:'close', run:() => this._resizeMaxLines(1) },
			{ id:'shrinkEditor', keys:['Mod-Shift-ArrowUp'],   label:'Shorter editor', menu:'close', run:() => this._resizeMaxLines(-1) },
			{ id:'autocomplete', keys:['Mod-Space'],      label:'Show suggestions',menu:'open',  run:() => this._ac.trigger() },
		];
		const edit = [
			{ id:'deleteLine',   keys:['Mod-D','Alt-D'],  label:'Delete line',     menu:'close', run:() => this._deleteLine() },
			{ id:'moveLineUp',   keys:['Alt-ArrowUp'],    label:'Move line up',    menu:'close', run:() => this._moveLine(-1) },
			{ id:'moveLineDown', keys:['Alt-ArrowDown'],  label:'Move line down',  menu:'close', run:() => this._moveLine(1) },
			{ id:'indent',       keys:['Tab'],            label:'Indent',          menu:'close', run:() => this._indent() },
			{ id:'dedent',       keys:['Shift-Tab'],      label:'Dedent',          menu:'close', run:() => this._dedent() },
		];
		const find = [
			{ id:'find', keys:['Mod-F'], label:'Find', menu:'close', run:() => this._find.open() },
		];
		const list = (this.opts.readOnly ? view : edit.concat(view)).concat(find);
		const keys = CerbUI.editorCore.keys;
		for(const sc of list) sc._parsed = sc.keys.map(k => keys.parse(k));
		return list;
	}

	_dispatchShortcut(e) {
		const keys = CerbUI.editorCore.keys;
		for(const sc of this._shortcuts) {
			if(!sc._parsed.some(p => keys.matchEvent(p, e))) continue;
			e.preventDefault();
			if(this._ac.isOpen()) {
				e.stopPropagation();
				if(sc.menu === 'close') this._ac.close();
			}
			sc.run(e);
			return true;
		}
		return false;
	}

	_handleGutterClick(e) {
		const mk = e.target.closest('.cerb-ui-scriptingeditor--gutter-marker');
		if(mk && typeof this.opts.onGutterClick === 'function') {
			const r = parseInt(mk.getAttribute('data-model-row'), 10);
			if(!isNaN(r)) this.opts.onGutterClick(r, e);
			return;
		}
		// A click on a diff-marked row (not the marker slot) floats a diff panel scrolled to that hunk.
		if(this.opts.diffGutter) {
			const line = e.target.closest('.cerb-ui-scriptingeditor--gutter-line');
			const numEl = line && line.querySelector('.cerb-ui-scriptingeditor--gutter-num');
			const r = numEl ? (parseInt(numEl.textContent, 10) - 1) : NaN;
			if(!isNaN(r) && CerbUI.editorCore.diff.rowIndex(this, r) >= 0)
				CerbUI.editorCore.diff.openPopover(this, r, e, { className: 'cerb-ui-scriptingeditor--diff-popover', anchorEl: this.gutter });
		}
	}

	_handleInput(e) {
		if(this._suppressInput) return;
		if(this.textarea.value.indexOf('\t') !== -1) { this._sanitizeTabs(); return; }
		// Single-line value: a paste/IME can still introduce newlines — collapse them to spaces (1:1, caret unmoved).
		if(this.opts.singleLine && this.textarea.value.indexOf('\n') !== -1) { this._stripNewlines(); return; }
		this._renderHighlight();
		this._autosize();
		this._renderGutter();
		this._scrollCaretIntoView();
		this._fireChange();
		this._ac.clearTimer();
		const it = e && e.inputType;
		if(it === 'historyUndo' || it === 'historyRedo') return;
		if(it === 'deleteContentBackward' || it === 'deleteContentForward') return;
		this._ac.schedule();
	}

	_handleKeydown(e) {
		const menuOpen = this._ac.isOpen();

		if(this._dispatchShortcut(e)) return;

		if(menuOpen) {
			const plain = !(e.metaKey || e.ctrlKey || e.altKey);
			if(plain && e.key === 'ArrowDown') { e.preventDefault(); e.stopPropagation(); this._ac.moveSelection(+1); return; }
			if(plain && e.key === 'ArrowUp' && this._ac.navigated) { e.preventDefault(); e.stopPropagation(); this._ac.moveSelection(-1); return; }
			if(e.key === 'ArrowDown' || e.key === 'ArrowUp' || e.key === 'ArrowLeft' || e.key === 'ArrowRight') {
				e.stopPropagation(); this._ac.close(); return;
			}
			if(e.key === 'Enter') {
				if(this._ac.navigated) { e.preventDefault(); e.stopPropagation(); this._ac.acceptSelection(e); return; }
				e.preventDefault(); e.stopPropagation(); this._ac.close();
				if(!this.opts.readOnly) this._insertNewline();
				return;
			}
			if(e.key === 'Escape') { e.preventDefault(); e.stopPropagation(); this._ac.close(); return; }
		}

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

	// Apply a new value while preserving native undo (see KataEditor._writeValue for the rationale).
	_writeValue(value) {
		const ta = this.textarea;
		const old = ta.value;
		if(old === value) return;
		let p = 0; const max = Math.min(old.length, value.length);
		while(p < max && old[p] === value[p]) p++;
		let so = old.length, sn = value.length;
		while(so > p && sn > p && old[so - 1] === value[sn - 1]) { so--; sn--; }
		const insert = value.slice(p, sn);
		// execCommand('insertText') is pathologically slow on large spans (Replace-All on a huge doc hangs).
		// Above this threshold write directly — fast, at the cost of native undo for this one bulk op; the
		// caller sets the caret + calls _refresh(), so no input event / render is needed here.
		const BIG_EDIT = 10000;
		if(Math.max(so - p, insert.length) > BIG_EDIT) { ta.value = value; return; }
		let ok = false;
		this._suppressInput = true;
		try {
			ta.focus();
			ta.setSelectionRange(p, so);
			ok = (insert.length === 0) ? document.execCommand('delete') : document.execCommand('insertText', false, insert);
		} catch(_) {}
		this._suppressInput = false;
		if(!ok || ta.value !== value) ta.value = value;
	}

	_refresh() {
		this._renderHighlight();
		this._autosize();
		this._renderGutter();
		this._scrollCaretIntoView();
		this._fireChange();
	}

	_scrollCaretIntoView() {
		const ta = this.textarea;
		const cs = window.getComputedStyle(ta);
		const lh = parseFloat(cs.lineHeight) || (parseFloat(cs.fontSize) * 1.5);
		const padTop = parseFloat(cs.paddingTop) || 0;
		const padBottom = parseFloat(cs.paddingBottom) || 0;
		const before = ta.value.slice(0, ta.selectionStart);
		const row = (before.match(/\n/g) || []).length;
		const lineTop = padTop + row * lh;
		const lineBottom = lineTop + lh;
		if(lineTop < ta.scrollTop) ta.scrollTop = lineTop - padTop;
		else if(lineBottom > ta.scrollTop + ta.clientHeight) ta.scrollTop = lineBottom - ta.clientHeight + padBottom;
		this._syncScroll();
	}

	_fireChange() {
		CerbUI.editorCore.diff.schedule(this);   // repaint the gutter diff vs the baseline (no-op unless diffGutter)
		for(const cb of this._changeCbs) { try { cb(this.getValue()); } catch(_) {} }
	}

	_indent() {
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

	_dedent() {
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

	_insertNewline() {
		if(this.opts.singleLine) return;  // the value is one line — Enter is a no-op (keydown already prevented default)
		this._ac.clearTimer();
		const ta = this.textarea, v = ta.value, s = ta.selectionStart, e = ta.selectionEnd;
		const lineStart = v.lastIndexOf('\n', s - 1) + 1;
		const indent = (v.slice(lineStart, s).match(/^[ \t]*/) || [''])[0];
		const ins = '\n' + indent;
		this._setValueAndCaret(v.slice(0, s) + ins + v.slice(e), s + ins.length);
		// A newline is a token boundary like a space — re-suggest for the fresh scope on the new line (the
		// _writeValue above suppresses the input event, so schedule here explicitly). Harmless when there's
		// nothing to suggest (onItems returns [] outside a scope, and the menu just stays closed).
		this._ac.schedule();
	}

	_deleteLine() {
		this._ac.clearTimer();
		const ta = this.textarea, v = ta.value, s = ta.selectionStart;
		const lineStart = v.lastIndexOf('\n', s - 1) + 1;
		const lineEnd = v.indexOf('\n', s);
		let cutStart, cutEnd;
		if(lineEnd === -1) { cutStart = lineStart > 0 ? lineStart - 1 : 0; cutEnd = v.length; }
		else { cutStart = lineStart; cutEnd = lineEnd + 1; }
		const next = v.slice(0, cutStart) + v.slice(cutEnd);
		const newLineStart = (cutStart === lineStart) ? lineStart : (v.lastIndexOf('\n', cutStart - 1) + 1);
		const col = s - lineStart;
		const afterNl = next.indexOf('\n', newLineStart);
		const lineLen = (afterNl === -1 ? next.length : afterNl) - newLineStart;
		this._setValueAndCaret(next, newLineStart + Math.min(col, lineLen));
	}

	_moveLine(dir) {
		this._ac.clearTimer();
		const ta = this.textarea, v = ta.value, s = ta.selectionStart, e = ta.selectionEnd;
		const rows = v.split('\n');
		const r0 = (v.slice(0, s).match(/\n/g) || []).length;
		let r1 = (v.slice(0, e).match(/\n/g) || []).length;
		if(e > s && e === (v.lastIndexOf('\n', e - 1) + 1)) r1--;
		if(r1 < r0) r1 = r0;
		if(dir < 0 && r0 === 0) return;
		if(dir > 0 && r1 === rows.length - 1) return;
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

	// Single-line mode: collapse any newlines to a single space (1 char -> 1 char, so the caret stays put). A space
	// (not '') avoids jamming tokens together when multi-line content is pasted into a one-line value.
	_stripNewlines() {
		const ta = this.textarea, caret = ta.selectionStart;
		this._setValueAndCaret(ta.value.replace(/\n/g, ' '), caret);
	}

	// ── Highlighting + gutter + sizing ──────────────────────────────────

	_renderHighlight() {
		let toks = this._tokenize(this.textarea.value);
		if(this.opts.indentGuides) toks = this._injectIndentGuides(toks);
		CerbUI.editorCore.renderTokens(this.highlight, toks, this.constructor._TOK_CLASS);
		if(this._find) this._find.repaintBands();   // re-add find-match bands (the mirror was just wiped)
		this._renderLineDecorations();              // …and any full-width line bands (edit flash / highlightLine)
		this._syncScroll();
	}

	// Plaintext + KataScript tags: feed each line through editorCore.kataScript with a plain-text tokenizer,
	// threading the open-tag state across lines so a multi-line `{% … %}` highlights continuously.
	_tokenize(text) {
		const lines = text.split('\n');
		const toks = [];
		const plain = function(t, s, bt) { if(s) t.push({ type: bt, value: s }); };
		let scriptOpen = null;
		for(let li = 0; li < lines.length; li++) {
			if(li > 0) toks.push({ type: 'text', value: '\n' });
			scriptOpen = CerbUI.editorCore.kataScript.tokenize(toks, lines[li], 'text', scriptOpen, plain);
		}
		return toks;
	}

	_injectIndentGuides(toks) {
		const tab = this.opts.tabSize;
		if(tab <= 0) return toks;
		const out = [];
		let atLineStart = true;
		for(const t of toks) {
			if(t.type === 'text' && t.value === '\n') { out.push(t); atLineStart = true; continue; }
			if(atLineStart) {
				atLineStart = false;
				const v = t.value;
				let sp = 0; while(sp < v.length && v[sp] === ' ') sp++;
				if(sp >= tab) {
					const levels = Math.floor(sp / tab);
					for(let i = 0; i < levels; i++) out.push({ type: 'indent-guide', value: ' '.repeat(tab) });
					const rem = sp - levels * tab;
					if(rem > 0) out.push({ type: 'text', value: ' '.repeat(rem) });
					const rest = v.slice(sp);
					if(rest.length) out.push({ type: t.type, value: rest });
					continue;
				}
			}
			out.push(t);
		}
		return out;
	}

	_syncScroll() {
		CerbUI.editorCore.syncScroll(this.textarea, this.highlight);
		if(this.gutter) this.gutter.scrollTop = this.textarea.scrollTop;
	}

	_renderGutter() {
		if(!this.gutter) return;
		const anyMarker = this._markers.size > 0 || typeof this.opts.onGutterClick === 'function';
		const esc = CerbUI.editorCore.escapeHtml;
		const count = this.textarea.value.split('\n').length;
		// Gutter diff marks vs the baseline (a right-edge bar on added/modified rows, a boundary wedge on deletions).
		const anyDiff = this.opts.diffGutter && (this._diffRows.size > 0 || this._diffDeletions.size > 0 || this._diffAtEnd);
		const lastRow = count - 1;
		let html = '';
		for(let r = 0; r < count; r++) {
			const active = (this._highlightRow === r) ? ' cerb-ui-scriptingeditor--gutter-line-active' : '';
			const diff = anyDiff ? CerbUI.editorCore.diff.gutterClasses(this, 'scriptingeditor', r, lastRow) : '';
			let marker = '';
			if(anyMarker) {
				const mk = this._markers.get(r);
				let cls = 'cerb-ui-scriptingeditor--gutter-marker', style = '', attrs = '';
				if(mk) {
					cls += mk.pip ? ' cerb-ui-scriptingeditor--gutter-marker-pip' : (mk.icon ? (' cerb-icons cerb-icon-' + mk.icon) : '');
					if(mk.type) cls += ' cerb-ui-scriptingeditor--gutter-marker-' + mk.type;
					if(mk.color) style = ' style="color:var(--cerb-color-tag-' + mk.color + ')"';
					if(mk.title) attrs = ' title="' + esc(mk.title).replace(/"/g, '&quot;') + '"';
				}
				marker = '<span class="' + cls + '" data-model-row="' + r + '"' + style + attrs + '></span>';
			}
			html += '<div class="cerb-ui-scriptingeditor--gutter-line' + active + diff + '">' +
				marker + '<span class="cerb-ui-scriptingeditor--gutter-num">' + (r + 1) + '</span></div>';
		}
		this.gutter.innerHTML = html;
		this.gutter.scrollTop = this.textarea.scrollTop;
	}

	_resizeMaxLines(delta) {
		const docLines = this.textarea.value.split('\n').length;
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
		// Match the decoration layers to the textarea's client height so a horizontal scrollbar doesn't drift them.
		CerbUI.editorCore.syncOverlayHeight(ta, [this.highlight]);
		CerbUI.editorCore.syncGutterHeight(ta, this.gutter, this._gutterPadBottom);
		this._syncScroll();
	}

	// The autocomplete scope at the caret: inside a script tag, the partial script word (so an accepted
	// suggestion replaces it); outside a tag, no prefix. Phase 1 has no document-level (variable) scope.
	_scopeAt(text, caret) {
		const tctx = CerbUI.editorCore.kataScript.contextAt(text, caret);
		if(tctx) return { path: [], prefix: tctx.prefix, prefixRaw: tctx.prefixRaw, caret };
		return { path: [], prefix: '', prefixRaw: '', caret };
	}

	// The autocomplete items at the caret. Inside a {{ }} / {% %} tag → script language suggestions
	// (command/function/filter, or the call's params). Outside a tag, plaintext has nothing to suggest (Phase 1)
	// — an `onAutocomplete` source can add document-scope items. A subclass overrides this for a richer grammar
	// (e.g. CerbUI.DataQuery suggests data-query fields outside tags).
	_autocompleteItems(ctx) {
		const v = this.textarea.value, c = this.textarea.selectionStart;
		const t = CerbUI.editorCore.kataScript.contextAt(v, c);
		if(!t) return [];   // outside a {{ }} / {% %} tag — nothing to suggest (Phase 1)
		if(t.sub === 'args') return CerbUI.editorCore.kataScript.suggestArgs(t);
		// Language suggestions (command/function/filter). Phase 2 will merge variable-scope items here.
		const items = CerbUI.editorCore.kataScript.suggest(t);
		if(typeof this.opts.onAutocomplete === 'function') {
			const extra = this.opts.onAutocomplete(ctx);
			if(Array.isArray(extra)) return extra.concat(items);
		}
		return items;
	}
};

// Token type -> CSS class. Plain text carries no class (default color). Script-tag tokens use the SHARED
// editorCore.kataScript classes (cerb-ui-editor--tok-kscript*) so the palette matches KataEditor exactly.
CerbUI.ScriptingEditor._TOK_CLASS = Object.assign({
	'indent-guide': 'cerb-ui-scriptingeditor--indent-guide',
}, CerbUI.editorCore.kataScript.TOK_CLASS);

CerbUI.ScriptingEditor._MARKER_TYPES = {
	error:      { icon: 'circle-exclamation-mark', color: 'red' },
	warning:    { icon: 'alert',                    color: 'orange' },
	info:       { icon: 'circle-info',              color: 'blue' },
	breakpoint: { pip: true,                        color: 'red' },
};
