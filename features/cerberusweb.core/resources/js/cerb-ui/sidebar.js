/*
 * CerbUI.Sidebar — a collapsible vertical navigation rail (sections + menu items), independent of any page.
 *
 * Enhances authored markup in place (progressive enhancement, like Tabs/Menu): an <aside class="cerb-ui-sidebar">
 * with three optional slots —
 *   --head : fixed (does not scroll). The component injects the collapse toggle (and, with filter:true, a search box).
 *   --body : the ONLY scroll region. Holds --section blocks (a muted --label + a <ul> of item <li>s).
 *   --foot : fixed (does not scroll). Optional (e.g. a future "current user" card).
 * A bare <aside> with just sections/uls is auto-wrapped into a --body, so simple callers can skip the slots.
 *
 * Each item <li> becomes a flex row: a leading icon (data-icon → cerb-icons) or pip (data-pip → a colored dot),
 * the label, and optional right content (data-badge / data-right). Labels are set via textContent and only the
 * source <li> + its data-* are trusted (the security boundary); that same <li> is what onSelect receives.
 *
 * The chevron toggle collapses the rail to an icon-only strip (labels / section headers / right / filter hidden).
 * With collapseTo:'closed' the collapsed rail instead hides its whole body, leaving only the toggle handle —
 * use this when every item shares one icon (an icon strip would be ambiguous).
 * Full height is opt-in (fullHeight:true → sticky 100vh so the body scrolls within the viewport); otherwise it is
 * a normal in-flow block usable anywhere (e.g. a node-editor's node library).
 *
 * Items can navigate without an onSelect: the built-in default action (CerbUI.Sidebar.defaultSelect) routes
 * data-item-ajax (tabs-style fetch into a content target, scripts run under the nonce), then data-item-url
 * (http(s):// or // = external new tab, otherwise relative = internal full-page nav), then an authored <a href>.
 *
 * Usage:
 *   const sb = new CerbUI.Sidebar(asideEl, { onSelect: (li) => { ... } });
 *   sb.toggle(); sb.collapse(); sb.expand(); sb.isCollapsed();
 *   sb.setActive(li); sb.setFilter('inv'); sb.getFilter();
 *   CerbUI.Sidebar.from(asideEl);
 *
 * CSS lives in cerb.css (.cerb-ui-sidebar--*) — this component never injects styles.
 */
CerbUI.Sidebar = class {
	static _instances = new WeakMap();

	static _DEFAULTS = {
		side: 'left',            // 'left' | 'right' — border side + which way the chevron points
		collapsed: false,        // start collapsed (icon-only strip)
		collapseTo: 'icons',     // 'icons' (icon-only strip) | 'closed' (hide the body, leave only the toggle handle — for rails whose items share one icon)
		fullHeight: false,       // sticky full-viewport height (body scrolls within); else a normal in-flow block
		storageKey: null,        // localStorage key to persist the collapsed state across reloads
		filter: false,           // inject a search box in the head that winnows items by label text
		filterPlaceholder: 'Filter…',
		onToggle: null,          // (collapsed) after expand/collapse
		onSelect: null,          // (li, sidebar, e) on item click; return truthy to handle it (skips the default action)
		onRenderItem: null,      // (renderedLi, srcLi) after icon+label, before right content — extra adornment hook
		onFilterItem: null,      // (srcLi, query) -> bool; overrides the default substring match
		contentTarget: null,     // element | selector — default destination for data-item-ajax loads
		variant: 'nav',          // 'nav' (icon/label/badge rows) | 'tile' (cerb-ui-tile items); per-item data-variant overrides
		draggable: false,        // make items draggable (clone helper) via CerbUI.Draggable — the host owns the drop zone
		palette: false,          // shorthand: a draggable tile palette (implies variant:'tile' + draggable:true)
		onItemDragStart: null,   // (li, e) when an item drag begins (draggable/palette mode)
		tooltips: true,          // when COLLAPSED, show each item's label on hover (CerbUI.Tooltip, else native title)
	};

	static from(el) {
		return CerbUI.Sidebar._instances.get(el);
	}

	constructor(el, opts = {}) {
		this.el = (typeof el === 'string') ? document.querySelector(el) : el;
		if(!this.el) return;
		this.opts = Object.assign({}, CerbUI.Sidebar._DEFAULTS, opts);
		this.collapsed = false;
		this.items = []; // { li, label } for each enhanced source <li>

		// palette is shorthand for a draggable tile palette.
		this.variant = this.opts.palette ? 'tile' : this.opts.variant;
		this.draggable = !!(this.opts.palette || this.opts.draggable);
		this._dragging = false;

		this._onItemOver = this._onItemOver.bind(this);
		this._onItemOut = this._onItemOut.bind(this);
		this._onBodyClick = this._onBodyClick.bind(this);
		this._onBodyKeydown = this._onBodyKeydown.bind(this);
		this._onToggleClick = this._onToggleClick.bind(this);
		this._onFilterInput = this._onFilterInput.bind(this);
		this._onFilterKeydown = this._onFilterKeydown.bind(this);

		this._build();
		CerbUI.Sidebar._instances.set(this.el, this);

		// Initial collapsed state: persisted (when storageKey set) wins over the option; else the option.
		let start = !!this.opts.collapsed;
		if(this.opts.storageKey) {
			try {
				const v = localStorage.getItem(this.opts.storageKey);
				if(v === '1') start = true;
				else if(v === '0') start = false;
			} catch(e) { /* private browsing / quota */ }
		}
		this._setCollapsed(start, false);
	}

	// ── Build / enhance ─────────────────────────────────────────────────

	_build() {
		this.el.classList.add('cerb-ui-sidebar');
		if(this.opts.side === 'right') this.el.classList.add('cerb-ui-sidebar--right');
		if(this.opts.fullHeight) this.el.classList.add('cerb-ui-sidebar--full-height');
		if(this.draggable) this.el.classList.add('cerb-ui-sidebar--palette');
		if(this.opts.collapseTo === 'closed') this.el.classList.add('cerb-ui-sidebar--collapse-closed');

		this.head = this.el.querySelector(':scope > .cerb-ui-sidebar--head');
		this.body = this.el.querySelector(':scope > .cerb-ui-sidebar--body');
		this.foot = this.el.querySelector(':scope > .cerb-ui-sidebar--foot');

		// Auto-wrap loose content (sections/uls authored directly under the rail) into a scroll body.
		if(!this.body) {
			this.body = document.createElement('div');
			this.body.className = 'cerb-ui-sidebar--body';
			const move = Array.from(this.el.children).filter(c => c !== this.head && c !== this.foot);
			if(this.foot) this.el.insertBefore(this.body, this.foot);
			else this.el.appendChild(this.body);
			move.forEach(c => this.body.appendChild(c));
		}

		// A head always exists (it carries the toggle); create one if the caller didn't author it.
		if(!this.head) {
			this.head = document.createElement('div');
			this.head.className = 'cerb-ui-sidebar--head';
			this.el.insertBefore(this.head, this.el.firstChild);
		}

		this._buildToggle();
		if(this.opts.filter) this._buildFilter();
		this._enhanceItems();

		this.body.addEventListener('click', this._onBodyClick);
		this.body.addEventListener('keydown', this._onBodyKeydown);
		if(this.draggable) this._initDraggable();
		if(this.opts.tooltips) this._initTooltips();
	}

	// Collapsed-only label tooltips. Prefer CerbUI.Tooltip (anchored, auto-flips to the side with room + an
	// arrow at the icon); fall back to a native `title` (toggled in _setCollapsed) when it isn't loaded.
	_initTooltips() {
		this._useTip = !!(window.CerbUI && CerbUI.Tooltip);
		if(!this._useTip) return; // native-title path lives in _setCollapsed
		this.body.addEventListener('mouseover', this._onItemOver);
		this.body.addEventListener('mouseout', this._onItemOut);
	}

	_onItemOver(e) {
		if(!this.collapsed) return; // labels are already visible when expanded
		const li = e.target.closest('.cerb-ui-sidebar--item');
		if(!li || !this.body.contains(li) || li === this._tipLi) return;
		if(!this._tip) this._tip = new CerbUI.Tooltip();
		const it = this.items.find(x => x.li === li);
		this._tipLi = li;
		// Pin BESIDE the icon (open side, arrow pointing back at it) — not above/below, so the vertical icon
		// track stays clear for straight up/down mouse travel. Left rail → tooltip right; right rail → left.
		const right = this.opts.side === 'right';
		this._tip.anchor(it ? it.label : (li.textContent || '').trim(), li, {
			my: right ? 'right' : 'left',
			at: right ? 'left' : 'right',
			interactive: false,
		});
	}

	_onItemOut(e) {
		const li = e.target.closest('.cerb-ui-sidebar--item');
		// Hide only when truly leaving the item (not when moving onto its own icon child).
		if(li && li === this._tipLi && (!e.relatedTarget || !li.contains(e.relatedTarget))) {
			this._tipLi = null;
			if(this._tip) this._tip.hide();
		}
	}

	// Palette mode: make the items draggable (clone helper) so the host's CerbUI.Droppable canvas can receive
	// them. The original stays — Draggable never moves it. A drag suppresses the click that would otherwise select.
	_initDraggable() {
		if(!(window.CerbUI && CerbUI.Draggable)) return;
		const self = this;
		this._draggable = new CerbUI.Draggable(this.body, {
			items: '.cerb-ui-sidebar--item',
			helper: 'clone',
			tilt: true,
			data: function(li) { return Object.assign({}, li.dataset); },
			onStart: function(li, e) {
				self._dragging = true;
				if(typeof self.opts.onItemDragStart === 'function') self.opts.onItemDragStart(li, e);
			},
			onStop: function() {
				// Clear after the click that may immediately follow the pointerup (so it's ignored, not selected).
				setTimeout(function() { self._dragging = false; }, 0);
			},
		});
	}

	_buildToggle() {
		// A header bar row holds any authored brand/logo content plus the collapse toggle (pinned to the edge).
		this.headBar = document.createElement('div');
		this.headBar.className = 'cerb-ui-sidebar--headbar';
		Array.from(this.head.childNodes).forEach(n => this.headBar.appendChild(n));

		this.toggleBtn = document.createElement('button');
		this.toggleBtn.type = 'button';
		this.toggleBtn.className = 'cerb-ui-sidebar--toggle';
		this.toggleBtn.setAttribute('aria-label', 'Toggle sidebar');
		this.toggleIcon = document.createElement('span');
		this.toggleIcon.className = 'cerb-icons';
		this.toggleBtn.appendChild(this.toggleIcon);
		this.toggleBtn.addEventListener('click', this._onToggleClick);
		this.headBar.appendChild(this.toggleBtn);

		this.head.insertBefore(this.headBar, this.head.firstChild);
	}

	_buildFilter() {
		this.filterInput = document.createElement('input');
		this.filterInput.type = 'search';
		this.filterInput.className = 'cerb-ui-sidebar--filter';
		this.filterInput.setAttribute('placeholder', this.opts.filterPlaceholder);
		this.filterInput.setAttribute('aria-label', this.opts.filterPlaceholder);
		// A query editor, not prose.
		this.filterInput.setAttribute('autocomplete', 'off');
		this.filterInput.setAttribute('spellcheck', 'false');
		this.filterInput.addEventListener('input', this._onFilterInput);
		this.filterInput.addEventListener('keydown', this._onFilterKeydown);
		// Sit the filter inline to the LEFT of the collapse toggle — one head row, no empty bar above it.
		this.headBar.insertBefore(this.filterInput, this.toggleBtn);
	}

	_enhanceItems() {
		this.items = [];
		this.body.querySelectorAll('ul > li').forEach(li => this._enhanceItem(li));
	}

	// Enhance one item <li> in place. Three shapes:
	//   passthrough — the <li> already holds rich markup (a non-anchor element child, e.g. an authored tile): keep it.
	//   tile        — variant:'tile' (or data-variant="tile"): render a cerb-ui-tile from data-icon/kind/name/color.
	//   nav         — the default: [icon|pip] [label] [onRenderItem] [badge|right].
	_enhanceItem(li) {
		if(li._cerbSidebarItem) return;
		li._cerbSidebarItem = true;
		li.classList.add('cerb-ui-sidebar--item');
		li.setAttribute('role', 'menuitem');
		li.setAttribute('tabindex', '-1');

		const anchor = li.querySelector(':scope > a');

		// Label text (also used for filtering): explicit data-label / data-name, else the anchor / first text node.
		let label = (li.getAttribute('data-label') || li.getAttribute('data-name') || '').trim();
		if(!label && anchor) label = (anchor.textContent || '').trim();
		if(!label) {
			for(const n of Array.from(li.childNodes)) {
				if(n.nodeType === Node.TEXT_NODE) {
					const t = n.textContent.trim();
					if(t) { label = t; break; }
				}
			}
		}

		const variant = li.getAttribute('data-variant') || this.variant;

		// Passthrough: an authored non-anchor element child means the caller built the item (e.g. a .cerb-ui-tile).
		// Leave its content untouched; just register it (and run the adornment hook).
		const richChild = Array.from(li.children).some(c => c.tagName !== 'A');
		if(richChild) {
			li.classList.add('cerb-ui-sidebar--item-raw');
			if(typeof this.opts.onRenderItem === 'function') this.opts.onRenderItem(li, li);
			this.items.push({ li, label });
			return;
		}

		if(variant === 'tile') {
			this._renderTile(li, label);
			if(typeof this.opts.onRenderItem === 'function') this.opts.onRenderItem(li, li);
			this.items.push({ li, label });
			return;
		}

		// ── nav (default) ──
		// Clear direct children except an authored anchor (which becomes the label element).
		Array.from(li.childNodes).forEach(n => { if(n !== anchor) li.removeChild(n); });

		// Leading icon slot (a fixed-width box so labels align and the collapsed strip has a slot). A cerb-icons
		// glyph rides on the slot itself; a pip nests INSIDE it so it keeps its own 9px circle (the slot is wider).
		const iconName = li.getAttribute('data-icon');
		const pip = li.getAttribute('data-pip');
		const slot = document.createElement('span');
		slot.className = 'cerb-ui-sidebar--icon';
		if(iconName) {
			// ".raw.class" = literal class(es) for non-cerb icons; otherwise a cerb-icons glyph name.
			const cls = iconName.charAt(0) === '.' ? iconName.slice(1).split('.').join(' ') : ('cerb-icons cerb-icon-' + iconName);
			cls.split(' ').forEach(c => { if(c) slot.classList.add(c); });
		} else if(pip != null) {
			const dot = document.createElement('span');
			dot.className = 'cerb-ui-pip';
			const color = this._resolveColor(pip);
			if(color) dot.style.color = color;
			if(li.hasAttribute('data-pip-live')) dot.classList.add('cerb-ui-pip--live');
			slot.appendChild(dot);
		} else {
			slot.classList.add('cerb-ui-sidebar--icon-empty');
		}
		li.appendChild(slot);

		// Label (reuse an authored anchor so href / middle-click / accessibility survive). Its own class
		// (--item-label, normal case) — distinct from the uppercase section header --label.
		let labelEl;
		if(anchor) {
			anchor.classList.add('cerb-ui-sidebar--item-label');
			labelEl = anchor;
		} else {
			labelEl = document.createElement('span');
			labelEl.className = 'cerb-ui-sidebar--item-label';
			labelEl.textContent = label;
		}
		li.appendChild(labelEl);

		// Adornment hook (after icon+label, before right content). Enhanced in place, so both args are this <li>.
		if(typeof this.opts.onRenderItem === 'function') this.opts.onRenderItem(li, li);

		// Right content: a badge pill (count/metric) or generic muted text.
		const badge = li.getAttribute('data-badge');
		const right = li.getAttribute('data-right');
		if(badge != null && badge !== '') {
			const b = document.createElement('span');
			b.className = 'cerb-ui-sidebar--badge';
			b.textContent = badge;
			li.appendChild(b);
		} else if(right != null && right !== '') {
			const r = document.createElement('span');
			r.className = 'cerb-ui-sidebar--right';
			r.textContent = right;
			li.appendChild(r);
		}

		this.items.push({ li, label });
	}

	// A bareword color = a tag-palette token (e.g. "green" -> var(--cerb-color-tag-green)); anything else literal.
	_resolveColor(value) {
		const c = (value || '').trim();
		if(!c) return '';
		return /^[a-z]+$/i.test(c) ? ('var(--cerb-color-tag-' + c + ')') : c;
	}

	// Build a cerb-ui-tile from data-icon / data-kind / data-name (or the label) / data-color (the icon square bg).
	_renderTile(li, label) {
		const icon = li.getAttribute('data-icon');
		const kind = li.getAttribute('data-kind');
		const name = (li.getAttribute('data-name') || label || '').trim();
		const color = this._resolveColor(li.getAttribute('data-color'));

		while(li.firstChild) li.removeChild(li.firstChild);

		const tile = document.createElement('div');
		tile.className = 'cerb-ui-tile';

		const ico = document.createElement('span');
		ico.className = 'cerb-ui-tile--icon';
		if(color) ico.style.background = color;
		if(icon) {
			const g = document.createElement('span');
			g.className = icon.charAt(0) === '.' ? icon.slice(1).split('.').join(' ') : ('cerb-icons cerb-icon-' + icon);
			ico.appendChild(g);
		}
		tile.appendChild(ico);

		const text = document.createElement('div');
		text.className = 'cerb-ui-tile--text';
		if(kind) {
			const k = document.createElement('div');
			k.className = 'cerb-ui-tile--kind';
			k.textContent = kind;
			text.appendChild(k);
		}
		const n = document.createElement('div');
		n.className = 'cerb-ui-tile--name';
		n.textContent = name;
		text.appendChild(n);
		tile.appendChild(text);

		li.appendChild(tile);
	}

	// ── Collapse ────────────────────────────────────────────────────────

	toggle() { this._setCollapsed(!this.collapsed); return this; }
	collapse() { this._setCollapsed(true); return this; }
	expand() { this._setCollapsed(false); return this; }
	isCollapsed() { return this.collapsed; }

	_setCollapsed(collapsed, fire = true) {
		this.collapsed = !!collapsed;
		this.el.classList.toggle('cerb-ui-sidebar--collapsed', this.collapsed);
		this._updateToggleIcon();
		if(this.opts.storageKey) {
			try { localStorage.setItem(this.opts.storageKey, this.collapsed ? '1' : '0'); } catch(e) { /* quota */ }
		}
		// Tooltips: drop any open one; without CerbUI.Tooltip, toggle a native `title` per item (collapsed only).
		if(this._tip) { this._tip.hide(); this._tipLi = null; }
		if(this.opts.tooltips && this._useTip === false) {
			for(const it of this.items) {
				if(this.collapsed) it.li.setAttribute('title', it.label);
				else it.li.removeAttribute('title');
			}
		}
		if(fire && typeof this.opts.onToggle === 'function') this.opts.onToggle(this.collapsed);
	}

	_updateToggleIcon() {
		// Left rail: expanded points the way it collapses (toward the edge = left); collapsed points to expand (right).
		// Right rail mirrors.
		const left = this.opts.side !== 'right';
		const dir = left ? (this.collapsed ? 'right' : 'left') : (this.collapsed ? 'left' : 'right');
		this.toggleIcon.className = 'cerb-icons cerb-icon-chevron-' + dir;
	}

	_onToggleClick() { this.toggle(); }

	// ── Selection ───────────────────────────────────────────────────────

	_onBodyClick(e) {
		if(this._dragging) return; // a real drag just ended — swallow the trailing click, don't select
		const li = e.target.closest('li.cerb-ui-sidebar--item');
		if(!li || !this.body.contains(li)) return;
		e.preventDefault(); // take full control; navigation is routed below (no double-nav from an authored <a>)
		let handled = false;
		if(typeof this.opts.onSelect === 'function') handled = !!this.opts.onSelect(li, this, e);
		if(!handled) CerbUI.Sidebar.defaultSelect(li, this);
		this.setActive(li);
	}

	setActive(liOrId) {
		let li = liOrId;
		if(typeof liOrId === 'string') {
			const esc = (window.CSS && CSS.escape) ? CSS.escape(liOrId) : liOrId;
			li = this.body.querySelector('[data-id="' + esc + '"]') || this.body.querySelector('#' + esc);
		}
		this.body.querySelectorAll('.cerb-ui-sidebar--item-active')
			.forEach(p => p.classList.remove('cerb-ui-sidebar--item-active'));
		if(li) li.classList.add('cerb-ui-sidebar--item-active');
		return this;
	}

	// Default item action when no onSelect handled the click (also callable by hosts).
	static defaultSelect(li, sidebar) {
		const ajax = li.getAttribute('data-item-ajax');
		if(ajax) { CerbUI.Sidebar._loadAjax(li, ajax, sidebar); return; }

		let url = li.getAttribute('data-item-url');
		let newTab = false;
		if(!url) {
			const a = li.querySelector('a[href]');
			if(a) { url = a.getAttribute('href'); newTab = (a.target === '_blank'); }
		}
		if(!url) return;

		// http(s):// or protocol-relative // = external (new tab); anything else = internal full-page nav.
		const external = newTab || /^(https?:)?\/\//i.test(url);
		if(external) window.open(url, '_blank', 'noopener');
		else window.location.href = url;
	}

	static _resolveTarget(li, sidebar) {
		const sel = li.getAttribute('data-item-target');
		if(sel) return document.querySelector(sel);
		const t = sidebar && sidebar.opts ? sidebar.opts.contentTarget : null;
		if(typeof t === 'string') return document.querySelector(t);
		return t || null;
	}

	// Tabs-style AJAX load into a content target: genericAjaxGet injects with jQuery so returned <script> runs
	// under the page's CSP nonce (the canonical Cerb path). Falls back to a non-script fetch if the helper is absent.
	static _loadAjax(li, url, sidebar) {
		const target = CerbUI.Sidebar._resolveTarget(li, sidebar);
		if(!target) {
			if(window.console) console.warn('CerbUI.Sidebar: data-item-ajax has no content target (set data-item-target or the contentTarget option).');
			return;
		}
		target.innerHTML = '';
		const wrap = document.createElement('div');
		wrap.className = 'cerb-ui-sidebar--loading';
		wrap.appendChild((window.CerbUI && CerbUI.Spinner) ? CerbUI.Spinner.create() : document.createElement('span'));
		target.appendChild(wrap);

		if(typeof genericAjaxGet === 'function' && window.jQuery) {
			genericAjaxGet(jQuery(target), url, function() {});
			return;
		}
		fetch(url).then(res => res.text()).then(html => { target.innerHTML = html; }).catch(() => { target.innerHTML = ''; });
	}

	// ── Filter ──────────────────────────────────────────────────────────

	_onFilterInput() { this.setFilter(this.filterInput.value); }

	// Visible, focusable item <li>s in DOM order (skips filtered-out and collapsed-away items).
	_focusableItems() {
		return this.items
			.map(it => it.li)
			.filter(li => !li.classList.contains('cerb-ui-sidebar--item-hidden') && li.offsetParent !== null);
	}

	// ArrowDown from the filter drops focus into the menu (first visible item).
	_onFilterKeydown(e) {
		if(e.key !== 'ArrowDown') return;
		const items = this._focusableItems();
		if(!items.length) return;
		e.preventDefault();
		items[0].focus();
	}

	// Roving keyboard nav once focus is in the menu: Up/Down move; Up past the top returns to the
	// filter; Home/End jump; Enter/Space select; Escape returns to the filter.
	_onBodyKeydown(e) {
		const li = e.target.closest('li.cerb-ui-sidebar--item');
		if(!li || !this.body.contains(li)) return;
		const items = this._focusableItems();
		const idx = items.indexOf(li);
		if(idx === -1) return;

		switch(e.key) {
			case 'ArrowDown':
				e.preventDefault();
				if(idx < items.length - 1) items[idx + 1].focus();
				break;
			case 'ArrowUp':
				e.preventDefault();
				if(idx > 0) items[idx - 1].focus();
				else if(this.filterInput) this.filterInput.focus();
				break;
			case 'Home':
				e.preventDefault();
				items[0].focus();
				break;
			case 'End':
				e.preventDefault();
				items[items.length - 1].focus();
				break;
			case 'Enter':
			case ' ':
				e.preventDefault();
				li.click();
				break;
			case 'Escape':
				if(this.filterInput) { e.preventDefault(); this.filterInput.focus(); }
				break;
		}
	}

	getFilter() { return this.filterInput ? this.filterInput.value : ''; }

	setFilter(query) {
		if(this.filterInput && this.filterInput.value !== query) this.filterInput.value = query;
		const q = (query || '').trim().toLowerCase();

		for(const it of this.items) {
			const show = (typeof this.opts.onFilterItem === 'function')
				? !!this.opts.onFilterItem(it.li, q)
				: (!q || it.label.toLowerCase().includes(q));
			it.li.classList.toggle('cerb-ui-sidebar--item-hidden', !show);
		}

		// Hide a section whose every item is filtered out.
		this.body.querySelectorAll('.cerb-ui-sidebar--section').forEach(sec => {
			const anyVisible = sec.querySelector('li.cerb-ui-sidebar--item:not(.cerb-ui-sidebar--item-hidden)');
			sec.classList.toggle('cerb-ui-sidebar--section-hidden', !anyVisible);
		});
		return this;
	}

	// ── Teardown ────────────────────────────────────────────────────────
	// Unbinds listeners + the registry entry; the enhanced markup is left in place (the rebuild isn't reverted).

	destroy() {
		CerbUI.Sidebar._instances.delete(this.el);
		this.body.removeEventListener('click', this._onBodyClick);
		this.body.removeEventListener('keydown', this._onBodyKeydown);
		if(this.toggleBtn) this.toggleBtn.removeEventListener('click', this._onToggleClick);
		if(this.filterInput) {
			this.filterInput.removeEventListener('input', this._onFilterInput);
			this.filterInput.removeEventListener('keydown', this._onFilterKeydown);
		}
		if(this._draggable) this._draggable.destroy();
		this.body.removeEventListener('mouseover', this._onItemOver);
		this.body.removeEventListener('mouseout', this._onItemOut);
		if(this._tip) this._tip.hide();
	}
};
