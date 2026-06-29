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
			panel.addEventListener('mouseover', () => { this._pointerInMenu = true; });
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
