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
 * TWO LAYOUTS, one of everything else:
 *   - SPLIT (default) -- for a host that's a big rectangle worth dividing (the builders, an editor popup).
 *   - FLOAT (`float:true`) -- the chat lives in a non-modal CerbUI.Dialog instead, and the host is never
 *     restructured at all. For a host that CAN'T be split: a one-line worklist search bar has no common
 *     wrapper around the field and the list, and a worklist is the widest thing on the page. Non-modal +
 *     minimizable is the point -- you keep working the list while the chat stays live. A side benefit worth
 *     knowing: the dialog lives at document.body, so the chat's form is never nested inside the host's.
 *
 * Usage:
 *   new CerbUI.AgentPane(hostEl, {
 *     component: 'icon',
 *     capabilities: 'getGeometry,setGeometry',
 *     toolbarHtml: '<ul class="cerb-ui-toolbar">…</ul>',
 *     storageKey: 'cerb-icon-builder-chat',
 *     toggleInto: someToolbarRowEl,           // optional; else an auto toggle-strip atop the main pane
 *     runCommand: (name, params) => …,        // read/write the live editor
 *   });
 *
 * Requires: CerbUI.SplitPane, CerbUI.Avatar, jQuery.fn.cerbBotTrigger, the cerb-agent-pane CSS.
 * Float mode additionally requires CerbUI.Dialog.
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
			float: false,             // chat in a non-modal CerbUI.Dialog instead of a split (unsplittable hosts)
			floatWidth: 460,          // float only: dialog width in px
			toggleEl: null,           // adopt this element as the toggle (its own markup is kept) instead of building one
			callerParams: null,       // extra caller.params merged under component/ui_capabilities (e.g. worklist_id)
			storageKey: 'cerb-agent-pane',
			chatTitle: 'New Agent Chat',
			toggleLabel: 'Agent',
			toggleInto: null,
			ratio: 0.68,
			min: 0.3,
			mutatingCommands: '',     // comma list of runCommand names that WRITE host state → flag the nav guard
		}, opts);

		this._float = !!this.opts.float;
		this.dialog = null;           // float only; built lazily on first open (see _ensureDialog)

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
	//
	// Float mode restructures NOTHING: no outer/main, no nodes moved (a one-line search bar has nothing sane to
	// split). The host element serves purely as the identity + DOM-liveness anchor -- `_instances`, `_pruneDirty`,
	// and markDirty's enclosing-dialog lookup all key off it -- while the chat subtree stays detached until
	// CerbUI.Dialog adopts it on first open.
	_buildDom() {
		let outer = null;
		let main = null;

		if(!this._float) {
			this.host.classList.add('cerb-agent-pane');
			// Fit-content mode (popup hosts): size to the content when the chat is closed (no forced height/scrollbar),
			// only bounding the height while the chat is open so the transcript scrolls. Builders omit it -> fixed height.
			if(this.opts.fit) this.host.classList.add('cerb-agent-pane--fit');

			outer = document.createElement('div');
			outer.className = 'cerb-agent-pane--outer';

			main = document.createElement('div');
			main.className = 'cerb-agent-pane--main';
			// Move whatever the host already rendered into the first (main) pane.
			while(this.host.firstChild)
				main.appendChild(this.host.firstChild);
		}

		const chat = document.createElement('div');
		chat.className = 'cerb-agent-pane--chat' + (this._float ? ' cerb-agent-pane--float' : '');
		chat.innerHTML =
			'<div class="cerb-agent-pane--chat-head">' +
				'<div class="cerb-agent-pane--chat-title"></div>' +
				'<button type="button" class="cerb-ui-button cerb-ui-button--transparent cerb-agent-pane--chat-close" title="Close"><span class="cerb-icons cerb-icon-circle-remove"></span></button>' +
			'</div>' +
			'<div class="cerb-agent-pane--chat-body"></div>';

		if(!this._float) {
			outer.appendChild(main);
			outer.appendChild(chat);
			this.host.appendChild(outer);
		}

		this.outerEl     = outer;
		this.mainEl      = main;
		this.chatEl      = chat;
		this.chatBodyEl  = chat.querySelector('.cerb-agent-pane--chat-body');
		this.chatCloseEl = chat.querySelector('.cerb-agent-pane--chat-close');
		this.chatTitleEl = chat.querySelector('.cerb-agent-pane--chat-title');

		this.setChatIdentity(null);

		// (In float mode the dialog's titlebar carries the title and the close/minimize controls, so the chat head
		// is hidden by the `--float` CSS rather than doubled up here.)

		// The toggle -- a host-supplied element adopted as-is (`toggleEl`, so the host keeps its own icon markup),
		// else a button built here and dropped into a host anchor (`toggleInto`) or an auto strip atop the main pane.
		this._ownToggle = !this.opts.toggleEl;

		if(!this._ownToggle) {
			this.toggleEl = this.opts.toggleEl;
		} else {
			this.toggleEl = document.createElement('button');
			this.toggleEl.type = 'button';
			this.toggleEl.className = 'cerb-ui-button cerb-ui-button--subtle cerb-agent-pane--toggle';
			this.toggleEl.title = 'Toggle the agent panel';

			if(this.opts.toggleInto) {
				this.opts.toggleInto.appendChild(this.toggleEl);
			} else if(main) {
				const strip = document.createElement('div');
				strip.className = 'cerb-agent-pane--toggle-strip';
				strip.appendChild(this.toggleEl);
				main.insertBefore(strip, main.firstChild);
			} else {
				// Float with neither toggleEl nor toggleInto -- there's no main pane to strip, so append to the host.
				this.host.appendChild(this.toggleEl);
			}
		}
	}

	_enhance() {
		if(!this._float) {
			this.outerSplit = new CerbUI.SplitPane(this.outerEl, {
				orientation: 'horizontal',
				ratio: this.opts.ratio,
				min: this.opts.min,
				collapsed: 'second',              // chat hidden until summoned
				storageKey: this.opts.storageKey,
				onToggle: () => this._onToggle(),
			});

			// Closing the chat resets it to the "New Agent Chat" selection (a new chat next open).
			this.chatCloseEl.addEventListener('click', () => {
				this._promptChatClose(this.chatCloseEl, () => { this.outerSplit.collapse('second'); this.showSelect(); });
			});
		}

		this.toggleEl.addEventListener('click', (e) => { e.preventDefault(); this.toggle(); });

		this._setupAgent();
		this._updateToggle();
	}

	// Float only: the chat's CerbUI.Dialog, built on first open rather than at construction. A search bar exists
	// on nearly every page (13 include sites), and most are never asked for an agent -- so don't put a hidden
	// dialog under document.body for each one just in case.
	//
	// Deliberately NOT given a `namespace`: Dialog treats one as a singleton identity, which would make a second
	// search bar's bot icon focus the FIRST bar's chat -- a chat wired to a different field's command bridge.
	// Each pane gets its own dialog (Dialog defaults the namespace to its uid).
	_ensureDialog() {
		if(this.dialog || !this._float || !(window.CerbUI && CerbUI.Dialog))
			return this.dialog;

		this.dialog = new CerbUI.Dialog(this.chatEl, {
			title: this.opts.chatTitle,
			header: 'bar',
			modal: false,           // keep working the host while the chat stays live -- the whole point of float
			minimizable: true,      // ...and dock it into the shared dialog tray rather than losing the transcript
			draggable: true,
			resizable: true,
			scrollBody: true,
			width: this.opts.floatWidth,
			closeOnEscape: false,   // Escape belongs to the composer being typed in, not to discarding the chat
		});

		// Float has no visible chat head -- the dialog's titlebar carries the close -- so the prompt hangs off the
		// dialog's own close instead, vetoing it until a choice is made. Same technique as
		// `Devblocks.decorateInteractionDialog()`, including the `_programmaticClose` guard: a close we fire
		// ourselves after the choice must pass straight through, or it would prompt forever.
		this.dialog.opts.onClose = () => {
			if(this.dialog._programmaticClose || this._chatCloseSettling)
				return undefined;

			if(!this._openContinuationToken())
				return undefined;

			const closeBtn = this.dialog.el
				? this.dialog.el.querySelector('.cerb-ui-dialog--btn[aria-label="Close"]')
				: null;

			this._promptChatClose(closeBtn || this.dialog.el, () => {
				this._chatCloseSettling = true;
				try { this.dialog.close(); } finally { this._chatCloseSettling = false; }
			});

			return false;
		};

		// Drive _onToggle off the dialog's own events rather than its onOpen/onClose hooks: `onClose` fires
		// BEFORE the dialog flips its open flag, so isCollapsed() would still report "open" to the host.
		this.chatEl.addEventListener('cerb-ui-dialog:open', () => this._onToggle());
		this.chatEl.addEventListener('cerb-ui-dialog:close', () => { this.showSelect(); this._onToggle(); });

		// A float chat is parented to document.body, not to the host -- so when the host itself lives in a popup
		// (a chooser, the quick-search popup), closing that popup would otherwise strand a live chat driving a
		// command bridge whose editor is gone. Resolved on first open, when the host is definitely placed.
		const hostDialogEl = this.host.closest ? this.host.closest('.cerb-ui-dialog') : null;
		if(hostDialogEl)
			hostDialogEl.addEventListener('cerb-ui-dialog:close', () => this.destroy());

		return this.dialog;
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
		if(this.isCollapsed()) this.open();
		else this.collapse();
	}

	open() {
		if(this._float) { const dlg = this._ensureDialog(); if(dlg) dlg.open(); return; }
		if(this.outerSplit) this.outerSplit.expand();
	}

	collapse() {
		if(this._float) { if(this.dialog) this.dialog.close(); return; }
		if(this.outerSplit) this.outerSplit.collapse('second');
	}

	isCollapsed() {
		if(this._float) return !this.dialog || !this.dialog.isOpen();
		return !this.outerSplit || this.outerSplit.isCollapsed();
	}

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

		// Back to the picker, so the head goes back to offering a new chat rather than naming the last one.
		this.setChatIdentity(null);

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
			// What this agent is for here -- its own per-surface line, or the surface's tagline
			// (`Launchers::getKata()`). A tile is otherwise a picture and a name, which tells you who but not
			// what for, and every agent on a surface looks equally plausible.
			const description = li.getAttribute('data-description') || '';

			const tile = document.createElement('div');
			tile.className = 'cerb-ui-tile cerb-agent-pane--tile';
			tile.setAttribute('role', 'button');
			tile.setAttribute('tabindex', '0');
			tile.setAttribute('data-interaction-uri', uri);
			if(params) tile.setAttribute('data-interaction-params', params);

			const avatar = document.createElement('span');
			avatar.className = 'cerb-ui-avatar';
			// An agent launcher carries the AGENT's picture, so a tile reads as who it is rather than as a
			// generic glyph. The icon stays as the fallback for a tile with no image (and for the monogram).
			const image = li.getAttribute('data-image') || '';
			if(image) avatar.setAttribute('data-avatar-image', image);
			avatar.setAttribute('data-avatar-icon', icon);
			avatar.setAttribute('data-avatar-seed', uri || label);
			avatar.setAttribute('data-avatar-size', '40');

			const text = document.createElement('div');
			text.className = 'cerb-ui-tile--text';
			const name = document.createElement('div');
			name.className = 'cerb-ui-tile--name';
			name.textContent = label;
			text.appendChild(name);

			if(description) {
				const body = document.createElement('div');
				body.className = 'cerb-ui-tile--body';
				body.textContent = description;
				text.appendChild(body);
			}

			tile.appendChild(avatar);
			tile.appendChild(text);

			// cerbBotTrigger owns the click that LAUNCHES; this only renames the head, so both run.
			tile.addEventListener('click', () => this.setChatIdentity({ label: label, image: image, icon: icon }));

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

	/*
	 * The chat head names WHO you're talking to, not what you were about to do.
	 *
	 * The picker's title is a call to action ("New Agent Chat"), which stops being true the moment a chat
	 * starts -- a running conversation with @cerb sat under a header offering to begin one. So launching a tile
	 * or resuming a row hands its own identity over, and returning to the picker (`showSelect`) hands back
	 * null. Float mode has no visible head of its own, so the dialog's titlebar carries the same name.
	 *
	 * `identity` is `{label, image, icon, badge}` -- `icon` being the glyph to show when there's no picture,
	 * and `badge` an optional `{icon, color}` corner mark. null restores the picker's title.
	 */
	setChatIdentity(identity) {
		if(!this.chatTitleEl) return;

		this._chatIdentity = identity || null;

		const label = (identity && identity.label) ? String(identity.label) : this.opts.chatTitle;

		this.chatTitleEl.replaceChildren();

		if(identity && identity.image) {
			// The agent's own face, with the model's mark demoted to a corner badge -- the same split the
			// transcript below it makes, so the head and the turns agree about who is speaking.
			this.chatTitleEl.appendChild(this._identityAvatar(identity, 24));

			// The head has to enhance its OWN avatar. `showSelect()` and `_loadHistory()` each run
			// `CerbUI.Avatar.enhance()` across the container they just filled, and the chat head is in neither --
			// so without this the `data-avatar-*` markup is correct and the picture simply never paints.
			if(window.CerbUI && CerbUI.Avatar && CerbUI.Avatar.enhance)
				CerbUI.Avatar.enhance(this.chatTitleEl, '.cerb-ui-avatar');
		} else {
			const glyph = document.createElement('span');
			glyph.className = 'cerb-icons cerb-icon-' + this._safeIcon((identity && identity.icon) || 'bot-message');
			this.chatTitleEl.appendChild(glyph);
		}

		const text = document.createElement('span');
		text.className = 'cerb-u-truncate';
		text.textContent = label;
		text.title = label;
		this.chatTitleEl.appendChild(text);

		if(this.dialog && typeof this.dialog.setTitle === 'function')
			this.dialog.setTitle(label);
	}

	/*
	 * An avatar for an identity that has a picture, badged with its model's mark when it carries one.
	 *
	 * Returns the BADGE WRAPPER when there's a badge and the bare avatar when there isn't, so a caller appends
	 * the result without caring which it got. `CerbUI.Avatar.enhance()` finds the avatar inside either.
	 */
	_identityAvatar(identity, size) {
		const avatar = document.createElement('span');
		avatar.className = 'cerb-ui-avatar';
		avatar.setAttribute('data-avatar', identity.label || '');
		avatar.setAttribute('data-avatar-image', identity.image);
		avatar.setAttribute('data-avatar-seed', identity.label || identity.image);
		avatar.setAttribute('data-avatar-size', String(size));

		const mark = identity.badge || null;

		// No mark to demote -- the picture stands alone. A launcher tile is this case: nothing has picked a
		// model yet, so there is nothing honest to badge it with.
		if(!mark || !mark.icon)
			return avatar;

		const wrap = document.createElement('span');
		wrap.className = 'cerb-avatar-badged';
		wrap.appendChild(avatar);

		const badge = document.createElement('span');
		badge.className = 'cerb-ui-pill cerb-ui-pill--circle';

		// The one place a font-size is set from code rather than inherited: `--circle` sizes itself in `em`, and
		// the avatar it pins to is sized in PIXELS by the caller. Inheriting would put the same badge on a 22px
		// head avatar and a 40px History row -- swallowing one and dwarfing the other. 0.3 keeps it at ~half the
		// avatar, which is the proportion the transcript's badge already reads at.
		badge.style.fontSize = (size * 0.3) + 'px';

		// A brand mark is drawn white on its own color everywhere else it appears (the transcript, the command
		// bar), so it's white here too rather than contrast-picked per color.
		if(mark.color) {
			badge.style.setProperty('--cerb-ui-pill-color', mark.color);
			badge.style.color = 'rgb(255,255,255)';
		}

		const glyph = document.createElement('span');
		glyph.className = 'cerb-icons cerb-icon-' + this._safeIcon(mark.icon);
		badge.appendChild(glyph);

		wrap.appendChild(badge);

		return wrap;
	}

	/* An icon name reaches the DOM as a class, so anything outside the set's own character range is refused. */
	_safeIcon(name) {
		return /^[a-z0-9-]+$/.test(String(name || '')) ? String(name) : 'bot-message';
	}

	// The launcher identity. The server derives the continuation's `resume_scope` from this at launch and
	// requires the same value to reopen one, so a chat can only ever resume on a surface that can drive it.
	// `ui_capabilities` rides along for the interaction but is deliberately NOT part of the scope — it grows as
	// a host gains commands, and scoping on it would orphan history every time.
	// `callerParams` lets a host name WHICH instance of its component this is (the worklist a search bar belongs
	// to, say). It lands in the continuation's `caller_params`, NOT `inputs` -- an item that wants these as inputs
	// declares them in its toolbar KATA off the server-side placeholders. `component`/`ui_capabilities` are
	// applied last so a host can never accidentally shadow the two keys the framework routes on.
	_caller() {
		return {
			name: 'agent.pane',
			params: Object.assign({}, this.opts.callerParams || {}, {
				component: this.opts.component,
				ui_capabilities: this.opts.capabilities,
			}),
		};
	}

	// Past conversations for THIS pane, appended under the launcher tiles. Fetched rather than rendered with the
	// select because it's a round trip and the tiles shouldn't wait on it; absent or empty, nothing shows at all.
	/*
	 * The close button's job depends on whether a conversation is live.
	 *
	 * With no chat running it just collapses the pane, as it always has. With a resumable one it first asks what
	 * to do with it -- the same Continue later / End choice a dialog-hosted interaction gets from
	 * `Devblocks.promptInteractionClose()`, in the same words and icons, because a worker should not have to
	 * learn that the sidebar's close means something different from the popup's.
	 *
	 * That is also what keeps the rule intact: the button itself never terminates a conversation. Continue later
	 * parks it exactly as an unanswered close always did; only End is terminal, and only when chosen.
	 *
	 * `proceed` is the close the host actually wanted -- run it after the choice is persisted, never before, or
	 * the panel is gone before its token can be read.
	 */
	_promptChatClose(anchorEl, proceed) {
		const token = this._openContinuationToken();

		// Nothing to persist -- no conversation, or one that cannot be resumed anyway.
		if(!token || !(window.CerbUI && CerbUI.Menu)) {
			proceed();
			return;
		}

		let settled = false;

		const restore = () => document.removeEventListener('keydown', onKeyCapture, true);

		const dispose = (disposition) => {
			if(settled) return;
			settled = true;

			restore();
			try { this._chatMenu.close(); } catch(e) {}

			this._disposeInteraction(token, this._chatLabel(), disposition, proceed);
		};

		// Escape while the menu is up parks it -- the safe default, and the same one the dialog uses. Capture, so
		// it beats the menu's own Escape handling and any focused input still in the panel.
		const onKeyCapture = (e) => {
			if('Escape' === e.key) {
				e.stopImmediatePropagation();
				e.preventDefault();
				dispose('pause');
			}
		};

		// Built once and reused: a fresh <ul> per close would leak one onto document.body every time.
		if(!this._chatMenu) {
			const ul = document.createElement('ul');
			ul.hidden = true;

			[
				{ disposition: 'pause', icon: 'stopwatch', label: 'Continue later' },
				{ disposition: 'end', icon: 'trash', label: 'End' },
			].forEach((it) => {
				const li = document.createElement('li');
				li.setAttribute('data-disposition', it.disposition);
				li.setAttribute('data-icon', it.icon);
				li.textContent = it.label;
				ul.appendChild(li);
			});

			document.body.appendChild(ul);

			this._chatMenuUl = ul;
			// The handler is rebound per open, since the token and the `proceed` differ each time.
			this._chatMenu = new CerbUI.Menu(ul, {
				onSelect: (renderedLi, sourceLi) => this._chatMenuOnSelect(sourceLi),
				// Dismissed without a pick (an outside click) -> leave the conversation as it was, but let go of
				// the capture listener, or a later Escape would park a conversation from a menu that is gone.
				onClose: () => { if(this._chatMenuOnClose) this._chatMenuOnClose(); },
				// Menu mirrors only data-* onto the rendered item and never reads an icon from the markup, so the
				// glyph has to be injected here -- same hook, same classes as the dialog's version of this menu.
				onRenderItem: (renderedLi, sourceLi) => {
					const icon = document.createElement('span');
					icon.className = 'cerb-icons cerb-u-mr-1 cerb-icon-' + sourceLi.dataset.icon;
					icon.setAttribute('aria-hidden', 'true');
					renderedLi.insertBefore(icon, renderedLi.firstChild);
				},
			});
		}

		// Rebound per open: one menu instance outlives many conversations, so the handlers have to close over
		// THIS open's token and `proceed`.
		this._chatMenuOnSelect = (sourceLi) => dispose(sourceLi.getAttribute('data-disposition'));
		this._chatMenuOnClose = restore;

		document.addEventListener('keydown', onKeyCapture, true);
		this._chatMenu.open(anchorEl || this.chatCloseEl);

		// Take focus off whatever in the panel had it -- the composer, usually -- so the keyboard drives the menu
		// rather than typing into a conversation being closed. Then pre-highlight the first item, so Enter picks
		// without an arrow press. Matches the dialog's version of this menu.
		if(document.activeElement && document.activeElement !== document.body
				&& typeof document.activeElement.blur === 'function')
			document.activeElement.blur();

		this._chatMenu.moveActive(1);
	}

	/*
	 * The open conversation's token, or ''. Both reads come from the panel the SERVER rendered:
	 * `__cerb_interaction_resumable` is its own answer to "can this be resumed", so an unfinished run or a
	 * non-resumable await closes silently rather than offering a choice that would not stick.
	 */
	_openContinuationToken() {
		if(!this.chatBodyEl) return '';

		if(!this.chatBodyEl.querySelector('input[name="__cerb_interaction_resumable"]'))
			return '';

		const input = this.chatBodyEl.querySelector('input[name="continuation_token"]');

		return input ? String(input.value || '') : '';
	}

	_chatLabel() {
		return this.chatTitleEl ? String(this.chatTitleEl.textContent || '').trim() : '';
	}

	/*
	 * End a conversation: `disposition: 'end'` sets the continuation's `state` to 'exit', which drops it from
	 * the resumable list. The TRANSCRIPT is untouched -- `disposeInteraction` never looks at llm_agent_session,
	 * so the conversation stays in Setup > Developers > LLM Agent Transcripts with its messages and usage.
	 * Clearing a chat here is tidying the sidebar, not deleting the record of it.
	 *
	 * The endpoint owns the permission check (worker_id, resumable extension, resume scope), so this passes the
	 * token and lets the server refuse.
	 */
	_disposeInteraction(token, label, disposition, done) {
		if(typeof genericAjaxPost !== 'function' || !token) return;

		const fd = new FormData();
		fd.set('c', 'profiles');
		fd.set('a', 'invoke');
		fd.set('module', 'automation');
		fd.set('action', 'disposeInteraction');
		fd.set('continuation_token', token);
		fd.set('disposition', ('pause' === disposition) ? 'pause' : 'end');
		fd.set('name', label || '');

		// Deliberately NOT Devblocks.flashInteractionButton() on a pause, which the dialog's version calls: that
		// points at the command bar, and a paused PANE conversation does not live there -- the command bar's scope
		// allowlist refuses an `agent.pane:` scope. It comes back under this pane's own Recent conversations.
		genericAjaxPost(fd, null, null, () => { if(typeof done === 'function') done(); });
	}

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

				// WHO over WHAT. A conversation with an agent wears the agent's face and demotes the model's mark
				// to a corner badge -- the same split the transcript makes, and the reason a History row is worth
				// scanning at all: every row here ran the same model, and none of them ran the same agent.
				let avatar;

				if(item.image) {
					avatar = this._identityAvatar({
						label: item.label || '',
						image: item.image,
						badge: item.icon ? { icon: item.icon, color: item.color || '' } : null,
					}, 40);
				} else {
					avatar = document.createElement('span');
					avatar.className = 'cerb-ui-avatar';
					avatar.setAttribute('data-avatar-icon', item.icon || 'history');
					avatar.setAttribute('data-avatar-seed', item.token || '');
					avatar.setAttribute('data-avatar-size', '40');
					// A conversation that declared its own color (its provider's brand mark, usually) keeps it; one
					// that didn't falls back to the token-seeded hash, so rows stay visually distinct either way.
					if(item.color)
						avatar.setAttribute('data-avatar-color', item.color);
				}

				const text = document.createElement('div');
				text.className = 'cerb-ui-tile--text';

				const name = document.createElement('div');
				name.className = 'cerb-ui-tile--name';
				name.textContent = item.label || 'Conversation';
				text.appendChild(name);

				if(item.preview) {
					const preview = document.createElement('div');
					preview.className = 'cerb-ui-tile--body cerb-agent-pane--tile-preview';
					preview.textContent = item.preview;
					text.appendChild(preview);
				}

				if(item.description) {
					const sub = document.createElement('div');
					sub.className = 'cerb-ui-tile--body cerb-agent-pane--tile-meta';
					sub.textContent = item.description;
					text.appendChild(sub);
				}

				tile.appendChild(avatar);
				tile.appendChild(text);

				const resume = () => {
					if(!(window.Devblocks && Devblocks.resumeInteraction)) return;

					this.setChatIdentity({
						label: item.label || '',
						image: item.image || '',
						icon: item.icon || 'history',
						badge: (item.image && item.icon) ? { icon: item.icon, color: item.color || '' } : null,
					});

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

				// Dismiss: ends this conversation so it stops coming back. Its own button rather than a gesture on
				// the tile, because the tile's whole surface already means "resume this" -- and it stops the click
				// there, or dismissing would resume the thing it just ended.
				const dismiss = document.createElement('button');
				dismiss.type = 'button';
				dismiss.className = 'cerb-ui-button cerb-ui-button--transparent cerb-agent-pane--tile-dismiss';
				dismiss.title = 'End this conversation';
				dismiss.setAttribute('aria-label', 'End this conversation');
				dismiss.innerHTML = '<span class="cerb-icons cerb-icon-remove"></span>';

				dismiss.addEventListener('click', (e) => {
					e.stopPropagation();
					this._disposeInteraction(item.token, item.label || '', 'end', () => tile.remove());
				});

				tile.appendChild(dismiss);

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
	//
	// A host-supplied toggle keeps its OWN markup -- it's the host's icon in the host's layout (a search bar's
	// trailing bot glyph), so rewriting innerHTML would replace it with a chevron+label that doesn't fit there.
	// It gets a state class instead.
	_updateToggle() {
		if(!this.toggleEl) return;

		if(!this._ownToggle) {
			this.toggleEl.classList.toggle('cerb-agent-pane--toggle--active', !this.isCollapsed());
			return;
		}

		const label = '<span class="cerb-icons cerb-icon-bot-message"></span> ' + this._escape(this.opts.toggleLabel);
		this.toggleEl.innerHTML = this.isCollapsed()
			? '<span class="cerb-icons cerb-icon-chevron-left"></span> ' + label
			: label + ' <span class="cerb-icons cerb-icon-chevron-right"></span>';
	}

	// Pin the chat scroll to the bottom: once after layout (rAF) and again shortly after, to catch late height
	// growth (avatar images / the transcript enhancer rebuilding turns).
	//
	// Only when the reader is already at the bottom. This fires on every interaction re-render -- each tool
	// call in a loop -- so pinning unconditionally drags anyone who scrolled up to re-read something. The chat
	// body survives those re-renders, so a detached reader simply keeps the offset they had.
	_scrollToBottom() {
		if(!this.chatBodyEl)
			return;

		// Idempotent, and the body outlives every re-render, so this binds once.
		if(window.CerbUI && CerbUI.AgentTranscript)
			CerbUI.AgentTranscript.trackStick(this.chatBodyEl);

		const pin = () => {
			if(!this.chatBodyEl) return;

			// The transcript component owns this rule; fall back to always pinning if it isn't loaded (a host
			// with a chat but no transcript element).
			if(window.CerbUI && CerbUI.AgentTranscript)
				CerbUI.AgentTranscript.stickToBottom(this.chatBodyEl);
			else
				this.chatBodyEl.scrollTop = this.chatBodyEl.scrollHeight;
		};

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
		// A float chat outlives its host's DOM otherwise -- the dialog lives at document.body, so a closed popup
		// would leave a live chat behind holding a dead command bridge.
		if(this.dialog && this.dialog.destroy) this.dialog.destroy();
		if(this._scrollObserver) this._scrollObserver.disconnect();
		if(this._scrollTimer) clearTimeout(this._scrollTimer);
		// The overflow menu's <ul> is parented to document.body, so it outlives the pane unless removed here.
		if(this._chatMenuUl) this._chatMenuUl.remove();
		CerbUI.AgentPane._instances.delete(this.host);
	}
};
