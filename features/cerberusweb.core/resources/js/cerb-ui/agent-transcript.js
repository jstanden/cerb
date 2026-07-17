/*
 * CerbUI.AgentTranscript — a read-only transcript of an LLM agent session, rendered as a conversation.
 *
 * The read-side counterpart to CerbUI.AgentPrompt (which composes a turn). Turns read like ticket messages:
 * a large left avatar for identity, a sender + meta header, an optional always-visible aside (token chips),
 * a hover-revealed action toolbar, and an indented sub-thread of thinking / tool-call bubbles.
 *
 * The caller authors bare, semantic markup; the component supplies chrome:
 *
 *   <div class="cerb-ui-agent-transcript" data-cerb-agent-transcript>
 *     <div data-cerb-transcript-turn data-role="assistant" data-seq="43">
 *       <span data-cerb-transcript-avatar data-avatar-icon="bot" data-avatar-seed="agent:…"></span>
 *       <div data-cerb-transcript-sender>Agent <span class="cerb-ui-pill">anthropic</span></div>
 *       <div data-cerb-transcript-meta>Date: …</div>
 *       <div data-cerb-transcript-aside><div class="cerb-ui-chip">…</div></div>
 *       <ul class="cerb-ui-toolbar" data-cerb-transcript-turn-toolbar>
 *         <li data-value="fork-from-here" data-icon="hierarchy" title="Fork from here"></li>
 *       </ul>
 *       <div data-cerb-transcript-body><div class="commentBodyHtml">…</div></div>
 *       <pre data-cerb-transcript-source>raw **markdown**</pre>
 *       <div data-cerb-transcript-images><img src="…"></div>
 *       <div data-cerb-transcript-thinking><div class="commentBodyHtml">…</div></div>
 *       <div data-cerb-transcript-tool data-tool-name="search_docs" data-tool-id="toolu_01">
 *         <textarea data-cerb-transcript-tool-params>{"query":"…"}</textarea>
 *         <pre data-cerb-transcript-tool-result>…</pre>
 *       </div>
 *     </div>
 *   </div>
 *
 *   CerbUI.AgentTranscript.enhance(scope);                       // every [data-cerb-agent-transcript] within
 *   new CerbUI.AgentTranscript(el, { onTurnAction(value, ctx) }); // one, with host-owned turn actions
 *
 * Structure attributes are `data-cerb-transcript-*`; values are plain `data-role` / `data-seq` /
 * `data-tool-name`. Classes are for CSS only — this never keys off them.
 *
 * `data-role` (NOT data-cerb-transcript-role): that name is already claimed by rules in cerb-form-builder.scss.
 *
 * Element type declares a tool payload: a <textarea> is JSON (→ a read-only CerbUI.JsonEditor); a <pre> is
 * plain text. data-content-type="application/json" forces
 * JSON either way. This lets a server that already detected JSON say so, rather than being re-sniffed.
 *
 * How much of the agent's work to surface is configurable — `thinking` and `tools` each take 'raw' (the full
 * bubble), 'summary' (THE DEFAULT — the same bubble, holding only its summary line: no params, no result, no
 * thinking body), or 'hide'. `data-display` on a node overrides its default, so a host can gate one tool
 * differently from the rest. Summary is the default deliberately: raw tool params and results routinely carry
 * data the reader isn't cleared for, so surfacing them is opt-in.
 *
 * A summarized node declares BOTH phrasings and the component picks by state: it is "active" while still
 * running (a tool with no result element yet; a thinking block with nothing to show), so a live transcript
 * reads data-summary-active ("Searching the documentation" / "Thinking…") and settles to data-summary-past
 * ("Searched the documentation" / "Thought for 5 seconds"). Either may be omitted. `data-icon` names the work
 * (default hammer / brain); an active node's glyph gets cerb-u-anim-pulse so in-progress reads at a glance,
 * not just in the wording.
 *
 * `data-duration-ms` is a MEASURED elapsed time — the component has no clock, so the caller measures and this
 * only formats. It fills the generic past verb ("Worked for 340ms"); an authored phrase is left alone, since
 * those are the author's words. Sub-second work is the common case, hence ms rather than rounded seconds.
 *
 * Authored nodes are MOVED, never cloned or re-serialized — a caller's <a data-cerb-peek> arrives with jQuery
 * handlers already bound, and innerHTML would silently drop them.
 *
 * `view` is 'toggle' (offer a Markdown/Text switcher), 'markdown' (the default), or 'text'. Text swaps a turn's
 * rendered body for its raw source; a turn with no source keeps its body rather than blanking.
 *
 * `--banded` renders the compact, full-width variant (role eyebrow above the body, no avatars) for dense hosts
 * like the interaction awaits. `--sample` dims it as design-time placeholder data.
 *
 * Events (bubbling): cerb-ui-agent-transcript:{ready,view,turn-added,turn-action}
 * CSS lives in cerb.css (.cerb-ui-agent-transcript*).
 */
CerbUI.AgentTranscript = class {
	static _instances = new WeakMap();
	static from(el) { return CerbUI.AgentTranscript._instances.get(el); }

	static _NS = 'cerb-ui-agent-transcript';

	static _DEFAULTS = {
		// 'toggle' gives the reader a Markdown/Text switcher (starting on markdown); 'markdown' and 'text' pin
		// the view and render no switcher. Defaults to the rendered body — the switcher is a reader affordance
		// a host asks for, not chrome every transcript should grow.
		view: 'markdown',        // 'toggle' | 'markdown' | 'text'
		viewStorageKey: null,    // remembers the reader's pick — 'toggle' only

		controls: true,          // the transcript-level controls row (just the view switcher today)

		copy: true,              // built-in per-turn copy (needs [data-cerb-transcript-source])
		copyRoles: ['assistant'], // …but only on these roles; null = every role with a source
		avatars: true,
		avatarSize: 44,
		bubbleAvatarSize: 32,

		// How much of the agent's work to surface. 'raw' = the full bubble — the ACTUAL request/response
		// payloads the agent sent and got back; 'summary' = the same bubble holding only its summary line
		// ("Searching the documentation"); 'hide' = drop it entirely. A node can override its own with
		// data-display, so a host can decide per tool.
		//
		// DEFAULT-DENY: those payloads routinely carry data the reader isn't cleared for, so surfacing them is
		// opt-in — a host that forgets these gets summaries, not a disclosure.
		thinking: 'summary',     // 'raw' | 'summary' | 'hide'
		tools: 'summary',        // 'raw' | 'summary' | 'hide'

		// Which 'raw' bubbles start open. 'latest' keeps a live conversation readable — the newest agent turn's
		// work is visible, everything older stays folded behind its summary.
		expand: 'latest',        // 'latest' | 'all' | 'none'

		json: true,
		jsonSniff: true,
		jsonMaxBytes: 262144,
		jsonOpts: { readOnly: true, minLines: 1, maxLines: 25 },

		toolbarOpts: {},
		onTurnAction: null,      // (value, ctx) => truthy = handled, suppresses the :turn-action event
		onEnhance: null,         // (turnEl, ctx) — host-specific per-turn wiring
	};

	// ── Batch-enhance every [data-cerb-agent-transcript] within a scope (element | selector | document) ──
	static enhance(scope, selector, opts = {}) {
		const root = (typeof scope === 'string') ? document.querySelector(scope) : (scope || document);
		if(!root || !root.querySelectorAll) return [];
		const out = [];
		root.querySelectorAll(selector || '[data-cerb-agent-transcript]').forEach(el => {
			out.push(CerbUI.AgentTranscript.from(el) || new CerbUI.AgentTranscript(el, opts));
		});
		return out;
	}

	constructor(el, opts = {}) {
		this.el = (typeof el === 'string') ? document.querySelector(el) : el;
		if(!this.el) return;

		this.opts = Object.assign({}, CerbUI.AgentTranscript._DEFAULTS, opts);
		this.opts.jsonOpts = Object.assign({}, CerbUI.AgentTranscript._DEFAULTS.jsonOpts, opts.jsonOpts || {});

		this.el.classList.add('cerb-ui-agent-transcript');
		this._enhanced = new WeakSet();
		this._toolbars = [];

		this._canToggleView = ('toggle' === this.opts.view);

		let view = ('text' === this.opts.view) ? 'text' : 'markdown';
		if(this._canToggleView && this.opts.viewStorageKey) {
			try {
				const saved = window.localStorage.getItem(this.opts.viewStorageKey);
				if(saved) view = saved;
			} catch(e) { /* storage blocked — fall back to the opt */ }
		}

		this._buildControls();
		this.refresh();
		this.setView(view, true);

		CerbUI.AgentTranscript._instances.set(this.el, this);
		this._emit('ready', { transcript: this });
	}

	// ── Public API ──────────────────────────────────────────────────────

	getView() { return this.el.getAttribute('data-cerb-view') || 'markdown'; }

	setView(view, silent) {
		view = ('text' === view) ? 'text' : 'markdown';
		this.el.setAttribute('data-cerb-view', view);

		if(this._viewSwitcher && this._viewSwitcher.setValue)
			this._viewSwitcher.setValue(view);

		if(this._canToggleView && this.opts.viewStorageKey) {
			try { window.localStorage.setItem(this.opts.viewStorageKey, view); } catch(e) { /* noop */ }
		}

		if(!silent) this._emit('view', { view: view });
		return this;
	}

	getTurns() {
		return this._turnEls().map(t => ({
			el: t,
			role: t.getAttribute('data-role') || '',
			seq: t.getAttribute('data-seq') || null,
		}));
	}

	getTurnSource(turnEl) {
		const src = turnEl ? turnEl.querySelector('[data-cerb-transcript-source]') : null;
		return src ? src.textContent : '';
	}

	// Parse + enhance + append a turn. Scroll pinning is the host's job — a transcript in a scroll box
	// (the interaction awaits) has its own rules about when to follow the tail.
	appendTurn(htmlOrEl) {
		let node = htmlOrEl;
		if(typeof htmlOrEl === 'string') {
			const tmp = document.createElement('div');
			tmp.innerHTML = htmlOrEl;
			node = tmp.firstElementChild;
		}
		if(!node) return null;

		this.el.appendChild(node);
		this._enhanceTurn(node);
		this._emit('turn-added', { turn: node });
		return node;
	}

	// Idempotent: already-enhanced turns are skipped, so this is safe to call after appending markup.
	refresh() {
		this._turnEls().forEach(t => this._enhanceTurn(t));
		return this;
	}

	destroy() {
		this._toolbars.forEach(tb => { if(tb && tb.destroy) tb.destroy(); });
		this._toolbars = [];
		if(this._controlsEl) this._controlsEl.remove();
		CerbUI.AgentTranscript._instances.delete(this.el);
	}

	// ── Internals ───────────────────────────────────────────────────────

	_emit(name, detail) {
		this.el.dispatchEvent(new CustomEvent(CerbUI.AgentTranscript._NS + ':' + name, {
			detail: detail || {},
			bubbles: true,
		}));
	}

	_turnEls() {
		return Array.from(this.el.querySelectorAll('[data-cerb-transcript-turn]'));
	}

	// A transcript-level controls row (Expand all + Markdown/Text), above the first turn.
	_buildControls() {
		if(!this.opts.controls || !this._canToggleView) return;
		if(!(window.CerbUI && CerbUI.Switcher)) return;

		const row = document.createElement('div');
		row.className = 'cerb-ui-agent-transcript--controls';

		const sw = document.createElement('div');
		sw.className = 'cerb-ui-switcher';
		sw.innerHTML =
			'<button type="button" class="cerb-ui-switcher--active" data-value="markdown">Markdown</button>' +
			'<button type="button" data-value="text">Text</button>';
		row.appendChild(sw);

		this._viewSwitcher = new CerbUI.Switcher(sw, { onSelect: v => this.setView(v) });

		this.el.insertBefore(row, this.el.firstChild);
		this._controlsEl = row;
	}

	// Rebuild one turn's chrome around its authored nodes. Every authored node is MOVED into the new
	// structure — never cloned (bound handlers) and never re-serialized.
	_enhanceTurn(turnEl) {
		if(!turnEl || this._enhanced.has(turnEl)) return;
		this._enhanced.add(turnEl);

		const role = turnEl.getAttribute('data-role') || 'assistant';
		turnEl.classList.add('cerb-ui-agent-transcript--turn', 'cerb-ui-agent-transcript--' + role);

		const q = sel => turnEl.querySelector(sel);
		const avatarEl = q('[data-cerb-transcript-avatar]');
		const senderEl = q('[data-cerb-transcript-sender]');
		const metaEl = q('[data-cerb-transcript-meta]');
		const asideEl = q('[data-cerb-transcript-aside]');
		const sourceEl = q('[data-cerb-transcript-source]');
		const toolbarUl = q('[data-cerb-transcript-turn-toolbar]');

		// A turn groups several messages, so bodies and images repeat; keep them in author order.
		const flowNodes = Array.from(turnEl.querySelectorAll('[data-cerb-transcript-body], [data-cerb-transcript-images]'));

		// Author order is the sub-thread's order — thinking and tool calls interleave meaningfully.
		const subNodes = Array.from(turnEl.querySelectorAll('[data-cerb-transcript-thinking], [data-cerb-transcript-tool]'))
			.filter(n => {
				if('hide' !== this._displayMode(n)) return true;
				n.remove();
				return false;
			});

		// ── Avatar column ──
		if(avatarEl && this.opts.avatars) {
			const box = document.createElement('div');
			box.className = 'cerb-ui-agent-transcript--avatar';
			avatarEl.parentNode.insertBefore(box, avatarEl);
			box.appendChild(avatarEl);
			// `size` (not a wrapper class) so Avatar sets width/height AND font-size — a class sizes only the
			// circle, leaving the monogram at the 22px default's font-size.
			if(window.CerbUI && CerbUI.Avatar) new CerbUI.Avatar(avatarEl, { size: this.opts.avatarSize });
		} else if(avatarEl && !this.opts.avatars) {
			avatarEl.remove();
		}

		// ── Main column ──
		const main = document.createElement('div');
		main.className = 'cerb-ui-agent-transcript--main';

		const head = document.createElement('div');
		head.className = 'cerb-ui-header cerb-ui-header--tight';

		const headLeft = document.createElement('div');
		if(senderEl) {
			senderEl.classList.add('cerb-ui-agent-transcript--sender');
			headLeft.appendChild(senderEl);
		}
		if(headLeft.childNodes.length) head.appendChild(headLeft);

		// The hover fade goes LEFTMOST here: the meta and aside are always visible and anchor the right edge,
		// so the toolbar opens inward instead of shifting them (or leaving a hole at the edge).
		const headRight = document.createElement('div');
		headRight.className = 'cerb-ui-header--right';

		const actions = this._buildTurnActions(turnEl, toolbarUl, sourceEl, role);
		if(actions) headRight.appendChild(actions);
		if(metaEl) {
			metaEl.classList.add('cerb-ui-agent-transcript--meta');
			headRight.appendChild(metaEl);
		}
		if(asideEl) headRight.appendChild(asideEl);

		// Only if it has content: an empty --right still occupies the flex row and its margins.
		if(headRight.childNodes.length) head.appendChild(headRight);

		if(head.childNodes.length)
			main.appendChild(head);

		if(sourceEl) {
			sourceEl.classList.add('cerb-ui-agent-transcript--source');
			main.appendChild(sourceEl);
			// Text view swaps body→source only on turns that HAVE a source; without this a sourceless turn
			// (a summarized tool-only turn, say) would hide its body and render blank.
			turnEl.classList.add('cerb-ui-agent-transcript--has-source');
		}

		flowNodes.forEach(n => {
			n.classList.add(n.hasAttribute('data-cerb-transcript-images')
				? 'cerb-ui-agent-transcript--images'
				: 'cerb-ui-agent-transcript--body');
			main.appendChild(n);
		});

		if(subNodes.length) {
			const sub = document.createElement('div');
			sub.className = 'cerb-ui-agent-transcript--subthread';
			subNodes.forEach(n => {
				// Build first — that moves the label/payload out of `n` — then drop the spent wrapper.
				sub.appendChild(this._buildBubble(n, this._displayMode(n)));
				n.remove();
			});
			main.appendChild(sub);
		}

		turnEl.appendChild(main);

		if(typeof this.opts.onEnhance === 'function')
			this.opts.onEnhance(turnEl, { role: role, seq: turnEl.getAttribute('data-seq') || null });
	}

	// The hover-fade wrapper lives here, NOT on the source <ul>: CerbUI.Toolbar hides that list with [hidden]
	// and builds its own strip, so a competing display/opacity on it leaves a raw bulleted list on screen.
	_buildTurnActions(turnEl, toolbarUl, sourceEl, role) {
		const copyRole = !this.opts.copyRoles || this.opts.copyRoles.indexOf(role) !== -1;
		const canCopy = this.opts.copy && copyRole && sourceEl && sourceEl.textContent.trim().length > 0;
		if(!toolbarUl && !canCopy) return null;

		let ul = toolbarUl;
		if(!ul) {
			ul = document.createElement('ul');
			ul.className = 'cerb-ui-toolbar';
		}

		if(canCopy) {
			// Namespaced so a host handling a plain 'copy' can't swallow the built-in.
			const li = document.createElement('li');
			li.setAttribute('data-value', 'cerb-copy');
			li.setAttribute('data-icon', 'copy');
			li.setAttribute('title', 'Copy');
			ul.insertBefore(li, ul.firstChild);
		}

		const wrap = document.createElement('div');
		wrap.className = 'cerb-ui-agent-transcript--turn-actions';
		wrap.appendChild(ul);

		if(window.CerbUI && CerbUI.Toolbar) {
			// tiny: small muted icons, no strip chrome — turn actions are secondary to the message. First in the
			// merge so a host can override it via toolbarOpts.
			const tb = new CerbUI.Toolbar(ul, Object.assign({ tiny: true }, this.opts.toolbarOpts, {
				onSelect: (item, sourceLi, e) => {
					const value = item && item.value;

					if('cerb-copy' === value) {
						this._copy(sourceEl);
						return;
					}

					const ctx = {
						transcript: this,
						turn: turnEl,
						role: role,
						seq: turnEl.getAttribute('data-seq') || null,
						source: sourceEl ? sourceEl.textContent : '',
						item: item,
						sourceLi: sourceLi,
					};

					if(typeof this.opts.onTurnAction === 'function' && this.opts.onTurnAction(value, ctx))
						return;

					this._emit('turn-action', Object.assign({ value: value }, ctx));
				},
			}));
			this._toolbars.push(tb);
		}

		return wrap;
	}

	// textContent (not an innerHTML round-trip): the <pre> is escaped exactly once by the server, so its
	// text is already the literal source. Re-parsing it as HTML would mangle a body containing `&` or `<`.
	_copy(sourceEl) {
		if(!sourceEl || !navigator.clipboard) return;
		navigator.clipboard.writeText(sourceEl.textContent).then(() => {
			if(window.Devblocks && Devblocks.createAlert) Devblocks.createAlert('Copied to clipboard!');
		});
	}

	// Per-node display mode: data-display overrides the component default, so a host can gate one tool
	// differently from the rest. Anything unrecognized normalizes to 'summary' — a typo or a stale value must
	// never be the thing that spills raw payloads.
	_displayMode(node) {
		const mode = node.getAttribute('data-display')
			|| (node.hasAttribute('data-cerb-transcript-tool') ? this.opts.tools : this.opts.thinking);

		return ('raw' === mode || 'hide' === mode) ? mode : 'summary';
	}

	// data-icon lets a caller name the work ('search', 'mail'); hammer/brain are the generic fallbacks.
	_iconFor(node, isTool) {
		return node.getAttribute('data-icon') || (isTool ? 'hammer' : 'brain');
	}

	// Still running: a tool until its result arrives, a thinking block until it has anything to show.
	_isActive(node) {
		if(node.hasAttribute('data-cerb-transcript-tool'))
			return !node.querySelector('[data-cerb-transcript-tool-result]');
		return !(node.textContent || '').trim();
	}

	// ms → a reader's duration. Sub-second work is the common case, so don't round it away to "0 seconds".
	_duration(ms) {
		if(ms < 1000) return ms + 'ms';
		if(ms < 60000) return (Math.round(ms / 100) / 10) + 's';

		const mins = Math.floor(ms / 60000);
		const secs = Math.round((ms % 60000) / 1000);

		return secs ? (mins + 'm ' + secs + 's') : (mins + 'm');
	}

	// Active vs past phrasing ("Searching…" / "Searched…", "Thinking…" / "Thought for 5 seconds"). Either may
	// be omitted; fall back to the other, then to a generic verb — a summary is prose for a reader, so the
	// fallback is never the machine tool name. The generic PAST verb takes the measured duration when the
	// caller supplies one ("Worked for 340ms"); an authored phrase is left alone, since it's the author's words.
	_summaryFor(node, isTool, isActive) {
		const d = node.dataset;
		const active = d.summaryActive || '';
		const past = d.summaryPast || '';
		const chosen = isActive ? (active || past) : (past || active);
		if(chosen) return chosen;

		if(isTool) {
			if(isActive) return 'Working';
			const ms = parseInt(d.durationMs, 10);
			return isNaN(ms) ? 'Worked' : ('Worked for ' + this._duration(ms));
		}

		return isActive ? 'Thinking' : 'Thought';
	}

	// A thinking block or tool call → a chat bubble. 'summary' keeps the same bubble and swaps its contents for
	// the one-line summary: no params, no result, no thinking body.
	_buildBubble(node, mode) {
		const isTool = node.hasAttribute('data-cerb-transcript-tool');
		const isActive = this._isActive(node);
		const isSummary = ('summary' === mode);

		const note = document.createElement('div');
		note.className = 'cerb-ui-agent-transcript--bubble';
		if(isActive) note.classList.add('cerb-ui-agent-transcript--bubble-active');

		const avatarBox = document.createElement('div');
		avatarBox.className = 'cerb-ui-agent-transcript--bubble-avatar-box';

		const avatarSpec = {
			icon: this._iconFor(node, isTool),
			size: this.opts.bubbleAvatarSize,
			color: 'var(--cerb-color-background-contrast-200)',
			className: 'cerb-ui-agent-transcript--bubble-avatar',
		};
		const avatar = (window.CerbUI && CerbUI.Avatar)
			? CerbUI.Avatar.create(avatarSpec)
			: document.createElement('span');

		// Still running → the glyph itself pulses, so in-progress reads at a glance, not just in the wording.
		if(isActive) {
			const glyph = avatar.querySelector('span.cerb-icons');
			if(glyph) glyph.classList.add('cerb-u-anim-pulse');
		}

		avatarBox.appendChild(avatar);
		note.appendChild(avatarBox);

		const main = document.createElement('div');
		main.className = 'cerb-ui-agent-transcript--bubble-main';

		// The summary always leads — 'raw' reveals the payloads BELOW it rather than replacing it, so a bubble
		// reads the same whether or not it's open. Prose, so no monospace (the tool's name appears as the
		// eyebrow over its params instead).
		const header = document.createElement('div');
		header.className = 'cerb-ui-agent-transcript--bubble-header';

		const label = document.createElement('span');
		label.className = 'cerb-ui-agent-transcript--bubble-author';
		label.textContent = this._summaryFor(node, isTool, isActive);
		header.appendChild(label);
		main.appendChild(header);

		if(isSummary) {
			note.appendChild(main);
			return note;
		}

		const body = document.createElement('div');
		body.className = 'cerb-ui-agent-transcript--bubble-body';

		if(isTool) {
			const params = node.querySelector('[data-cerb-transcript-tool-params]');
			const result = node.querySelector('[data-cerb-transcript-tool-result]');

			// The tool's real name, as the eyebrow over its params — matching RESULT below it.
			let name = node.querySelector('[data-cerb-transcript-tool-label]');
			if(!name) {
				name = document.createElement('div');
				name.textContent = node.getAttribute('data-tool-name') || 'tool';
			}
			name.classList.add('cerb-ui-agent-transcript--tool-eyebrow');
			body.appendChild(name);

			if(params) {
				body.appendChild(this._buildPayload(params));
			} else {
				const none = document.createElement('div');
				none.className = 'cerb-u-text-muted cerb-u-fs-n1';
				none.textContent = '(no parameters)';
				body.appendChild(none);
			}

			if(result) {
				const resultLabel = document.createElement('div');
				resultLabel.className = 'cerb-ui-agent-transcript--tool-eyebrow';
				resultLabel.textContent = 'Result';
				body.appendChild(resultLabel);
				body.appendChild(this._buildPayload(result));
			}
		} else {
			// Thinking: the authored markdown body, moved as-is.
			while(node.firstChild) body.appendChild(node.firstChild);
		}

		// The payloads hide behind the summary until asked for; `expand` seeds which start open.
		if(!this._isExpanded(node)) note.classList.add('cerb-ui-agent-transcript--bubble-collapsed');

		const toggle = document.createElement('button');
		toggle.type = 'button';
		toggle.className = 'cerb-ui-agent-transcript--bubble-toggle cerb-ui-toolbar-button';
		toggle.innerHTML = '<span class="cerb-icons cerb-icon-chevron-right"></span>';
		toggle.setAttribute('title', 'Details');
		toggle.addEventListener('click', e => {
			e.stopPropagation();
			note.classList.toggle('cerb-ui-agent-transcript--bubble-collapsed');
		});
		header.appendChild(toggle);

		main.appendChild(body);
		note.appendChild(main);
		return note;
	}

	// Which 'raw' bubbles start open. 'latest' = only the last assistant turn's, so recent work is visible and
	// history stays quiet; 'all' = every one (reviewing a whole session); 'none' = all closed.
	_isExpanded(node) {
		if('all' === this.opts.expand) return true;
		if('none' === this.opts.expand) return false;

		const turn = node.closest('[data-cerb-transcript-turn]');
		return !!turn && turn === this._latestAgentTurn();
	}

	_latestAgentTurn() {
		const agents = this._turnEls().filter(t => 'user' !== (t.getAttribute('data-role') || ''));
		return agents.length ? agents[agents.length - 1] : null;
	}

	// Element type declares the payload: <textarea> = JSON, <pre> = text. data-content-type wins over both.
	_buildPayload(el) {
		const text = el.textContent || '';
		const declared = (el.getAttribute('data-content-type') || '').toLowerCase();

		let isJson = ('textarea' === el.tagName.toLowerCase());
		if('application/json' === declared) isJson = true;
		else if(declared) isJson = false;
		else if(!isJson && this.opts.jsonSniff) isJson = this._sniffJson(text);

		if(isJson && this.opts.json && window.CerbUI && CerbUI.JsonEditor)
			return this._buildJsonEditor(text);

		return ('pre' === el.tagName.toLowerCase()) ? el : this._toPre(text);
	}

	_sniffJson(text) {
		const t = text.trim();
		if(!t || t.length > this.opts.jsonMaxBytes) return false;
		if('{' !== t[0] && '[' !== t[0]) return false;
		try { JSON.parse(t); return true; } catch(e) { return false; }
	}

	_toPre(text) {
		const pre = document.createElement('pre');
		pre.textContent = text;
		return pre;
	}

	_buildJsonEditor(text) {
		const wrap = document.createElement('div');
		wrap.className = 'cerb-ui-agent-transcript--json-editor';
		const ta = document.createElement('textarea');
		ta.spellcheck = false;
		ta.value = text;
		wrap.appendChild(ta);
		// Deferred: JsonEditor measures, so it needs the node in the document first — this wrap is still
		// detached until the caller appends it.
		queueMicrotask(() => {
			if(!ta.isConnected) return;
			try { new CerbUI.JsonEditor(ta, this.opts.jsonOpts); } catch(e) { /* leave the raw textarea */ }
		});
		return wrap;
	}

};
