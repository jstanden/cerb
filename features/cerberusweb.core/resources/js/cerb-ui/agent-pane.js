/*
 * CerbUI.AgentPane — a collapsible LLM-agent chat sidebar that any editor host can mount.
 *
 * Wraps the host's existing content in a horizontal SplitPane whose second pane is an agent chat: a
 * "New Agent Chat" tile picker (built from a server-rendered `agent.pane` toolbar) that launches an
 * interaction INLINE into the chat body. The launched interaction reads/writes the host's live editor
 * through `uiCommand` awaits — the host supplies a `runCommand(name, params)` bridge that the tile's
 * `cerbBotTrigger` forwards as its `command` option, and the interaction's `ui_capabilities` names which
 * commands it may call.
 *
 * It owns ONLY the agent side (split, chat pane, tiles, command bridge, scroll delegation). The host keeps
 * its own editor / main-pane DOM; on construction the pane moves whatever is already inside `hostEl` into
 * the split's first pane. Named alongside CerbUI.AgentTranscript / CerbUI.AgentPrompt because it isn't
 * builder-specific — icon/data-query/chart/sheet builders and draft composers all mount the same pane.
 *
 * Usage:
 *   new CerbUI.AgentPane(hostEl, {
 *     component: 'icon',
 *     capabilities: 'get_geometry,set_geometry',
 *     toolbarHtml: '<ul class="cerb-ui-toolbar">…</ul>',
 *     storageKey: 'cerb-icon-builder-chat',
 *     toggleInto: someToolbarRowEl,           // optional; else an auto toggle-strip atop the main pane
 *     runCommand: (name, params) => …,        // read/write the live editor
 *   });
 *
 * Requires: CerbUI.SplitPane, CerbUI.Avatar, jQuery.fn.cerbBotTrigger, the cerb-agent-pane CSS.
 */
CerbUI.AgentPane = class {
	static _instances = new WeakMap();
	static from(el) { return CerbUI.AgentPane._instances.get(el); }

	// ── Unsaved-edit navigation guard ───────────────────────────────────────────
	// An agent's setField/editField write the host editor PROGRAMMATICALLY, which never trips the host's own
	// keystroke-based dirty check — so without this an accidental Cmd+[/back/reload/close silently loses the
	// agent's changes. A shared beforeunload guard is attached only while some pane holds unsaved agent edits
	// (a permanent beforeunload disables the bfcache), and it prunes panes whose host has left the DOM so a
	// closed popup can never guard forever.
	static _dirtyPanes = new Set();
	static _navHandler = null;

	static _looksLikeError(r) {
		return typeof r === 'string' && /^(error|unknown|invalid)\b/i.test(r);
	}

	static _pruneDirty() {
		for(const p of CerbUI.AgentPane._dirtyPanes)
			if(!p.host || (document.body && !document.body.contains(p.host)))
				CerbUI.AgentPane._dirtyPanes.delete(p);
	}

	static _syncNavGuard() {
		CerbUI.AgentPane._pruneDirty();
		const need = CerbUI.AgentPane._dirtyPanes.size > 0;

		if(need && !CerbUI.AgentPane._navHandler) {
			CerbUI.AgentPane._navHandler = (e) => {
				CerbUI.AgentPane._pruneDirty();
				if(!CerbUI.AgentPane._dirtyPanes.size) {   // nothing left → self-detach (restores bfcache)
					window.removeEventListener('beforeunload', CerbUI.AgentPane._navHandler);
					CerbUI.AgentPane._navHandler = null;
					return;
				}
				e.preventDefault();
				e.returnValue = '';
			};
			window.addEventListener('beforeunload', CerbUI.AgentPane._navHandler);
		} else if(!need && CerbUI.AgentPane._navHandler) {
			window.removeEventListener('beforeunload', CerbUI.AgentPane._navHandler);
			CerbUI.AgentPane._navHandler = null;
		}
	}

	constructor(hostEl, opts = {}) {
		this.host = (typeof hostEl === 'string') ? document.querySelector(hostEl) : hostEl;
		if(!this.host) return;

		this.opts = Object.assign({
			component: '',            // which editor this pane is mounted on → cerbBotTrigger caller.params.component
			capabilities: '',
			toolbarHtml: '',
			runCommand: null,
			onToggle: null,           // (collapsed) → host hook fired after the split toggles (e.g. widen a popup)
			fit: false,               // size to content when the chat is closed (popup hosts); bound only when open
			storageKey: 'cerb-agent-pane',
			chatTitle: 'New Agent Chat',
			toggleLabel: 'Agent',
			toggleInto: null,
			ratio: 0.68,
			min: 0.3,
			mutatingCommands: '',     // comma list of runCommand names that WRITE host state → flag the nav guard
		}, opts);

		this._mutating = new Set(String(this.opts.mutatingCommands || '')
			.split(',').map(s => s.trim()).filter(Boolean));
		this._dirty = false;

		this._buildDom();
		this._enhance();

		CerbUI.AgentPane._instances.set(this.host, this);
	}

	// ── DOM ─────────────────────────────────────────────────────────────────────
	// Wrap the host's existing content in an outer horizontal split whose second pane is a collapsible agent
	// chat. Done in JS (moving the existing nodes) so the host's own markup stays untouched.
	_buildDom() {
		this.host.classList.add('cerb-agent-pane');
		// Fit-content mode (popup hosts): size to the content when the chat is closed (no forced height/scrollbar),
		// only bounding the height while the chat is open so the transcript scrolls. Builders omit it → fixed height.
		if(this.opts.fit) this.host.classList.add('cerb-agent-pane--fit');

		const outer = document.createElement('div');
		outer.className = 'cerb-agent-pane--outer';

		const main = document.createElement('div');
		main.className = 'cerb-agent-pane--main';
		// Move whatever the host already rendered into the first (main) pane.
		while(this.host.firstChild)
			main.appendChild(this.host.firstChild);

		const chat = document.createElement('div');
		chat.className = 'cerb-agent-pane--chat';
		chat.innerHTML =
			'<div class="cerb-agent-pane--chat-head">' +
				'<div class="cerb-agent-pane--chat-title"><span class="cerb-icons cerb-icon-bot-message"></span> ' +
					this._escape(this.opts.chatTitle) +
				'</div>' +
				'<button type="button" class="cerb-ui-button cerb-ui-button--transparent cerb-agent-pane--chat-close" title="Close"><span class="cerb-icons cerb-icon-circle-remove"></span></button>' +
			'</div>' +
			'<div class="cerb-agent-pane--chat-body"></div>';

		outer.appendChild(main);
		outer.appendChild(chat);
		this.host.appendChild(outer);

		this.outerEl     = outer;
		this.mainEl      = main;
		this.chatEl      = chat;
		this.chatBodyEl  = chat.querySelector('.cerb-agent-pane--chat-body');
		this.chatCloseEl = chat.querySelector('.cerb-agent-pane--chat-close');

		// The toggle button — into a host-supplied anchor (e.g. an existing toolbar row) or an auto strip at
		// the top of the main pane.
		this.toggleEl = document.createElement('button');
		this.toggleEl.type = 'button';
		this.toggleEl.className = 'cerb-ui-button cerb-ui-button--subtle cerb-agent-pane--toggle';
		this.toggleEl.title = 'Toggle the agent panel';

		if(this.opts.toggleInto) {
			this.opts.toggleInto.appendChild(this.toggleEl);
		} else {
			const strip = document.createElement('div');
			strip.className = 'cerb-agent-pane--toggle-strip';
			strip.appendChild(this.toggleEl);
			main.insertBefore(strip, main.firstChild);
		}
	}

	_enhance() {
		this.outerSplit = new CerbUI.SplitPane(this.outerEl, {
			orientation: 'horizontal',
			ratio: this.opts.ratio,
			min: this.opts.min,
			collapsed: 'second',              // chat hidden until summoned
			storageKey: this.opts.storageKey,
			onToggle: () => this._onToggle(),
		});

		this.toggleEl.addEventListener('click', () => this.toggle());
		// Closing the chat resets it to the "New Agent Chat" selection (a new chat next open).
		this.chatCloseEl.addEventListener('click', () => { this.outerSplit.collapse('second'); this.showSelect(); });

		this._setupAgent();
		this._updateToggle();
	}

	// ── Agent panel ───────────────────────────────────────────────────────────
	// Fired after the outer split toggles: refresh the toggle button, then let the host react (e.g. a popup host
	// widening its dialog to make room for the chat). `collapsed` = chat currently hidden.
	_onToggle() {
		this._updateToggle();
		if(typeof this.opts.onToggle === 'function')
			this.opts.onToggle(this.isCollapsed());
	}

	toggle() {
		if(!this.outerSplit) return;
		if(this.outerSplit.isCollapsed()) this.outerSplit.expand();
		else this.outerSplit.collapse('second');
	}

	open()      { if(this.outerSplit) this.outerSplit.expand(); }
	collapse()  { if(this.outerSplit) this.outerSplit.collapse('second'); }
	isCollapsed() { return !this.outerSplit || this.outerSplit.isCollapsed(); }

	// If the toolbar has launchable items, show the "New Agent Chat" selection in the body and keep it pinned
	// to the bottom as an interaction re-renders; else hide the toggle (nothing to summon).
	_setupAgent() {
		const probe = document.createElement('div');
		probe.innerHTML = this.opts.toolbarHtml || '';
		this._hasItems = !!probe.querySelector('ul.cerb-ui-toolbar li[data-interaction-uri], ul.cerb-ui-toolbar li[data-behavior-id]');

		if(!this._hasItems) {
			this.toggleEl.style.display = 'none';
			return;
		}

		this.showSelect();

		// The chat body owns the scroll, so pin it to the bottom each time the interaction re-renders its form
		// (a `.cerb-form-data` content swap on send / tool loop). Filter to that target so composer typing —
		// deeper mutations — doesn't yank the scroll. Bound once; survives the select ↔ interaction body swaps.
		if(this.chatBodyEl && window.MutationObserver) {
			this._scrollObserver = new MutationObserver((mutations) => {
				if(mutations.some((m) => m.target && m.target.classList && m.target.classList.contains('cerb-form-data')))
					this._scrollToBottom();
			});
			this._scrollObserver.observe(this.chatBodyEl, { childList: true, subtree: true });
		}
	}

	// Render the interaction picker into the empty chat body (the "New Agent Chat" state) as cerb-ui tiles — a
	// colored CerbUI.Avatar (larger icon) beside the interaction's label. Built from the server-rendered
	// agent.pane toolbar items (already caller-policy-filtered). Starting an interaction replaces the whole
	// body (target = the body); closing restores this. Each tile carries the command bridge into what it launches.
	showSelect() {
		const $ = window.jQuery;
		if(!this.chatBodyEl || !this._hasItems) return;

		const src = document.createElement('div');
		src.innerHTML = this.opts.toolbarHtml || '';

		const select = document.createElement('div');
		select.className = 'cerb-agent-pane--select';

		src.querySelectorAll('ul.cerb-ui-toolbar > li[data-interaction-uri]').forEach((li) => {
			const iconAttr = li.getAttribute('data-icon') || '';
			// data-avatar-icon wants a bare cerb-icons name; a leading-dot data-icon is a raw class list — fall back.
			const icon = (iconAttr && iconAttr.charAt(0) !== '.') ? iconAttr : 'bot-message';
			const uri = li.getAttribute('data-interaction-uri') || '';
			const params = li.getAttribute('data-interaction-params') || '';
			const label = (li.textContent || '').trim() || 'Start a chat';

			const tile = document.createElement('div');
			tile.className = 'cerb-ui-tile cerb-agent-pane--tile';
			tile.setAttribute('role', 'button');
			tile.setAttribute('tabindex', '0');
			tile.setAttribute('data-interaction-uri', uri);
			if(params) tile.setAttribute('data-interaction-params', params);

			const avatar = document.createElement('span');
			avatar.className = 'cerb-ui-avatar';
			avatar.setAttribute('data-avatar-icon', icon);
			avatar.setAttribute('data-avatar-seed', uri || label);
			avatar.setAttribute('data-avatar-size', '40');

			const text = document.createElement('div');
			text.className = 'cerb-ui-tile--text';
			const name = document.createElement('div');
			name.className = 'cerb-ui-tile--name';
			name.textContent = label;
			text.appendChild(name);

			tile.appendChild(avatar);
			tile.appendChild(text);
			select.appendChild(tile);
		});

		this.chatBodyEl.innerHTML = '';
		this.chatBodyEl.appendChild(select);

		if(window.CerbUI && CerbUI.Avatar && CerbUI.Avatar.enhance)
			CerbUI.Avatar.enhance(select, '.cerb-ui-avatar');

		if($ && $.fn.cerbBotTrigger) {
			select.querySelectorAll('.cerb-agent-pane--tile').forEach((tile) => {
				$(tile).cerbBotTrigger({
					target: $(this.chatBodyEl),
					width: '100%',
					caller: this._caller(),
					command: (name, params) => this._runCommand(name, params),
				});
			});
		}

		this._loadHistory(select);
	}

	// The launcher identity. The server derives the continuation's `resume_scope` from this at launch and
	// requires the same value to reopen one, so a chat can only ever resume on a surface that can drive it.
	// `ui_capabilities` rides along for the interaction but is deliberately NOT part of the scope — it grows as
	// a host gains commands, and scoping on it would orphan history every time.
	_caller() {
		return {
			name: 'agent.pane',
			params: { component: this.opts.component, ui_capabilities: this.opts.capabilities },
		};
	}

	// Past conversations for THIS pane, appended under the launcher tiles. Fetched rather than rendered with the
	// select because it's a round trip and the tiles shouldn't wait on it; absent or empty, nothing shows at all.
	_loadHistory(select) {
		const $ = window.jQuery;
		if(!$ || typeof genericAjaxPost !== 'function') return;

		// showSelect() can run again (the × close resets to the picker) before this lands. Stamp the attempt so a
		// stale response can't graft history onto a newer screen.
		const gen = (this._selectGen = (this._selectGen || 0) + 1);
		const caller = this._caller();

		const fd = new FormData();
		fd.set('c', 'profiles');
		fd.set('a', 'invoke');
		fd.set('module', 'automation');
		fd.set('action', 'listInteractions');
		fd.set('caller[name]', caller.name);
		Object.keys(caller.params || {}).forEach((k) => fd.set('caller[params][' + k + ']', caller.params[k]));

		genericAjaxPost(fd, null, null, (json) => {
			const items = (json && json.items) ? json.items : [];

			if(gen !== this._selectGen || !items.length || !select.isConnected)
				return;

			const head = document.createElement('div');
			head.className = 'cerb-agent-pane--history-head';
			head.textContent = 'Recent conversations';
			select.appendChild(head);

			items.forEach((item) => {
				const tile = document.createElement('div');
				tile.className = 'cerb-ui-tile cerb-agent-pane--tile';
				tile.setAttribute('role', 'button');
				tile.setAttribute('tabindex', '0');

				const avatar = document.createElement('span');
				avatar.className = 'cerb-ui-avatar';
				avatar.setAttribute('data-avatar-icon', item.icon || 'history');
				avatar.setAttribute('data-avatar-seed', item.token || '');
				avatar.setAttribute('data-avatar-size', '40');

				const text = document.createElement('div');
				text.className = 'cerb-ui-tile--text';

				const name = document.createElement('div');
				name.className = 'cerb-ui-tile--name';
				name.textContent = item.label || 'Conversation';
				text.appendChild(name);

				if(item.description) {
					const sub = document.createElement('div');
					sub.className = 'cerb-ui-tile--body';
					sub.textContent = item.description;
					text.appendChild(sub);
				}

				tile.appendChild(avatar);
				tile.appendChild(text);

				const resume = () => {
					if(!(window.Devblocks && Devblocks.resumeInteraction)) return;

					Devblocks.resumeInteraction(item.token, {
						target: $(this.chatBodyEl),
						caller: caller,
						command: (name, params) => this._runCommand(name, params),
					});
				};

				tile.addEventListener('click', resume);
				tile.addEventListener('keydown', (e) => {
					if('Enter' === e.key || ' ' === e.key) { e.preventDefault(); resume(); }
				});

				select.appendChild(tile);
			});

			if(window.CerbUI && CerbUI.Avatar && CerbUI.Avatar.enhance)
				CerbUI.Avatar.enhance(select, '.cerb-ui-avatar');
		});
	}

	// Route an interaction's `uiCommand` await to the host's editor bridge.
	_runCommand(name, params) {
		if(typeof this.opts.runCommand !== 'function')
			return '';

		const result = this.opts.runCommand(name, params || {});

		// A command that WROTE host state (declared in `mutatingCommands`) and didn't return an error left
		// unsaved agent edits the host's keystroke-based dirty check never saw → arm the navigation guard.
		if(this._mutating.has(name) && !CerbUI.AgentPane._looksLikeError(result))
			this.markDirty();

		return result;
	}

	// The pane holds unsaved agent-applied edits. Also flips the enclosing CerbUI.Dialog's own dirty flag
	// (best-effort) so a host dialog that opted into `closeWarnOnUnsavedChanges` warns on its (×)/Esc close
	// too; the shared beforeunload guard here covers page navigation (Cmd+[/back/reload/tab-close).
	markDirty() {
		this._dirty = true;
		CerbUI.AgentPane._dirtyPanes.add(this);
		CerbUI.AgentPane._syncNavGuard();

		const dlgEl = (this.host && this.host.closest) ? this.host.closest('.cerb-ui-dialog') : null;
		if(dlgEl && window.CerbUI && CerbUI.Dialog && typeof CerbUI.Dialog.from === 'function') {
			const dlg = CerbUI.Dialog.from(dlgEl);
			if(dlg && typeof dlg.markDirty === 'function') dlg.markDirty();
		}
	}

	// The host saved (or closed) — the agent's edits are no longer unsaved here. Callers: host `peek_saved`.
	markClean() {
		this._dirty = false;
		CerbUI.AgentPane._dirtyPanes.delete(this);
		CerbUI.AgentPane._syncNavGuard();
	}

	isDirty() { return !!this._dirty; }

	// The Agent toggle reflects the pane state: closed → a leading left chevron (open it); open → a trailing
	// right chevron (hide it). The `bot-message` icon (same as the chat pane's title) leads the label in both.
	_updateToggle() {
		if(!this.toggleEl) return;
		const label = '<span class="cerb-icons cerb-icon-bot-message"></span> ' + this._escape(this.opts.toggleLabel);
		this.toggleEl.innerHTML = this.isCollapsed()
			? '<span class="cerb-icons cerb-icon-chevron-left"></span> ' + label
			: label + ' <span class="cerb-icons cerb-icon-chevron-right"></span>';
	}

	// Pin the chat scroll to the bottom: once after layout (rAF) and again shortly after, to catch late height
	// growth (avatar images / the transcript enhancer rebuilding turns).
	_scrollToBottom() {
		const pin = () => { if(this.chatBodyEl) this.chatBodyEl.scrollTop = this.chatBodyEl.scrollHeight; };
		requestAnimationFrame(pin);
		if(this._scrollTimer) clearTimeout(this._scrollTimer);
		this._scrollTimer = setTimeout(pin, 150);
	}

	_escape(str) {
		return String(str == null ? '' : str).replace(/[&<>"']/g, (c) =>
			({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
	}

	destroy() {
		this.markClean();   // drop the nav guard if this pane held one
		if(this.outerSplit && this.outerSplit.destroy) this.outerSplit.destroy();
		if(this._scrollObserver) this._scrollObserver.disconnect();
		if(this._scrollTimer) clearTimeout(this._scrollTimer);
		CerbUI.AgentPane._instances.delete(this.host);
	}
};
