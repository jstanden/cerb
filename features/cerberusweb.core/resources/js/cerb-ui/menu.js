/*
 * CerbUI.Menu — a lightweight cascading menu that replaces jQuery UI for large/deep trees.
 *
 * Parses a nested UL/LI once into a plain data model, then:
 *   - renders panels lazily (only the open path is ever in the DOM),
 *   - virtualizes any single panel with more than `virtThreshold` items (a windowed list with two
 *     <li> spacers carrying the height above/below the visible rows — 100k items, ~25 DOM nodes),
 *   - delegates one mouseover/click listener per panel,
 *   - positions with viewport flip/clamp (no layout thrash),
 *   - optional type-to-filter (filter:true): start typing to reveal a search box (hidden until used,
 *     tucks away when emptied). A flat menu filters its labels in place; a nested menu searches a
 *     flattened list of ALL leaves and shows matches with breadcrumb context.
 *
 * Markup (progressive enhancement): an authored UL > LI for the menu, UL > LI > UL > LI for a submenu.
 * An empty/whitespace <li> is a separator. Only data-* attributes are mirrored onto the rendered item
 * (the security boundary); labels are set via textContent. Icons are NOT in the markup — inject them with
 * the onRenderItem hook (read sourceLi.dataset.* and prepend a cerb-icons span).
 *
 * Usage:
 *   const menu = new CerbUI.Menu(ulEl, { onSelect: (li, src, e) => { ... } });
 *   menu.open(anchorEl);   // float below the anchor (submenus cascade)
 *   menu.close();
 *   CerbUI.Menu.from(ulEl) // -> the instance for a source UL
 *
 * CSS lives in cerb.css (.cerb-ui-menu--*) — this component never injects styles. The .cerb-ui-menu--item
 * height (28px) must stay in lockstep with the `itemHeight` option; the virtual-scroll math depends on it.
 */
CerbUI.Menu = class {
	static _instances = new WeakMap();
	static _hoverGroups = new Map();

	static _DEFAULTS = {
		onSelect: null,       // (renderedLi, sourceLi, event) on leaf click / Enter
		onClose: null,        // () when the menu finishes closing (panels removed)
		closeOnSelect: true,  // close the menu after a leaf is chosen (false = stay open to pick several)
		onRenderItem: null,   // (renderedLi, sourceLi) after the label, before the arrow — the icon hook
		itemHeight: 28,       // px; MUST match the .cerb-ui-menu--item CSS height (virt math depends on it)
		maxHeight: 380,       // px before a panel scrolls
		virtThreshold: 60,    // virtualize panels larger than this
		openDelay: 80,        // ms hover delay before a submenu opens
		virtBuffer: 6,        // extra rows rendered above/below the visible window
		inline: false,        // render the root panel in document flow vs. floating
		hoverTrigger: null,   // element that opens on mouseenter / closes on mouseleave
		hoverGroup: null,     // links sibling hover menus (only one open per group)
		hoverCloseDelay: 150, // ms before a hover menu closes after the mouse leaves
		fixed: false,         // position:fixed instead of absolute for floating panels
		filter: false,        // type-to-filter: a search input above the root panel (filters the flat list)
		filterPlaceholder: 'Filter…',
		filterEmptyText: 'No matches',
	};

	static from(el) {
		return CerbUI.Menu._instances.get(el);
	}

	constructor(ul, opts = {}) {
		this.opts = Object.assign({}, CerbUI.Menu._DEFAULTS, opts);
		this.root = CerbUI.Menu._parseUl(ul);
		this.sourceUl = ul;
		this.pnls = [];
		this.hoverTimer = null;
		this.hoverCloseTimer = null;
		this.hoverMouseInside = false;
		this.triggerEnter = null;
		this.triggerLeave = null;
		this.anchor = null;
		this.docDown = null;
		this.docKey = null;

		CerbUI.Menu._instances.set(ul, this);
		if(this.opts.hoverTrigger) this._bindHoverTrigger(this.opts.hoverTrigger);
		if(this.opts.hoverGroup) {
			let g = CerbUI.Menu._hoverGroups.get(this.opts.hoverGroup);
			if(!g) { g = new Set(); CerbUI.Menu._hoverGroups.set(this.opts.hoverGroup, g); }
			g.add(this);
		}
		if(this.opts.inline) this.open();
	}

	// Walk a UL/LI tree once into a plain array. Children arrays exist only where sub-ULs do (leaf-cheap).
	static _parseUl(ul) {
		const out = [];
		for(let i = 0; i < ul.children.length; i++) {
			const child = ul.children[i];
			if(!(child instanceof HTMLLIElement)) continue;

			if((child.textContent ?? '').trim() === '') {
				out.push({ label: '', separator: true, el: child, children: null });
				continue;
			}

			const childUl = child.querySelector(':scope > ul');

			let label = '';
			for(let j = 0; j < child.childNodes.length; j++) {
				const n = child.childNodes[j];
				if(n.nodeType === Node.TEXT_NODE) {
					const t = n.textContent?.trim();
					if(t) { label = t; break; }
				} else if(n.nodeType === Node.ELEMENT_NODE && n.tagName !== 'UL') {
					label = (n.textContent ?? '').trim();
					break;
				}
			}

			out.push({
				label,
				el: child,
				children: childUl instanceof HTMLUListElement ? CerbUI.Menu._parseUl(childUl) : null,
			});
		}
		return out;
	}

	static _spacerLi() {
		const li = document.createElement('li');
		li.className = 'cerb-ui-menu--spacer';
		li.setAttribute('aria-hidden', 'true');
		return li;
	}

	// ── Public API ──────────────────────────────────────────────────────

	open(anchor) {
		this.close();
		// A floating menu is the active popup while open, so it listens on document. An inline menu is
		// permanently open and in-flow — a document listener would let it steal arrow keys from whatever menu
		// you're really using. Instead its keydown is bound to its focusable root panel (see _buildPanel), so
		// it only drives the keyboard while focus is actually inside it.
		if(!this.opts.inline) {
			this.docKey = (e) => this._onKey(e);
			document.addEventListener('keydown', this.docKey);
		}

		if(this.opts.inline) {
			// Inline: clicks outside the panels collapse floating submenus but leave the root in place.
			this.docDown = (e) => {
				if(!this._hitTest(e.target)) {
					while(this.pnls.length > 1) {
						const popped = this.pnls.pop();
						if(popped) popped.el.remove();
					}
				}
			};
		} else {
			this.anchor = anchor ?? null;
			this.docDown = (e) => {
				// Let the trigger's own click handler toggle; only close on genuine outside clicks.
				if(this.anchor && this.anchor.contains(e.target)) return;
				if(!this._hitTest(e.target)) this.close();
			};
		}

		document.addEventListener('pointerdown', this.docDown, { capture: true });
		this._push(this.root, 0);
	}

	close() {
		if(this.hoverTimer !== null) { clearTimeout(this.hoverTimer); this.hoverTimer = null; }
		if(this.hoverCloseTimer !== null) { clearTimeout(this.hoverCloseTimer); this.hoverCloseTimer = null; }
		const wasOpen = this.pnls.length > 0;
		for(const p of this.pnls) (p.outer || p.el).remove();
		this.pnls = [];
		if(this.docDown) document.removeEventListener('pointerdown', this.docDown, { capture: true });
		if(this.docKey) document.removeEventListener('keydown', this.docKey);
		this.docDown = null;
		this.docKey = null;
		if(wasOpen && typeof this.opts.onClose === 'function') this.opts.onClose();
	}

	isOpen() {
		return this.pnls.length > 0;
	}

	destroy() {
		CerbUI.Menu._instances.delete(this.sourceUl);
		if(this.opts.hoverGroup) {
			const g = CerbUI.Menu._hoverGroups.get(this.opts.hoverGroup);
			if(g) g.delete(this);
		}
		if(this.opts.hoverTrigger && this.triggerEnter && this.triggerLeave) {
			this.opts.hoverTrigger.removeEventListener('mouseenter', this.triggerEnter);
			this.opts.hoverTrigger.removeEventListener('mouseleave', this.triggerLeave);
		}
		this.close();
	}

	// ── Panel stack ─────────────────────────────────────────────────────

	_push(items, depth) {
		while(this.pnls.length > depth) {
			const popped = this.pnls.pop();
			if(popped) (popped.outer || popped.el).remove();
		}
		const pnl = this._buildPanel(items, depth);
		this.pnls.push(pnl);
		if(this.opts.inline && depth === 0) {
			// Root panel goes into the document flow right after the source UL.
			this.sourceUl.insertAdjacentElement('afterend', pnl.outer || pnl.el);
		} else {
			document.body.appendChild(pnl.outer || pnl.el);
			this._place(pnl, depth);
		}
	}

	_buildPanel(items, depth) {
		const el = document.createElement('ul');
		el.className = 'cerb-ui-menu cerb-ui-menu--panel'
			+ (this.opts.inline && depth === 0 ? ' cerb-ui-menu--inline' : '')
			+ (this.opts.fixed ? ' cerb-ui-menu--fixed' : '');
		el.setAttribute('role', 'menu');

		// pnl.el is always the list <ul>; pnl.outer is the positioned/appended element — the same <ul>,
		// or a filter wrapper (input + ul) when type-to-filter is on at the root.
		const pnl = { el, outer: null, items, depth, activeIdx: -1, virt: false, visH: 0, spacerT: null, spacerB: null, scrollBound: false, filterInput: null, filterActive: false, flipUp: null };

		el.addEventListener('mouseover', (e) => this._onOver(e, pnl));
		el.addEventListener('click', (e) => this._onClickItem(e, pnl));
		if(this.opts.hoverTrigger) {
			el.addEventListener('mouseenter', () => this._hoverIn());
			el.addEventListener('mouseleave', () => this._hoverOut());
		}

		this._fillPanel(pnl); // render the (virtualized or plain) list from pnl.items

		if(this.opts.filter && depth === 0) {
			// A search input above the scrolling list. The wrapper carries the float chrome so the inner
			// <ul> keeps its own scroll + virtualization untouched.
			const wrap = document.createElement('div');
			wrap.className = 'cerb-ui-menu--filterwrap'
				+ (this.opts.inline ? ' cerb-ui-menu--inline' : '')
				+ (this.opts.fixed ? ' cerb-ui-menu--fixed' : '');
			const input = document.createElement('input');
			input.type = 'search';
			input.className = 'cerb-ui-menu--filter';
			input.hidden = true; // stays out of the way until the first keystroke reveals it (_showFilter)
			input.setAttribute('placeholder', this.opts.filterPlaceholder);
			input.setAttribute('aria-label', this.opts.filterPlaceholder);
			input.addEventListener('input', () => {
				if(input.value === '') this._hideFilter(pnl); // backspaced/cleared to empty -> tuck it away again
				else this._applyFilter(pnl, input.value);
			});
			wrap.appendChild(input);
			wrap.appendChild(el);
			pnl.outer = wrap;
			pnl.filterInput = input;
			// Inline menus drive the keyboard from their root element (no document listener). With a filter,
			// focus lives on the wrapper/input, so the keydown must sit on the wrapper to fire for nav/Enter/typing.
			if(this.opts.inline) {
				wrap.setAttribute('tabindex', '0');
				wrap.addEventListener('keydown', (e) => this._onKey(e));
			}
		} else if(this.opts.inline && depth === 0) {
			// Focusable so the menu can hold focus; keydown is scoped here (bubbles up from the focused item)
			// rather than on document, so an always-open inline menu only reacts when it's focused.
			el.setAttribute('tabindex', '0');
			el.addEventListener('keydown', (e) => this._onKey(e));
		}

		return pnl;
	}

	// (Re)render a panel's list from pnl.items — virtualized above virtThreshold, plain below. Runs on the
	// initial build and on every filter keystroke (the <ul>'s own listeners persist across re-fills).
	_fillPanel(pnl) {
		const o = this.opts;
		const el = pnl.el;
		const items = pnl.items;
		const virt = items.length > o.virtThreshold;

		pnl.virt = virt;
		pnl.activeIdx = -1;
		pnl.spacerT = pnl.spacerB = null;
		el.classList.toggle('cerb-ui-menu--virt', virt);
		el.replaceChildren();
		el.scrollTop = 0;

		if(virt) {
			const visH = Math.min(items.length * o.itemHeight, o.maxHeight);
			el.style.height = visH + 'px';
			el.style.maxHeight = '';   // height controls; --virt CSS supplies the scroll
			el.style.overflowY = '';
			pnl.visH = visH;
			pnl.spacerT = el.appendChild(CerbUI.Menu._spacerLi());
			pnl.spacerB = el.appendChild(CerbUI.Menu._spacerLi());
			this._renderVirt(pnl);
			if(!pnl.scrollBound) {
				el.addEventListener('scroll', () => this._renderVirt(pnl), { passive: true });
				pnl.scrollBound = true;
			}
		} else {
			// Cap + scroll a plain (non-virtualized) panel too — otherwise a list between ~13 and virtThreshold
			// items grows past maxHeight unbounded (the base panel CSS has no max-height; only --virt scrolls).
			el.style.height = '';
			el.style.maxHeight = o.maxHeight + 'px';
			el.style.overflowY = 'auto';
			pnl.visH = 0;
			if(items.length === 0 && this.opts.filter) {
				const empty = document.createElement('li');
				empty.className = 'cerb-ui-menu--filterempty';
				empty.setAttribute('aria-hidden', 'true');
				empty.textContent = this.opts.filterEmptyText;
				el.appendChild(empty);
			} else {
				const frag = document.createDocumentFragment();
				for(let i = 0; i < items.length; i++) frag.appendChild(this._mkItem(items[i], i));
				el.appendChild(frag);
			}
		}

		// Filtering changes the root panel's height. If it's already floating on screen, re-run placement so an
		// upward-flipped menu re-anchors to its trigger instead of leaving a gap (skipped during initial build,
		// when the element isn't connected yet, and for inline menus, which sit in document flow).
		if(pnl.depth === 0 && !this.opts.inline && (pnl.outer || pnl.el).isConnected)
			this._place(pnl, 0);
	}

	// Narrow the list to the query. A flat menu filters its root labels in place (cheap, no allocation).
	// A nested menu searches a flattened list of ALL leaves across the tree and shows matches as a flat
	// list with breadcrumb context — so a deep item is findable by name. Empty query restores the cascade.
	_applyFilter(pnl, query) {
		query = (query || '').trim().toLowerCase();

		if(!query) {
			pnl.items = this.root;
		} else if(this._hasNesting()) {
			pnl.items = this._flatten()
				.filter(leaf => leaf.search.includes(query))
				.map(leaf => ({ el: leaf.el, label: leaf.label, children: null, pathLabel: leaf.pathLabel }));
		} else {
			pnl.items = this.root.filter(it => !it.separator && (it.label || '').toLowerCase().includes(query));
		}

		// A changed list invalidates any open submenus
		while(this.pnls.length > 1) {
			const popped = this.pnls.pop();
			if(popped) (popped.outer || popped.el).remove();
		}

		this._fillPanel(pnl);
	}

	_hasNesting() {
		if(this._nested == null) this._nested = this.root.some(it => !!it.children);
		return this._nested;
	}

	// Walk the tree once into a flat list of leaves, each carrying its source <li>, its own label, a
	// breadcrumb of ancestor labels (display), and a space-joined lowercase key (search). Cached — the
	// tree is parsed once and never mutated.
	_flatten() {
		if(this._flat) return this._flat;
		const out = [];
		const walk = (items, trail) => {
			for(let i = 0; i < items.length; i++) {
				const it = items[i];
				if(it.separator) continue;
				if(it.children) {
					walk(it.children, trail.concat(it.label));
				} else {
					out.push({
						el: it.el,
						label: it.label,
						pathLabel: trail.length ? trail.join(' › ') : '', // › = "›" breadcrumb separator
						search: trail.concat(it.label).join(' ').toLowerCase(),
					});
				}
			}
		};
		walk(this.root, []);
		this._flat = out;
		return out;
	}

	// Reveal the (hidden) search box, seeded with the first typed char, so the menu stays uncluttered until
	// you actually filter. Subsequent chars flow into the focused input normally.
	_showFilter(pnl, ch) {
		const input = pnl.filterInput;
		if(!input) return;
		pnl.filterActive = true;
		input.hidden = false;
		input.value = (ch != null) ? ch : '';
		input.focus();
		this._applyFilter(pnl, input.value);
	}

	// Emptying the box (backspace / clear / Esc) tucks it away again and restores the full list.
	_hideFilter(pnl) {
		const input = pnl.filterInput;
		pnl.filterActive = false;
		if(input) { input.value = ''; input.hidden = true; }
		pnl.items = this.root;
		this._fillPanel(pnl);
		// Keep keyboard control: inline menus refocus the wrapper; floating menus already listen on document.
		if(this.opts.inline && pnl.outer) pnl.outer.focus();
	}

	_mkItem(item, idx, virt = false) {
		if(item.separator) {
			const li = document.createElement('li');
			li.className = 'cerb-ui-menu--separator';
			li.setAttribute('role', 'separator');
			li.dataset['i'] = String(idx);
			if(virt) li.style.height = this.opts.itemHeight + 'px';
			li.appendChild(document.createElement('hr'));
			return li;
		}

		const li = document.createElement('li');
		li.className = 'cerb-ui-menu--item' + (item.children ? ' cerb-ui-menu--item-has-sub' : '');
		li.setAttribute('role', 'menuitem');
		li.setAttribute('tabindex', '-1');
		li.dataset['i'] = String(idx);

		// Mirror data-* attributes from the source LI. The data-* allowlist is the security boundary —
		// non-data attrs (including any on* handlers from a hostile source) never reach the rendered tree.
		const attrs = item.el.attributes;
		for(let i = 0; i < attrs.length; i++) {
			const a = attrs[i];
			if(a.name.startsWith('data-') && a.name !== 'data-i')
				li.setAttribute(a.name, a.value);
		}

		const lbl = document.createElement('span');
		lbl.className = 'cerb-ui-menu--label';
		lbl.textContent = item.label; // textContent — never innerHTML for user data
		li.appendChild(lbl);

		if(item.pathLabel) { // breadcrumb context for a flattened deep-filter match (muted, right-aligned)
			li.classList.add('cerb-ui-menu--item-pathed');
			const path = document.createElement('span');
			path.className = 'cerb-ui-menu--path';
			path.textContent = item.pathLabel;
			li.appendChild(path);
		}

		if(typeof this.opts.onRenderItem === 'function') this.opts.onRenderItem(li, item.el);

		if(item.children) {
			const arrow = document.createElement('span');
			arrow.className = 'cerb-ui-menu--arrow';
			arrow.setAttribute('aria-hidden', 'true');
			const ico = document.createElement('span'); // cerb-icons glyph (no innerHTML write at all)
			ico.className = 'cerb-icons cerb-icon-chevron-right';
			arrow.appendChild(ico);
			li.appendChild(arrow);
		}

		return li;
	}

	// ── Virtual list ────────────────────────────────────────────────────
	// Only ~(visible + 2*buffer) items exist in the DOM. Two sentinel <li.cerb-ui-menu--spacer> carry the
	// height above/below the render window so scrollTop stays stable.

	_renderVirt(pnl) {
		if(!pnl.spacerT || !pnl.spacerB) return;
		const o = this.opts;
		const ih = o.itemHeight;
		const buf = o.virtBuffer;
		const el = pnl.el;
		const st = el.scrollTop;
		const len = pnl.items.length;

		const s = Math.max(0, Math.floor(st / ih) - buf);
		const e2 = Math.min(len - 1, Math.ceil((st + pnl.visH) / ih) + buf);

		// Spacer heights keep total scrollable height = len * ih (constant)
		pnl.spacerT.style.height = (s * ih) + 'px';
		pnl.spacerB.style.height = Math.max(0, (len - 1 - e2) * ih) + 'px';

		const frag = document.createDocumentFragment();
		for(let i = s; i <= e2; i++) {
			const li = this._mkItem(pnl.items[i], i, true);
			if(i === pnl.activeIdx) li.classList.add('cerb-ui-menu--item-active');
			frag.appendChild(li);
		}

		// Remove the previous window items (between the two sentinels)
		while(pnl.spacerT.nextSibling && pnl.spacerT.nextSibling !== pnl.spacerB)
			pnl.spacerT.nextSibling.remove();
		el.insertBefore(frag, pnl.spacerB);
	}

	// ── Hover trigger ───────────────────────────────────────────────────

	_bindHoverTrigger(el) {
		this.triggerEnter = () => {
			this._hoverIn();
			if(!this.isOpen()) {
				if(this.opts.hoverGroup) {
					const g = CerbUI.Menu._hoverGroups.get(this.opts.hoverGroup);
					if(g) g.forEach(m => { if(m !== this) m.close(); });
				}
				this.open(el);
			}
		};
		this.triggerLeave = () => this._hoverOut();
		el.addEventListener('mouseenter', this.triggerEnter);
		el.addEventListener('mouseleave', this.triggerLeave);
	}

	_hoverIn() {
		this.hoverMouseInside = true;
		if(this.hoverCloseTimer !== null) { clearTimeout(this.hoverCloseTimer); this.hoverCloseTimer = null; }
	}

	_hoverOut() {
		this.hoverMouseInside = false;
		if(this.hoverCloseTimer !== null) clearTimeout(this.hoverCloseTimer);
		this.hoverCloseTimer = window.setTimeout(() => {
			this.hoverCloseTimer = null;
			if(!this.hoverMouseInside) this.close();
		}, this.opts.hoverCloseDelay);
	}

	// ── Event handlers ──────────────────────────────────────────────────

	_onOver(e, pnl) {
		const target = e.target;
		const li = target ? target.closest('.cerb-ui-menu--item') : null;
		if(!li || !pnl.el.contains(li)) return;

		const prev = pnl.el.querySelectorAll('.cerb-ui-menu--item-active');
		for(let i = 0; i < prev.length; i++) prev[i].classList.remove('cerb-ui-menu--item-active');
		li.classList.add('cerb-ui-menu--item-active');
		pnl.activeIdx = +(li.dataset['i'] ?? -1);

		if(this.hoverTimer !== null) { clearTimeout(this.hoverTimer); this.hoverTimer = null; }
		const item = pnl.items[pnl.activeIdx];

		if(item && item.children) {
			this.hoverTimer = window.setTimeout(() => {
				if(item.children) this._push(item.children, pnl.depth + 1);
			}, this.opts.openDelay);
		} else {
			// Trim deeper panels immediately
			while(this.pnls.length > pnl.depth + 1) {
				const popped = this.pnls.pop();
				if(popped) popped.el.remove();
			}
		}
	}

	_select(renderedLi, sourceLi, e) {
		if(typeof this.opts.onSelect === 'function') {
			this.opts.onSelect(renderedLi, sourceLi, e);
		} else {
			const a = sourceLi.querySelector('a');
			if(a) a.click();
		}
	}

	_onClickItem(e, pnl) {
		const target = e.target;
		const li = target ? target.closest('.cerb-ui-menu--item') : null;
		if(!li) return;
		const item = pnl.items[+(li.dataset['i'] ?? -1)];
		if(item && !item.children) {
			this._select(li, item.el, e);
			if(this.opts.inline) {
				// Collapse floating submenus but leave the root panel open.
				while(this.pnls.length > 1) {
					const popped = this.pnls.pop();
					if(popped) popped.el.remove();
				}
			} else if(this.opts.closeOnSelect) {
				this.close();
			}
			// else (floating, closeOnSelect:false): leave the panels open where they are
			// so several siblings can be picked in a row.
		}
	}

	// ── Keyboard ────────────────────────────────────────────────────────
	// Esc — close current panel (or whole menu at depth 0); ArrowLeft — up one level;
	// ArrowRight/Enter — open submenu or select leaf; ArrowUp/Down/Home/End — navigate the current panel.

	_onKey(e) {
		const depth = this.pnls.length - 1;
		if(depth < 0) return;
		const pnl = this.pnls[depth];

		// Type-to-filter: the first printable key (from ANY depth) reveals the root search box seeded with
		// that char and collapses to a flat search of the whole tree. Then keys fall through to the input.
		const root = this.pnls[0];
		if(this.opts.filter && root && root.filterInput && !root.filterActive
			&& e.key.length === 1 && e.key !== ' ' && !e.ctrlKey && !e.metaKey && !e.altKey) {
			e.preventDefault();
			this._showFilter(root, e.key);
			return;
		}

		switch(e.key) {
			case 'Escape':
				e.preventDefault();
				if(this.opts.filter && depth === 0 && pnl.filterActive) {
					this._hideFilter(pnl);
				} else if(depth > 0) {
					const popped = this.pnls.pop();
					if(popped) (popped.outer || popped.el).remove();
				} else if(!this.opts.inline) {
					this.close();
				}
				return;

			case 'ArrowLeft':
				if(depth > 0) {
					e.preventDefault();
					const popped = this.pnls.pop();
					if(popped) popped.el.remove();
				}
				return;

			case 'ArrowRight':
			case 'Enter': {
				e.preventDefault();
				const active = pnl.el.querySelector('.cerb-ui-menu--item-active');
				if(!active) return;
				const item = pnl.items[+(active.dataset['i'] ?? -1)];
				if(!item) return;
				if(item.children) {
					this._push(item.children, pnl.depth + 1);
					// keyboard-opened: highlight the submenu's first item so nav flows straight into it
					this._navigate(this.pnls[this.pnls.length - 1], 0, true);
				} else {
					this._select(active, item.el, e);
					if(this.opts.inline) {
						while(this.pnls.length > 1) {
							const popped = this.pnls.pop();
							if(popped) popped.el.remove();
						}
					} else if(this.opts.closeOnSelect) {
						this.close();
					}
				}
				return;
			}

			case 'ArrowDown': e.preventDefault(); this._navigate(pnl, +1); return;
			case 'ArrowUp':   e.preventDefault(); this._navigate(pnl, -1); return;
			case 'Home':      e.preventDefault(); this._navigate(pnl, 0, true); return;
			case 'End':       e.preventDefault(); this._navigate(pnl, 0, false, true); return;
		}
	}

	_navigate(pnl, dir, home = false, end = false) {
		const items = pnl.items;
		const len = items.length;
		if(len === 0) return;

		let next;
		if(home) {
			next = 0;
			while(next < len - 1 && items[next].separator) next++;
		} else if(end) {
			next = len - 1;
			while(next > 0 && items[next].separator) next--;
		} else {
			const step = dir > 0 ? 1 : -1;
			const cur = pnl.activeIdx < 0 ? (dir > 0 ? -1 : len) : pnl.activeIdx;
			next = cur;
			for(let t = 0; t < len; t++) {
				next = ((next + step) % len + len) % len;
				if(!items[next].separator) break;
			}
		}

		if(items[next] && items[next].separator) return;
		pnl.activeIdx = next;

		if(pnl.virt) {
			const ih = this.opts.itemHeight;
			pnl.el.scrollTop = Math.max(0, next * ih - (pnl.visH / 2) + ih / 2);
			this._renderVirt(pnl);
		} else {
			const prev = pnl.el.querySelectorAll('.cerb-ui-menu--item-active');
			for(let i = 0; i < prev.length; i++) prev[i].classList.remove('cerb-ui-menu--item-active');
			const target = pnl.el.querySelector('.cerb-ui-menu--item[data-i="' + next + '"]');
			if(target) {
				target.classList.add('cerb-ui-menu--item-active');
				target.scrollIntoView({ block: 'nearest' });
			}
		}
	}

	// ── Positioning ─────────────────────────────────────────────────────
	// Panels are position:absolute and append to <body>. Flip logic runs in viewport space
	// (getBoundingClientRect); scroll offsets are added when writing the final coords (unless `fixed`).

	_place(pnl, depth) {
		const el = pnl.outer || pnl.el;
		// clientWidth/clientHeight exclude the scrollbar track (innerWidth/Height include it → off-by-scrollbar).
		const vw = document.documentElement.clientWidth;
		const vh = document.documentElement.clientHeight;

		// Park offscreen-left so the panel never spawns a right-side scrollbar during measurement.
		el.style.left = '-9999px';
		el.style.top = '0px';
		const pw = el.offsetWidth || 200;
		const ph = el.offsetHeight || 100;

		let x;
		let y;

		if(depth === 0) {
			if(!this.anchor) return;
			const r0 = this.anchor.getBoundingClientRect();
			x = r0.left;
			// Decide the flip direction ONCE, on the first placement (full-height list), and keep it for the
			// life of the panel. Filtering re-runs _place on a shorter panel; re-deciding here would let a menu
			// that opened upward flip back below a near-bottom trigger once it shrank — dropping off the fold.
			// When flipped up the panel's bottom stays pinned to the trigger top, so it can't leave the viewport.
			if(pnl.flipUp === null) pnl.flipUp = (r0.bottom + 2 + ph > vh);
			y = pnl.flipUp ? (r0.top - ph - 2) : (r0.bottom + 2);
			y = Math.max(4, Math.min(y, vh - ph - 4)); // backstop clamp (e.g. a panel taller than the space above)
			if(x + pw > vw) x = vw - pw - 4;
			x = Math.max(0, x);
		} else {
			const par = this.pnls[depth - 1];
			const refEl = par.el.querySelector('.cerb-ui-menu--item-active') ?? par.el;
			const r1 = refEl.getBoundingClientRect();
			x = r1.right + 2;
			y = r1.top;
			if(x + pw > vw) x = r1.left - pw - 2;
			x = Math.max(0, Math.min(x, vw - pw - 4));
			if(y + ph > vh) y = Math.max(4, vh - ph - 4);
			if(y < 0) y = 4;
		}

		el.style.left = (x + (this.opts.fixed ? 0 : window.scrollX)) + 'px';
		el.style.top  = (y + (this.opts.fixed ? 0 : window.scrollY)) + 'px';
	}

	_hitTest(target) {
		for(const p of this.pnls) {
			if((p.outer || p.el).contains(target)) return true;
		}
		return false;
	}
};
