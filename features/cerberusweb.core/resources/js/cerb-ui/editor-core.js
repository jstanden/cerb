/*
 * CerbUI.editorCore — shared plumbing for the plain-JS code editors (CerbUI.SearchQuery, CerbUI.KataEditor).
 *
 * Like CerbUI.num / CerbUI.date it's a plain CerbUI.<name> module: a bag of stateless helpers plus one small
 * stateful helper class (Autocomplete) that drives the caret-anchored suggestion menu. The split of concerns:
 *   - editorCore owns the things that are identical regardless of language: the overlay-highlight rendering
 *     (transparent textarea over a colored mirror div), caret pixel measurement, scroll-sync, the fuzzy
 *     match/filter + score ordering, the Ace-snippet → `$0` converter, and the suggestion-menu lifecycle.
 *   - each editor owns what's genuinely its own: the tokenizer, the scope/key-path computation, the keyboard
 *     model (Enter submits vs. inserts a newline), the autosize/gutter, and its field-source adapter.
 *
 * Must load BEFORE searchquery.js / kataeditor.js (and after menu.js, which Autocomplete uses).
 */
CerbUI.editorCore = {
	// Items without an explicit score sort as if they had this one (matches the legacy Ace completer's default),
	// so a source can float a field above the baseline or sink one below it.
	DEFAULT_SCORE: 1000,

	// Filtering modes for suggestion menus (see match()).
	MATCH_MODES: ['subsequence', 'substring', 'prefix'],

	/*
	 * Match a candidate caption against the typed query. Three modes:
	 *   'subsequence' — the query's chars appear in the caption in order, not necessarily contiguous, so typing
	 *                   `linadd` matches `links.address:` (lin…add). A loose "fuzzy" match. (DEFAULT)
	 *   'substring'   — caption contains the query as a contiguous run
	 *   'prefix'      — caption starts with the query (left-anchored only)
	 * Case-insensitive; an empty query matches everything.
	 */
	match: function(text, query, mode) {
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
	},

	// Filter an array of suggestion items (or plain strings) by query + mode, then order by `score` (higher
	// first) so a source's hand-ranked order wins over the matched order. The sort is stable, so equally-scored
	// items keep their authored order. `keyFn` selects the text to match (defaults to caption, or the string).
	filterItems: function(items, query, mode, keyFn) {
		const self = CerbUI.editorCore;
		const key = keyFn || (it => (typeof it === 'string') ? it : (it && it.caption != null ? it.caption : ''));
		const scoreOf = it => (it && typeof it.score === 'number') ? it.score : self.DEFAULT_SCORE;
		const matched = query ? items.filter(it => self.match(key(it), query, mode)) : items.slice();
		return matched.sort((a, b) => scoreOf(b) - scoreOf(a));
	},

	// The backends emit Ace-format snippets (`"${1}"`, `field:[${1}]`, `${1:3.14}`). Convert their tabstops to
	// our single `$0` caret marker: keep any default text, drop the numbering, mark the FIRST stop as the caret.
	aceSnippetToCerb: function(snip) {
		let placed = false;
		return String(snip).replace(/\$\{(\d+):([^}]*)\}|\$\{(\d+)\}|\$(\d+)/g, function(m, _n1, def) {
			const fill = (def != null) ? def : '';
			if(!placed) { placed = true; return fill + '$0'; }
			return fill;
		});
	},

	// Shift every line after the first by `indent`, so a multi-line snippet keeps its internal
	// indentation but nests under the caret's current line. No-op for single-line text / empty indent.
	indentSnippet: function(text, indent) {
		return (!indent || text.indexOf('\n') === -1) ? text : text.replace(/\n/g, '\n' + indent);
	},

	escapeHtml: function(s) {
		return s.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
	},

	// Styles copied from the textarea into the offscreen mirror so wrapping/metrics match exactly.
	MIRROR_PROPS: [
		'boxSizing', 'paddingTop', 'paddingRight', 'paddingBottom', 'paddingLeft',
		'borderTopWidth', 'borderRightWidth', 'borderBottomWidth', 'borderLeftWidth',
		'fontFamily', 'fontSize', 'fontWeight', 'fontStyle', 'letterSpacing',
		'lineHeight', 'textTransform', 'tabSize', 'textIndent',
		'whiteSpace', 'wordWrap', 'overflowWrap',
	],

	// Build the mirror HTML string for a token list (no DOM write). Split out from renderTokens so a windowed
	// renderer (KataEditor's viewport virtualization) can compose it between spacer <div>s. A trailing newline
	// collapses the last line under `white-space:pre`, so pad it to keep heights in lockstep with the textarea.
	tokensToHtml: function(tokens, classMap) {
		const esc = CerbUI.editorCore.escapeHtml;
		let html = '';
		for(const t of tokens) {
			const cls = classMap[t.type];
			// A classed token with an empty value still emits its span (e.g. an icon drawn via CSS mask, like
			// the KataEditor fold indicator) — backward-compatible since no text token is ever empty.
			if(cls && t.value === '') { html += '<span class="' + cls + '"></span>'; continue; }
			html += cls ? ('<span class="' + cls + '">' + esc(t.value) + '</span>') : esc(t.value);
		}
		if(html.endsWith('\n')) html += ' ';
		return html;
	},

	// Render a token list into the mirror --highlight element. `classMap` maps a token's `type` to a CSS class
	// (no class = plain text).
	renderTokens: function(highlightEl, tokens, classMap) {
		highlightEl.innerHTML = CerbUI.editorCore.tokensToHtml(tokens, classMap);
	},

	// Pure viewport math for row virtualization: given the textarea's scroll position + height and the line
	// height, return the clamped [first,last] VIEW-row range to paint (plus `overscan` buffer rows each side).
	// Node-testable (no DOM). `viewRowCount` is the projection's line count.
	computeWindow: function(scrollTop, clientHeight, lh, viewRowCount, overscan) {
		if(!(lh > 0) || viewRowCount <= 0) return { first: 0, last: Math.max(0, viewRowCount - 1) };
		const over = (overscan == null) ? 12 : overscan;
		const first = Math.max(0, Math.min(Math.floor(scrollTop / lh) - over, viewRowCount - 1));
		const last = Math.min(viewRowCount - 1, Math.ceil((scrollTop + clientHeight) / lh) + over);
		return { first: first, last: Math.max(first, last) };
	},

	syncScroll: function(textarea, highlightEl) {
		highlightEl.scrollTop = textarea.scrollTop;
		highlightEl.scrollLeft = textarea.scrollLeft;
	},

	// Size the decorative overlay layers (the colored mirror) to the textarea's CLIENT height. A horizontal
	// scrollbar on the textarea steals rows from its client height but NOT from the overflow:hidden overlays — so
	// without this the mirror ends up taller than the caret layer and lines drift by the scrollbar height (worst
	// scrolled to the bottom). Call AFTER the textarea's height/overflow are set; clientHeight already excludes the
	// h-scrollbar (a vertical scrollbar only steals width). Falsy overlays skipped.
	// The GUTTER needs the same scroll math but must stay full height (it's a visible surface, not a transparent
	// mirror) — see syncGutterHeight.
	syncOverlayHeight: function(textarea, overlays) {
		if(!textarea) return;
		const h = textarea.clientHeight + 'px';
		(overlays || []).forEach((el) => { if(el) el.style.height = h; });
	},

	// The WIDTH counterpart, for WRAPPING editors only (white-space:pre-wrap — MarkdownEditor, SearchQuery). A
	// vertical scrollbar steals width from the textarea's content box but NOT from the `inset:0` mirror, so the
	// textarea wraps EARLIER than the mirror and every line below the first drifts (the caret lands a row off).
	// clientWidth excludes the scrollbar and the border (the --input has none) and both layers share
	// box-sizing:border-box + identical padding, so an explicit width equalizes their content boxes. `inset:0` plus
	// a width is over-constrained, which drops `right` — the inline width wins. Call AFTER overflowY is set.
	// Don't call it for `white-space:pre` editors: they don't wrap, so a narrower box changes nothing.
	syncOverlayWidth: function(textarea, overlays) {
		if(!textarea) return;
		const w = textarea.clientWidth + 'px';
		(overlays || []).forEach((el) => { if(el) el.style.width = w; });
	},

	// The gutter's variant of syncOverlayHeight — it's a VISIBLE surface, so it must cover the editor's full height
	// (sizing it to clientHeight like the mirror leaves the shell's editor background showing as a band under it
	// once a horizontal scrollbar appears). An explicit height is still required: the gutter is the flex container's
	// tallest item if left to size on its own content, which would drag the whole editor to the document's height —
	// `align-items:stretch` can't help, since the container's own height comes FROM its items.
	// So: size the border box to the textarea's FULL height, and equalize the scrollable RANGE separately, which is
	// what the scroll-sync (scrollTop mirroring) actually needs. The gutter's client box is taller than the
	// textarea's by the scrollbar's height, so its max scrollTop would land short and clamp — drifting the numbers
	// up off their rows at the bottom. Padding-bottom counts toward scrollHeight, so growing it by the scrollbar
	// height restores the match. `basePadBottom` is the gutter's AUTHORED padding-bottom in px — cache it before the
	// first call (a later read would return our own inline value). Call AFTER the textarea's height/overflow are set.
	syncGutterHeight: function(textarea, gutter, basePadBottom) {
		if(!textarea || !gutter) return;
		const scrollbarH = Math.max(0, textarea.offsetHeight - textarea.clientHeight);   // --input has no borders
		gutter.style.height = textarea.offsetHeight + 'px';
		gutter.style.paddingBottom = ((basePadBottom || 0) + scrollbarH) + 'px';
	},

	// The gutter's authored padding-bottom in px, read before any inline write — pair with syncGutterHeight.
	gutterPadBottom: function(gutter) {
		return gutter ? (parseFloat(window.getComputedStyle(gutter).paddingBottom) || 0) : 0;
	},

	// Observe an element's WIDTH and invoke cb on change (coalesced to one call per frame). Height-only changes are
	// ignored so an editor's own autosize height writes don't re-trigger it. Returns a disposer, or null when
	// ResizeObserver is unavailable. Used to re-run _autosize when the editor is resized narrow/wide, which toggles
	// the horizontal scrollbar that syncOverlayHeight compensates for.
	observeWidth: function(el, cb) {
		if(!el || typeof ResizeObserver !== 'function' || typeof cb !== 'function') return null;
		let lastW = -1, raf = 0;
		const ro = new ResizeObserver((entries) => {
			const w = entries[0] ? Math.round(entries[0].contentRect.width) : el.clientWidth;
			if(w === lastW) return;
			lastW = w;
			if(raf) return;
			raf = requestAnimationFrame(() => { raf = 0; cb(); });
		});
		ro.observe(el);
		return function() { ro.disconnect(); if(raf) cancelAnimationFrame(raf); };
	},

	// Mirror-div caret measurement: clone the textarea's text-affecting styles into an offscreen div, slice the
	// text at the caret, and read the offset of a marker span. Coordinates are relative to the textarea's border
	// box (callers position a caret anchor inside a position:relative --field that the textarea fills).
	caretCoords: function(textarea, caret) {
		const div = document.createElement('div');
		const cs = window.getComputedStyle(textarea);
		for(const p of CerbUI.editorCore.MIRROR_PROPS) div.style[p] = cs[p];
		div.style.position = 'absolute';
		div.style.visibility = 'hidden';
		div.style.whiteSpace = div.style.whiteSpace || 'pre-wrap';
		div.style.wordWrap = 'break-word';
		div.style.overflow = 'hidden';
		div.style.width = textarea.clientWidth + 'px';
		div.style.height = 'auto';

		div.textContent = textarea.value.slice(0, caret);
		const marker = document.createElement('span');
		marker.textContent = textarea.value.slice(caret) || '.';
		div.appendChild(marker);

		document.body.appendChild(div);
		const left = marker.offsetLeft;
		const top = marker.offsetTop;
		const height = parseFloat(cs.lineHeight) || (parseFloat(cs.fontSize) * 1.2);
		document.body.removeChild(div);

		return { left, top, height };
	},

	// ── Keyboard-shortcut matcher (shared by the editors' abstract keymaps) ──
	// Dash notation, modifiers case-insensitive, last segment is the key token:
	//   "Mod-D"  Mod = Cmd OR Ctrl (interchangeable)   "Alt-D" / "Opt-D"  altKey
	//   "Shift-Tab"   "Mod-ArrowUp"   "Mod-Space"   explicit "Ctrl-"/"Cmd-" also work.
	// Letters match on e.code ('KeyD'), NOT e.key — Opt+D on Mac yields e.key '∂'. Build descriptors with
	// parse(), test them with matchEvent(), and render hint labels with label() (⌘D on Mac, Ctrl+D on Win).
	keys: {
		isMac: (function() {
			const p = (navigator.userAgentData && navigator.userAgentData.platform)
				|| navigator.platform || navigator.userAgent || '';
			return /Mac|iPhone|iPad|iPod/i.test(p);
		})(),

		_NAMED: { ArrowUp:'ArrowUp', ArrowDown:'ArrowDown', ArrowLeft:'ArrowLeft', ArrowRight:'ArrowRight',
			Tab:'Tab', Enter:'Enter', Escape:'Escape', Space:'Space' },

		// key token -> the e.code it matches
		_codeFor: function(tok) {
			if(/^[A-Za-z]$/.test(tok)) return 'Key' + tok.toUpperCase();
			if(/^[0-9]$/.test(tok)) return 'Digit' + tok;
			return this._NAMED[tok] || tok;
		},

		parse: function(spec) {
			const out = { mod:false, alt:false, shift:false, ctrl:false, meta:false };
			const parts = String(spec).split('-');
			out.key = parts.pop();
			for(const p of parts) {
				const m = p.toLowerCase();
				if(m === 'mod') out.mod = true;
				else if(m === 'alt' || m === 'opt') out.alt = true;
				else if(m === 'shift') out.shift = true;
				else if(m === 'ctrl' || m === 'control') out.ctrl = true;
				else if(m === 'cmd' || m === 'meta') out.meta = true;
			}
			out.code = this._codeFor(out.key);
			out.codeMatch = (e) => e.code === out.code;
			return out;
		},

		// Exact match: every required modifier present AND no stray modifier present. Cmd/Ctrl/Mod are one family.
		matchEvent: function(parsed, e) {
			if(!parsed.codeMatch(e)) return false;
			const mod = e.metaKey || e.ctrlKey;
			if(parsed.mod && !mod) return false;
			if(parsed.ctrl && !e.ctrlKey) return false;
			if(parsed.meta && !e.metaKey) return false;
			// No-stray for the Cmd/Ctrl family: if none of mod/ctrl/meta was requested, neither may be down.
			if(!parsed.mod && !parsed.ctrl && !parsed.meta && mod) return false;
			if(parsed.alt !== e.altKey) return false;
			if(parsed.shift !== e.shiftKey) return false;
			return true;
		},

		// OS-aware label for a hint popup, in each platform's conventional modifier order. Mac (Apple HIG):
		// ⌃ ⌥ ⇧ ⌘, glyphs, no separators. Win/Linux: Ctrl Alt Shift, '+'-joined. `Mod` = ⌘ on Mac, Ctrl on Win.
		_KEY_LABEL: { ArrowUp:'↑', ArrowDown:'↓', ArrowLeft:'←', ArrowRight:'→', Escape:'Esc', Space:'Space',
			BracketLeft:'[', BracketRight:']', Slash:'/' },
		label: function(spec) {
			const p = this.parse(spec), mac = this.isMac;
			const k = this._KEY_LABEL[p.key] || (p.key.length === 1 ? p.key.toUpperCase() : p.key);
			if(mac) {
				let s = '';
				if(p.ctrl) s += '⌃';
				if(p.alt) s += '⌥';
				if(p.shift) s += '⇧';
				if(p.meta || p.mod) s += '⌘';
				return s + k;
			}
			const mods = [];
			if(p.ctrl || p.mod) mods.push('Ctrl');
			if(p.alt) mods.push('Alt');
			if(p.shift) mods.push('Shift');
			if(p.meta) mods.push('Win');
			return mods.length ? mods.join('+') + '+' + k : k;
		},
	},
};

// Run `cb` once, the first time `el` becomes visible. For editors constructed/setValue'd inside a display:none
// panel (preview tabs, collapsed fieldsets): a hidden textarea reports scrollHeight 0, so _autosize would clamp
// to minLines and never recover. This lets _autosize defer the measure until the panel is revealed. Returns a
// disposer; degrades to a no-op where IntersectionObserver is unavailable.
CerbUI.editorCore.onFirstReveal = function(el, cb) {
	if(typeof IntersectionObserver === 'undefined') return function() {};
	let obs = new IntersectionObserver(function(entries) {
		if(!obs) return; // already fired/disposed — ignore any trailing batch
		for(const e of entries) {
			if(e.isIntersecting) { obs.disconnect(); obs = null; cb(); break; }
		}
	});
	obs.observe(el);
	return function() { if(obs) { obs.disconnect(); obs = null; } };
};

/*
 * CerbUI.editorCore.buildEditorShell — generate the standard editor DOM around a BARE <textarea>/<input> so a
 * template only has to author the field. Mirrors the per-editor markup under the `.cerb-ui-<NS>--*` prefix
 * (--gutter? / --icon? / --field[--highlight + --input + --caret-anchor] / --right?). Idempotent: a field that
 * already lives inside a `.cerb-ui-<NS>` wrapper returns that wrapper untouched (so a constructor can call this
 * unconditionally on either bare or pre-built markup). An <input> keeps the ORIGINAL named control as a hidden
 * `--value` carrier and gets a shadow <textarea> as the editing surface, so the form POST is unchanged.
 * opts: { gutter, singleLine, leftIcon, rightHTML }. Returns { wrap, textarea, isInput, reused }.
 */
CerbUI.editorCore.buildEditorShell = function(field, NS, opts = {}) {
	field = (typeof field === 'string') ? document.querySelector(field) : field;
	if(!field || !field.parentNode) return null;

	const cls = (suffix) => 'cerb-ui-' + NS + (suffix ? '--' + suffix : '');

	const existing = field.closest('.' + cls());
	if(existing)
		return { wrap: existing, textarea: existing.querySelector('.' + cls('input')) || field, isInput: false, reused: true };

	const isInput = (field.tagName === 'INPUT');
	const singleLine = (opts.singleLine != null) ? opts.singleLine : isInput;
	const gutter = singleLine ? false : (opts.gutter !== false);

	const wrap = document.createElement('div');
	wrap.className = cls();

	// Carry a `.placeholders` opt-in (full floating placeholder strip) + its placement attr from a bare source
	// field onto the wrapper, and drop it from the field — so the legacy `textarea.placeholders` peek delegate
	// won't also match, and the component reads the opt-in from its own wrapper.
	if(field.classList && field.classList.contains('placeholders')) {
		wrap.classList.add('placeholders');
		field.classList.remove('placeholders');
		const _pl = field.getAttribute('data-cerb-placeholders-placement');
		if(_pl) {
			wrap.setAttribute('data-cerb-placeholders-placement', _pl);
			field.removeAttribute('data-cerb-placeholders-placement');
		}
	}

	let gutterEl = null;
	if(gutter) {
		gutterEl = document.createElement('div');
		gutterEl.className = cls('gutter');
		gutterEl.setAttribute('aria-hidden', 'true');
	}

	let iconEl = null;
	if(opts.leftIcon) {
		iconEl = document.createElement('span');
		iconEl.className = cls('icon') + ' cerb-icons cerb-icon-' + opts.leftIcon;
	}

	const fieldDiv = document.createElement('div');
	fieldDiv.className = cls('field');
	const highlight = document.createElement('div');
	highlight.className = cls('highlight');
	highlight.setAttribute('aria-hidden', 'true');
	const caretAnchor = document.createElement('span');
	caretAnchor.className = cls('caret-anchor');

	let textarea;
	if(isInput) {
		textarea = document.createElement('textarea');
		textarea.className = cls('input');
		textarea.value = field.value;
		if(field.placeholder) textarea.placeholder = field.placeholder;
		const lines = field.getAttribute('data-editor-lines');
		if(lines) textarea.setAttribute('data-editor-lines', lines);
		if(field.disabled || field.readOnly) textarea.setAttribute('data-editor-readonly', '');
		field.classList.add(cls('value'));          // hide + keep submittable (NOT disabled)
		field.setAttribute('tabindex', '-1');
		field.setAttribute('aria-hidden', 'true');
	} else {
		textarea = field;
		textarea.classList.add(cls('input'));
	}

	let rightEl = null;
	if(opts.rightHTML) {
		rightEl = document.createElement('div');
		rightEl.className = cls('right');
		rightEl.innerHTML = opts.rightHTML;
	}

	field.parentNode.insertBefore(wrap, field);
	if(gutterEl) wrap.appendChild(gutterEl);
	if(iconEl) wrap.appendChild(iconEl);
	wrap.appendChild(fieldDiv);
	fieldDiv.appendChild(highlight);
	fieldDiv.appendChild(textarea);
	fieldDiv.appendChild(caretAnchor);
	if(rightEl) wrap.appendChild(rightEl);
	if(isInput) wrap.appendChild(field);            // the value carrier rides inside the shell

	return { wrap, textarea, isInput, reused: false };
};

// Polymorphic-constructor helper: a bare <textarea>/<input> is wrapped in the shell on the fly (so callers can do
// `new CerbUI.X(textareaEl, opts)`); a real `.cerb-ui-<NS>` wrapper (or anything else) passes through unchanged.
CerbUI.editorCore.resolveEditorEl = function(el, NS, shellOpts = {}) {
	if(!el || (el.tagName !== 'TEXTAREA' && el.tagName !== 'INPUT')) return el;
	const built = CerbUI.editorCore.buildEditorShell(el, NS, shellOpts);
	return built ? built.wrap : el;
};

// Named-factory helper backing each editor's `static enhance(field, opts)`. Builds the shell, constructs the
// editor on it, and (for an <input> value-carrier) mirrors the editor value back to the original named control.
CerbUI.editorCore.enhanceEditor = function(EditorClass, field, opts = {}, shellOpts = null) {
	shellOpts = shellOpts || { gutter: opts.gutter, singleLine: opts.singleLine };
	const built = CerbUI.editorCore.buildEditorShell(field, EditorClass._NS, shellOpts);
	if(!built) return null;
	if(built.reused && EditorClass.from(built.wrap)) return EditorClass.from(built.wrap);   // already enhanced

	const editor = new EditorClass(built.wrap, opts);
	if(!editor || !editor.textarea) return null;

	if(built.isInput && typeof editor.onChange === 'function') {
		const carrier = built.wrap.querySelector('.cerb-ui-' + EditorClass._NS + '--value');
		if(carrier) {
			editor.onChange((val) => { carrier.value = val; });
			carrier.value = editor.getValue();      // initial sync
		}
	}
	return editor;
};

/*
 * CerbUI.editorCore.Autocomplete — the caret-anchored suggestion-menu controller shared by both editors.
 *
 * It owns the debounce timer, a monotonic request token (so a slow source can't clobber a newer request), the
 * CerbUI.Menu lifecycle, item rendering (optional leading icon + muted hint), and prefix replacement with a
 * `$0` caret marker. The host editor owns the keyboard model and just drives this controller:
 *   - call schedule() on input (debounced) or trigger() to force suggestions now (e.g. ⌘/Ctrl+Space)
 *   - read isOpen() and set `navigated = true` when the user arrows INTO the menu (the opt-in for Enter=select)
 *   - call close() to dismiss
 *
 * opts:
 *   textarea, caretAnchor   — the host's elements
 *   context, editor         — passed through to onItems' ctx (editor lets a source read back into the host)
 *   delay                   — debounce ms (default 200)
 *   onScope(text, caret)    — returns { path, prefix, prefixRaw } for the caret (host's grammar)
 *   onItems(ctx)            — returns Array<item> | Promise<...>; ctx = {path, prefix, context, query, caret, editor}
 *   onAfterApply()          — host re-highlights/autosizes after an insert (before re-focus)
 *     item = { caption, value, snippet?, hint?, icon?, iconColor?, suppressAutocomplete? }
 *       value   = text inserted at the caret (default); snippet overrides it, a single `$0` marks the caret
 *       suppressAutocomplete = don't re-open suggestions after this pick (terminal values)
 */
CerbUI.editorCore.Autocomplete = class {
	constructor(opts) {
		this.opts = opts || {};
		this.textarea = opts.textarea;
		this.caretAnchor = opts.caretAnchor;
		this.delay = (typeof opts.delay === 'number') ? opts.delay : 200;
		this._menu = null;
		this._token = 0;     // monotonic guard
		this._timer = null;  // debounce timer
		this.navigated = false; // true once the user arrows INTO the menu
		this._pointerInMenu = false; // true while the mouse is hovering the open menu (Enter then selects the hovered row)
	}

	isOpen() { return !!(this._menu && this._menu.isOpen()); }

	// True while the pointer is over the open menu — so a host's Enter handler can select the hover-highlighted
	// row even when the user never arrowed into the list (the menu popped up under a resting mouse).
	pointerInMenu() { return this._pointerInMenu; }

	// Drive the menu DIRECTLY from the host editor's keydown handler instead of relying on the Menu's own
	// document-bubble keydown listener — a CerbUI.Dialog's bubble-phase key containment blocks that listener,
	// so arrows/Enter never reached the menu when an editor was inside a popup. The host stopPropagation()s
	// these keys, so the (still-registered) Menu listener can't also act on them outside a dialog.
	moveSelection(dir) { if(this.isOpen()) { this.navigated = true; this._menu.moveActive(dir); } return this; }
	acceptSelection(e) { return this.isOpen() ? this._menu.selectActive(e) : false; }

	schedule() {
		this.clearTimer();
		this._timer = window.setTimeout(() => this.trigger(), this.delay);
	}

	clearTimer() {
		if(this._timer !== null) { clearTimeout(this._timer); this._timer = null; }
	}

	trigger() {
		this.clearTimer();
		if(typeof this.opts.onItems !== 'function') return;

		const ta = this.textarea;
		const caret = ta.selectionStart;
		const sp = this.opts.onScope(ta.value, caret);
		const ctx = {
			path: sp.path,
			prefix: sp.prefix,
			context: this.opts.context || '',
			query: ta.value,
			caret: caret,
			editor: this.opts.editor || null,
		};

		// Line index of the caret when the request went out — used to drop a stale async response.
		const reqLine = ta.value.slice(0, caret).split('\n').length;

		const token = ++this._token;
		Promise.resolve(this.opts.onItems(ctx)).then(items => {
			if(token !== this._token) return; // a newer request superseded this one
			// Stale: the caret jumped to a different line while the request was in flight (e.g. an Enter
			// newline landed before slow suggestions returned) — don't pop a menu at the new position.
			if(ta.value.slice(0, ta.selectionStart).split('\n').length !== reqLine) { this.close(); return; }
			if(!Array.isArray(items) || items.length === 0) { this.close(); return; }
			this._open(items, sp);
		}).catch(() => { this.close(); });
	}

	_open(items, sp) {
		this.close();

		const ul = document.createElement('ul'); // detached; CerbUI.Menu only reads its <li> children
		ul.hidden = true;
		let _i = 0;
		for(const it of items) {
			const li = document.createElement('li');
			li.dataset.acIndex = String(_i++); // back-reference to the source item for the per-item onSelect hook
			li.textContent = (it.caption != null) ? it.caption : (it.value != null ? it.value : '');
			li.dataset.value = (it.value != null) ? it.value : (it.caption != null ? it.caption : '');
			if(it.snippet != null) li.dataset.snippet = it.snippet;
			if(it.suppressAutocomplete) li.dataset.suppress = '1';
			if(it.hint) li.dataset.hint = it.hint;
			if(it.icon) li.dataset.icon = it.icon;
			if(it.iconColor) li.dataset.iconColor = it.iconColor;
			// Optional rich-row fields (e.g. @mention: avatar + name + right-aligned handle + a subtitle line).
			// Purely additive — only set when a source supplies them, so plain icon/hint sources are unaffected.
			if(it.avatar) {
				li.dataset.avatarLabel = it.avatar.label || (it.caption || '');
				if(it.avatar.seed) li.dataset.avatarSeed = it.avatar.seed;
				if(it.avatar.imageUrl) li.dataset.avatarImage = it.avatar.imageUrl;
			}
			if(it.handle) li.dataset.handle = it.handle;
			if(it.subtitle) li.dataset.subtitle = it.subtitle;
			ul.appendChild(li);
		}
		this._menuUl = ul;
		this._items = items; // kept so onSelect can reach a chosen item's custom onSelect hook
		this.navigated = false; // a fresh list isn't navigated until the user arrows into it

		// Rich rows (avatar @mentions, or a two-line label + description like #commands) are taller — itemHeight
		// must match the CSS height for the virtualized-scroll math, and they get a wider panel so the label and
		// its description aren't truncated.
		const richRows = items.some(it => it && (it.avatar || it.subtitle));

		this._menu = new CerbUI.Menu(ul, {
			// absolute (not fixed) so the dropdown is placed at document coords and scrolls WITH the field/page,
			// staying glued to the caret instead of locking to the viewport.
			fixed: false,
			closeOnSelect: true,
			itemHeight: richRows ? 40 : 28,
			panelClass: richRows ? 'cerb-ui-editor-menu--wide' : undefined,
			onRenderItem: (li, src) => this._renderItem(li, src),
			onClose: () => { this.navigated = false; },
			onSelect: (li, src) => {
				// A source item may carry its own onSelect(editor, ac) for non-default insertion (e.g. the reply
				// composer's #delete_quote_from_here / #snippet picks). When present it fully handles the pick.
				const item = this._items ? this._items[+(src.dataset.acIndex)] : null;
				if(item && typeof item.onSelect === 'function') {
					this.close();
					item.onSelect(this.opts.editor, this);
					return;
				}
				// A suggestion carrying an `interaction` runs a named automation and inserts its `return: snippet:`
				// output instead of the caption (e.g. the chart date/number format pickers).
				if(item && item.interaction) {
					this._applyInteraction(item);
					return;
				}
				this._apply({
					insert: (src.dataset.snippet != null) ? src.dataset.snippet : src.dataset.value,
					suppress: src.dataset.suppress === '1',
				});
			},
		});

		this._positionAnchor(sp.caret);
		this._menu.open(this.caretAnchor);

		// Track whether the pointer is over the menu so a hovered row is Enter-selectable (mouseover, not
		// mouseenter, so it also fires when the menu pops up under an already-resting mouse).
		this._pointerInMenu = false;
		const panel = (this._menu.pnls && this._menu.pnls[0]) ? (this._menu.pnls[0].outer || this._menu.pnls[0].el) : null;
		if(panel) {
			// Hovering a row also marks the menu `navigated` — the hover highlight is the user's choice, so Enter
			// selects it, and (unlike _pointerInMenu) that persists after the pointer leaves, matching the sticky
			// highlight. Fixes hosts that gate Enter on `navigated` alone (SearchQuery, ScriptingEditor).
			panel.addEventListener('mouseover', () => { this._pointerInMenu = true; this.navigated = true; });
			panel.addEventListener('mouseleave', () => { this._pointerInMenu = false; });
		}
	}

	// onRenderItem hook: a rich avatar row (@mention: avatar + name/handle on one line + a subtitle line) when
	// the source supplied avatar fields, else the plain leading-icon + muted right-aligned hint.
	_renderItem(li, src) {
		if(src.dataset.avatarLabel && window.CerbUI && CerbUI.Avatar && typeof CerbUI.Avatar.create === 'function') {
			li.classList.add('cerb-ui-editor-menu--rich');

			// The name lives in the Menu's --label span; move its text into our own text column.
			const labelEl = li.querySelector('.cerb-ui-menu--label');
			const name = labelEl ? labelEl.textContent : src.dataset.avatarLabel;
			if(labelEl) labelEl.remove();

			const avatar = CerbUI.Avatar.create({
				label: src.dataset.avatarLabel,
				seed: src.dataset.avatarSeed || src.dataset.avatarLabel,
				imageUrl: src.dataset.avatarImage || null,
				size: 28,
			});
			avatar.classList.add('cerb-ui-editor-menu--avatar');
			li.insertBefore(avatar, li.firstChild);

			// Line 1: name (left) + the @handle (right). Line 2 (optional): the subtitle (e.g. title).
			const text = document.createElement('span');
			text.className = 'cerb-ui-editor-menu--text';

			const nameLine = document.createElement('span');
			nameLine.className = 'cerb-ui-editor-menu--nameline';
			const nameSpan = document.createElement('span');
			nameSpan.className = 'cerb-ui-editor-menu--name';
			nameSpan.textContent = name;
			nameLine.appendChild(nameSpan);
			if(src.dataset.handle) {
				const h = document.createElement('span');
				h.className = 'cerb-ui-editor-menu--handle';
				h.textContent = src.dataset.handle;
				nameLine.appendChild(h);
			}
			text.appendChild(nameLine);

			if(src.dataset.subtitle) {
				const sub = document.createElement('span');
				sub.className = 'cerb-ui-editor-menu--sub';
				sub.textContent = src.dataset.subtitle;
				text.appendChild(sub);
			}
			li.insertBefore(text, avatar.nextSibling);
			return;
		}

		// Two-line row WITHOUT an avatar (e.g. #command: the label on top, its description muted below) — keeps
		// the label from being truncated by a right-aligned hint.
		if(src.dataset.subtitle) {
			li.classList.add('cerb-ui-editor-menu--rich');

			const labelEl = li.querySelector('.cerb-ui-menu--label');
			const name = labelEl ? labelEl.textContent : (src.dataset.value || '');
			if(labelEl) labelEl.remove();

			const text = document.createElement('span');
			text.className = 'cerb-ui-editor-menu--text';

			const nameLine = document.createElement('span');
			nameLine.className = 'cerb-ui-editor-menu--nameline';
			const nameSpan = document.createElement('span');
			nameSpan.className = 'cerb-ui-editor-menu--name';
			nameSpan.textContent = name;
			nameLine.appendChild(nameSpan);
			text.appendChild(nameLine);

			const sub = document.createElement('span');
			sub.className = 'cerb-ui-editor-menu--sub';
			sub.textContent = src.dataset.subtitle;
			text.appendChild(sub);

			li.appendChild(text);
			return;
		}

		if(src.dataset.icon) {
			const ico = document.createElement('span');
			const name = src.dataset.icon;
			ico.className = 'cerb-ui-editor-menu-icon ' + ((name.charAt(0) === '.')
				? name.slice(1).split('.').join(' ')
				: ('cerb-icons cerb-icon-' + name));
			if(src.dataset.iconColor)
				ico.style.setProperty('--cerb-ui-editor-menu-icon-color', 'var(--cerb-color-tag-' + src.dataset.iconColor + ')');
			li.insertBefore(ico, li.firstChild);
		}
		if(src.dataset.hint) {
			const hint = document.createElement('span');
			hint.className = 'cerb-ui-editor-menu-hint';
			hint.textContent = src.dataset.hint;
			li.appendChild(hint);
		}
	}

	// An interaction-backed suggestion: remove the partial token, run the named automation, and insert its
	// `return: snippet:` output at the caret (port of the legacy cerberus.js `insertMatch` interaction branch).
	_applyInteraction(item) {
		this.close();
		const editor = this.opts.editor;
		this._replacePrefix('');          // drop the typed filter text; the snippet replaces it
		this.textarea.focus();

		const $ = window.jQuery;
		if(!$ || typeof $.fn.cerbBotTrigger !== 'function') return;

		const $trigger = $('<div/>')
			.attr('data-interaction-uri', item.interaction)
			.attr('data-interaction-params', item.interaction_params || '')
			.cerbBotTrigger({
				caller: 'automation.editor.kata.autocomplete',
				done: function(e) {
					e.stopPropagation();
					$trigger.remove();
					if(window.Devblocks && typeof Devblocks.interactionWorkerPostActions === 'function')
						Devblocks.interactionWorkerPostActions(e.eventData, editor);
				},
				error: function(e) { e.stopPropagation(); $trigger.remove(); },
				abort: function(e) { e.stopPropagation(); $trigger.remove(); }
			})
			.click();
	}

	// Replace the partial word at the caret with the chosen text, then re-suggest the next level (unless the
	// item asked us not to) so deep paths can be built without re-typing.
	_apply({ insert, suppress }) {
		this.close();
		this._replacePrefix(insert ?? '');
		if(typeof this.opts.onAfterApply === 'function') this.opts.onAfterApply();
		this.textarea.focus();
		if(suppress) return;
		// Don't immediately re-open the menu when the pick COMPLETED the current token — a non-empty prefix at
		// the new caret means we'd just re-offer the value we inserted. Re-suggest only when the caret was left
		// in a fresh slot (empty prefix), i.e. a chain like `sender:($0)` descending into a new scope. Typing
		// re-triggers normally via the input handler.
		const sp = this.opts.onScope(this.textarea.value, this.textarea.selectionStart);
		if(sp && sp.prefix) return;
		this.trigger();
	}

	// Swap the partial word being typed for `insert`. A single `$0` in `insert` marks where the caret lands
	// (e.g. `sender:($0)` -> caret between the parens); without it, the caret goes to the end.
	_replacePrefix(insert) {
		const ta = this.textarea;
		const caret = ta.selectionStart;
		const sp = this.opts.onScope(ta.value, caret);
		const start = caret - sp.prefixRaw.length;

		// Multi-line snippets nest under the caret's current line: shift continuation lines by its indent.
		const lineStart = ta.value.lastIndexOf('\n', start - 1) + 1;
		const indent = (ta.value.slice(lineStart, start).match(/^[ \t]*/) || [''])[0];
		insert = CerbUI.editorCore.indentSnippet(insert, indent);

		let caretOffset = insert.length;
		const marker = insert.indexOf('$0');
		if(marker !== -1) {
			caretOffset = marker;
			insert = insert.slice(0, marker) + insert.slice(marker + 2);
		}

		ta.value = ta.value.slice(0, start) + insert + ta.value.slice(caret);
		ta.selectionStart = ta.selectionEnd = start + caretOffset;
	}

	// Move the (zero-width) caret anchor to the caret's pixel position so the menu floats just below it.
	_positionAnchor(caret) {
		const c = CerbUI.editorCore.caretCoords(this.textarea, caret);
		this.caretAnchor.style.left = (c.left - this.textarea.scrollLeft) + 'px';
		this.caretAnchor.style.top = (c.top - this.textarea.scrollTop) + 'px';
		this.caretAnchor.style.height = c.height + 'px';
	}

	close() {
		if(this._menu) {
			this._menu.destroy();
			this._menu = null;
		}
		this._menuUl = null;
		this._pointerInMenu = false;
	}

	destroy() {
		this.clearTimer();
		this.close();
	}
};

// KATA field-autocomplete schema maps (kataToolbar, kataSchemaSheet, …), relocated from cerberus.js to
// autocomplete-schemas.js. Canonical accessor for the per-context suggestion maps fed to
// CerbUI.KataEditor.kataFieldSource(). Attached here (not in autocomplete-schemas.js) because this file
// reassigns CerbUI.editorCore wholesale above and loads after that file in both dev and the bundle.
CerbUI.editorCore.autocompleteSchemas = (typeof cerbAutocompleteSuggestions !== 'undefined' && cerbAutocompleteSuggestions) || {};

/*
 * CerbUI.editorCore.kataScript — composable highlighting + autocomplete for Cerb's scripting tags.
 *
 * "KataScript" is what's replacing Twig; the syntax is identical for now (`{{ … }}` output, `{% … %}`
 * commands), so this reads the existing static `twigAutocompleteSuggestions` (the de-facto suggestion set) and
 * the `--cerb-editor-syntax-twig*` theme colors — only the NEW symbols here are KataScript-branded. The module
 * is editor-agnostic so KataEditor (KATA + script) and the upcoming TemplateEditor (plaintext + script) share
 * it. Three pieces:
 *   - tokenize(): a state machine that opens a tag on `{{`/`{%` IMMEDIATELY (even unterminated) and colors the
 *     inside (delimiters/strings/numbers/functions); non-tag runs go to a caller-supplied `plain` tokenizer.
 *   - contextAt(): the caret's tag context (command / function / filter / args), for autocomplete.
 *   - suggest(): maps the suggestion set to menu items (with colored type icons) for a context.
 */
CerbUI.editorCore.kataScript = {
	// Token type -> shared CSS class (editors merge this into their own classMap). Identifiers/operators carry
	// no class (the default literal color).
	TOK_CLASS: {
		'kscript':        'cerb-ui-editor--tok-kscript',
		'kscript-string': 'cerb-ui-editor--tok-kscript-string',
		'kscript-number': 'cerb-ui-editor--tok-kscript-number',
		'kscript-func':   'cerb-ui-editor--tok-kscript-func',
	},

	// Suggestion `meta` -> menu type icon + tag color (confirmed-existing cerb-icons; easily tweaked).
	META_ICON: {
		command:  { icon: 'play-button',   color: 'purple' },
		function: { icon: 'function',    color: 'blue' },
		filter:   { icon: 'funnel', color: 'green' },
		snippet:  { icon: 'clipboard',   color: 'gray' },
		variable: { icon: 'tag',    color: 'gray' },
	},

	_suggestions: function() {
		// `twigAutocompleteSuggestions` (cerberus.js) is a top-level `let`, so it lives in the shared global
		// lexical scope (reachable by a bare reference from any classic script) but is NOT a property of
		// `window` — the typeof guard reads it safely whether or not it's loaded.
		return (typeof twigAutocompleteSuggestions !== 'undefined' && twigAutocompleteSuggestions)
			|| { snippets: [], tags: [], filters: [], functions: [] };
	},

	// Push tokens for `str` into `toks`. `startOpen` (a '{{' / '{%' opener, or falsy) continues a tag begun on a
	// previous line; returns the opener still in effect at the end (or null) so a line-based caller can chain.
	// `plain(toks, text, baseType)` tokenizes the runs OUTSIDE any tag (default: one baseType token).
	tokenize: function(toks, str, baseType, startOpen, plain) {
		plain = plain || function(t, s, bt) { if(s) t.push({ type: bt, value: s }); };
		const OPEN = /\{\{-?|\{%-?/g;
		let i = 0, open = startOpen || null;
		const n = str.length;

		while(i < n) {
			if(!open) {
				OPEN.lastIndex = i;
				const m = OPEN.exec(str);
				if(!m) { plain(toks, str.slice(i), baseType); break; }
				if(m.index > i) plain(toks, str.slice(i, m.index), baseType);
				toks.push({ type: 'kscript', value: m[0] });
				open = m[0].slice(0, 2); // '{{' or '{%'
				i = m.index + m[0].length;
			} else {
				const CLOSE = (open === '{{') ? /-?\}\}/g : /-?%\}/g;
				CLOSE.lastIndex = i;
				const m = CLOSE.exec(str);
				const end = m ? m.index : n;
				this._tokenizeInner(toks, str.slice(i, end));
				i = end;
				if(!m) break; // unterminated — the tag stays open into the next line
				toks.push({ type: 'kscript', value: m[0] });
				i = m.index + m[0].length;
				open = null;
			}
		}
		return open;
	},

	// Color the inside of a tag: strings, numbers, and function names (a word directly before `(` or right after
	// a `|` pipe). Identifiers, operators and whitespace stay literal. Gaps/identifiers use 'kscript-text' (no
	// class) so they render in the default color.
	_tokenizeInner: function(toks, str) {
		if(!str) return;
		const RX = /"(?:\\.|[^"\\])*"?|'(?:\\.|[^'\\])*'?|\d+(?:\.\d+)?|[A-Za-z_][A-Za-z0-9_]*/g;
		let last = 0, m;
		while((m = RX.exec(str)) !== null) {
			if(m.index > last) toks.push({ type: 'kscript-text', value: str.slice(last, m.index) });
			const v = m[0], c = v.charAt(0);
			let type;
			if(c === '"' || c === "'") {
				type = 'kscript-string';
			} else if(c >= '0' && c <= '9') {
				type = 'kscript-number';
			} else {
				const after = str.charAt(m.index + v.length);
				let beforeChar = null;
				for(let k = m.index - 1; k >= 0; k--) { if(str[k] !== ' ' && str[k] !== '\t') { beforeChar = str[k]; break; } }
				type = (after === '(' || beforeChar === '|') ? 'kscript-func' : 'kscript-text';
			}
			toks.push({ type: type, value: v });
			last = m.index + v.length;
		}
		if(last < str.length) toks.push({ type: 'kscript-text', value: str.slice(last) });
	},

	// The script context at the caret, or null if not inside a tag. Returns { open:'{{'|'{%', sub, prefix,
	// prefixRaw } where sub is 'command' (the verb after `{%`), 'function' (an expression in `{{`), 'filter'
	// (after a `|`), or 'args' (inside an unbalanced `(`). For 'args' it also carries { name, kind } — the
	// enclosing call's identifier and whether it's a `function(` or a `|filter(` — so a caller can suggest
	// that call's parameters (see suggestArgs).
	contextAt: function(text, caret) {
		const before = text.slice(0, caret);
		const DELIM = /\{\{-?|\{%-?|-?\}\}|-?%\}/g;
		let last = null, m;
		while((m = DELIM.exec(before)) !== null) last = m;
		if(!last || last[0].indexOf('}') !== -1) return null; // nearest delimiter is a closer (or none) -> outside

		const open = (last[0].charAt(1) === '{') ? '{{' : '{%';
		const inner = before.slice(last.index + last[0].length);
		const pm = inner.match(/[A-Za-z_][A-Za-z0-9_]*$/);
		const prefix = pm ? pm[0] : '';

		// String-aware scan: find the innermost still-open `(` (parens inside quotes don't count).
		const openStack = [];
		let q = null;
		for(let k = 0; k < inner.length; k++) {
			const ch = inner[k];
			if(q) { if(ch === '\\') { k++; } else if(ch === q) { q = null; } continue; }
			if(ch === '"' || ch === "'") q = ch;
			else if(ch === '(') openStack.push(k);
			else if(ch === ')') openStack.pop();
		}
		const openParen = openStack.length ? openStack[openStack.length - 1] : -1;

		if(openParen >= 0) {
			// Inside a call's args — walk back over whitespace to the identifier just before the `(`.
			let j = openParen - 1;
			while(j >= 0 && (inner[j] === ' ' || inner[j] === '\t')) j--;
			let end = j + 1, start = end;
			while(start > 0 && /[A-Za-z0-9_]/.test(inner[start - 1])) start--;
			let name = null, kind = null;
			if(end > start) {
				name = inner.slice(start, end);
				let p = start - 1;                          // a `|name(` is a filter, else a function call
				while(p >= 0 && (inner[p] === ' ' || inner[p] === '\t')) p--;
				kind = (inner[p] === '|') ? 'filter' : 'function';
			} // else: a bare grouping `(` — name/kind stay null, suggestArgs returns nothing
			return { open: open, sub: 'args', prefix: prefix, prefixRaw: prefix, name: name, kind: kind, inner: inner, openParen: openParen };
		}

		const beforeWord = inner.slice(0, inner.length - prefix.length);
		const sig = beforeWord.replace(/\s+$/, '').slice(-1);
		let sub;
		if(sig === '|') sub = 'filter';
		else if(open === '{%' && beforeWord.trim().length === 0) sub = 'command';
		else sub = 'function';
		return { open: open, sub: sub, prefix: prefix, prefixRaw: prefix };
	},

	// Map the suggestion set to menu items for a context (filtered by its prefix). No items inside `(…)` args.
	suggest: function(tctx, opts) {
		if(!tctx || tctx.sub === 'args') return [];
		const S = this._suggestions();
		const list = (tctx.sub === 'command') ? (S.tags || [])
			: (tctx.sub === 'filter') ? (S.filters || [])
			: (S.functions || []);
		const META = this.META_ICON;
		const items = list.map(function(s) {
			const ic = META[s.meta] || null;
			const item = {
				caption: s.value,
				value: CerbUI.editorCore.aceSnippetToCerb(s.snippet != null ? s.snippet : s.value),
				// a concrete verb/filter/function is terminal; chaining (`|`) re-triggers on the next keystroke.
				suppressAutocomplete: true,
			};
			if(ic) { item.icon = ic.icon; item.iconColor = ic.color; }
			return item;
		});
		return CerbUI.editorCore.filterItems(items, tctx.prefix, opts && opts.filterMode);
	},

	// Suggest the parameters of the function/filter the caret is inside (tctx from contextAt, sub==='args').
	// The parameter names are already encoded in the suggestion signature (e.g. "array_column(a,b,c)"), so no
	// new data is needed. Lists every param (caption=name, hint=full signature) and floats the param the caret
	// is currently on (counted by top-level commas) to the top via score.
	suggestArgs: function(tctx, opts) {
		if(!tctx || tctx.sub !== 'args' || !tctx.name) return [];
		const S = this._suggestions();
		const list = (tctx.kind === 'filter') ? (S.filters || []) : (S.functions || []);
		const nameOf = function(v) { const i = String(v).indexOf('('); return (i === -1) ? String(v) : String(v).slice(0, i); };
		const entry = list.find(function(s) { return nameOf(s.value) === tctx.name; });
		if(!entry) return [];
		const sig = String(entry.value);
		const lp = sig.indexOf('('), rp = sig.lastIndexOf(')');
		if(lp === -1) return [];
		const inside = sig.slice(lp + 1, rp === -1 ? undefined : rp).trim();
		if(!inside.length) return [];                            // zero-arg call — nothing to suggest
		// Split on TOP-LEVEL commas only — a param default can carry quoted or nested commas (e.g. `glue=', '`,
		// `date('F d, Y')`, `pick=[a, b]`), which a plain `.split(',')` would mangle.
		const params = this._splitTopLevel(inside).map(function(p) { return p.trim(); }).filter(Boolean);

		// Active arg index = top-level commas between the innermost open `(` and the caret (= segments - 1).
		let active = 0;
		if(typeof tctx.inner === 'string' && typeof tctx.openParen === 'number')
			active = this._splitTopLevel(tctx.inner.slice(tctx.openParen + 1)).length - 1;

		const kindMeta = this.META_ICON[tctx.kind] || this.META_ICON.function;
		const items = params.map(function(p, idx) {
			return {
				caption: p,
				value: p,                                       // inserts the bare param name — never garbage
				hint: entry.value,                              // full signature for context
				suppressAutocomplete: true,
				score: (idx === active) ? 2000 : 1000,          // float the param the caret is on
				icon: 'parentheses',                            // a distinct args glyph…
				iconColor: kindMeta.color,                      // …colored by kind (function blue / filter green)
			};
		});
		return CerbUI.editorCore.filterItems(items, tctx.prefix, opts && opts.filterMode);
	},

	// Split a string on TOP-LEVEL commas, honoring quotes (with escapes) and nested ()/[]/{} — so a comma inside
	// a string or a nested call/list isn't a separator. Returns raw segments (trailing empty kept, so callers can
	// use `.length - 1` as the comma count); trim/filter at the call site for a clean name list.
	_splitTopLevel: function(s) {
		const out = []; let depth = 0, q = null, buf = '';
		for(let k = 0; k < s.length; k++) {
			const ch = s[k];
			if(q) {
				buf += ch;
				if(ch === '\\' && k + 1 < s.length) buf += s[++k];
				else if(ch === q) q = null;
				continue;
			}
			if(ch === '"' || ch === "'") { q = ch; buf += ch; continue; }
			if(ch === '(' || ch === '[' || ch === '{') { depth++; buf += ch; continue; }
			if(ch === ')' || ch === ']' || ch === '}') { if(depth > 0) depth--; buf += ch; continue; }
			if(ch === ',' && depth === 0) { out.push(buf); buf = ''; continue; }
			buf += ch;
		}
		out.push(buf);
		return out;
	},

};

/*
 * CerbUI.editorCore.searchQuery — the shared "brain" for Cerb search-query syntax: a small sticky-regex
 * tokenizer (drives highlighting) and the nested-filter scope-path walker at the caret (drives autocomplete).
 * Extracted from CerbUI.SearchQuery so CerbUI.DataQuery can reuse the exact grammar — data queries embed
 * `query:(…)` clauses in the same syntax. SearchQuery delegates its `_tokenize`/`_scopePathAt` here; the token
 * classes keep their `cerb-ui-searchquery--tok-*` names so colors are unchanged and DataQuery reuses them.
 */
CerbUI.editorCore.searchQuery = {
	// Token type -> CSS class for the highlight mirror (text/ws/comma have no class).
	TOK_CLASS: {
		field:     'cerb-ui-searchquery--tok-field',
		quoted:    'cerb-ui-searchquery--tok-string',
		bool:      'cerb-ui-searchquery--tok-bool',
		number:    'cerb-ui-searchquery--tok-number',
		lparen:    'cerb-ui-searchquery--tok-paren',
		lparenNeg: 'cerb-ui-searchquery--tok-paren',
		rparen:    'cerb-ui-searchquery--tok-paren',
		lbrack:    'cerb-ui-searchquery--tok-paren',
		rbrack:    'cerb-ui-searchquery--tok-paren',
	},

	// Ordered, sticky tokenizer rules (first match at the cursor wins).
	_RX: [
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
	],

	// Cerb's grammar is small, so a sticky-regex scan is enough. Order matters: the first pattern that matches at
	// the cursor wins. Returns [{type, value, start, end, inner?, terminated?}, ...] covering the whole string
	// (every char belongs to exactly one token).
	tokenize: function(text) {
		const toks = [];
		let i = 0;
		const n = text.length;
		const RX = this._RX;

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
	},

	// Push the query tokens for `str` into an existing `toks` array — the `plain(toks, str, baseType)` shape that
	// kataScript.tokenize expects, so DataQuery can thread the query tokenizer UNDER kataScript per line. The
	// `baseType` arg is accepted (signature compatibility) but ignored — every char already maps to a concrete
	// query token type. Pushed start/end are relative to `str`; the highlight renderer ignores them.
	tokenizeInto: function(toks, str /*, baseType */) {
		if(!str) return;
		for(const t of this.tokenize(str)) toks.push(t);
	},

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
	scopePathAt: function(text, caret) {
		const toks = this.tokenize(text.slice(0, caret));
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
	},
};

/*
 * EditorToolbar — the built-in CerbUI.Toolbar strip shared by the editor family. Because we own the editors, an
 * editor that has toolbar-able actions (today only MarkdownEditor's formatting) builds one of these in its own
 * constructor instead of every call site hand-authoring a button strip. It builds a `cerb-ui-toolbar` <ul> of the
 * editor's built-in actions, MERGES any host-provided toolbar section <ul>s into it (via CerbUI.Toolbar's own
 * `sections` — e.g. a worker-configured toolbar record rendered to DOM by ui/toolbar/render.tpl), and inserts the
 * strip (plus an optional markdown↔plaintext switcher) ABOVE the editor element. CerbUI.Toolbar does the rendering,
 * dividers, interaction firing, and overflow; this just composes the source list + routes clicks through ONE
 * dispatch:
 *
 *     onAction(value, editor, item, sourceLi)  -> truthy = the host handled it (stop here)
 *                                              -> falsy  = run the editor's built-in handler (if any)
 *
 * So a host conditionally OVERRIDES any built-in (intercept 'bold') and otherwise lets the editor do its default;
 * host section items (read their data-value / `.cerb-bot-trigger` class / data-* off `sourceLi`) are handled in
 * onAction, or fire as interactions through CerbUI.Toolbar directly. Per-item callbacks are gone — everything is
 * the one `onAction` keyed by the clicked item's value.
 *
 *   spec = {
 *     anchor:      el,                              // insert the strip before this (defaults to editor.el)
 *     builtins:    { name: { icon, title, fn } },   // the editor's built-in actions; fn(editor) is the default
 *     buttons:     ['bold','italic',…],             // which built-ins to show, in order (default: all builtin keys)
 *     formatClass: 'cerb-format-md-item',           // built-in items hidden while the switcher is in 'plaintext'
 *     mode:        false | { value, onSelect },     // optional markdown↔plaintext switcher (value = initial)
 *     sections:    [ul|selector, …],                // host toolbar section <ul>s merged after the built-ins
 *     onAction:    (value, editor, item, sourceLi) => bool,   // host override; first crack at every click
 *     toolbarOpts: { … },                           // passthrough to CerbUI.Toolbar (caller/start/done/bare/overflow/…)
 *   }
 */
CerbUI.editorCore.EditorToolbar = class {
	constructor(editor, spec = {}) {
		if(!editor || !window.CerbUI || !CerbUI.Toolbar) return;
		const anchor = spec.anchor || editor.el;
		if(!anchor || !anchor.parentNode) return;

		this.editor = editor;
		this._formatClass = spec.formatClass || null;

		const builtins = spec.builtins || {};
		const buttons = Array.isArray(spec.buttons) ? spec.buttons : Object.keys(builtins);

		const bar = document.createElement('div');
		// No bottom margin — the strip sits flush on top of the editor (connected, like the old hand-authored toolbars).
		bar.className = 'cerb-ui-editor-toolbar cerb-u-flex cerb-u-items-center cerb-u-gap-2';
		this.el = bar;

		// Optional markdown↔plaintext switcher; flips the editor's mode (via spec.mode.onSelect) and hides the
		// format buttons in plaintext (they'd be meaningless).
		if(spec.mode) {
			const sw = document.createElement('div');
			sw.className = 'cerb-ui-switcher';
			sw.innerHTML = '<button type="button" data-value="markdown" title="Markdown"><span class="cerb-icons cerb-icon-paintbrush"></span></button>'
				+ '<button type="button" data-value="plaintext" title="Plain text"><span class="cerb-icons cerb-icon-text"></span></button>';
			bar.appendChild(sw);
			this.switcher = new CerbUI.Switcher(sw, {
				value: spec.mode.value || 'markdown',
				onSelect: (v) => {
					this.toggleFormatItems(v === 'markdown');
					if(typeof spec.mode.onSelect === 'function') spec.mode.onSelect(v);
				},
			});
		}

		const ul = document.createElement('ul');
		ul.className = 'cerb-ui-toolbar';
		this.ul = ul;

		buttons.forEach(name => {
			const b = builtins[name];
			if(!b) return;
			const li = document.createElement('li');
			li.dataset.value = name;
			if(b.icon) li.dataset.icon = b.icon;
			if(b.title) li.title = b.title;
			if(this._formatClass) li.className = this._formatClass;
			ul.appendChild(li);
		});

		bar.appendChild(ul);
		anchor.parentNode.insertBefore(bar, anchor);

		// CerbUI.Toolbar renders the strip, folds in the host sections (divider between), and fires interactions.
		this.toolbar = new CerbUI.Toolbar(ul, Object.assign({}, spec.toolbarOpts, {
			sections: spec.sections,
			onSelect: (item, sourceLi, e) => {
				const v = item ? item.value : null;
				// onAction gets first crack at EVERY activation — including value-less items (e.g. a placeholder-tree
				// leaf carrying only data-token on its sourceLi). `e` is the click event (e.currentTarget = the strip
				// button) for anchoring popups. Truthy return = handled.
				if(typeof spec.onAction === 'function' && spec.onAction(v, editor, item, sourceLi, e)) return;
				if(!v) return;
				const b = builtins[v];
				if(b && typeof b.fn === 'function') { b.fn(editor); return; }
				if(typeof editor[v] === 'function') editor[v]();  // a method named for the value (else a no-op host item)
			},
		}));

		if(spec.mode && spec.mode.value === 'plaintext')
			this.toggleFormatItems(false);
	}

	// Drive the markdown↔plaintext switcher programmatically (e.g. a pasted image forcing markdown). Fires the
	// switcher's onSelect so the editor mode, format-item visibility, and the host onMode all sync. No-op if there's
	// no switcher.
	setMode(value) {
		if(this.switcher && typeof this.switcher.setValue === 'function')
			this.switcher.setValue(value, { fireCallback: true });
		return this;
	}

	toggleFormatItems(show) {
		if(!this.ul || !this._formatClass) return;
		const items = this.ul.querySelectorAll('.' + this._formatClass);
		items.forEach(li => { li.hidden = !show; });
		// Hide the divider trailing the formatting section too — otherwise a leading divider floats before the
		// host sections once the format buttons are gone (plaintext mode).
		if(items.length) {
			const next = items[items.length - 1].nextElementSibling;
			if(next instanceof HTMLLIElement && next.children.length === 0 && next.textContent.trim() === ''
					&& !next.dataset.icon && !next.dataset.label)
				next.hidden = !show;
		}
		if(this.toolbar && typeof this.toolbar.refresh === 'function') this.toolbar.refresh();
	}

	destroy() {
		if(this.toolbar && typeof this.toolbar.destroy === 'function') this.toolbar.destroy();
		if(this.switcher && typeof this.switcher.destroy === 'function') this.switcher.destroy();
		if(this.el && this.el.parentNode) this.el.parentNode.removeChild(this.el);
	}
};

/*
 * attachToolbar(editor, opts, defaults) — the CORE editor-family hook for a built-in toolbar. EVERY editor calls
 * this at the end of its constructor, so a caller can add extensible toolbar `sections` to ANY editor (the strip is
 * inserted above the field). Editors that publish a static `TOOLBAR_BUILTINS` map (today only MarkdownEditor's
 * formatting) also get those buttons; editors without one are sections-only. No `opts.toolbar` → nothing is built;
 * `opts.readOnly` suppresses it. `defaults` lets an editor seed config (MarkdownEditor passes `{mode:true}` so its
 * markdown↔plaintext switcher is on by default). Stores the instance on `editor._editorToolbar` (cleaned up in the
 * editor's destroy).
 *
 *   opts.toolbar = true | { buttons, mode, onMode, sections, onAction, toolbarOpts }
 */
CerbUI.editorCore.attachToolbar = function(editor, opts, defaults) {
	opts = opts || {};
	if(!editor || !opts.toolbar || opts.readOnly) return null;

	const cfg = Object.assign({}, defaults || {}, (opts.toolbar === true) ? {} : opts.toolbar);
	const builtins = (editor.constructor && editor.constructor.TOOLBAR_BUILTINS) || {};

	// A markdown↔plaintext switcher only when the config asks for it AND the editor actually has modes.
	let mode = false;
	if(cfg.mode && typeof editor.setMode === 'function') {
		mode = {
			value: (typeof editor.getMode === 'function') ? editor.getMode() : 'markdown',
			onSelect: (v) => { editor.setMode(v); if(typeof cfg.onMode === 'function') cfg.onMode(v); },
		};
	}

	editor._editorToolbar = new CerbUI.editorCore.EditorToolbar(editor, {
		builtins: builtins,
		buttons: cfg.buttons,
		formatClass: cfg.formatClass || 'cerb-ui-editor-toolbar--format',
		mode: mode,
		sections: cfg.sections,
		onAction: cfg.onAction,
		toolbarOpts: cfg.toolbarOpts,
	});
	return editor._editorToolbar;
};

/*
 * CerbUI.editorCore.attachEventHandlerTester — wire the shared automation event-handler "Test" panel to a
 * main KataEditor. The panel markup lives in automations/triggers/editor_event_handler.tpl
 * (`[data-cerb-event-tester]` with a nested placeholders <textarea> + a ▶ Run button + a results slot).
 * This initializes the placeholders KataEditor and binds Run to POST automation_event/tester, rendering the
 * matched handlers as clickable bubbles that jump to their line in the main editor.
 *
 * Replaces the legacy `$.fn.cerbCodeEditorToolbarEventHandler` for cerb-ui peeks: that helper located the
 * panel via `closest('fieldset')`, but a converted editor lives in a `cerb-ui-panel`, not a `<fieldset>`, so
 * the lookup no longer resolves. Here the caller passes the scope to search (typically `$popup`) explicitly.
 * The placeholders/tester show-hide toggles are driven by the editor toolbar's `onAction`, not this helper.
 *
 * @param {Element|jQuery} scope   container searched for `[data-cerb-event-tester]` (e.g. the popup)
 * @param {Object} mainEditor      the main CerbUI.KataEditor whose value is tested + line-jumped
 * @returns {Object|null}          the placeholders KataEditor, or null when no tester panel is present
 */
CerbUI.editorCore.attachEventHandlerTester = function(scope, mainEditor) {
	const $tester = ((scope instanceof jQuery) ? scope : $(scope)).find('[data-cerb-event-tester]');

	if(!$tester.length || !mainEditor)
		return null;

	const $textarea = $tester.find('textarea');
	const tester_editor = ($textarea.length && window.CerbUI && CerbUI.KataEditor)
		? new CerbUI.KataEditor($textarea[0])
		: null;

	$tester.find('.cerb-code-editor-toolbar-button--run').on('click', function() {
		const formData = new FormData();
		formData.set('c', 'profiles');
		formData.set('a', 'invoke');
		formData.set('module', 'automation_event');
		formData.set('action', 'tester');
		formData.set('automations_kata', mainEditor.getValue());
		formData.set('placeholders_kata', tester_editor ? tester_editor.getValue() : '');

		const $results = $tester.find('[data-cerb-event-tester-results]').empty();

		genericAjaxPost(formData, null, null, function(json) {
			if('object' !== typeof json)
				return;

			if(json.hasOwnProperty('error') && json.error) {
				$results.append($('<div/>').addClass('cerb-ui-panel cerb-ui-panel--alert').text(json.error));
				return;
			}

			if(!Array.isArray(json))
				return;

			const $container = $('<div/>').addClass('bubbles');

			json.forEach(function(handler) {
				$('<div/>')
					.addClass('bubble')
					.css({ 'font-weight': 'bold', 'margin': '0 5px 5px 0', 'cursor': 'pointer' })
					.attr('data-line', handler.hasOwnProperty('kata') && handler.kata.line ? handler.kata.line : null)
					.text(handler.id)
					.on('click', function() {
						const line = $(this).attr('data-line');

						if(!line)
							return;

						// Jump to the handler's definition in the main editor
						mainEditor.gotoLine(line, 0, true);
						mainEditor.focus();
					})
					.appendTo($container)
				;
			});

			const $close_button = $('<span/>')
				.addClass('cerb-icons cerb-icon-circle-remove')
				.css({ 'position': 'absolute', 'top': '0', 'right': '0', 'cursor': 'pointer', 'font-size': '16px' })
				.on('click', function() { $results.empty(); })
			;

			const $fieldset = $('<fieldset/>').addClass('black');
			$fieldset.append($('<legend/>').text('Results'));
			$fieldset.append($container);

			$results.append($fieldset);
			$results.append($close_button);
		});
	});

	return tester_editor;
};

/*
 * ── Find/Replace (Ctrl/Cmd+F) — one shared controller + a thin per-editor adapter ──────────────────────────
 *
 * editor-core loads before every editor in the bundle, so FindController is visible to all of them. The
 * controller owns everything language-agnostic: the floating panel DOM, the in-panel keyboard state machine,
 * substring match computation (regex/replace land in later phases — their UI is scaffolded but hidden here),
 * the decoration "bands" painted behind the mirror text, and prev/next navigation with wrap-around. Each editor
 * contributes a small ADAPTER (built once in its constructor) that isolates the only real difference between the
 * editors: folding (Kata/Json map model⇄view offsets) vs. 1:1 (Scripting/Markdown where getValue() === textarea
 * value). The adapter is built by makeFindAdapter() below from the methods every editor already exposes, so a
 * host editor's wiring is just: build the adapter, add a Mod-F shortcut, call repaintBands() in _renderHighlight,
 * and destroy() it.
 *
 * Bands are absolutely-positioned <div>s appended into the editor's scroll-synced overlay (--highlight) at
 * z-index:-1, exactly like KataEditor's --active-line / --line-deco — so they track scroll for free and read
 * BEHIND the colored mirror text. Every _renderHighlight() wipes the mirror, so the editor re-invokes
 * repaintBands() right after (the same spot the active-line band is re-added).
 */

// Measure the pixel width of `text` in the textarea's font via a cached <canvas> 2d context — O(text length) but
// NO DOM layout, so it's cheap even on huge documents (unlike caretCoords, which slices value.slice(0,offset) and
// lays out the whole prefix). Used for horizontal band geometry in no-wrap code editors (monospace, letter-spacing
// normal, tabs sanitized to spaces — so canvas measureText matches the rendered glyph advance).
CerbUI.editorCore._measureWidth = function(cs, text) {
	if(!text) return 0;
	let ctx = CerbUI.editorCore._measureCtx;
	if(!ctx) ctx = CerbUI.editorCore._measureCtx = document.createElement('canvas').getContext('2d');
	const font = cs.fontStyle + ' ' + cs.fontWeight + ' ' + cs.fontSize + ' ' + cs.fontFamily;
	if(CerbUI.editorCore._measureFont !== font) { ctx.font = font; CerbUI.editorCore._measureFont = font; }
	return ctx.measureText(text).width;
};

// Per-visual-line content rects for a MODEL offset range, in the overlay's coordinate space (children of the
// scroll-synced --highlight). Split the range at MODEL newlines; skip folded (mapRow < 0) and OFF-SCREEN segments
// (the overlay clips them anyway — and skipping avoids the expensive measure). For each visible segment in a NO-WRAP
// editor (white-space:pre — Kata/Json/Scripting), geometry is cheap: top = padTop + viewRow*lh, left/width from a
// canvas measure of the line's prefix (short string, no layout). Wrapping editors (Markdown, pre-wrap) fall back to
// caretCoords so wrapped rows stay correct (those docs are small).
//   modelLines() -> the full document's lines (a visible folded-editor model line == its view line, folds drop whole lines)
//   mapRow(modelRow) -> viewRow (-1 = hidden in a fold)   mapOffset(modelOffset) -> view offset (for the wrap fallback)
CerbUI.editorCore.computeFindRects = function(textarea, overlay, mStart, mEnd, modelLines, mapRow, mapOffset) {
	const lines = modelLines();
	const cs = window.getComputedStyle(textarea);
	const lh = parseFloat(cs.lineHeight) || (parseFloat(cs.fontSize) * 1.5);
	const padTop = parseFloat(cs.paddingTop) || 0;
	const padLeft = parseFloat(cs.paddingLeft) || 0;
	const noWrap = (cs.whiteSpace === 'pre');
	// Visible view-row band (+small margin) — segments outside it are clipped by the overlay, so don't measure them.
	const firstVis = Math.floor(textarea.scrollTop / lh) - 2;
	const lastVis = Math.ceil((textarea.scrollTop + textarea.clientHeight) / lh) + 2;
	const rects = [];
	let acc = 0;
	for(let r = 0; r < lines.length; r++) {
		const lineStart = acc, lineEnd = acc + lines[r].length; // offsets exclude the trailing newline
		acc = lineEnd + 1;
		if(lineEnd < mStart) continue;        // line entirely before the range
		if(lineStart > mEnd) break;           // line entirely after — done
		const vr = mapRow(r);
		if(vr < 0) continue;                  // folded away (no band, but the match stays navigable)
		if(vr < firstVis || vr > lastVis) continue;   // off-screen — clipped anyway, skip the measure
		const segStart = Math.max(mStart, lineStart);
		const segEnd = Math.min(mEnd, lineEnd);
		if(noWrap) {
			const pre = lines[r].slice(0, segStart - lineStart);
			const left = padLeft + CerbUI.editorCore._measureWidth(cs, pre);
			const width = Math.max(2, CerbUI.editorCore._measureWidth(cs, lines[r].slice(segStart - lineStart, segEnd - lineStart)));
			rects.push({ left: left, top: padTop + vr * lh, width: width, height: lh });
		} else {
			const cS = CerbUI.editorCore.caretCoords(textarea, mapOffset(segStart));
			const cE = CerbUI.editorCore.caretCoords(textarea, mapOffset(segEnd));
			const width = (cE.top === cS.top) ? Math.max(2, cE.left - cS.left) : Math.max(2, overlay.clientWidth - cS.left);
			rects.push({ left: cS.left, top: cS.top, width: width, height: cS.height });
		}
	}
	return rects;
};

// Build a FindController adapter from the methods an editor already exposes. `mode` is 'folding' (Kata/Json:
// getValue() is the full model, _modelRowToViewRow/_modelOffsetToViewOffset bridge to the projection) or
// 'linear' (Scripting/Markdown: the textarea IS the value, offsets map 1:1).
CerbUI.editorCore.makeFindAdapter = function(ed, mode) {
	const folding = (mode === 'folding');
	const modelLines = folding ? () => ed._modelLines() : () => ed.getValue().split('\n');
	const mapRow = folding ? (r) => ed._modelRowToViewRow(r) : () => 0;
	const mapOffset = folding ? (o) => ed._modelOffsetToViewOffset(o) : (o) => o;
	const rowOfOffset = (o) => {
		const L = modelLines();
		let acc = 0;
		for(let r = 0; r < L.length; r++) { if(o <= acc + L[r].length) return r; acc += L[r].length + 1; }
		return Math.max(0, L.length - 1);
	};
	return {
		hostEl: () => ed.el,
		fieldEl: () => ed.field,
		overlayEl: () => ed.highlight,
		textareaEl: () => ed.textarea,
		getText: () => ed.getValue(),
		readOnly: () => !!(ed.opts && ed.opts.readOnly),
		// Reveal any fold hiding the match, select the MODEL range, and scroll it into view. We do NOT focus the
		// textarea — focus stays in the find input so Enter/Shift-Enter keep cycling matches.
		revealRange: (mStart, mEnd) => {
			if(folding) {
				const row = rowOfOffset(mStart);
				ed._revealModelRow(row);
				ed.textarea.setSelectionRange(ed._modelOffsetToViewOffset(mStart), ed._modelOffsetToViewOffset(mEnd));
				ed.scrollToLine(row);
			} else {
				const ta = ed.textarea;
				ta.setSelectionRange(mStart, mEnd);
				const c = CerbUI.editorCore.caretCoords(ta, mStart);
				if(c.top < ta.scrollTop) ta.scrollTop = Math.max(0, c.top - c.height);
				else if(c.top + c.height > ta.scrollTop + ta.clientHeight) ta.scrollTop = c.top + c.height - ta.clientHeight + c.height;
				if(typeof ed._syncScroll === 'function') ed._syncScroll();
			}
		},
		rectsForRange: (mStart, mEnd) => CerbUI.editorCore.computeFindRects(ed.textarea, ed.highlight, mStart, mEnd, modelLines, mapRow, mapOffset),

		// The MODEL-offset span currently in (or near) the viewport — so the painter can skip matches that aren't
		// visible BEFORE calling rectsForRange (the overlay clips them anyway). One O(model) pass per paint.
		visibleModelRange: () => {
			const ta = ed.textarea, cs = window.getComputedStyle(ta);
			const lh = parseFloat(cs.lineHeight) || (parseFloat(cs.fontSize) * 1.5);
			if(!(lh > 0)) return null;
			const lines = modelLines();
			const firstV = Math.max(0, Math.floor(ta.scrollTop / lh) - 8);
			const lastV = Math.ceil((ta.scrollTop + ta.clientHeight) / lh) + 8;
			const startRow = folding ? ed._viewRowToModelRow(firstV) : Math.min(firstV, lines.length - 1);
			const endRow = folding ? ed._viewRowToModelRow(lastV) : Math.min(lastV, lines.length - 1);
			let acc = 0, startOff = 0, endOff = 0;
			for(let r = 0; r < lines.length; r++) {
				if(r === startRow) startOff = acc;
				if(r === endRow) { endOff = acc + lines[r].length; break; }
				acc += lines[r].length + 1;
			}
			return [startOff, endOff];
		},

		// Replace a single MODEL range — always through the editor's undo-safe (execCommand-backed) edit path so
		// native Cmd/Ctrl+Z still works and the model/projection/change-tracking stay consistent.
		replaceRange: (mStart, mEnd, text) => {
			if(folding) {
				// Reveal first so the span is in the projection, edit the projection (mapped), and let the editor
				// fold the change back into the model (_setValueAndCaret -> _applyProjectionEditToModel).
				ed._revealModelRow(rowOfOffset(mStart));
				const proj = ed.textarea.value;
				const vS = ed._modelOffsetToViewOffset(mStart), vE = ed._modelOffsetToViewOffset(mEnd);
				ed._setValueAndCaret(proj.slice(0, vS) + text + proj.slice(vE), vS + text.length);
			} else if(typeof ed._setValueAndCaret === 'function') {
				const v = ed.getValue();
				ed._setValueAndCaret(v.slice(0, mStart) + text + v.slice(mEnd), mStart + text.length);
			} else if(typeof ed._replaceRange === 'function') {
				ed._replaceRange(mStart, mEnd, text);   // MarkdownEditor's execCommand primitive
			}
		},

		// Apply the whole-document replacement as ONE edit so Replace-All is a single undo entry. Folding editors
		// unfold first so the projection equals the model and the single span covers everything.
		replaceAll: (newText) => {
			if(folding) {
				ed.unfoldAll();
				ed._setValueAndCaret(newText, Math.min(ed.textarea.selectionStart, newText.length));
			} else if(typeof ed._setValueAndCaret === 'function') {
				ed._setValueAndCaret(newText, Math.min(ed.textarea.selectionStart, newText.length));
			} else if(typeof ed._replaceRange === 'function') {
				ed._replaceRange(0, ed.getValue().length, newText);   // MarkdownEditor — whole-doc swap, one undo
			}
		},
	};
};

// ── Shared Mod-F interception (one document-level capture handler for every editor) ────────────────────────
// Every FindController registers here; the handler routes Cmd/Ctrl+F to the editor that owns focus (or, when
// focus drifted to surrounding popup/dialog chrome, the most recently focused editor still in the DOM). Bound
// once, in capture phase, so it runs before the browser's native find and regardless of DOM re-parenting.
CerbUI.editorCore._findControllers = CerbUI.editorCore._findControllers || new Set();
CerbUI.editorCore._findLastFocused = CerbUI.editorCore._findLastFocused || null;
CerbUI.editorCore._findRegister = function(fc) {
	CerbUI.editorCore._findControllers.add(fc);
	if(CerbUI.editorCore._findDocBound) return;
	document.addEventListener('keydown', CerbUI.editorCore._findOnDocKeydown, true);
	CerbUI.editorCore._findDocBound = true;
};
CerbUI.editorCore._findOnDocKeydown = function(e) {
	if(e.code !== 'KeyF' || !(e.metaKey || e.ctrlKey) || e.altKey || e.shiftKey) return;
	const ae = document.activeElement;
	const controllers = CerbUI.editorCore._findControllers;
	// Already typing in a find panel? leave Mod-F to the panel's own keymap (re-focus + select-all).
	for(const fc of controllers) { if(fc.panel && fc.panel.contains(ae)) return; }
	// Prefer the editor that owns focus; else fall back to the most recently focused editor still connected.
	let target = null;
	for(const fc of controllers) { if(fc._host && fc._host.contains(ae)) { target = fc; break; } }
	if(!target) {
		const last = CerbUI.editorCore._findLastFocused;
		if(last && controllers.has(last) && last._host && last._host.isConnected) target = last;
	}
	if(!target) return;   // Mod-F with no editor focused — let the browser have it
	e.preventDefault();
	e.stopPropagation();
	if(target.editor._ac && typeof target.editor._ac.close === 'function') target.editor._ac.close();
	target.open();
};

CerbUI.editorCore.FindController = class {
	constructor(editor, adapter) {
		this.editor = editor;
		this.adapter = adapter;
		this.panel = null;
		this.matches = [];        // [{start,end}] in MODEL offsets
		this.currentIndex = -1;
		this.query = '';
		this.caseSensitive = false;
		this.useRegex = false;
		this._regexError = null;  // last invalid-regex message (phase 2), or null
		this._debounce = null;
		this._savedSel = null;    // editor caret/selection at open, restored on Esc

		// Open find on Mod-F via a single DOCUMENT-level capture handler (see _findEnsureDocHandler below) rather
		// than a listener on each editor root. A per-host listener is fragile in some embeddings: the workflow
		// record popup is a jQuery-UI dialog built by genericAjaxPopup, which re-parents content, so the host
		// listener never saw the keydown and the browser's native find won. The document handler is immune to node
		// moves and to which inner element holds focus; it routes Mod-F to whichever editor owns (or most recently
		// held) focus. We track the last-focused editor via a focusin listener on the host for the fallback path.
		this._host = (typeof adapter.hostEl === 'function') ? adapter.hostEl() : null;
		CerbUI.editorCore._findRegister(this);
		if(this._host) {
			this._onHostFocusIn = () => { CerbUI.editorCore._findLastFocused = this; };
			this._host.addEventListener('focusin', this._onHostFocusIn);
		}
	}

	// Phase 1: paint at most this many match bands; above it we paint only the current match (the count + the
	// navigable matches array stay exact — only the decoration is capped, for cost).
	static BAND_CAP = 500;

	isOpen() { return !!(this.panel && !this.panel.hidden); }

	// Find works even in readOnly editors (the replace row stays hidden — phase 3).
	open() {
		if(!this.panel) this._build();
		// Close any open autocomplete so its menu doesn't fight the panel.
		if(this.editor._ac && typeof this.editor._ac.close === 'function') this.editor._ac.close();

		const ta = this.adapter.textareaEl();
		this._savedSel = { start: ta.selectionStart, end: ta.selectionEnd };

		this.panel.hidden = false;
		// Seed from a single-line selection (Ace parity); otherwise keep the previous query.
		const sel = (typeof this.editor.getSelectedText === 'function') ? this.editor.getSelectedText() : '';
		if(sel && sel.indexOf('\n') === -1) this.input.value = sel;
		this.input.focus();
		this.input.select();
		this._recompute(true);
	}

	close() {
		if(!this.panel || this.panel.hidden) return;
		this.panel.hidden = true;
		this.matches = [];
		this.currentIndex = -1;
		this._clearBands();
		const ta = this.adapter.textareaEl();
		ta.focus();
		if(this._savedSel) ta.setSelectionRange(this._savedSel.start, this._savedSel.end);
	}

	destroy() {
		if(this._debounce !== null) { clearTimeout(this._debounce); this._debounce = null; }
		CerbUI.editorCore._findControllers.delete(this);
		if(CerbUI.editorCore._findLastFocused === this) CerbUI.editorCore._findLastFocused = null;
		if(this._host && this._onHostFocusIn) this._host.removeEventListener('focusin', this._onHostFocusIn);
		this._clearBands();
		if(this.panel && this.panel.parentNode) this.panel.parentNode.removeChild(this.panel);
		this.panel = null;
	}

	// Called by the host editor's _renderHighlight() AFTER it rebuilds the mirror (which wipes our bands).
	repaintBands() { if(this.isOpen()) this._paintBands(); }

	// ── Panel ───────────────────────────────────────────────────────────
	_build() {
		const panel = document.createElement('div');
		panel.className = 'cerb-ui-editor-find';
		panel.hidden = true;
		panel.setAttribute('role', 'search');
		panel.innerHTML =
			'<div class="cerb-ui-editor-find--row">'
			+ '<button type="button" class="cerb-ui-editor-find--toggle cerb-ui-editor-find--case" title="Match case" aria-pressed="false">Aa</button>'
			+ '<button type="button" class="cerb-ui-editor-find--toggle cerb-ui-editor-find--regex" title="Regular expression" aria-pressed="false">.*</button>'
			+ '<input type="text" class="cerb-ui-editor-find--input" placeholder="Find" spellcheck="false" autocomplete="off" autocorrect="off" autocapitalize="off">'
			+ '<span class="cerb-ui-editor-find--count">0 / 0</span>'
			+ '<button type="button" class="cerb-ui-editor-find--btn cerb-ui-editor-find--prev" title="Previous (Shift+Enter)"><span class="cerb-icons cerb-icon-chevron-up"></span></button>'
			+ '<button type="button" class="cerb-ui-editor-find--btn cerb-ui-editor-find--next" title="Next (Enter)"><span class="cerb-icons cerb-icon-chevron-down"></span></button>'
			+ '<button type="button" class="cerb-ui-editor-find--btn cerb-ui-editor-find--close" title="Close (Esc)"><span class="cerb-icons cerb-icon-circle-remove"></span></button>'
			+ '</div>'
			// Replace row — phase 3 (scaffolded, hidden).
			+ '<div class="cerb-ui-editor-find--row cerb-ui-editor-find--replace-row" hidden>'
			+ '<input type="text" class="cerb-ui-editor-find--replace-input" placeholder="Replace" spellcheck="false" autocomplete="off">'
			+ '<button type="button" class="cerb-ui-editor-find--btn cerb-ui-editor-find--replace">Replace</button>'
			+ '<button type="button" class="cerb-ui-editor-find--btn cerb-ui-editor-find--replace-all">All</button>'
			+ '</div>';

		this.adapter.hostEl().appendChild(panel);
		this.panel = panel;
		this.input = panel.querySelector('.cerb-ui-editor-find--input');
		this.count = panel.querySelector('.cerb-ui-editor-find--count');
		const caseBtn = panel.querySelector('.cerb-ui-editor-find--case');

		this.input.addEventListener('input', () => {
			if(this._debounce !== null) clearTimeout(this._debounce);
			this._debounce = setTimeout(() => { this._debounce = null; this._recompute(true); }, 150);
		});
		this.input.addEventListener('keydown', (e) => this._onInputKeydown(e));
		caseBtn.addEventListener('click', () => {
			this.caseSensitive = !this.caseSensitive;
			caseBtn.setAttribute('aria-pressed', this.caseSensitive ? 'true' : 'false');
			caseBtn.classList.toggle('cerb-ui-editor-find--toggle-on', this.caseSensitive);
			this.input.focus();
			this._recompute(true);
		});
		const regexBtn = panel.querySelector('.cerb-ui-editor-find--regex');
		regexBtn.addEventListener('click', () => {
			this.useRegex = !this.useRegex;
			regexBtn.setAttribute('aria-pressed', this.useRegex ? 'true' : 'false');
			regexBtn.classList.toggle('cerb-ui-editor-find--toggle-on', this.useRegex);
			this.input.focus();
			this._recompute(true);
		});
		panel.querySelector('.cerb-ui-editor-find--prev').addEventListener('click', () => { this._navigate(-1); this.input.focus(); });
		panel.querySelector('.cerb-ui-editor-find--next').addEventListener('click', () => { this._navigate(1); this.input.focus(); });
		panel.querySelector('.cerb-ui-editor-find--close').addEventListener('click', () => this.close());

		// Replace row (phase 3) — shown only when the editor is editable (readOnly hides it).
		this.replaceInput = panel.querySelector('.cerb-ui-editor-find--replace-input');
		this._canReplace = !this.adapter.readOnly();
		if(this._canReplace) {
			panel.querySelector('.cerb-ui-editor-find--replace-row').hidden = false;
			this.replaceInput.addEventListener('keydown', (e) => this._onReplaceKeydown(e));
			panel.querySelector('.cerb-ui-editor-find--replace').addEventListener('click', () => this._replaceCurrent());
			panel.querySelector('.cerb-ui-editor-find--replace-all').addEventListener('click', () => this._replaceAll());
		}
	}

	_onInputKeydown(e) {
		if(e.key === 'Enter') {
			e.preventDefault();
			this._navigate(e.shiftKey ? -1 : 1);
			return;
		}
		if(e.key === 'Escape') { e.preventDefault(); this.close(); return; }
		// Tab into the replace field (when shown) — the find↔replace toggle.
		if(e.key === 'Tab' && !e.shiftKey && this._canReplace && this.replaceInput) {
			e.preventDefault();
			this.replaceInput.focus();
			return;
		}
		// Mod-F while the panel already has focus re-selects the query (Ace behavior) instead of bubbling.
		if(e.code === 'KeyF' && (e.metaKey || e.ctrlKey) && !e.altKey && !e.shiftKey) {
			e.preventDefault();
			this.input.select();
			return;
		}
	}

	_onReplaceKeydown(e) {
		if(e.key === 'Enter') { e.preventDefault(); this._replaceCurrent(); return; }
		if(e.key === 'Escape') { e.preventDefault(); this.close(); return; }
		if(e.key === 'Tab' && e.shiftKey) { e.preventDefault(); this.input.focus(); return; }
		if(e.code === 'KeyF' && (e.metaKey || e.ctrlKey) && !e.altKey && !e.shiftKey) {
			e.preventDefault();
			this.input.focus();
			this.input.select();
			return;
		}
	}

	// ── Matching ────────────────────────────────────────────────────────
	// `keepIndex` true picks the match nearest the saved caret (open / retype); false keeps stepping from where we
	// are. Phase 1 is a plain case-folded substring scan; phase 2 swaps in a RegExp matcher here.
	_recompute(keepIndex) {
		this.query = this.input.value;
		this.matches = this._computeMatches(this.query);
		if(this.matches.length) {
			if(keepIndex) {
				const caret = this._savedSel ? this._savedSel.start : 0;
				const idx = this.matches.findIndex(m => m.start >= caret);
				this.currentIndex = (idx >= 0) ? idx : 0;
			} else if(this.currentIndex < 0 || this.currentIndex >= this.matches.length) {
				this.currentIndex = 0;
			}
		} else {
			this.currentIndex = -1;
		}
		this._paintBands();
		this._updateCounter();
		// Reveal the current match ONLY when it's off-screen — otherwise typing would scroll + repaint the editor on
		// every keystroke (the "re-styling while searching" churn on large docs). Explicit next/prev always reveals.
		if(this.currentIndex >= 0) {
			const m = this.matches[this.currentIndex];
			const vis = (typeof this.adapter.visibleModelRange === 'function') ? this.adapter.visibleModelRange() : null;
			if(!vis || m.start > vis[1] || m.end < vis[0]) this.adapter.revealRange(m.start, m.end);
		}
	}

	// Phase 1 cap on total matches — keeps a pathological pattern (e.g. `.` on a huge doc) from freezing the loop.
	// The counter/navigation stay exact up to this many; band painting is separately capped by BAND_CAP.
	static MATCH_CAP = 10000;

	_computeMatches(q) {
		this._regexError = null;
		if(!q) return [];
		return this.useRegex ? this._computeRegexMatches(q) : this._computeSubstringMatches(q);
	}

	_computeSubstringMatches(q) {
		const text = this.adapter.getText();
		// Cache the lowercased doc so retyping doesn't re-lowercase the whole (possibly huge) document each keystroke.
		let hay;
		if(this.caseSensitive) hay = text;
		else if(this._lcKey === text) hay = this._lcText;
		else { hay = text.toLowerCase(); this._lcText = hay; this._lcKey = text; }
		const needle = this.caseSensitive ? q : q.toLowerCase();
		const out = [];
		let i = 0;
		const step = Math.max(1, needle.length);
		const cap = CerbUI.editorCore.FindController.MATCH_CAP;
		while((i = hay.indexOf(needle, i)) !== -1) {
			out.push({ start: i, end: i + q.length });   // groups[0] (whole match) is text.slice(start,end)
			i += step;
			if(out.length >= cap) break;
		}
		return out;
	}

	// Capture groups are kept on each match (`groups` = the RegExpExecArray) for phase-3 `$1`/`$&` expansion. A
	// zero-width match (e.g. `a*`, a lookahead) advances lastIndex by one so the scan can't spin forever; such
	// matches stay counted + navigable but paint no band (see _paintBands).
	_computeRegexMatches(q) {
		let re;
		try {
			re = new RegExp(q, 'g' + (this.caseSensitive ? '' : 'i'));
		} catch(err) {
			this._regexError = (err && err.message) ? err.message : 'Invalid regular expression';
			return [];
		}
		const text = this.adapter.getText();
		const out = [];
		const cap = CerbUI.editorCore.FindController.MATCH_CAP;
		let m;
		while((m = re.exec(text)) !== null) {
			out.push({ start: m.index, end: m.index + m[0].length, groups: m });
			if(re.lastIndex === m.index) re.lastIndex++;   // zero-width — don't loop on the same position
			if(out.length >= cap) break;
		}
		return out;
	}

	_navigate(dir) {
		if(!this.matches.length) return;
		const n = this.matches.length;
		this.currentIndex = (this.currentIndex + dir + n) % n;   // wrap-around
		const m = this.matches[this.currentIndex];
		this.adapter.revealRange(m.start, m.end);
		this._paintBands();
		this._updateCounter();
	}

	// ── Replace (phase 3) ───────────────────────────────────────────────
	// The replacement text for one match. Literal in substring mode; in regex mode `$&` (whole match), `$1`..`$99`
	// (capture groups), and `$$` (a literal `$`) expand from the match's stored RegExpExecArray.
	_expandReplacement(m) {
		const rep = this.replaceInput ? this.replaceInput.value : '';
		if(!this.useRegex) return rep;
		const g = m.groups || [];
		return rep.replace(/\$(\$|&|\d{1,2})/g, (whole, k) => {
			if(k === '$') return '$';
			if(k === '&') return (g[0] != null) ? g[0] : '';
			const n = parseInt(k, 10);
			return (g[n] != null) ? g[n] : '';
		});
	}

	// Replace the current match, then recompute and advance to the next match after the replacement.
	_replaceCurrent() {
		if(!this._canReplace || this.currentIndex < 0 || !this.matches.length) return;
		const m = this.matches[this.currentIndex];
		const rep = this._expandReplacement(m);
		this.matches = [];                 // drop stale offsets before the edit re-renders (repaintBands no-ops)
		this.adapter.replaceRange(m.start, m.end, rep);
		this._afterEdit(m.start + rep.length);
		if(this.replaceInput) this.replaceInput.focus();
	}

	// Replace every match in ONE edit (single undo). Build the new full document by splicing each match's
	// replacement in document order, then hand the whole text to the adapter.
	_replaceAll() {
		if(!this._canReplace || !this.matches.length) return;
		const text = this.adapter.getText();
		let out = '', last = 0;
		for(const m of this.matches) {
			if(m.start < last) continue;   // skip any overlap (defensive — matches are non-overlapping by construction)
			out += text.slice(last, m.start) + this._expandReplacement(m);
			last = m.end;
		}
		out += text.slice(last);
		this.matches = [];
		this.adapter.replaceAll(out);
		this._afterEdit(0);
		if(this.replaceInput) this.replaceInput.focus();
	}

	// After any replace the document changed: re-scan, place the current match at/after `caretPos` (wrapping to the
	// first), reveal it, and repaint. Called once the editor's own edit-driven re-render has settled.
	_afterEdit(caretPos) {
		this.matches = this._computeMatches(this.query);
		if(this.matches.length) {
			const idx = this.matches.findIndex(mm => mm.start >= caretPos);
			this.currentIndex = (idx >= 0) ? idx : 0;
			this.adapter.revealRange(this.matches[this.currentIndex].start, this.matches[this.currentIndex].end);
		} else {
			this.currentIndex = -1;
		}
		this._paintBands();
		this._updateCounter();
	}

	// ── Bands + counter ─────────────────────────────────────────────────
	_clearBands() {
		const ov = this.adapter.overlayEl();
		if(ov) ov.querySelectorAll('.cerb-ui-editor-find--match, .cerb-ui-editor-find--current').forEach(n => n.remove());
	}

	_paintBands() {
		const ov = this.adapter.overlayEl();
		if(!ov) return;
		this._clearBands();
		if(!this.matches.length) return;
		const paint = (m, current) => {
			if(m.end <= m.start) return;   // zero-width regex match — counted + navigable, but nothing to paint
			const rects = this.adapter.rectsForRange(m.start, m.end);
			for(const r of rects) {
				const band = document.createElement('div');
				band.className = current ? 'cerb-ui-editor-find--current' : 'cerb-ui-editor-find--match';
				band.style.left = r.left + 'px';
				band.style.top = r.top + 'px';
				band.style.width = r.width + 'px';
				band.style.height = r.height + 'px';
				ov.appendChild(band);
			}
		};
		// Only paint matches in (or near) the viewport — off-screen bands are clipped by the overlay anyway, and
		// skipping them here avoids a rectsForRange (whole-doc split + measure) call per off-screen match. On a huge
		// doc this is the difference between painting ~tens of bands and ~hundreds. `null` (adapter w/o the hook) =
		// paint all (still capped below).
		const vis = (typeof this.adapter.visibleModelRange === 'function') ? this.adapter.visibleModelRange() : null;
		const inView = (m) => !vis || (m.end >= vis[0] && m.start <= vis[1]);
		if(this.matches.length <= CerbUI.editorCore.FindController.BAND_CAP)
			this.matches.forEach((m, i) => { if(i !== this.currentIndex && inView(m)) paint(m, false); });
		// Current match appended LAST so it reads over the other bands (revealRange already scrolled it into view).
		if(this.currentIndex >= 0) paint(this.matches[this.currentIndex], true);
	}

	_updateCounter() {
		const n = this.matches.length;
		this.count.textContent = this._regexError ? '!' : ((n ? (this.currentIndex + 1) : 0) + ' / ' + n);
		this.panel.classList.toggle('cerb-ui-editor-find--nomatch', !!this.query && (n === 0 || !!this._regexError));
		// Surface an invalid pattern as a hover tooltip on the query field (red state already shows via --nomatch).
		this.input.title = this._regexError || '';
	}
};

/*
 * CerbUI.editorCore.lineDiff — a pure, DOM-free line-level diff shared by CerbUI.DiffViewer (the change-history
 * viewer) and the editors' gutter-diff feature (KataEditor marks its changes against a save checkpoint).
 *
 * It lives HERE, not on DiffViewer, because of load order: editor-core.js loads BEFORE kataeditor.js which
 * loads before diffviewer.js. An editor needs the diff at construction time, so it can't reach up to
 * DiffViewer. DiffViewer's own `_normalize`/`_diffLines`/`_blocks` statics now delegate down to this module.
 *
 * The engine is a line-level Myers O(ND) shortest-edit-script: intern lines to ints, trim the common
 * prefix/suffix, run Myers on the changed middle, with a memory-budgeted D-cap that degrades to
 * replace-the-middle for a pathologically dissimilar pair. Documents are small (KATA), so it's cheap.
 */
CerbUI.editorCore.lineDiff = {
	// A run must hide at least this many lines to be worth eliding (used by DiffViewer's collapse); kept here so
	// the two components share the constant. Not referenced by hunks().
	MIN_ELIDE: 2,

	// Coerce to string and fold CRLF/CR to LF. Without this a `\r\n`-stored baseline vs a `\n` live value
	// mismatches EVERY line.
	normalize(text) { return (text == null ? '' : String(text)).replace(/\r\n?/g, '\n'); },

	// Align two documents by lines. Returns a flat op list in document order:
	//   { type:'eq'|'del'|'add', left, right }  where left/right are the 0-based row each op sits at.
	// A 'del' consumes a LEFT row (right holds at the insertion point); an 'add' consumes a RIGHT row. So within a
	// contiguous run of non-'eq' ops the consumed left rows are contiguous from the first op's `left`, and the
	// consumed right rows contiguous from its `right` — which blocks() relies on.
	diffLines(aText, bText) {
		const a = String(aText).split('\n'), b = String(bText).split('\n');

		// Intern lines to integer ids so the inner Myers loop compares ints, not strings.
		const ids = new Map();
		const idOf = (s) => { let id = ids.get(s); if(id === undefined) { id = ids.size; ids.set(s, id); } return id; };
		const A = a.map(idOf), B = b.map(idOf);

		const types = this._diffTypes(A, B);   // ordered 0=eq / 1=del / 2=add

		// Walk the type sequence, assigning the running left/right row each op sits at.
		const out = [];
		let i = 0, j = 0;
		for(const t of types) {
			if(t === 0) { out.push({ type: 'eq', left: i, right: j }); i++; j++; }
			else if(t === 1) { out.push({ type: 'del', left: i, right: j }); i++; }
			else { out.push({ type: 'add', left: i, right: j }); j++; }
		}
		return out;
	},

	// Ordered edit types for two integer sequences. Trims the common prefix + suffix (so near-identical documents
	// reduce to a tiny middle), then runs Myers on the middle. Versions of the same doc differ in a handful of
	// lines, so the work + memory stay small even at ~10K lines.
	_diffTypes(A, B) {
		const N = A.length, M = B.length;
		const head = [];
		let lo = 0;
		while(lo < N && lo < M && A[lo] === B[lo]) { head.push(0); lo++; }
		const tail = [];
		let hiA = N, hiB = M;
		while(hiA > lo && hiB > lo && A[hiA - 1] === B[hiB - 1]) { tail.push(0); hiA--; hiB--; }

		const mid = this._myers(A.subarray ? A.subarray(lo, hiA) : A.slice(lo, hiA),
		                        B.subarray ? B.subarray(lo, hiB) : B.slice(lo, hiB));
		return head.concat(mid, tail);   // tail is all-eq, so order within it is irrelevant
	},

	// Classic Myers shortest-edit-script over two integer arrays -> ordered types (0=eq,1=del,2=add). O(ND) time;
	// the V snapshots are O(D·(N+M)) memory, tiny when D (edit distance) is small — the common case for consecutive
	// document versions. A memory-budgeted cap on D falls back to "replace the middle" for the rare wildly-different
	// pair (where a precise diff isn't useful anyway).
	_myers(A, B) {
		const N = A.length, M = B.length;
		if(N === 0) { const o = new Array(M); for(let j = 0; j < M; j++) o[j] = 2; return o; }
		if(M === 0) { const o = new Array(N); for(let i = 0; i < N; i++) o[i] = 1; return o; }

		const MAX = N + M;
		const offset = MAX;
		const size = 2 * MAX + 1;
		// Cap D so the trace can't blow past ~200MB (size ints per snapshot, D+1 snapshots).
		const dCap = Math.max(1, Math.min(MAX, Math.floor(50000000 / size)));

		const v = new Int32Array(size);
		const trace = [];
		let foundD = -1;

		for(let d = 0; d <= dCap; d++) {
			trace.push(Int32Array.from(v));
			for(let k = -d; k <= d; k += 2) {
				let x;
				if(k === -d || (k !== d && v[offset + k - 1] < v[offset + k + 1])) x = v[offset + k + 1];   // down (insert)
				else x = v[offset + k - 1] + 1;                                                             // right (delete)
				let y = x - k;
				while(x < N && y < M && A[x] === B[y]) { x++; y++; }
				v[offset + k] = x;
				if(x >= N && y >= M) { foundD = d; break; }
			}
			if(foundD >= 0) break;
		}

		if(foundD < 0) {   // exceeded the cap — degrade to replace-the-middle
			const o = []; for(let i = 0; i < N; i++) o.push(1); for(let j = 0; j < M; j++) o.push(2); return o;
		}

		// Backtrack through the snapshots to recover the ordered edit (built in reverse).
		const rev = [];
		let x = N, y = M;
		for(let d = foundD; d > 0; d--) {
			const vd = trace[d];
			const k = x - y;
			let prevK;
			if(k === -d || (k !== d && vd[offset + k - 1] < vd[offset + k + 1])) prevK = k + 1;
			else prevK = k - 1;
			const prevX = vd[offset + prevK];
			const prevY = prevX - prevK;
			while(x > prevX && y > prevY) { rev.push(0); x--; y--; }   // diagonal (equal lines)
			if(x === prevX) { rev.push(2); y--; }                      // down move -> an added (right) line
			else { rev.push(1); x--; }                                // right move -> a deleted (left) line
		}
		while(x > 0 && y > 0) { rev.push(0); x--; y--; }              // d=0 leading diagonal
		while(x > 0) { rev.push(1); x--; }
		while(y > 0) { rev.push(2); y--; }
		rev.reverse();
		return rev;
	},

	// Collapse the op list into change blocks: each maximal run of non-'eq' ops -> a left line-span [start,end) and
	// a right line-span [start,end). dels in the run count toward the left span, adds toward the right.
	blocks(aligned) {
		const blocks = [];
		let cur = null;
		for(const op of aligned) {
			if(op.type === 'eq') { if(cur) { blocks.push(cur); cur = null; } continue; }
			if(!cur) cur = { leftStartLine: op.left, rightStartLine: op.right, dels: 0, adds: 0 };
			if(op.type === 'del') cur.dels++; else cur.adds++;
		}
		if(cur) blocks.push(cur);
		return blocks.map(b => ({
			leftStartLine: b.leftStartLine,
			leftEndLine: b.leftStartLine + b.dels,
			rightStartLine: b.rightStartLine,
			rightEndLine: b.rightStartLine + b.adds,
		}));
	},

	// Classify baseline→current changes for a gutter (and the agent-readable diff). Returns:
	//   { rows:        Map<currentRow, 'added'|'modified'>   — rows present now, colored in the gutter
	//     deletions:   Set<currentRow>                       — a deletion sits ABOVE this current row (boundary wedge)
	//     deletedAtEnd: bool                                 — a deletion past the last current row
	//     hunks: [ { status:'added'|'modified'|'deleted',
	//                rowStart, rowEnd,                        — current-doc rows [start,end); empty for a pure deletion
	//                baseStart, baseEnd,                      — baseline rows [start,end); empty for a pure addition
	//                added:[…lines], removed:[…lines] } ] }  — the actual line text, for the panel + agents
	// `baseEnd`/`rowEnd` are EXCLUSIVE. A pure addition has an empty baseline span; a pure deletion an empty current
	// span (and lands as a boundary in `deletions`/`deletedAtEnd` rather than coloring a current row).
	hunks(baselineText, currentText) {
		const base = this.normalize(baselineText), cur = this.normalize(currentText);
		const rows = new Map(), deletions = new Set(), hunks = [];
		let deletedAtEnd = false;
		if(base === cur) return { rows, deletions, deletedAtEnd, hunks };

		const baseLines = base.split('\n'), curLines = cur.split('\n');
		const curCount = curLines.length;
		const blocks = this.blocks(this.diffLines(base, cur));

		for(const b of blocks) {
			const dels = b.leftEndLine - b.leftStartLine;
			const adds = b.rightEndLine - b.rightStartLine;
			const status = (dels > 0 && adds > 0) ? 'modified' : (adds > 0 ? 'added' : 'deleted');

			if(adds > 0) {
				for(let r = b.rightStartLine; r < b.rightEndLine; r++) rows.set(r, status);
			} else {
				// Pure deletion — no current row to color. Mark the boundary: above the current row the removed
				// lines used to precede, or past the end when they were the document's tail.
				if(b.rightStartLine < curCount) deletions.add(b.rightStartLine);
				else deletedAtEnd = true;
			}

			hunks.push({
				status: status,
				rowStart: b.rightStartLine,
				rowEnd: b.rightEndLine,
				baseStart: b.leftStartLine,
				baseEnd: b.leftEndLine,
				added: curLines.slice(b.rightStartLine, b.rightEndLine),
				removed: baseLines.slice(b.leftStartLine, b.leftEndLine),
			});
		}
		return { rows, deletions, deletedAtEnd, hunks };
	},
};
