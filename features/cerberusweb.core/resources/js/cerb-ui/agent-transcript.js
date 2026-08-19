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

		// How a turn's content is laid out. 'interleaved' (default) keeps bodies and bubbles in author order, so
		// each preamble ("let me search…") sits directly above the tool it prompted — reads like an agent's
		// step-by-step work log. 'conversation' pools the agent's text as one flowing message with all its
		// thinking/tool work in a single sub-thread below — reads like a chat answer.
		layout: 'interleaved',   // 'interleaved' | 'conversation'

		json: true,
		jsonSniff: true,
		jsonMaxBytes: 262144,
		jsonOpts: { readOnly: true, minLines: 1, maxLines: 25 },

		toolbarOpts: {},
		onTurnAction: null,      // (value, ctx) => truthy = handled, suppresses the :turn-action event
		onEnhance: null,         // (turnEl, ctx) — host-specific per-turn wiring
	};

	/*
	 * ── Stick-to-bottom ──────────────────────────────────────────────────────────────────────────────
	 *
	 * A transcript that always jumps to the newest turn is right until someone scrolls up to re-read
	 * something, at which point every poll tick yanks them back. These three helpers let a caller pin only
	 * when the reader was already at the bottom.
	 *
	 * The intent is tracked on a SCROLL LISTENER rather than sampled around each update, because the pins
	 * fire from a MutationObserver -- by then the DOM has already changed and there is nothing left to
	 * measure.
	 *
	 * It is stored on a HOST element that outlives the scroll box: the interaction form, which survives the
	 * inner re-renders (the same persistence `_cerbInteractionCommand` relies on), or the box itself when
	 * there is no form above it (an AgentPane's chat body). So scrolling up to read survives a tool-loop
	 * re-render instead of silently resetting.
	 */

	// Sub-pixel layout and fractional zoom mean scrollTop rarely lands exactly on the bottom, so an equality
	// test reads as "scrolled up" when nobody moved. One line of slack.
	static STICK_TOLERANCE_PX = 32;

	static isAtBottom(el, tolerance) {
		if(!el) return true;
		const slack = (tolerance == null) ? CerbUI.AgentTranscript.STICK_TOLERANCE_PX : tolerance;
		return (el.scrollHeight - el.scrollTop - el.clientHeight) <= slack;
	}

	/*
	 * The element that ACTUALLY scrolls for a given transcript.
	 *
	 * Standalone, the transcript div is its own scroll box (max-height:75vh). Inside an AgentPane that cap is
	 * dropped and the pane's chat body scrolls instead -- so pinning the transcript there sets scrollTop on an
	 * element that cannot move, silently. Walk up to the first ancestor that genuinely overflows.
	 */
	static scrollBoxFor(el) {
		if(!el) return el;

		// Bounded deliberately. An unbounded walk would climb past a SHORT transcript into whatever scrolls
		// above it -- the dialog, or the page itself -- and start yanking that instead. Only these two are ever
		// the transcript's own scroller.
		const isOwnScroller = (n) =>
			n === el || (n.classList && n.classList.contains('cerb-agent-pane--chat-body'));

		for(let n = el; n && n !== document.body && n !== document.documentElement; n = n.parentElement) {
			if(!isOwnScroller(n))
				continue;

			if(n.scrollHeight <= n.clientHeight + 1)
				continue;

			const overflow = getComputedStyle(n).overflowY;

			if(overflow === 'auto' || overflow === 'scroll' || overflow === 'overlay')
				return n;
		}

		return el;
	}

	static _stickHost(el) {
		return (el && el.closest && el.closest('form.cerb-form-builder')) || el;
	}

	// Idempotent: safe to call on every render, which is the point -- the box may be new each time while the
	// host carrying the flag is not.
	static trackStick(el) {
		if(!el || el._cerbStickTracked) return;
		el._cerbStickTracked = true;

		CerbUI.AgentTranscript.attachJump(el);

		/*
		 * Detaching requires a real GESTURE, not merely a scroll event.
		 *
		 * The box gets scrolled by things that aren't the reader: `panel.tpl` focuses the first focusable after
		 * every re-render and the browser scrolls it into view, which on an `on_tool:` render is a per-turn copy
		 * button near the TOP. Reading that as "they scrolled up" recorded a near-zero offset and the next
		 * render dutifully restored it -- so clicking "Jump to latest" held for exactly one tool step.
		 *
		 * A gesture opens a window; scroll events inside it count and extend it (trackpad momentum keeps firing
		 * long after the fingers stop). Everything else moves the box without changing intent.
		 */
		const GESTURE_MS = 700;
		const stamp = () => { el._cerbUserScrollAt = Date.now(); };

		// Unambiguous: nothing else produces these.
		el.addEventListener('wheel', stamp, { passive: true });
		el.addEventListener('touchmove', stamp, { passive: true });

		// Dragging the scrollbar gutter targets the container ITSELF. Any deeper target is a click on content
		// -- and in an AgentPane the transcript's own copy buttons live in here, so counting those would let a
		// click plus the focus-scroll that follows it read as "they scrolled away".
		el.addEventListener('mousedown', (e) => { if(e.target === el) stamp(); }, { passive: true });

		// Only keys that actually scroll, and never while typing -- an AgentPane keeps its composer inside this
		// box, so counting every keystroke would let a re-render landing mid-sentence detach the reader.
		const SCROLL_KEYS = ['PageUp', 'PageDown', 'Home', 'End', 'ArrowUp', 'ArrowDown', ' ', 'Spacebar'];

		el.addEventListener('keydown', (e) => {
			const t = e.target;
			const editable = t && (t.isContentEditable || /^(input|textarea|select)$/i.test(t.tagName || ''));

			if(!editable && SCROLL_KEYS.indexOf(e.key) !== -1)
				stamp();
		}, { passive: true });

		el.addEventListener('scroll', () => {
			const host = CerbUI.AgentTranscript._stickHost(el);
			if(!host) return;

			// The button is position-only, so keep it honest on every scroll however it was caused.
			CerbUI.AgentTranscript.syncJump(el);

			if((Date.now() - (el._cerbUserScrollAt || 0)) > GESTURE_MS)
				return;

			el._cerbUserScrollAt = Date.now(); // momentum is still the same gesture

			host._cerbStickBottom = CerbUI.AgentTranscript.isAtBottom(el);
			// Remembered for the case where the scroll box itself is replaced on the next render; the flag alone
			// would only tell us NOT to jump to the bottom, which would leave the reader at the top instead.
			host._cerbScrollTop = el.scrollTop;
		}, { passive: true });
	}

	// Defaults to true: a reader who has never scrolled wants the newest turn.
	static shouldStick(el) {
		const host = CerbUI.AgentTranscript._stickHost(el);
		return !host || host._cerbStickBottom !== false;
	}

	// Pin only if the reader hasn't scrolled away. Returns whether it pinned. For a scroll box that SURVIVES
	// the update -- where a detached reader is already sitting at the right offset and doing nothing is correct.
	static stickToBottom(el) {
		if(!el) return false;

		if(!CerbUI.AgentTranscript.shouldStick(el)) {
			// Detached, and this call means new content just landed -- which is exactly when the reader needs
			// to be told there is something below.
			CerbUI.AgentTranscript.syncJump(el);
			return false;
		}

		el.scrollTop = el.scrollHeight;
		CerbUI.AgentTranscript.syncJump(el);
		return true;
	}

	/*
	 * "Jump to latest" — the signal that content is arriving below a reader who scrolled up.
	 *
	 * Appended to the SCROLL BOX itself (not the turns container), so `setTurns()` swapping the turns during a
	 * streaming poll doesn't take it with them. It is `position: sticky` with zero height: a normal absolute
	 * child would need a positioned wrapper around a container the interaction re-renders, and any real height
	 * would extend the scrollable area, pushing the bottom away from itself so "at bottom" could never be true.
	 */
	static attachJump(el) {
		if(!el || el._cerbJumpEl) return el && el._cerbJumpEl;

		const strip = document.createElement('div');
		strip.className = 'cerb-ui-transcript-jump';
		strip.hidden = true;

		const btn = document.createElement('button');
		btn.type = 'button';
		btn.innerHTML = '<span class="cerb-icons cerb-icon-down-arrow"></span> Jump to latest';
		btn.addEventListener('click', () => {
			CerbUI.AgentTranscript.resetStick(el);
			el.scrollTop = el.scrollHeight;
			CerbUI.AgentTranscript.syncJump(el);
		});

		strip.appendChild(btn);
		el.appendChild(strip);
		el._cerbJumpEl = strip;

		return strip;
	}

	// Visible only while detached AND there is somewhere to go. Called from the scroll listener (the reader
	// moved) and from each pin site (content arrived).
	static syncJump(el) {
		if(!el || !el._cerbJumpEl) return;

		const overflows = el.scrollHeight > el.clientHeight;

		el._cerbJumpEl.hidden = !(overflows && !CerbUI.AgentTranscript.isAtBottom(el));
	}

	/*
	 * Re-arm sticking, from anywhere inside the conversation (the composer calls this on send).
	 *
	 * There can be TWO scroll boxes above a composer with two different hosts: the transcript element, whose
	 * host is the interaction form, and -- inside an AgentPane -- the chat body, which CONTAINS that form and
	 * is therefore its own host. Clearing just one leaves the other detached, so walk up and clear every
	 * tracked box on the way as well as the form.
	 */
	static resetStick(el) {
		const arm = (host) => {
			if(!host) return;
			host._cerbStickBottom = true;
			host._cerbScrollTop = 0;
		};

		arm(CerbUI.AgentTranscript._stickHost(el));

		for(let n = el; n; n = n.parentElement) {
			if(n._cerbStickTracked) {
				arm(CerbUI.AgentTranscript._stickHost(n));
				if(n._cerbJumpEl) n._cerbJumpEl.hidden = true;
			}
		}
	}

	// For a scroll box REPLACED by the render (a fresh element at scrollTop 0): pin to the bottom, or put a
	// detached reader back where they were. Doing nothing here would drop them at the top -- further from
	// what they were reading than the bottom was.
	static restoreScroll(el) {
		if(!el) return;

		if(CerbUI.AgentTranscript.stickToBottom(el))
			return;

		const host = CerbUI.AgentTranscript._stickHost(el);
		const top = host && host._cerbScrollTop;

		// Content shifts between renders, so this is an approximation -- clamped, since the new render may be
		// shorter than the offset we saved.
		if(top > 0)
			el.scrollTop = Math.min(top, Math.max(0, el.scrollHeight - el.clientHeight));

		CerbUI.AgentTranscript.syncJump(el);
	}

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

	// Replace every turn with freshly-rendered markup, keeping the instance (and therefore the view mode, the
	// Markdown/Text switcher, and any host handlers bound to this element) alive. The controls row lives INSIDE
	// this element, so a naive `innerHTML = html` would destroy it and orphan every per-turn toolbar — hence the
	// explicit teardown here.
	setTurns(html) {
		this._toolbars.forEach(tb => { if(tb && tb.destroy) tb.destroy(); });
		this._toolbars = [];

		// Enhanced-ness is tracked per node; the nodes are all about to be replaced.
		this._enhanced = new WeakSet();

		this._turnEls().forEach(t => t.remove());
		this.el.querySelectorAll('.cerb-ui-agent-transcript--checkpoint').forEach(t => t.remove());

		const tmp = document.createElement('div');
		tmp.innerHTML = (html == null) ? '' : html;
		while(tmp.firstChild) this.el.appendChild(tmp.firstChild);

		this.refresh();
		this.setView(this.getView(), true);
		this._emit('turns-replaced', { transcript: this });
		return this;
	}

	// Replace ONE turn, addressed by its STABLE `data-turn-seq`, appending it if it isn't on screen yet.
	// Returns the node.
	//
	// Addressed by `data-turn-seq` and NOT `data-seq`: the latter is the turn's newest message, which advances
	// every time an agent turn accretes another message in its tool loop. Keying on it means a growing turn
	// stops matching the node already on screen, so every tick appends another copy of the same turn.
	//
	// This exists because setTurns() is a full teardown — it destroys every per-turn toolbar, drops the
	// enhanced-node set, and rebuilds the lot. That's the right call for a re-sync, and completely wrong for a
	// turn that updates several times a second while an answer streams in: the toolbars would churn, and any
	// selection or scroll anchoring inside untouched turns would be lost on every tick.
	updateTurn(seq, html) {
		if(seq == null || seq === '') return null;

		let node = html;

		if(typeof html === 'string') {
			const tmp = document.createElement('div');
			tmp.innerHTML = html;
			node = tmp.firstElementChild;
		}

		if(!node) return null;

		const existing = this.el.querySelector('[data-cerb-transcript-turn][data-turn-seq="' + String(seq).replace(/"/g, '') + '"]');

		// Which bubbles the reader has opened or closed. Replacing the node rebuilds them from server markup,
		// which knows only the `expand:` default — so without this, expanding a Thought to read it snaps shut
		// on the next tick, roughly once a second, and the content is unreadable while it streams.
		const openState = existing ? this._captureBubbleState(existing) : null;

		if(existing) {
			existing.replaceWith(node);
		} else {
			// Appending a USER turn that we couldn't match means the reader's own optimistic echo is standing in
			// for it: the echo has no seq (the message wasn't stored yet), so it can never match by key, and
			// appending on top of it shows the same message twice. The real turn supersedes the placeholder.
			//
			// Only for a user turn. An agent turn appended while a placeholder is up is the ordinary case — the
			// poll sends the newest turn only, so the reader's message exists ON SCREEN solely as that
			// placeholder, and clearing it there would erase the message from the conversation.
			if('user' === node.getAttribute('data-role'))
				this.el.querySelectorAll('[data-cerb-transcript-turn][data-cerb-transcript-pending]').forEach(p => p.remove());

			this.el.appendChild(node);
		}

		// Toolbars are tracked in one flat list, so a targeted replace can't know which entries belonged to the
		// node just swapped out. Sweep the ones whose element has left the document instead — self-correcting,
		// and it also collects anything an earlier partial update orphaned.
		this._toolbars = this._toolbars.filter(tb => {
			const el = tb && (tb.el || tb.element);

			if(el && !document.contains(el)) {
				if(tb.destroy) tb.destroy();
				return false;
			}

			return true;
		});

		this._enhanceTurn(node);

		if(openState) this._restoreBubbleState(node, openState);

		// Re-apply the view mode so a turn arriving mid-stream matches the Markdown/Text state the reader chose,
		// rather than reverting to the server default on every patch.
		this.setView(this.getView(), true);
		this._emit('turn-updated', { turn: node, seq: seq });

		return node;
	}

	// Bubble open/closed state, in DOM order. Index-keyed rather than id-keyed because a thinking bubble has
	// no id — and it holds up in practice: bubbles only ever APPEND as a turn runs (a new tool call), so the
	// ones the reader already touched keep their positions. A bubble that appears later simply keeps the
	// server's `expand:` default, which is the right answer for something they haven't seen yet.
	_captureBubbleState(turnEl) {
		return Array.from(turnEl.querySelectorAll('.cerb-ui-agent-transcript--bubble'))
			.map(b => b.classList.contains('cerb-ui-agent-transcript--bubble-collapsed'));
	}

	_restoreBubbleState(turnEl, state) {
		Array.from(turnEl.querySelectorAll('.cerb-ui-agent-transcript--bubble')).forEach((b, i) => {
			if(i < state.length)
				b.classList.toggle('cerb-ui-agent-transcript--bubble-collapsed', state[i]);
		});
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

		// A turn groups several messages, so bodies/images and thinking/tool bubbles repeat. Collect ALL of them
		// in author (document) order; 'hide' bubbles are dropped here so neither layout renders them. The layout
		// below decides whether bodies pool above one sub-thread (conversation) or interleave with it (steps).
		const isBubbleNode = n => n.hasAttribute('data-cerb-transcript-thinking') || n.hasAttribute('data-cerb-transcript-tool');
		const contentNodes = Array.from(turnEl.querySelectorAll(
			'[data-cerb-transcript-body], [data-cerb-transcript-images], [data-cerb-transcript-thinking], [data-cerb-transcript-tool]'
		)).filter(n => {
			if(isBubbleNode(n) && 'hide' === this._displayMode(n)) { n.remove(); return false; }
			return true;
		});

		// On a turn still being written, the LAST bubble is the one being written — mark it so _isActive()
		// doesn't have to guess from emptiness (see there). Stamped HERE, before the layout loop moves these
		// nodes into sub-threads: afterwards they're no longer reachable from turnEl in document order, so
		// "which one is last" stops being answerable.
		if(turnEl.hasAttribute('data-cerb-transcript-streaming')) {
			const bubbles = contentNodes.filter(isBubbleNode);

			if(bubbles.length)
				bubbles[bubbles.length - 1].setAttribute('data-cerb-transcript-active', '');
		}

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

		// Turn-level collapse (opt-in via data-cerb-transcript-collapsible; value "collapsed" starts folded).
		// Reuses the thinking/tool bubbles' chevron + rotate pattern. Only the system-prompt turn uses this
		// today; a direct child of --right, so it's always visible rather than hover-gated like --turn-actions.
		if(turnEl.hasAttribute('data-cerb-transcript-collapsible')) {
			const toggle = document.createElement('button');
			toggle.type = 'button';
			toggle.className = 'cerb-ui-agent-transcript--turn-toggle cerb-ui-toolbar-button';
			toggle.innerHTML = '<span class="cerb-icons cerb-icon-chevron-right"></span>';
			toggle.setAttribute('title', 'Details');
			toggle.addEventListener('click', e => {
				e.stopPropagation();
				turnEl.classList.toggle('cerb-ui-agent-transcript--turn-collapsed');
			});
			headRight.appendChild(toggle);
			if('collapsed' === turnEl.getAttribute('data-cerb-transcript-collapsible'))
				turnEl.classList.add('cerb-ui-agent-transcript--turn-collapsed');
		}

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

		const appendFlow = n => {
			n.classList.add(n.hasAttribute('data-cerb-transcript-images')
				? 'cerb-ui-agent-transcript--images'
				: 'cerb-ui-agent-transcript--body');
			main.appendChild(n);
		};

		if('interleaved' === this.opts.layout) {
			// Keep bodies and bubbles in author order so each preamble sits with the tool it prompted.
			// Contiguous bubbles still group into one indented sub-thread; a body/images between them breaks
			// the run into a new one — the avatar/header stays a single stamp at the top of the turn.
			let sub = null;
			contentNodes.forEach(n => {
				if(isBubbleNode(n)) {
					if(!sub) {
						sub = document.createElement('div');
						sub.className = 'cerb-ui-agent-transcript--subthread';
						main.appendChild(sub);
					}
					// Build first — that moves the label/payload out of `n` — then drop the spent wrapper.
					sub.appendChild(this._buildBubble(n, this._displayMode(n)));
					n.remove();
				} else {
					sub = null;
					appendFlow(n);
				}
			});
		} else {
			// Conversation (default): pool the bodies as one flowing message, then all work in one sub-thread.
			contentNodes.filter(n => !isBubbleNode(n)).forEach(appendFlow);

			const subNodes = contentNodes.filter(isBubbleNode);
			if(subNodes.length) {
				const sub = document.createElement('div');
				sub.className = 'cerb-ui-agent-transcript--subthread';
				subNodes.forEach(n => {
					sub.appendChild(this._buildBubble(n, this._displayMode(n)));
					n.remove();
				});
				main.appendChild(sub);
			}
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
		// An explicit mark wins. A STREAMED thinking block breaks the emptiness heuristic below: it used to be
		// safe because a thinking block only appeared once complete, so empty could only mean "not done yet".
		// Now it accumulates text as it's written, so the moment the first token lands it would read as
		// finished — labelled "Thought" while visibly still being thought. _enhanceTurn stamps this on the
		// last bubble of a turn the server says is still streaming.
		if(node.hasAttribute('data-cerb-transcript-active'))
			return true;

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
		toggle.setAttribute('aria-label', 'Toggle details');
		header.appendChild(toggle);

		// Expand/collapse on a click ANYWHERE in the header (summary label + chevron), not only the chevron. The
		// header holds just the plain summary and this button — the tool's peek link lives in the body — so the
		// whole strip is a safe hit target. The <button> stays the keyboard control: its Enter/Space fires a
		// click that bubbles here. Only reached for a 'raw' (expandable) bubble; summaries returned above.
		header.style.cursor = 'pointer';
		header.addEventListener('click', e => {
			e.stopPropagation();
			note.classList.toggle('cerb-ui-agent-transcript--bubble-collapsed');
		});

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
