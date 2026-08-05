/*
 * CerbUI.Toolbar — a plain-JS strip of icon buttons + pop-up menus that open worker interactions.
 *
 * This is the standalone successor to the jQuery `$.fn.cerbToolbar()` plugin (cerberus.js): it does the
 * same job — render a toolbar strip, pop nested menus, fire interactions — but with no jQuery UI. The
 * first level of the source list is the strip; any item that has a child <ul> becomes a trigger that
 * opens a CerbUI.Menu (submenus cascade there). Firing is delegated to the existing $.fn.cerbBotTrigger
 * (the startInteraction AJAX + await-popup flow), so real interactions keep working unchanged.
 *
 * Markup (progressive enhancement of a nested ul/li — the component hides the source <ul> and builds the
 * visible strip after it):
 *   <ul class="cerb-ui-toolbar" id="tb">
 *     <li data-icon="bold" title="Bold" data-interaction-uri="cerb:automation:demo.bold"></li>
 *     <li></li>                                          <!-- empty top-level li = a strip divider -->
 *     <li data-icon="magic" data-label="Generate">       <!-- has a child ul → a menu trigger -->
 *       <ul>
 *         <li data-icon="sparkles" data-interaction-uri="cerb:automation:demo.nl2query">From text…</li>
 *         <li></li>                                       <!-- empty li = menu separator -->
 *         <li data-icon="bookmark" data-label="Presets">  <!-- nested submenu (CerbUI.Menu cascades) -->
 *           <ul><li data-value="status:o">Open</li><li data-value="status:w">Waiting</li></ul>
 *         </li>
 *       </ul>
 *     </li>
 *     <li hidden data-icon="lock">…</li>                  <!-- `hidden`/.is-hidden item is omitted -->
 *   </ul>
 *
 * Per-item data-* attributes (mirror CerbUI.Menu so the two compose):
 *   (label)      the <li>'s OWN text is the label — same as CerbUI.Menu (NOT data-label, which Menu ignores);
 *                data-label is only a fallback for an icon-only <li> with no text. In `bare` mode the label
 *                rides as the tooltip so the strip stays icon-only.
 *   data-icon    bare name → `cerb-icons cerb-icon-<name>`; leading '.' → raw class list
 *   data-icon-at `end` renders the icon after the label (default: icon-first)
 *   data-class   extra CSS class(es) copied onto the rendered button (e.g. `action-always-show`)
 *   title        tooltip; data-keyboard  shortcut hint shown in menus
 *   data-badge   a count bubble (floating pill) on the strip button; data-badge-color tints it (else accent)
 *   data-value   arbitrary value surfaced to onSelect (non-interaction items, e.g. presets)
 *   data-interaction-uri / -params / -done  fire an interaction via cerbBotTrigger on select
 *   data-toggle  client-state toggle button: clicking flips its pressed state (aria-pressed +
 *                .cerb-ui-toolbar--item-active) and calls onSelect with the NEW item.pressed — it
 *                never fires an interaction. Independent (not a radio group); give it a stable
 *                data-key so host JS can drive it via setPressed(key,on)/isPressed(key).
 *   data-pressed initial pressed state for a data-toggle item (data-pressed="0"/"false" = off)
 *   hidden (attribute) or class `cerb-ui-toolbar--hidden`  omit the item (role / record-type gating)
 *   data-hover (on a menu item)  open its menu on hover instead of click
 *
 * Usage:
 *   new CerbUI.Toolbar(document.getElementById('tb'), {
 *     bare: false,                                  // false = strip chrome; true = icons + menus only;
 *                                                   //   'tiny' (or tiny:true) = small muted icons (searchquery --right look)
 *     overflow: 'wrap',                             // too-wide: 'wrap' rows (default) | 'menu' collapse trailing
 *                                                   //   items into a '…' more-vertical menu (one row) | 'none' clip
 *     onSelect: (item, sourceLi, e) => { ... },     // item = {key,value,label,interactionUri,interactionParams,toggle,pressed}
 *                                                   //   sourceLi = the original <li> (read arbitrary data-attrs or
 *                                                   //   classes, e.g. .cerb-bot-trigger) — item.value is its data-value
 *     sections: [otherUl, '#more'],                 // MERGE extra source <ul>s into one strip (divider between) — hybrid
 *     caller: { name: 'cerb.toolbar.demo', params: {} },  // passed through to cerbBotTrigger
 *     target: null, width: '50%',                   // target=jQuery el → inline interaction; null → popup
 *     start, done, error, reset                     // cerbBotTrigger lifecycle callbacks
 *   });
 *   // Toggle buttons (<li data-toggle data-key="placeholders">): read/drive pressed state with
 *   //   tb.isPressed('placeholders')  and  tb.setPressed('placeholders', true)
 *
 * CSS lives in cerb.css (.cerb-ui-toolbar--*) — this component never injects styles.
 */
CerbUI.Toolbar = class {
	static _instances = new WeakMap();
	static from(el) { return CerbUI.Toolbar._instances.get(el); }

	static _DEFAULTS = {
		bare: false,        // trimless variant: icon-only buttons, no strip chrome. true | 'tiny'
		tiny: false,        // small muted icons (implies bare); same as bare:'tiny'
		onSelect: null,     // (item, sourceLi, event) on any leaf activation (strip button or menu leaf)
		caller: null,       // { name, params } passed through to cerbBotTrigger
		mode: 'popup',      // parity with legacy cerbToolbar; 'popup' unless a target is given
		target: null,       // a jQuery element → render the interaction inline into it (else a popup)
		width: '50%',       // await-popup width
		hover: false,       // open every menu on hover (per-item data-hover overrides for one item)
		overflow: 'wrap',   // too-wide handling: 'wrap' = flow to multiple rows; 'menu' = collapse trailing items
		                    //   into a trailing '…' more-vertical menu (single row, ResizeObserver); 'none' = clip
		badgeStyle: 'pill', // data-badge rendering: 'pill' = floating corner alert; 'count' = calm leading inline tally
		start: null,        // (formData) cerbBotTrigger hook — append caller params just-in-time
		done: null,         // (event) interaction finished
		error: null,        // (event) interaction errored
		reset: null,        // (event) interaction reset
		command: null,      // (name, params) → value|Promise — the UI-command bridge for `uiCommand` awaits
		selectableParents: false, // menu items with a submenu are ALSO selectable (click = onSelect; hover = expand)
		sections: null,     // additional source <ul>s (elements or selectors) to MERGE into this one strip, each
		                    //   preceded by a divider — build a hybrid toolbar from several authored/record-rendered
		                    //   <ul class="cerb-ui-toolbar">s. Their <li>s move into this list (consumed once at construct).
	};

	constructor(el, opts = {}) {
		el = (typeof el === 'string') ? document.querySelector(el) : el;
		if(!el) return;

		// Idempotent: re-enhancing a source <ul> that already has an instance (e.g. a re-rendered widget
		// toolbar constructed again on the same list) tears down the prior strip first so we don't stack two.
		const prior = CerbUI.Toolbar._instances.get(el);
		if(prior && prior !== this && typeof prior.destroy === 'function') prior.destroy();

		this.el = el;
		this.opts = Object.assign({}, CerbUI.Toolbar._DEFAULTS, opts);
		// tiny implies bare; either can come from the option (bare:'tiny' / tiny:true) or the source class.
		this.tiny = this.opts.bare === 'tiny' || this.opts.tiny || el.classList.contains('cerb-ui-toolbar--bare-tiny');
		this.bare = this.tiny || !!this.opts.bare || el.classList.contains('cerb-ui-toolbar--bare');
		this.menus = new Map();   // top-level item key -> CerbUI.Menu (built lazily)
		this._toggles = new Map(); // toggle item key -> item descriptor (carries its rendered .btn)
		this.strip = null;        // the rendered visible strip
		this._hoverGroup = 'cerb-ui-toolbar-' + (CerbUI.Toolbar._seq = (CerbUI.Toolbar._seq || 0) + 1);
		// overflow:'menu' state
		this._ro = null;           // ResizeObserver on the strip's container
		this._overflowBtn = null;  // the trailing '…' button
		this._overflowUl = null;   // the '…' menu's source <ul> (overflowed source <li>s move in/out)
		this._overflowMenu = null; // the rebuilt-per-reflow CerbUI.Menu
		this._reflowEntries = [];  // [{ el, overflowable, item }] in strip order (buttons + dividers)
		this._sourceOrder = null;  // the authored <li>s in original order (to restore after moving)
		this._reflowing = false;   // re-entrancy guard for the ResizeObserver callback

		CerbUI.Toolbar._instances.set(el, this);

		// Hybrid: fold any extra source <ul>s into this one (a divider between sections) BEFORE the first render,
		// so refresh()/overflow keep operating on the single merged source list.
		if(Array.isArray(this.opts.sections) && this.opts.sections.length)
			this._mergeSections(this.opts.sections);

		this._render();
	}

	// Move the <li>s of each additional source <ul> into this list, each section led by a divider. Lets a host
	// compose a hybrid strip from several authored/record-rendered cerb-ui-toolbar <ul>s (e.g. an editor's built-in
	// formatting + a worker-configured toolbar section). Each section <ul> is consumed (emptied + hidden) once.
	_mergeSections(sections) {
		sections.forEach(src => {
			const ul = (typeof src === 'string') ? document.querySelector(src) : src;
			if(!ul || ul === this.el || !ul.children) return;
			const lis = Array.from(ul.children).filter(n => n instanceof HTMLLIElement);
			if(!lis.length) return;
			if(this.el.children.length && !this._isDividerLi(this.el.lastElementChild))
				this.el.appendChild(document.createElement('li')); // divider between sections
			lis.forEach(li => this.el.appendChild(li));             // move (not clone) — keeps interaction bindings/attrs
			if(ul.parentNode) ul.hidden = true;
		});
	}

	_isDividerLi(li) {
		return li instanceof HTMLLIElement && li.children.length === 0 && li.textContent.trim() === ''
			&& !li.dataset.icon && !li.dataset.label;
	}

	// ── Public API ──────────────────────────────────────────────────────

	// Re-read the source list (badges / hidden may have changed) and rebuild the strip.
	refresh() {
		this._teardown();
		this._render();
		return this;
	}

	destroy() {
		this._teardown();
		this.el.hidden = false;
		CerbUI.Toolbar._instances.delete(this.el);
	}

	// Is the data-toggle item with this data-key currently pressed?
	isPressed(key) {
		const item = this._toggles.get(key);
		return item ? !!item.pressed : false;
	}

	// Drive a data-toggle item's pressed state. Mirrors CerbUI.Switcher.setValue's {fireCallback}
	// convention — silent by default so host-side syncs don't re-enter onSelect.
	setPressed(key, on, opts = {}) {
		const item = this._toggles.get(key);
		if(!item) return this;

		on = !!on;
		item.pressed = on;
		this._setPressed(item.btn, on);

		if(item.sourceLi) {
			if(on) item.sourceLi.setAttribute('data-pressed', '1');
			else item.sourceLi.removeAttribute('data-pressed');
		}

		if(opts.fireCallback && typeof this.opts.onSelect === 'function')
			this.opts.onSelect(item, item.sourceLi, null);

		return this;
	}

	// Fire the item bound to a keyboard shortcut (e.g. from a host keydown handler). The shortcut hook
	// (data-interaction-keyboard) lives on the source <li>; clicking it fires the interaction via
	// cerbBotTrigger. Returns true if a matching item was found.
	triggerShortcut(keys) {
		if(keys == null) return false;
		const li = this.el.querySelector('[data-interaction-keyboard="' + keys + '"]');
		if(!li) return false;
		if(window.jQuery) jQuery(li).trigger('click'); else li.click();
		return true;
	}

	// ── Build ───────────────────────────────────────────────────────────

	_render() {
		// Hide the authored <ul> — it stays in the DOM as the data + menu source.
		this.el.hidden = true;

		const items = this._readItems(this.el);

		// Nothing to show (an empty toolbar, or every item hidden/divider) — don't render an empty strip
		// frame. A later refresh() re-reads and renders once real items exist.
		if(!items.some(it => !it.hidden && !it.divider)) {
			this.strip = null;
			return;
		}

		// Snapshot the authored <li> order so _restoreOverflow can put moved-out items back exactly.
		this._sourceOrder = Array.from(this.el.children);

		const strip = document.createElement('div');
		strip.className = 'cerb-ui-toolbar--strip'
			+ (this.bare ? ' cerb-ui-toolbar--bare' : '')
			+ (this.tiny ? ' cerb-ui-toolbar--bare-tiny' : '')
			+ (this.opts.overflow === 'menu' ? ' cerb-ui-toolbar--strip-overflow-menu' : '')
			+ (this.opts.overflow === 'none' ? ' cerb-ui-toolbar--strip-nowrap' : '')
			+ (this.opts.badgeStyle === 'count' ? ' cerb-ui-toolbar--badge-count' : '');
		this.strip = strip;

		// Bind firing once per interaction <li> anywhere in the tree (strip + nested menus).
		this._bindInteractions(this.el);

		this._reflowEntries = [];

		items.forEach(item => {
			if(item.hidden) return;

			if(item.divider) {
				const sep = document.createElement('span');
				sep.className = 'cerb-ui-toolbar--divider';
				strip.appendChild(sep);
				this._reflowEntries.push({ el: sep, overflowable: true, item: null });
				return;
			}

			const btn = this._mkButton(item);
			strip.appendChild(btn);
			// Toggles are pinned (their pressed state lives on the strip button — they never collapse).
			this._reflowEntries.push({ el: btn, overflowable: !item.toggle, item: item });
		});

		// Backstop: drop a strip orphaned right after this source list by a prior render whose instance
		// was lost (the source <ul> was re-inserted but its old rendered strip lingered) — avoids a double.
		let staleSib = this.el.nextElementSibling;
		if(staleSib && staleSib.classList && staleSib.classList.contains('cerb-ui-toolbar--strip'))
			staleSib.remove();

		this.el.insertAdjacentElement('afterend', strip);

		if(this.opts.overflow === 'menu')
			this._setupOverflow();
	}

	// ── Overflow ('menu') ───────────────────────────────────────────────

	// Append the trailing '…' button + its (hidden) menu source <ul>, watch the container, and lay out once.
	_setupOverflow() {
		this._overflowBtn = this._mkOverflowButton();
		this._overflowBtn.style.display = 'none';
		this.strip.appendChild(this._overflowBtn);

		this._overflowUl = document.createElement('ul');
		this._overflowUl.hidden = true;
		this.strip.insertAdjacentElement('afterend', this._overflowUl);

		if(window.ResizeObserver && this.strip.parentElement) {
			this._ro = new ResizeObserver(() => this._reflow());
			this._ro.observe(this.strip.parentElement);
		}

		this._reflow();
	}

	_mkOverflowButton() {
		const btn = document.createElement('button');
		btn.type = 'button';
		btn.className = 'cerb-ui-toolbar--item cerb-ui-toolbar--item-overflow';
		btn.title = 'More';
		const ico = this._mkIcon('more-vertical');
		if(ico) btn.appendChild(ico);
		btn.addEventListener('click', (e) => {
			e.stopPropagation();
			if(!this._overflowMenu) return;
			this._overflowMenu.isOpen() ? this._overflowMenu.close() : this._overflowMenu.open(btn);
		});
		return btn;
	}

	// Measure the container; collapse the trailing items that don't fit into the '…' menu. The overflowed
	// SOURCE <li>s are moved into the menu's <ul> (cerbBotTrigger + submenus stay intact, sourceLi stays
	// real). CerbUI.Menu snapshots its source, so the menu is rebuilt each pass. Runs on render + on resize.
	_reflow() {
		if(this.opts.overflow !== 'menu' || !this.strip || !this._overflowBtn || this._reflowing) return;
		this._reflowing = true;

		try {
			// Reset to all-visible (move any overflowed <li>s back, drop the old menu, show every button).
			this._restoreOverflow();
			this._reflowEntries.forEach(en => { en.el.style.display = ''; });
			this._overflowBtn.style.display = 'none';

			const parent = this.strip.parentElement;
			const budget = parent ? parent.clientWidth : 0;
			if(budget <= 0) return; // not laid out yet — a later resize fires this again

			const gap = 2;
			let total = 0;
			this._reflowEntries.forEach(en => { total += en.el.offsetWidth + gap; });
			if(total <= budget) return; // everything fits — no '…' needed

			// Reserve room for the '…' button, then collapse the trailing overflowable items.
			this._overflowBtn.style.display = '';
			const reserve = this._overflowBtn.offsetWidth + gap;

			let used = 0;
			let cut = false;
			const overflowItems = [];
			for(const en of this._reflowEntries) {
				const w = en.el.offsetWidth + gap;
				if(!cut && used + w > budget - reserve) cut = true;

				if(cut && en.overflowable) {
					en.el.style.display = 'none';
					if(en.item) overflowItems.push(en.item); // dividers just hide (no menu row)
				} else {
					used += w; // visible: still fits, or a pinned (toggle) item
				}
			}

			if(!overflowItems.length) {
				this._overflowBtn.style.display = 'none';
				return;
			}

			overflowItems.forEach(it => this._overflowUl.appendChild(it.sourceLi));

			if(window.CerbUI && CerbUI.Menu) {
				this._overflowMenu = new CerbUI.Menu(this._overflowUl, {
					onRenderItem: (li, src) => {
						const ico = this._mkIcon(src.dataset.icon);
						if(!ico) return;
						ico.style.marginRight = '0.5em';
						li.insertBefore(ico, li.firstChild);
					},
					onSelect: (renderedLi, sourceLi, e) => this._activate(sourceLi, this._itemForLi(sourceLi), e),
				});
			}
		} finally {
			this._reflowing = false;
		}
	}

	// Tear down the '…' menu and return every authored <li> to the source <ul> in its original order.
	_restoreOverflow() {
		if(this._overflowMenu) { this._overflowMenu.destroy(); this._overflowMenu = null; }
		if(this._sourceOrder)
			// Re-append in snapshot order to restore any overflow-moved <li>s. Skip <li>s the HOST removed from the
			// source since the last render (li.remove() only detaches → isConnected=false) — else a refresh() would
			// resurrect them, so a host that splices+refreshes a shared source ends up accumulating stale items.
			this._sourceOrder.forEach(li => { if(li.isConnected) this.el.appendChild(li); });
	}

	// Read only the TOP-LEVEL <li> of a source <ul> into a flat model.
	_readItems(ul) {
		const out = [];
		let idx = 0;

		for(const li of ul.children) {
			if(!(li instanceof HTMLLIElement)) continue;

			const childUl = li.querySelector(':scope > ul');
			const hasOwnText = this._directText(li) !== '';

			// An empty top-level li (no text, no submenu) is a strip divider. Honor `hidden` so a divider can
			// hide/show with the group it separates (e.g. a format-button group toggled off in plaintext mode).
			if(!hasOwnText && !childUl && !li.dataset.icon && !li.dataset.label) {
				out.push({ divider: true, hidden: li.hidden || li.classList.contains('cerb-ui-toolbar--hidden') });
				continue;
			}

			out.push({
				key: li.dataset.key || ('i' + (idx++)),
				sourceLi: li,
				childUl: childUl || null,
				icon: li.dataset.icon || null,
				iconAt: li.dataset.iconAt || null,
				cssClass: li.dataset.class || null,
				// Label = the li's own text (matches CerbUI.Menu); data-label is only a fallback for icon-only items.
				label: this._directText(li) || li.dataset.label || null,
				tooltip: li.getAttribute('title') || null,
				keyboard: li.dataset.keyboard || null,
				badge: li.dataset.badge || null,
				badgeColor: li.dataset.badgeColor || null,
				value: li.dataset.value || null,
				interactionUri: li.dataset.interactionUri || null,
				interactionParams: li.dataset.interactionParams || null,
				toggle: li.hasAttribute('data-toggle'),
				pressed: li.hasAttribute('data-pressed') && li.getAttribute('data-pressed') !== '0' && li.getAttribute('data-pressed') !== 'false',
				hover: this.opts.hover || li.hasAttribute('data-hover'),
				hidden: li.hidden || li.classList.contains('cerb-ui-toolbar--hidden'),
			});
		}

		return out;
	}

	_mkButton(item) {
		const btn = document.createElement('button');
		btn.type = 'button';
		btn.className = 'cerb-ui-toolbar--item';

		// Per-item custom classes (e.g. `action-always-show`) ride onto the rendered button so
		// any custom/admin CSS keyed off them keeps matching — same as the legacy <button> did.
		if(item.cssClass)
			btn.classList.add(...item.cssClass.split(/\s+/).filter(Boolean));

		const tooltip = item.tooltip || (this.bare ? item.label : null);
		if(tooltip) btn.title = tooltip + (item.keyboard ? ' (' + item.keyboard + ')' : '');
		// The `data-interaction-keyboard` shortcut hook stays on the SOURCE <li> (server-rendered, present
		// immediately) — host keydown dispatchers find it there and click it (firing cerbBotTrigger), so it
		// doesn't depend on this button having been built yet. We don't duplicate it onto the button.

		if(item.badge != null) {
			const badge = document.createElement('span');
			badge.className = 'cerb-ui-toolbar--badge';
			badge.textContent = item.badge;
			if(item.badgeColor) badge.style.backgroundColor = item.badgeColor; // else the SCSS accent
			btn.appendChild(badge);
		}

		const ico = this._mkIcon(item.icon);
		// In the bare variant the label rides as a tooltip, so the strip stays icon-only.
		const labelNode = (item.label && !this.bare) ? document.createTextNode(item.label) : null;

		// `icon_at: end` renders the icon after the label (else icon-first, the default).
		if(ico && item.iconAt === 'end') {
			if(labelNode) btn.appendChild(labelNode);
			btn.appendChild(ico);
		} else {
			if(ico) btn.appendChild(ico);
			if(labelNode) btn.appendChild(labelNode);
		}

		if(item.childUl) {
			btn.classList.add('cerb-ui-toolbar--item-has-menu');
			const caret = document.createElement('span');
			caret.className = 'cerb-ui-toolbar--caret cerb-icons cerb-icon-chevron-down';
			caret.setAttribute('aria-hidden', 'true');
			btn.appendChild(caret);

			if(item.hover) this._buildMenu(item, btn); // eager so hover-open is wired
			btn.addEventListener('click', (e) => { e.stopPropagation(); this._toggleMenu(item, btn); });
		} else if(item.toggle) {
			// A client-state toggle: flip pressed, mirror onto the source <li> (so refresh() keeps it),
			// notify onSelect with the new state. No interaction is fired.
			btn.classList.add('cerb-ui-toolbar--item-toggle');
			item.btn = btn;
			this._setPressed(btn, item.pressed);
			this._toggles.set(item.key, item);

			btn.addEventListener('click', (e) => {
				e.stopPropagation();
				this.setPressed(item.key, !item.pressed);
				if(typeof this.opts.onSelect === 'function')
					this.opts.onSelect(item, item.sourceLi, e);
			});
		} else {
			btn.addEventListener('click', (e) => { e.stopPropagation(); this._activate(item.sourceLi, item, e); });
		}

		return btn;
	}

	// Reflect a toggle button's pressed state into the DOM (aria + the active wash).
	_setPressed(btn, on) {
		btn.setAttribute('aria-pressed', on ? 'true' : 'false');
		btn.classList.toggle('cerb-ui-toolbar--item-active', !!on);
	}

	// bare name -> cerb-icons glyph; a leading '.' means raw class list (same rule as CerbUI.Menu).
	_mkIcon(name) {
		if(!name) return null;
		const span = document.createElement('span');
		span.className = (name.charAt(0) === '.')
			? name.slice(1).split('.').join(' ')
			: ('cerb-icons cerb-icon-' + name);
		span.setAttribute('aria-hidden', 'true');
		return span;
	}

	// ── Menus ───────────────────────────────────────────────────────────

	_buildMenu(item, btn) {
		if(this.menus.has(item.key) || !item.childUl || !(window.CerbUI && CerbUI.Menu))
			return this.menus.get(item.key);

		const menu = new CerbUI.Menu(item.childUl, {
			onRenderItem: (li, src) => {
				const ico = this._mkIcon(src.dataset.icon);
				if(!ico) return;
				if(src.dataset.iconAt === 'end') { ico.style.marginLeft = '0.5em'; li.appendChild(ico); }
				else { ico.style.marginRight = '0.5em'; li.insertBefore(ico, li.firstChild); }
			},
			onSelect: (renderedLi, sourceLi, e) => this._activate(sourceLi, this._itemForLi(sourceLi), e),
			selectableParents: this.opts.selectableParents,
			hoverTrigger: item.hover ? btn : null,
			hoverGroup: item.hover ? this._hoverGroup : null,
		});

		this.menus.set(item.key, menu);
		return menu;
	}

	_toggleMenu(item, btn) {
		const menu = this._buildMenu(item, btn);
		if(!menu) return;
		// pointerdown on the anchor doesn't auto-close (CerbUI.Menu ignores anchor clicks), so toggle here.
		if(menu.isOpen()) menu.close();
		else menu.open(btn);
	}

	// ── Activation / firing ─────────────────────────────────────────────

	// Bind cerbBotTrigger once to every <li> in the tree that carries data-interaction-uri. The handler
	// reads the attributes fresh on each click, so a later trigger('click') fires the right interaction.
	_bindInteractions(ul) {
		if(!(window.jQuery && jQuery.fn.cerbBotTrigger)) return;

		const passthrough = {
			caller: this.opts.caller || { name: '', params: {} },
			target: this.opts.target,
			width: this.opts.width,
			start: this.opts.start || undefined,
			done: this.opts.done || undefined,
			error: this.opts.error || undefined,
			reset: this.opts.reset || undefined,
			command: this.opts.command || undefined,
		};

		ul.querySelectorAll('li[data-interaction-uri], li[data-behavior-id]').forEach(li => {
			if(li._cerbToolbarBound) return;
			li._cerbToolbarBound = true;
			jQuery(li).cerbBotTrigger(passthrough);
		});
	}

	// A leaf was chosen (strip button or menu item): notify the caller, then fire any interaction.
	_activate(sourceLi, item, e) {
		if(typeof this.opts.onSelect === 'function')
			this.opts.onSelect(item || this._itemForLi(sourceLi), sourceLi, e);

		if(sourceLi && (sourceLi.getAttribute('data-interaction-uri') || sourceLi.getAttribute('data-behavior-id')) && window.jQuery && jQuery.fn.cerbBotTrigger)
			jQuery(sourceLi).trigger('click');
	}

	// Build a lightweight item descriptor from any source <li> (used for menu-leaf onSelect payloads).
	_itemForLi(li) {
		if(!li) return null;
		return {
			key: li.dataset.key || null,
			value: li.dataset.value || null,
			label: this._directText(li) || li.dataset.label || null,
			interactionUri: li.dataset.interactionUri || null,
			interactionParams: li.dataset.interactionParams || null,
		};
	}

	// ── Helpers ─────────────────────────────────────────────────────────

	// The li's own text, ignoring any nested submenu <ul>.
	_directText(li) {
		let t = '';
		for(const n of li.childNodes) {
			if(n.nodeType === Node.TEXT_NODE) t += n.textContent;
			else if(n.nodeType === Node.ELEMENT_NODE && n.tagName !== 'UL') t += n.textContent;
		}
		return t.trim();
	}

	_teardown() {
		if(this._ro) { this._ro.disconnect(); this._ro = null; }
		this._restoreOverflow();                       // put moved-out <li>s back so a re-read sees them all
		if(this._overflowUl) { this._overflowUl.remove(); this._overflowUl = null; }
		this._overflowBtn = null;
		this._reflowEntries = [];
		this._sourceOrder = null;
		this.menus.forEach(menu => menu.destroy());
		this.menus.clear();
		this._toggles.clear();
		if(this.strip) { this.strip.remove(); this.strip = null; }
	}
};
