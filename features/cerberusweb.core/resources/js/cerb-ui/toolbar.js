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
 *   title        tooltip; data-keyboard  shortcut hint shown in menus
 *   data-badge   a count bubble (floating pill) on the strip button; data-badge-color tints it (else accent)
 *   data-value   arbitrary value surfaced to onSelect (non-interaction items, e.g. presets)
 *   data-interaction-uri / -params / -done  fire an interaction via cerbBotTrigger on select
 *   hidden (attribute) or class `cerb-ui-toolbar--hidden`  omit the item (role / record-type gating)
 *   data-hover (on a menu item)  open its menu on hover instead of click
 *
 * Usage:
 *   new CerbUI.Toolbar(document.getElementById('tb'), {
 *     bare: false,                                  // false = strip chrome; true = icons + menus only;
 *                                                   //   'tiny' (or tiny:true) = small muted icons (searchquery --right look)
 *     onSelect: (item, sourceLi, e) => { ... },     // item = {key,value,label,interactionUri,interactionParams}
 *     caller: { name: 'cerb.toolbar.demo', params: {} },  // passed through to cerbBotTrigger
 *     target: null, width: '50%',                   // target=jQuery el → inline interaction; null → popup
 *     start, done, error, reset                     // cerbBotTrigger lifecycle callbacks
 *   });
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
		start: null,        // (formData) cerbBotTrigger hook — append caller params just-in-time
		done: null,         // (event) interaction finished
		error: null,        // (event) interaction errored
		reset: null,        // (event) interaction reset
	};

	constructor(el, opts = {}) {
		el = (typeof el === 'string') ? document.querySelector(el) : el;
		if(!el) return;

		this.el = el;
		this.opts = Object.assign({}, CerbUI.Toolbar._DEFAULTS, opts);
		// tiny implies bare; either can come from the option (bare:'tiny' / tiny:true) or the source class.
		this.tiny = this.opts.bare === 'tiny' || this.opts.tiny || el.classList.contains('cerb-ui-toolbar--bare-tiny');
		this.bare = this.tiny || !!this.opts.bare || el.classList.contains('cerb-ui-toolbar--bare');
		this.menus = new Map();   // top-level item key -> CerbUI.Menu (built lazily)
		this.strip = null;        // the rendered visible strip
		this._hoverGroup = 'cerb-ui-toolbar-' + (CerbUI.Toolbar._seq = (CerbUI.Toolbar._seq || 0) + 1);

		CerbUI.Toolbar._instances.set(el, this);

		this._render();
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

	// ── Build ───────────────────────────────────────────────────────────

	_render() {
		// Hide the authored <ul> — it stays in the DOM as the data + menu source.
		this.el.hidden = true;

		const strip = document.createElement('div');
		strip.className = 'cerb-ui-toolbar--strip'
			+ (this.bare ? ' cerb-ui-toolbar--bare' : '')
			+ (this.tiny ? ' cerb-ui-toolbar--bare-tiny' : '');
		this.strip = strip;

		// Bind firing once per interaction <li> anywhere in the tree (strip + nested menus).
		this._bindInteractions(this.el);

		const items = this._readItems(this.el);

		items.forEach(item => {
			if(item.hidden) return;

			if(item.divider) {
				const sep = document.createElement('span');
				sep.className = 'cerb-ui-toolbar--divider';
				strip.appendChild(sep);
				return;
			}

			strip.appendChild(this._mkButton(item));
		});

		this.el.insertAdjacentElement('afterend', strip);
	}

	// Read only the TOP-LEVEL <li> of a source <ul> into a flat model.
	_readItems(ul) {
		const out = [];
		let idx = 0;

		for(const li of ul.children) {
			if(!(li instanceof HTMLLIElement)) continue;

			const childUl = li.querySelector(':scope > ul');
			const hasOwnText = this._directText(li) !== '';

			// An empty top-level li (no text, no submenu) is a strip divider.
			if(!hasOwnText && !childUl && !li.dataset.icon && !li.dataset.label) {
				out.push({ divider: true });
				continue;
			}

			out.push({
				key: li.dataset.key || ('i' + (idx++)),
				sourceLi: li,
				childUl: childUl || null,
				icon: li.dataset.icon || null,
				// Label = the li's own text (matches CerbUI.Menu); data-label is only a fallback for icon-only items.
				label: this._directText(li) || li.dataset.label || null,
				tooltip: li.getAttribute('title') || null,
				keyboard: li.dataset.keyboard || null,
				badge: li.dataset.badge || null,
				badgeColor: li.dataset.badgeColor || null,
				value: li.dataset.value || null,
				interactionUri: li.dataset.interactionUri || null,
				interactionParams: li.dataset.interactionParams || null,
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

		const tooltip = item.tooltip || (this.bare ? item.label : null);
		if(tooltip) btn.title = tooltip + (item.keyboard ? ' (' + item.keyboard + ')' : '');
		if(item.keyboard) btn.dataset.interactionKeyboard = item.keyboard;

		if(item.badge != null) {
			const badge = document.createElement('span');
			badge.className = 'cerb-ui-toolbar--badge';
			badge.textContent = item.badge;
			if(item.badgeColor) badge.style.backgroundColor = item.badgeColor; // else the SCSS accent
			btn.appendChild(badge);
		}

		const ico = this._mkIcon(item.icon);
		if(ico) btn.appendChild(ico);

		// In the bare variant the label rides as a tooltip, so the strip stays icon-only.
		if(item.label && !this.bare)
			btn.appendChild(document.createTextNode(item.label));

		if(item.childUl) {
			btn.classList.add('cerb-ui-toolbar--item-has-menu');
			const caret = document.createElement('span');
			caret.className = 'cerb-ui-toolbar--caret cerb-icons cerb-icon-chevron-down';
			caret.setAttribute('aria-hidden', 'true');
			btn.appendChild(caret);

			if(item.hover) this._buildMenu(item, btn); // eager so hover-open is wired
			btn.addEventListener('click', (e) => { e.stopPropagation(); this._toggleMenu(item, btn); });
		} else {
			btn.addEventListener('click', (e) => { e.stopPropagation(); this._activate(item.sourceLi, item, e); });
		}

		return btn;
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
				if(ico) { ico.style.marginRight = '0.5em'; li.insertBefore(ico, li.firstChild); }
			},
			onSelect: (renderedLi, sourceLi, e) => this._activate(sourceLi, this._itemForLi(sourceLi), e),
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
		};

		ul.querySelectorAll('li[data-interaction-uri]').forEach(li => {
			if(li._cerbToolbarBound) return;
			li._cerbToolbarBound = true;
			jQuery(li).cerbBotTrigger(passthrough);
		});
	}

	// A leaf was chosen (strip button or menu item): notify the caller, then fire any interaction.
	_activate(sourceLi, item, e) {
		if(typeof this.opts.onSelect === 'function')
			this.opts.onSelect(item || this._itemForLi(sourceLi), sourceLi, e);

		if(sourceLi && sourceLi.getAttribute('data-interaction-uri') && window.jQuery && jQuery.fn.cerbBotTrigger)
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
		this.menus.forEach(menu => menu.destroy());
		this.menus.clear();
		if(this.strip) { this.strip.remove(); this.strip = null; }
	}
};
