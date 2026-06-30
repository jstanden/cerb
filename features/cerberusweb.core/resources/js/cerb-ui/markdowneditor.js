/*
 * CerbUI.MarkdownEditor — a lightweight markdown text editor (replaces the legacy jQuery `cerbTextEditor`
 * + `cerbTextEditorToolbarMarkdown` + `cerbTextEditorAutocomplete*` + `cerbTextEditorInlineImagePaster`
 * stack used by the mail reply / comment composers).
 *
 * Like CerbUI.SearchQuery / CerbUI.KataEditor it's a third member of the editor-core family: a plain
 * <textarea> with live syntax highlighting drawn on a transparent mirror <div> overlay, plus a caret-anchored
 * autocomplete menu — all the shared plumbing (overlay render, caret measurement, scroll sync, the menu
 * lifecycle, fuzzy match/filter) lives in CerbUI.editorCore. This file owns the markdown bits: the tokenizer,
 * the formatting actions (bold/italic/link/…), the plaintext↔markdown mode toggle, the textarea command API
 * (ported from the legacy `cerbTextEditor` widget), and inline-image paste/upload.
 *
 * Highlighting is COLOR-ONLY (the mirror's glyph advances must match the textarea or the caret drifts) — it's
 * a syntax highlighter, not WYSIWYG. So `**bold**` is colored, not rendered bold.
 *
 * Markup (mirrors SearchQuery):
 *   <div class="cerb-ui-markdowneditor">
 *     <div class="cerb-ui-markdowneditor--field">
 *       <div class="cerb-ui-markdowneditor--highlight" aria-hidden="true"></div>
 *       <textarea class="cerb-ui-markdowneditor--input" name="…"></textarea>
 *       <span class="cerb-ui-markdowneditor--caret-anchor"></span>
 *     </div>
 *   </div>
 *
 * Usage:
 *   const ed = new CerbUI.MarkdownEditor(el, {
 *     mode: 'markdown',                                  // 'markdown' | 'plaintext'
 *     onChange: (value) => {…},
 *     onAutocomplete: CerbUI.MarkdownEditor.mentionSource(),   // (ctx) => items|Promise<items>
 *     onImage: ({url, file_id, file_name, labels, values}) => {…},  // host adds the attachment
 *     minHeight: 80, maxHeight: 400,
 *   });
 *   ed.bold(); ed.getValue(); ed.insertText('@'); ed.openAutocomplete(); ed.setMode('plaintext');
 *
 * CSS lives in cerb.css (.cerb-ui-markdowneditor--*) — this component never injects styles.
 */
CerbUI.MarkdownEditor = class {
	static _instances = new WeakMap();
	static from(el) { return CerbUI.MarkdownEditor._instances.get(el); }
	static _NS = 'markdowneditor';   // element-class namespace -> `.cerb-ui-markdowneditor--input`, etc. (no gutter)

	// Wrap a BARE <textarea> in the editor shell and construct, so a template only authors the textarea.
	static enhance(field, opts = {}) { return CerbUI.editorCore.enhanceEditor(this, field, opts, { gutter: false }); }

	static _DEFAULTS = {
		mode: 'markdown',          // 'markdown' enables syntax coloring; 'plaintext' renders plain
		readOnly: false,           // view-only: block typing/paste/formatting (data-editor-readonly attr also sets it)
		toolbar: false,            // built-in formatting toolbar above the field. true = default formatting set, or an
		                           // object { buttons:[…], mode:true, onMode:fn, extra:[{value,icon,title,onSelect}|{separator:true}] }.
		                           // The component OWNS the formatting strip; host-specific actions (placeholder, preview)
		                           // merge in as their own section via `extra`. Suppressed when readOnly.
		scripting: false,          // also highlight + autocomplete Twig/KataScript tags ({{ }} / {% %}) anywhere
		onChange: null,            // (value) after any edit
		onAutocomplete: null,      // (ctx) -> Array<item> | Promise<...>; ctx = {path, prefix, context, query, caret, editor}
		images: true,              // false disables inline images entirely: no paste-upload, no image() insert, and the
		                           // toolbar drops its Image button (for hosts that can't serve worker-side /files URLs)
		onImage: null,             // ({url,file_id,file_name,labels,values}) after an image is chosen/pasted+inserted
		imageMarkdown: null,       // (info) => string — the markdown inserted for an image (default ![inline-image](url))
		context: '',               // passed through to onAutocomplete
		autocompleteDelay: 150,    // ms debounce
		minHeight: 80,             // px the textarea starts at
		maxHeight: 400,            // px it grows to before scrolling
		placeholder: null,
	};

	constructor(el, opts = {}) {
		el = (typeof el === 'string') ? document.querySelector(el) : el;
		// Polymorphic: a bare <textarea> self-builds the editor shell (no gutter) so callers can skip the boilerplate.
		el = CerbUI.editorCore.resolveEditorEl(el, CerbUI.MarkdownEditor._NS, { gutter: false });
		if(!el) return;

		this.el = el;
		this.opts = Object.assign({}, CerbUI.MarkdownEditor._DEFAULTS, opts);
		this._markdown = (this.opts.mode !== 'plaintext');
		// Valid @mention handles (lowercased, no @) — the highlighter colors only these, not arbitrary @words.
		// Populated by the mention autocomplete source via registerMentions().
		this._mentionHandles = new Set();

		this.textarea = el.querySelector('.cerb-ui-markdowneditor--input');
		this.field = el.querySelector('.cerb-ui-markdowneditor--field');
		this.highlight = el.querySelector('.cerb-ui-markdowneditor--highlight');
		this.caretAnchor = el.querySelector('.cerb-ui-markdowneditor--caret-anchor');
		if(!this.textarea || !this.field || !this.highlight || !this.caretAnchor) return;

		// Prose, so DO leave spellcheck on by default — but kill the browser's form autofill which fights the
		// overlay. (spellcheck honors any author-set attribute.)
		this.textarea.setAttribute('autocomplete', 'off');
		this.textarea.setAttribute('autocorrect', 'off');
		this.textarea.setAttribute('autocapitalize', 'sentences');

		if(this.opts.placeholder != null) this.textarea.placeholder = this.opts.placeholder;

		// View-only: the textarea's native readOnly blocks typing; the mutation primitive + paste are gated below.
		if(this.textarea.hasAttribute('data-editor-readonly')) this.opts.readOnly = true;
		if(this.opts.readOnly) this.textarea.readOnly = true;

		this.el.classList.toggle('cerb-ui-markdowneditor--plaintext', !this._markdown);

		this._ac = new CerbUI.editorCore.Autocomplete({
			textarea: this.textarea,
			caretAnchor: this.caretAnchor,
			context: this.opts.context,
			editor: this,
			delay: this.opts.autocompleteDelay,
			onScope: (text, caret) => this._scopePathAt(text, caret),
			onItems: (ctx) => {
				// A script tag wins wherever one is open (scripting docs can place {{ }} / {% %} anywhere).
				if(this.opts.scripting) {
					const t = CerbUI.editorCore.kataScript.contextAt(this.textarea.value, this.textarea.selectionStart);
					if(t) return (t.sub === 'args') ? CerbUI.editorCore.kataScript.suggestArgs(t) : CerbUI.editorCore.kataScript.suggest(t);
				}
				return (typeof this.opts.onAutocomplete === 'function') ? this.opts.onAutocomplete(ctx) : [];
			},
			onAfterApply: () => { this._renderHighlight(); this._autosize(); this._emitChange(); },
		});

		CerbUI.MarkdownEditor._instances.set(el, this);

		this._onInput = () => this._handleInput();
		this._onKeydown = (e) => this._handleKeydown(e);
		this._onScroll = () => this._syncScroll();
		this._onBlur = () => { this._ac.clearTimer(); };
		this._onPaste = (e) => this._handlePaste(e);

		this.textarea.addEventListener('input', this._onInput);
		this.textarea.addEventListener('keydown', this._onKeydown);
		this.textarea.addEventListener('scroll', this._onScroll, { passive: true });
		this.textarea.addEventListener('blur', this._onBlur);
		this.textarea.addEventListener('paste', this._onPaste);

		this._renderHighlight();
		this._autosize();

		// The component owns its formatting toolbar (opt-in) — the shared core hook. Markdown seeds the
		// markdown↔plaintext switcher ON by default (a caller's `toolbar.mode:false` overrides it). A read-only
		// viewer gets none. Built-in formatting buttons come from MarkdownEditor.TOOLBAR_BUILTINS; when
		// `images:false`, the Image button is dropped from the default set (unless the caller set its own `buttons`).
		const toolbarDefaults = { mode: true };
		if(this.opts.images === false)
			toolbarDefaults.buttons = Object.keys(CerbUI.MarkdownEditor.TOOLBAR_BUILTINS).filter(n => n !== 'image');
		CerbUI.editorCore.attachToolbar(this, this.opts, toolbarDefaults);

		// Find/Replace (Mod-F) — shared controller + a 1:1 (no folding) adapter. MarkdownEditor has no shortcut
		// registry, so the Mod-F binding lives in _handleKeydown.
		this._find = new CerbUI.editorCore.FindController(this, CerbUI.editorCore.makeFindAdapter(this, 'linear'));
	}

	// ── Public API ──────────────────────────────────────────────────────

	getValue() { return this.textarea ? this.textarea.value : ''; }

	setValue(str) {
		if(!this.textarea) return this;
		this.textarea.value = str ?? '';
		this._renderHighlight();
		this._autosize();
		this._emitChange();
		return this;
	}

	focus() { if(this.textarea) this.textarea.focus(); return this; }

	openAutocomplete() { this._ac.trigger(); return this; }

	onChange(cb) { if(typeof cb === 'function') this.opts.onChange = cb; return this; }

	// Register valid @mention handles (any of `@name`, `name`) so the highlighter colors only real mentions
	// present in the text — not arbitrary @words. The mention autocomplete source calls this with the loaded
	// list, so any valid handle (typed or picked) highlights once the source has run.
	registerMentions(list) {
		if(!Array.isArray(list)) return this;
		let added = false;
		for(const h of list) {
			const norm = String(h == null ? '' : h).replace(/^@/, '').trim().toLowerCase();
			if(norm && !this._mentionHandles.has(norm)) { this._mentionHandles.add(norm); added = true; }
		}
		if(added && this._markdown) this._renderHighlight();
		return this;
	}

	// Mode: 'markdown' (syntax coloring on) | 'plaintext' (off). The host maps this to its submit flag.
	getMode() { return this._markdown ? 'markdown' : 'plaintext'; }
	isMarkdown() { return this._markdown; }
	setMode(mode) {
		const on = (mode !== 'plaintext');
		if(on === this._markdown) return this;
		this._markdown = on;
		this.el.classList.toggle('cerb-ui-markdowneditor--plaintext', !on);
		this._renderHighlight();
		return this;
	}
	toggleMode() { return this.setMode(this._markdown ? 'plaintext' : 'markdown'); }

	destroy() {
		this._ac.destroy();
		if(this._find) this._find.destroy();
		CerbUI.MarkdownEditor._instances.delete(this.el);
		if(this._editorToolbar && typeof this._editorToolbar.destroy === 'function') this._editorToolbar.destroy();
		if(this.textarea) {
			this.textarea.removeEventListener('input', this._onInput);
			this.textarea.removeEventListener('keydown', this._onKeydown);
			this.textarea.removeEventListener('scroll', this._onScroll);
			this.textarea.removeEventListener('blur', this._onBlur);
			this.textarea.removeEventListener('paste', this._onPaste);
		}
	}

	// ── Textarea command API (ported from the legacy cerb.cerbTextEditor widget) ──

	// Caret row/column — family parity (ScriptingEditor/KataEditor/JsonEditor); the shared editor toolbars read it.
	getCursorPosition() {
		const caret = this.textarea.selectionEnd;
		const before = this.textarea.value.slice(0, caret);
		return { row: (before.match(/\n/g) || []).length, column: caret - (before.lastIndexOf('\n') + 1) };
	}
	setCursorPosition(row, column) {
		const rows = this.textarea.value.split('\n');
		const r = Math.max(0, Math.min(row || 0, rows.length - 1));
		let off = 0;
		for(let i = 0; i < r; i++) off += rows[i].length + 1;
		off += Math.min(column || 0, (rows[r] || '').length);
		this.setSelection(off, off);
		return this;
	}
	getSelectionBounds() { return { start: this.textarea.selectionStart, end: this.textarea.selectionEnd }; }
	getSelection() { const b = this.getSelectionBounds(); return this.textarea.value.substring(b.start, b.end); }
	getSelectedText() { return this.getSelection(); }
	setSelection(start, end) { this.textarea.selectionStart = start; this.textarea.selectionEnd = end; }

	getCurrentWordPos() {
		const v = this.textarea.value;
		let start = this.textarea.selectionStart - 1;
		const end = this.textarea.selectionStart;
		for(let x = start; x >= 0; x--) {
			if(/\s/.test(v[x])) { start = x + 1; break; }
			if(x === 0) start = 0;
		}
		if(this.textarea.selectionStart === 0) start = 0;
		return { start: Math.max(0, start), end };
	}
	getCurrentLinePos() {
		const v = this.textarea.value;
		let start = this.textarea.selectionStart - 1;
		const end = this.textarea.selectionStart;
		for(let x = start; x >= 0; x--) {
			if(/[\r\n]/.test(v[x])) { start = x + 1; break; }
			if(x === 0) start = 0;
		}
		if(this.textarea.selectionStart === 0) start = 0;
		return { start: Math.max(0, start), end };
	}
	getCurrentWord() { const p = this.getCurrentWordPos(); return this.textarea.value.substring(p.start, p.end); }
	getCurrentLine() { const p = this.getCurrentLinePos(); return this.textarea.value.substring(p.start, p.end); }
	replaceCurrentWord(s) { const p = this.getCurrentWordPos(); this._replaceRange(p.start, p.end, s); }
	replaceCurrentLine(s) { const p = this.getCurrentLinePos(); this._replaceRange(p.start, p.end, s); }

	// Insert at the caret (replacing any selection); caret lands after the inserted text.
	insertText(text) { const b = this.getSelectionBounds(); this._replaceRange(b.start, b.end, text); }

	// Insert at the caret (replacing any selection); a single `$0` marks the final caret. Family parity — the hook
	// `interactionWorkerPostActions` uses for a toolbar automation's `snippet` return.
	insertSnippet(text) {
		const b = this.getSelectionBounds();
		// Accept Ace-format snippets: flatten numbered tab-stops to defaults + mark the first as `$0` (idempotent).
		let insert = CerbUI.editorCore.aceSnippetToCerb(String(text == null ? '' : text));
		let off = insert.length;
		const m = insert.indexOf('$0');
		if(m !== -1) { off = m; insert = insert.slice(0, m) + insert.slice(m + 2); }
		this._replaceRange(b.start, b.end, insert);
		this.setSelection(b.start + off, b.start + off);
		this.focus();
		return this;
	}

	// Family-parity insert API (matches ScriptingEditor/KataEditor) — the hook placeholder-insert menus use.
	// `opts.replace` first clears the whole document.
	insertAtCursor(content, opts) {
		opts = opts || {};
		if(opts.replace) this.setValue('');
		this.insertText(String(content == null ? '' : content));
		this.focus();
		return this;
	}
	appendText(content) {
		let v = this.getValue();
		if(v.length > 0 && v.charAt(v.length - 1) !== '\n') v += '\n\n';
		this.setValue(v);
		this.setSelection(this.textarea.value.length, this.textarea.value.length);
		this.insertText(String(content == null ? '' : content));
		return this;
	}
	// Replace the current selection; caret after.
	replaceSelection(text) { const b = this.getSelectionBounds(); this._replaceRange(b.start, b.end, text); }
	wrapSelection(wrapWith) { this.replaceSelection(wrapWith + this.getSelection() + wrapWith); }
	prefixSelection(prefixWith) { this.replaceSelection(prefixWith + this.getSelection()); }
	prefixCurrentLine(prefixWith) {
		const start = Math.max(0, this.textarea.value.substring(0, this.textarea.selectionStart).lastIndexOf('\n') + 1);
		this.textarea.selectionStart = start; // keep selectionEnd
		this.prefixSelection(prefixWith);
	}

	// The single mutation primitive — undo-safe via execCommand('insertText') (falls back to setRangeText).
	_replaceRange(start, end, text) {
		if(this.opts.readOnly) return;
		const ta = this.textarea;
		text = String(text == null ? '' : text).replace(/\r/g, '');
		ta.focus();
		ta.setSelectionRange(start, end);
		let ok = false;
		// execCommand('insertText') is pathologically slow on large spans (Replace-All on a huge doc hangs).
		// Above this threshold use setRangeText directly — fast, at the cost of native undo for this one bulk op.
		const BIG_EDIT = 10000;
		try { ok = (Math.max(end - start, text.length) > BIG_EDIT) ? false : document.execCommand('insertText', false, text); } catch(e) { ok = false; }
		if(!ok) {
			ta.setRangeText(text, start, end, 'end');
			this._renderHighlight(); this._autosize(); this._emitChange();
		}
		// execCommand fires a native 'input' event -> _handleInput re-highlights/autosizes/emits.
	}

	// ── Markdown formatting actions (ported from cerbTextEditorToolbarMarkdown) ──

	bold() { this.wrapSelection('**'); }
	italic() { this.wrapSelection('_'); }
	heading() { this.prefixSelection('# '); }

	link() {
		const sel = this.getSelection();
		if(sel.length === 0) { this.insertText('[link text](https://example.com)'); return; }
		const bounds = this.getSelectionBounds();
		const cursorAt = bounds.start + sel.length + 3;
		const defaultLink = 'https://example.com';
		this.replaceSelection('[' + sel + '](' + defaultLink + ')');
		this.setSelection(cursorAt, cursorAt + defaultLink.length);
	}

	list() {
		const sel = this.getSelection();
		if(sel.length === 0 || sel.indexOf('\n') === -1) { this.prefixCurrentLine('* '); return; }
		this.replaceSelection('* ' + sel.trim().replace(/\n/g, '\n* ') + '\n');
	}

	quote() {
		const sel = this.getSelection();
		if(sel.length === 0 || sel.indexOf('\n') === -1) { this.prefixCurrentLine('> '); return; }
		this.replaceSelection('> ' + sel.trim().replace(/\n/g, '\n> ') + '\n');
	}

	code() {
		const sel = this.getSelection();
		if(sel.length === 0) { this.insertText('~~~\nyour code goes here\n~~~\n'); return; }
		this.wrapSelection(sel.indexOf('\n') === -1 ? '`' : '~~~\n');
	}

	table() { this.insertText('Column | Column\n--- | ---\nValue | Value\n'); }

	// Open the file chooser, then insert an inline-image markdown reference + notify the host (which adds the
	// attachment). Mirrors the legacy toolbar image button. No-op when `images:false`.
	image() {
		if(this.opts.images === false) return;
		const $chooser = genericAjaxPopup('chooser', 'c=internal&a=invoke&module=records&action=chooserOpenFile&single=1', null, true, '750');
		$chooser.one('chooser_save', (event) => {
			const file_id = event.values[0];
			const file_label = event.labels[0];
			const file_name = file_label.substring(0, file_label.lastIndexOf(' ('));
			const url = this._fileUrl(file_id, file_name);
			const info = { labels: event.labels, values: event.values, file_id, file_name, url };
			this.setMode('markdown');
			this.insertText(this._imageMarkdown(info));
			this._emitImage(info);
		});
	}

	// ── Image paste (ported from cerbTextEditorInlineImagePaster) ──

	_handlePaste(e) {
		if(this.opts.readOnly || this.opts.images === false) return;
		const files = e.clipboardData && e.clipboardData.files;
		if(!files || files.length === 0) return;
		e.preventDefault();
		e.stopPropagation();
		for(const f of files) {
			if(f.type.lastIndexOf('image/', 0) !== 0) continue;
			this._uploadImage(f);
		}
	}

	_uploadImage(f) {
		const xhr = new XMLHttpRequest();
		if(!xhr.upload) return;

		xhr.open('POST', DevblocksAppPath + 'ajax.php?c=internal&a=invoke&module=records&action=chooserOpenFileAjaxUpload', true);
		xhr.setRequestHeader('X-File-Name', encodeURIComponent(f.name));
		xhr.setRequestHeader('X-File-Type', f.type);
		xhr.setRequestHeader('X-File-Size', f.size);
		xhr.setRequestHeader('X-CSRF-Token', $('meta[name="_csrf_token"]').attr('content'));

		xhr.onreadystatechange = () => {
			if(xhr.readyState !== 4 || xhr.status !== 200) return;
			let json;
			try { json = JSON.parse(xhr.responseText); } catch(err) { return; }
			const file_id = json.id, file_name = json.name, file_type = json.type;
			if(file_type.lastIndexOf('image/', 0) !== 0) return;
			const url = this._fileUrl(file_id, file_name);
			const info = {
				labels: [file_name + ' (' + (json.size_label || '') + ')'],
				values: [file_id],
				file_id, file_name, url,
			};
			this.setMode('markdown');
			this.insertText(this._imageMarkdown(info) + '\n');
			this._emitImage(info);
		};

		xhr.send(f);
	}

	_fileUrl(file_id, file_name) {
		return document.location.protocol + '//' + document.location.host + DevblocksWebPath
			+ 'files/' + encodeURIComponent(file_id) + '/' + encodeURIComponent(file_name);
	}

	// The markdown inserted for a chosen/pasted image — a literal URL by default; a host can override (e.g. KB
	// articles insert a {{cerb_file_url(id,"name")}} Twig expression instead).
	_imageMarkdown(info) {
		return (typeof this.opts.imageMarkdown === 'function')
			? this.opts.imageMarkdown(info)
			: ('![inline-image](' + info.url + ')');
	}

	_emitImage(info) { if(typeof this.opts.onImage === 'function') this.opts.onImage(info); }
	_emitChange() { if(typeof this.opts.onChange === 'function') this.opts.onChange(this.getValue()); }

	// ── Input / keyboard ────────────────────────────────────────────────

	_handleInput() {
		this._renderHighlight();
		this._autosize();
		this._emitChange();
		this._ac.clearTimer();
		if(typeof this.opts.onAutocomplete !== 'function' && !this.opts.scripting) return;
		// Only chase suggestions when the caret sits in a trigger token (@mention / #command / inside a script
		// tag) — prose typing shouldn't ping the source on every keystroke.
		const sp = this._scopePathAt(this.textarea.value, this.textarea.selectionStart);
		if(sp.trigger) this._ac.schedule(); else this._ac.close();
	}

	// Prose editor: Enter inserts a newline (native). The component only intercepts keys when the suggestion
	// menu is open, plus ⌘/Ctrl+Space to force it. Host save shortcuts (Ctrl+Enter, etc.) pass through.
	_handleKeydown(e) {
		const menuOpen = this._ac.isOpen();

		// Mod-F opens find (suppressing the browser's native page find), even in readOnly editors.
		if(e.code === 'KeyF' && (e.ctrlKey || e.metaKey) && !e.altKey && !e.shiftKey) {
			e.preventDefault();
			if(menuOpen) { e.stopPropagation(); this._ac.close(); }
			this._find.open();
			return;
		}

		if(e.code === 'Space' && (e.ctrlKey || e.metaKey)) {
			e.preventDefault();
			if(menuOpen) e.stopPropagation();
			this._ac.trigger();
			return;
		}

		if(!menuOpen) return;

		// Plain ↑/↓ move into the menu — driven directly (a popup's key containment blocks the Menu's own
		// document keydown listener, so don't rely on the event bubbling to it).
		if((e.key === 'ArrowDown' || e.key === 'ArrowUp') && !(e.metaKey || e.ctrlKey || e.altKey)) {
			e.preventDefault();
			e.stopPropagation();
			this._ac.moveSelection(e.key === 'ArrowDown' ? +1 : -1);
			return;
		}
		// Any other arrow = caret navigation: dismiss the menu, let the textarea move natively.
		if(e.key === 'ArrowLeft' || e.key === 'ArrowRight' || e.key === 'ArrowDown' || e.key === 'ArrowUp') {
			e.stopPropagation();
			this._ac.close();
			return;
		}
		if(e.key === 'Enter') {
			// Select when the user arrowed INTO the list, OR when the mouse is hovering the menu (the row is
			// highlighted under the pointer) — otherwise Enter is a plain newline, ignoring a stale highlight.
			if(this._ac.navigated || this._ac.pointerInMenu()) {
				e.preventDefault();
				e.stopPropagation();
				this._ac.acceptSelection(e); // select the active (arrowed or hovered) item
				return;
			}
			e.stopPropagation();
			this._ac.close();
			return;
		}
		if(e.key === 'Escape') {
			e.preventDefault();
			e.stopPropagation();
			this._ac.close();
			return;
		}
	}

	// ── Highlighting ────────────────────────────────────────────────────

	_renderHighlight() {
		const toks = (this._markdown || this.opts.scripting) ? this._tokenize(this.textarea.value) : [{ type: 'text', value: this.textarea.value }];
		CerbUI.editorCore.renderTokens(this.highlight, toks, CerbUI.MarkdownEditor._TOK_CLASS);
		if(this._find) this._find.repaintBands();   // re-add find-match bands (the mirror was just wiped)
		this._syncScroll();
	}

	_syncScroll() { CerbUI.editorCore.syncScroll(this.textarea, this.highlight); }

	_autosize() {
		const ta = this.textarea;
		ta.style.height = 'auto';
		const h = Math.min(Math.max(ta.scrollHeight, this.opts.minHeight), this.opts.maxHeight);
		ta.style.height = h + 'px';
		ta.style.overflowY = (ta.scrollHeight > this.opts.maxHeight) ? 'auto' : 'hidden';
		this.highlight.style.height = ta.style.height;
	}

	// ── Markdown tokenizer (covers every char incl. newlines) ──
	// SUBTLE style: only the markdown *punctuation* (the `syntax` token) is dimmed; the prose content stays the
	// normal text color. So `**bold**` = dim `**` + normal "bold". Block-aware per line (fenced code, headings,
	// hr, blockquote, list markers) + inline within the body.

	_tokenize(text) {
		const toks = [];
		const lines = text.split('\n');
		const md = this._markdown;
		const scripting = this.opts.scripting;
		let inFence = false, scriptOpen = null;

		// Non-tag runs go to markdown inline tokenizing (markdown mode) or plain text (plaintext+scripting).
		const plain = md ? (t, s) => this._tokenizeInline(t, s) : (t, s) => { if(s) t.push({ type: 'text', value: s }); };
		// Tokenize an inline run: thread kataScript over it (so {{ }} / {% %} color anywhere) when scripting,
		// carrying the open-tag state across lines; otherwise just the plain tokenizer.
		const inline = (str) => {
			if(scripting) scriptOpen = CerbUI.editorCore.kataScript.tokenize(toks, str, 'text', scriptOpen, plain);
			else plain(toks, str);
		};

		for(let li = 0; li < lines.length; li++) {
			if(li > 0) toks.push({ type: 'text', value: '\n' });
			const line = lines[li];

			// Continuation of a multi-line script tag — the whole line is tag content (skip markdown blocks).
			if(scripting && scriptOpen) { scriptOpen = CerbUI.editorCore.kataScript.tokenize(toks, line, 'text', scriptOpen, plain); continue; }

			// Plaintext + scripting: no markdown block/inline structure, just script tags over plain text.
			if(!md) { inline(line); continue; }

			const fence = /^\s*(```+|~~~+)/.test(line);
			if(inFence) {
				if(fence) { toks.push({ type: 'syntax', value: line }); inFence = false; } // closing fence
				else toks.push({ type: 'code', value: line });                             // code content (literal)
				continue;
			}
			if(fence) { inFence = true; toks.push({ type: 'syntax', value: line }); continue; } // opening fence

			if(/^\s*([-*_])(\s*\1){2,}\s*$/.test(line)) { toks.push({ type: 'syntax', value: line }); continue; } // hr

			const hm = line.match(/^(\s*#{1,6}\s)(.*)$/);
			if(hm) { toks.push({ type: 'syntax', value: hm[1] }); inline(hm[2]); continue; }

			const bq = line.match(/^(\s*>+\s?)/);
			const lm = bq ? null : line.match(/^(\s*(?:[-*+]|\d+\.)\s)/);
			let body = line;
			if(bq) { toks.push({ type: 'syntax', value: bq[1] }); body = line.slice(bq[1].length); }
			else if(lm) { toks.push({ type: 'syntax', value: lm[1] }); body = line.slice(lm[1].length); }

			inline(body);
		}
		return toks;
	}

	_tokenizeInline(toks, str) {
		if(!str) return;
		// image / link / bold / inline-code / italic / @mention — first match at the cursor wins.
		const RX = /!?\[[^\]]*\]\([^)]*\)|\*\*[^*]+\*\*|__[^_]+__|`[^`]+`|\*[^*\s][^*]*\*|(?:^|\b)_[^_\s][^_]*_|@[A-Za-z0-9_]+/g;
		let last = 0, m;
		while((m = RX.exec(str)) !== null) {
			let idx = m.index, v = m[0];
			// The italic `_` alternation may include a leading word-boundary char — re-anchor to the underscore.
			if(v[0] !== '!' && v[0] !== '[' && v[0] !== '*' && v[0] !== '`' && v[0] !== '_' && v[0] !== '@') {
				const u = v.indexOf('_'); idx += u; v = v.slice(u);
			}
			if(idx > last) toks.push({ type: 'text', value: str.slice(last, idx) });
			// @mention: only color a registered handle at a word boundary (so `foo@bar` emails don't match).
			if(v[0] === '@') {
				const boundary = (idx === 0) || /\s/.test(str[idx - 1]);
				const known = boundary && this._mentionHandles.has(v.slice(1).toLowerCase());
				toks.push({ type: known ? 'mention' : 'text', value: v });
				last = idx + v.length;
				continue;
			}
			this._pushInline(toks, v);
			last = idx + v.length;
		}
		if(last < str.length) toks.push({ type: 'text', value: str.slice(last) });
	}

	// Split one inline construct into dim `syntax` markers + normal content (the subtle look). Links/images
	// also get a muted underlined `url`; inline code a faint-chip `code` body.
	_pushInline(toks, v) {
		const c = v[0];

		// link [text](url) / image ![alt](url)
		if(c === '!' || c === '[') {
			const lb = v.indexOf('['), rb = v.indexOf(']', lb), lp = v.indexOf('(', rb), rp = v.lastIndexOf(')');
			if(rb === -1 || lp === -1 || rp === -1) { toks.push({ type: 'text', value: v }); return; }
			toks.push({ type: 'syntax', value: v.slice(0, lb + 1) });           // '![' or '['
			if(rb > lb + 1) toks.push({ type: 'text', value: v.slice(lb + 1, rb) });
			toks.push({ type: 'syntax', value: v.slice(rb, lp + 1) });          // ']('
			if(rp > lp + 1) toks.push({ type: 'url', value: v.slice(lp + 1, rp) });
			toks.push({ type: 'syntax', value: v.slice(rp) });                  // ')'
			return;
		}
		// bold **x** / __x__
		if(v.startsWith('**') || v.startsWith('__')) {
			toks.push({ type: 'syntax', value: v.slice(0, 2) });
			if(v.length > 4) toks.push({ type: 'text', value: v.slice(2, -2) });
			toks.push({ type: 'syntax', value: v.slice(-2) });
			return;
		}
		// inline code `x`
		if(c === '`') {
			toks.push({ type: 'syntax', value: '`' });
			if(v.length > 2) toks.push({ type: 'code', value: v.slice(1, -1) });
			toks.push({ type: 'syntax', value: '`' });
			return;
		}
		// italic *x* / _x_
		toks.push({ type: 'syntax', value: v.slice(0, 1) });
		if(v.length > 2) toks.push({ type: 'text', value: v.slice(1, -1) });
		toks.push({ type: 'syntax', value: v.slice(-1) });
	}

	// ── Scope at the caret (for autocomplete) ───────────────────────────
	// Markdown prose has two triggers: an `@mention` word and a `#command` word (commands are reply-only, but
	// recognized here so a reply source can serve them). Returns {trigger, path, prefix, prefixRaw, line, caret}.

	_scopePathAt(text, caret) {
		const lineStart = text.lastIndexOf('\n', caret - 1) + 1;
		const line = text.slice(lineStart, caret);

		// Inside a {{ }} / {% %} tag the script word is the prefix (so an accepted suggestion replaces it).
		if(this.opts.scripting) {
			const t = CerbUI.editorCore.kataScript.contextAt(text, caret);
			if(t) return { trigger: 'scripting', path: [], prefix: t.prefix, prefixRaw: t.prefixRaw, line, caret };
		}

		let s = caret;
		while(s > 0 && !/\s/.test(text[s - 1])) s--;
		const word = text.slice(s, caret);

		if(word.charAt(0) === '@')
			return { trigger: 'mention', path: ['@'], prefix: word.slice(1), prefixRaw: word, line, caret };
		if(word.charAt(0) === '#')
			return { trigger: 'command', path: ['#'], prefix: word.slice(1), prefixRaw: word, line, caret };
		// A `#snippet `/`#attach ` continuation ANYWHERE on the line (not just at column 0) — the term is
		// everything after it up to the caret, nearest occurrence wins. The picker fires mid-line too.
		const snipAt = line.lastIndexOf('#snippet ');
		if(snipAt !== -1) {
			const term = line.slice(snipAt + '#snippet '.length);
			return { trigger: 'snippet', path: ['#snippet'], prefix: term, prefixRaw: term, line, caret };
		}
		const attAt = line.lastIndexOf('#attach ');
		if(attAt !== -1) {
			const term = line.slice(attAt + '#attach '.length);
			return { trigger: 'attach', path: ['#attach'], prefix: term, prefixRaw: term, line, caret };
		}

		return { trigger: null, path: [], prefix: '', prefixRaw: '', line, caret };
	}
};

// The built-in formatting actions surfaced by the toolbar (CerbUI.editorCore.EditorToolbar). Each fn(editor) runs
// the matching formatting method; a host can override any of them via the toolbar's onAction callback, or restrict
// the set via `toolbar.buttons`. Order here = default button order.
CerbUI.MarkdownEditor.TOOLBAR_BUILTINS = {
	bold:    { icon: 'bold',    title: 'Bold',    fn: (ed) => ed.bold() },
	italic:  { icon: 'italic',  title: 'Italics', fn: (ed) => ed.italic() },
	heading: { icon: 'header',  title: 'Heading', fn: (ed) => ed.heading() },
	link:    { icon: 'link',    title: 'Link',    fn: (ed) => ed.link() },
	image:   { icon: 'picture', title: 'Image',   fn: (ed) => ed.image() },
	list:    { icon: 'list',    title: 'List',    fn: (ed) => ed.list() },
	quote:   { icon: 'quote',   title: 'Quote',   fn: (ed) => ed.quote() },
	code:    { icon: 'embed',   title: 'Code',    fn: (ed) => ed.code() },
	table:   { icon: 'table',   title: 'Table',   fn: (ed) => ed.table() },
};

// Token type -> CSS class for the highlight mirror (plain `text` renders in the default color). Subtle:
// `syntax` = dimmed markdown punctuation, `url` = muted underline, `mention` = accent. `code` content carries
// no class (renders as plain text) — the dim backticks / fence markers are the only cue.
// Script-tag tokens (only emitted when `scripting` is on) reuse the SHARED editorCore.kataScript classes
// (cerb-ui-editor--tok-kscript*), so the Twig/KataScript palette matches KataEditor/ScriptingEditor.
CerbUI.MarkdownEditor._TOK_CLASS = Object.assign({
	syntax:  'cerb-ui-markdowneditor--tok-syntax',
	url:     'cerb-ui-markdowneditor--tok-url',
	mention: 'cerb-ui-markdowneditor--tok-mention',
}, CerbUI.editorCore.kataScript.TOK_CLASS);

/*
 * mentionSource(opts) — a ready-made onAutocomplete for `@mention` completion, porting the legacy
 * `_sourceMentions`: lazy-load `c=ui&a=getMentionsJson` (cached), filter by the typed prefix, render
 * avatar + name + handle. Drop into `onAutocomplete:` for the comment / reply editors.
 */
CerbUI.MarkdownEditor.mentionSource = function(opts) {
	opts = opts || {};
	let cache = null;

	function load() {
		if(Array.isArray(cache)) return Promise.resolve(cache);
		return new Promise((resolve) => {
			genericAjaxGet('', 'c=ui&a=getMentionsJson', function(json) {
				cache = (typeof json === 'object' && Array.isArray(json)) ? json : [];
				resolve(cache);
			});
		});
	}

	return function(ctx) {
		if(!ctx || ctx.path[0] !== '@') return [];
		const term = (ctx.prefix || '').toLowerCase();
		return load().then((mentions) => {
			// Register every known handle so the editor can highlight valid mentions present in the text.
			if(ctx.editor && typeof ctx.editor.registerMentions === 'function')
				ctx.editor.registerMentions(mentions.map((m) => m.mention));
			return mentions
				.filter((m) => m.label.toLowerCase().startsWith(term) || m.value.toLowerCase().startsWith('@' + term))
				.map((m) => ({
					caption: m.label,         // name (left of the first line)
					value: m.value,           // the full @handle replaces the typed @prefix
					handle: m.mention || null, // the @handle, right side of the name line
					subtitle: m.title || null, // job title on the second line
					avatar: { label: m.label, seed: (m._type || 'worker') + ':' + m.id, imageUrl: m.image_url || null },
				}));
		});
	};
};
