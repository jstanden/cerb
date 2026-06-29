/*
 * CerbUI.Sortable — pointer-drag reordering for a list of child elements (zero-dependency).
 *
 * Usage:
 *   new CerbUI.Sortable(el, { handle: '.drag', onSorted: (info) => { ... } });
 *
 * Markup: a container whose direct children are the sortable items (override with `items`). During a drag
 * the component uses two placeholders: an ORIGIN placeholder (dashed) holds the item's starting slot, and a
 * DEST placeholder (accent) moves to follow the current insertion point. The dragged element floats as the
 * "helper" (the item itself by default, or a clone / custom node).
 *
 * Options:
 *   items            CSS selector for sortable children (default '> *')
 *   handle           CSS selector for a drag handle within each item; drag only starts from it (default none)
 *   helper           'original' | 'clone' | (item) => HTMLElement  (default 'original')
 *   distance         pixels the pointer must move before a drag activates (default 5)
 *   tolerance        'pointer' (midpoint) | 'intersect' (max overlap)  (default 'pointer')
 *   connectWith      other Sortable containers for cross-list dragging — an array of elements, or a CSS
 *                    selector string (resolved against the document at drag-start, so containers added later
 *                    are picked up automatically). Only elements that are themselves Sortables participate.
 *   placeholderClass extra class added to both placeholder elements
 *   onStart/onStop/onSorted  callbacks receiving { item, from, to, fromIndex, toIndex }
 *
 * On sort, also dispatches a `cerb-ui-sortable:sorted` CustomEvent (detail = the same info) on the container.
 */
CerbUI.Sortable = class {
	static _instances = new WeakMap();
	static from(el) { return CerbUI.Sortable._instances.get(el); }
	static _uid = 0;

	constructor(el, options = {}) {
		this.container = (typeof el === 'string') ? document.querySelector(el) : el;
		this.opts = Object.assign({
			items:            '> *',
			handle:           '',
			helper:           'original',
			distance:         5,
			tolerance:        'pointer',
			connectWith:      [],
			placeholderClass: '',
			ghostOrigin:      false, // show the origin slot as a dimmed clone of the item (vs. a dashed box)
			grid:             false, // 2D wrap-grid mode: hold the source slot with the origin ghost and show
			                         // the drop spot as an absolute insertion bar (nothing reflows). Pairs with
			                         // ghostOrigin:true so the grid stays static while dragging.
			anchor:           null,  // which point of the dragged item decides the drop target:
			                         // 'pointer' (cursor) | 'top-left' | 'top-right' | 'center'. Use when the
			                         // drag handle isn't where you visually aim (e.g. a top-right handle on a
			                         // wide widget). null = mode default (grid → 'top-left', else 'pointer').
			disabled:         false, // when true, drags don't start (toggle opts.disabled to gate dragging)
			onStart:          null,
			onStop:           null,  // fires after release, BEFORE the DOM commit (valid drops only)
			onSorted:         null,  // fires after the DOM commit (valid drops only)
			onEnd:            null,  // fires when the drag finishes — commit OR cancel (snap-back) — for cleanup
		}, options);
		this.uid = ++CerbUI.Sortable._uid;
		this.drag = null;

		this.onPointerDown = this.onPointerDown.bind(this);
		this.onDragMove = this.onDragMove.bind(this);
		this.onDragEnd = this.onDragEnd.bind(this);

		if(!this.container) return;
		this.init();
		CerbUI.Sortable._instances.set(this.container, this);
	}

	// Re-apply item/handle cursor classes after external DOM mutations.
	refresh() {
		this.refreshItemClasses();
	}

	destroy() {
		if(this.drag) this.cancelDrag();
		this.container.removeEventListener('pointerdown', this.onPointerDown);
		this.container.classList.remove('cerb-ui-sortable');
		for(const item of this.getItems()) {
			item.classList.remove('cerb-ui-sortable--item');
		}
		if(this.opts.handle) {
			for(const el of Array.from(this.container.querySelectorAll(this.opts.handle))) {
				el.classList.remove('cerb-ui-sortable--handle');
			}
		}
		CerbUI.Sortable._instances.delete(this.container);
	}

	// ── Init ──────────────────────────────────────────────────────────────

	init() {
		this.container.classList.add('cerb-ui-sortable');
		this.container.addEventListener('pointerdown', this.onPointerDown);
		this.refreshItemClasses();
	}

	// Prefix :scope when a selector starts with a combinator (e.g. '> b', '+ .x') so querySelector(All)
	// receives a valid relative selector. Plain descendant/class selectors pass through unchanged.
	scopeSelector(sel) {
		const raw = sel.trimStart();
		return /^[>+~]/.test(raw) ? `:scope ${raw}` : raw;
	}

	refreshItemClasses() {
		if(this.opts.handle) {
			const handleSel = this.scopeSelector(this.opts.handle);
			for(const item of this.getItems()) {
				item.classList.remove('cerb-ui-sortable--item');
				const handle = item.querySelector(handleSel);
				if(handle) handle.classList.add('cerb-ui-sortable--handle');
			}
		} else {
			for(const item of this.getItems()) {
				item.classList.add('cerb-ui-sortable--item');
			}
		}
	}

	// ── Item query ────────────────────────────────────────────────────────

	getItems() {
		// Prepend :scope when selector starts with a combinator (e.g. '> li')
		// so querySelectorAll receives a valid absolute selector.
		const sel = this.scopeSelector(this.opts.items);
		return Array.from(this.container.querySelectorAll(sel)).filter(
			el =>
				el.parentElement === this.container &&
				!el.classList.contains('cerb-ui-sortable--origin') &&
				!el.classList.contains('cerb-ui-sortable--dest') &&
				// the dragged item (original-mode helper kept in place) is out of flow — never a real slot
				!el.classList.contains('cerb-ui-sortable--helper'),
		);
	}

	getItemIndex(el) {
		return this.getItems().indexOf(el);
	}

	// ── Orientation detection ─────────────────────────────────────────────

	detectOrientation(items) {
		if(items.length < 2) return 'vertical';
		const r0 = items[0].getBoundingClientRect();
		const r1 = items[1].getBoundingClientRect();
		return Math.abs(r1.top - r0.top) > 4 ? 'vertical' : 'horizontal';
	}

	// ── Pointer events ────────────────────────────────────────────────────

	onPointerDown(e) {
		if(e.button !== 0) return;
		if(this.opts.disabled) return; // dragging gated off (e.g. a column's multi-select mode)

		const target = e.target;

		// Find the sortable item that was clicked — avoids relative-selector issues
		// with closest() by scanning the live item list instead.
		const item = this.getItems().find(el => el === target || el.contains(target));
		if(!item) return;

		// Don't hijack pointer-downs on interactive text controls inside an item — let inputs/textareas/
		// contenteditables keep native caret placement, text selection, and double-click word-select.
		if(target.closest('input, textarea, select, [contenteditable]'))
			return;

		// Enforce handle constraint. Resolve the handle element(s) RELATIVE to the item (so combinator
		// selectors like '> b' work) and require the pointer-down to land on one of them.
		if(this.opts.handle) {
			const handles = item.querySelectorAll(this.scopeSelector(this.opts.handle));
			let onHandle = false;
			for(const h of handles) {
				if(h === target || h.contains(target)) { onHandle = true; break; }
			}
			if(!onHandle) return;
		}

		e.preventDefault();
		this.beginPreDrag(e, item);
	}

	beginPreDrag(startEvent, item) {
		const startX = startEvent.clientX;
		const startY = startEvent.clientY;

		const onMove = (e) => {
			const dx = e.clientX - startX;
			const dy = e.clientY - startY;
			if(Math.hypot(dx, dy) >= this.opts.distance) {
				document.removeEventListener('pointermove', onMove);
				document.removeEventListener('pointerup', onCancel);
				this.activateDrag(e, item);
			}
		};

		const onCancel = () => {
			document.removeEventListener('pointermove', onMove);
			document.removeEventListener('pointerup', onCancel);
		};

		document.addEventListener('pointermove', onMove);
		document.addEventListener('pointerup', onCancel);
	}

	activateDrag(e, item) {
		const itemRect = item.getBoundingClientRect();
		const fromIndex = this.getItemIndex(item);

		// Capture the original next-sibling before any DOM mutations so we can
		// restore the item to its exact original position on cancel.
		const restoreAnchor = item.nextElementSibling;

		// originEl fills the item's slot for the duration of the drag.
		// destEl moves to indicate the current drop target.
		// Build the origin ghost from the item BEFORE it's turned into the helper (still clean here).
		// Capture the item's computed box participation so the placeholders hold the slot without
		// breaking a horizontal / inline-block / floated / flex row (a default block <div> would wrap).
		const itemCS = getComputedStyle(item);
		// When the items are table sections/rows the placeholders must be valid table elements (a <div>
		// gets hoisted out of a <table> by the parser); tableTag is '' for normal element lists.
		const tableTag = this.tablePartTag(item);
		const originEl = this.createPlaceholder('origin', itemRect, item, itemCS, tableTag);
		const destEl   = this.createPlaceholder('dest',   itemRect, null, itemCS, tableTag);

		// Insert originEl where item is now (before the item in the DOM).
		item.parentElement.insertBefore(originEl, item);

		const helperEl = this.buildHelper(item, itemRect);

		if(this.opts.helper === 'original') {
			// The item itself becomes the floating helper.
			item.classList.add('cerb-ui-sortable--helper');
			item.style.width = `${itemRect.width}px`;
			item.style.left  = `${itemRect.left}px`;
			item.style.top   = `${itemRect.top}px`;
			// Item stays in place — position:fixed lifts it out of flow so its slot collapses. Only if a
			// transformed/contained ancestor hijacks the fixed containing block does the offset check below
			// relocate it to <body>; keeping it here in the common case preserves ancestor-selector styling
			// (e.g. the nav menu's `ul.navmenu > li`).
		} else {
			// Clone / custom: detach the original item so it doesn't consume space.
			// restoreAnchor already points past it, so the slot is held by originEl.
			item.remove();
			helperEl.style.left  = `${itemRect.left}px`;
			helperEl.style.top   = `${itemRect.top}px`;
			helperEl.style.width = `${itemRect.width}px`;
			document.body.appendChild(helperEl);
			// A bare cloned/custom node (e.g. a <fieldset>/<li> with no background of its own) is
			// transparent — the page shows through while dragging. Give it a solid bg only when it's
			// genuinely see-through, so an item that carries its own background keeps it.
			const hbg = getComputedStyle(helperEl).backgroundColor;
			if(hbg === 'rgba(0, 0, 0, 0)' || hbg === 'transparent')
				helperEl.style.backgroundColor = 'var(--cerb-color-background)';
		}

		// Detect a transformed / filtered / contained ANCESTOR that hijacks the position:fixed containing
		// block: `fixed` then resolves against ITS box (not the viewport), so the helper sits offset — often
		// far down-right — from the cursor, with a phantom source-slot gap (seen in AJAX-loaded peeks and the
		// worklist export panel). Neutralize the helper's own rotate/scale and measure where its fixed box
		// landed vs. where we asked.
		helperEl.style.transform = 'none';
		let placedRect = helperEl.getBoundingClientRect();
		helperEl.style.transform = ''; // restore the class-driven rotate/scale
		let fixedOffsetX = placedRect.left - itemRect.left;
		let fixedOffsetY = placedRect.top  - itemRect.top;

		// If an ancestor IS hijacking it, relocate the floating ORIGINAL item to <body> so position:fixed is
		// viewport-relative again — this fixes the offset AND the gap. Done only when needed, so the common
		// case keeps the item in place (ancestor selectors still style it). Clone/custom helpers already live
		// on <body>; they just keep any residual offset below.
		if((fixedOffsetX || fixedOffsetY) && this.opts.helper === 'original' && helperEl.parentElement !== document.body) {
			document.body.appendChild(helperEl);
			helperEl.style.left = `${itemRect.left}px`;
			helperEl.style.top  = `${itemRect.top}px`;
			helperEl.style.transform = 'none';
			placedRect = helperEl.getBoundingClientRect();
			helperEl.style.transform = '';
			fixedOffsetX = placedRect.left - itemRect.left;
			fixedOffsetY = placedRect.top  - itemRect.top;
		}

		// Carry any residual offset (e.g. <body> itself transformed) and subtract it on every move.
		if(fixedOffsetX || fixedOffsetY) {
			helperEl.style.left = `${itemRect.left - fixedOffsetX}px`;
			helperEl.style.top  = `${itemRect.top  - fixedOffsetY}px`;
		}

		// Grid mode: the drop spot is an absolute insertion bar (no per-move reflow). ghostOrigin HOLDS the
		// source slot with a dimmed clone, so the grid stays completely static while dragging (the lifted
		// tile's space is reserved); without it, free the slot so the grid settles with the item lifted out.
		if(this.opts.grid) {
			if(!this.opts.ghostOrigin) originEl.style.display = 'none';
			// Thin absolute bar (positioned on the first move); start collapsed so the full-size dest
			// box never flashes in the grid before positioning.
			destEl.style.position = 'absolute';
			destEl.style.width = '3px';
			destEl.style.height = '0';
			this.container.appendChild(destEl);
		} else {
			// Single moving placeholder (jQuery-UI feel): destEl marks the drop spot; originEl is only a
			// hidden anchor for destEl's initial position. The dragged item is floated out of the container
			// (to <body> for 'original', detached for clone), so the source slot already collapses.
			originEl.after(destEl);
			originEl.style.display = 'none';
		}

		// Detect layout axis from the current item set.
		const preItems = this.getItems().filter(el => el !== item);
		const axis = this.detectOrientation(preItems);

		// Cache connected container rects once for the drag lifetime. connectWith may be an array of
		// elements or a CSS selector string — resolve the string here so containers added since init
		// (e.g. a board column created at runtime) are included. Only elements that are themselves
		// Sortables participate; never connect a container to itself.
		const connectTargets = (typeof this.opts.connectWith === 'string')
			? Array.from(document.querySelectorAll(this.opts.connectWith))
			: this.opts.connectWith;
		const connectedRects = connectTargets
			.filter(el => el !== this.container)
			.map(el => ({ instance: CerbUI.Sortable.from(el), rect: el.getBoundingClientRect() }))
			.filter(r => r.instance != null);

		const offsetX = e.clientX - itemRect.left;
		const offsetY = e.clientY - itemRect.top;

		this.container.classList.add('cerb-ui-sortable--active');
		// Suppress text selection page-wide while dragging (the cursor sweeps over other content; the
		// container's own user-select:none doesn't cover that). Removed in teardownDrag (commit OR cancel).
		document.documentElement.classList.add('cerb-ui-sortable-dragging');

		const moveHandler = (me) => this.onDragMove(me);
		const upHandler   = (ue) => this.onDragEnd(ue);
		const keyHandler  = (ke) => {
			if(ke.key === 'Escape') this.cancelDrag();
		};

		document.addEventListener('pointermove', moveHandler);
		document.addEventListener('pointerup',   upHandler);
		document.addEventListener('keydown',     keyHandler);

		this.drag = {
			item, helperEl, originEl, destEl,
			restoreAnchor,
			fromContainer: this.container,
			fromIndex,
			activeInstance: this,
			connectedRects,
			offsetX, offsetY,
			itemWidth: itemRect.width, itemHeight: itemRect.height,
			fixedOffsetX, fixedOffsetY,
			axis,
			currentDestIndex: fromIndex,
			moveHandler, upHandler, keyHandler,
		};

		if(this.opts.onStart) this.opts.onStart(this.makeInfo());
	}

	onDragMove(e) {
		const drag = this.drag;
		if(!drag) return;

		const cx = e.clientX;
		const cy = e.clientY;

		// Move the helper element (subtracting any containing-block offset measured at drag start, so a
		// transformed/contained ancestor doesn't push the helper away from the cursor).
		const floatEl = (this.opts.helper === 'original') ? drag.item : drag.helperEl;
		floatEl.style.left = `${cx - drag.offsetX - drag.fixedOffsetX}px`;
		floatEl.style.top  = `${cy - drag.offsetY - drag.fixedOffsetY}px`;

		const newActive = this.resolveActiveContainer(cx, cy, drag);

		if(newActive === null) {
			// Outside all containers: retract the dest indicator (drop here snaps back to origin).
			if(drag.destEl.parentElement) {
				drag.destEl.remove();
				drag.activeInstance.container.classList.remove('cerb-ui-sortable--active');
			}
			return;
		}

		if(newActive !== drag.activeInstance) {
			drag.activeInstance.container.classList.remove('cerb-ui-sortable--active');
			newActive.container.classList.add('cerb-ui-sortable--active');
			drag.activeInstance = newActive;
			drag.axis = this.detectOrientation(newActive.getItems());
		}

		this.updateDestPlaceholder(cx, cy, drag);
	}

	onDragEnd(e) {
		const drag = this.drag;
		if(!drag) return;

		// If released outside all valid containers, snap back to origin.
		if(this.resolveActiveContainer(e.clientX, e.clientY, drag) === null) {
			this.teardownDrag(drag, true);
			return;
		}

		// destEl may have been retracted (mouse briefly left then re-entered too fast).
		// Re-insert it at the last known good position before committing.
		if(!drag.destEl.parentElement) {
			this.updateDestPlaceholder(e.clientX, e.clientY, drag);
		}

		const info = this.makeInfo();

		if(this.opts.onStop) this.opts.onStop(info);

		this.commitDrop(drag);

		if(this.opts.onSorted) this.opts.onSorted(info);
		if(drag.activeInstance !== this && drag.activeInstance.opts.onSorted) {
			drag.activeInstance.opts.onSorted(info);
		}

		this.container.dispatchEvent(new CustomEvent('cerb-ui-sortable:sorted', {
			detail: info,
			bubbles: true,
		}));

		this.teardownDrag(drag, false);
	}

	cancelDrag() {
		const drag = this.drag;
		if(!drag) return;
		this.teardownDrag(drag, true);
	}

	// ── Drag helpers ──────────────────────────────────────────────────────

	// Returns 'tbody'/'tr' when items are table sections/rows (placeholders + the float helper must then be
	// real table elements), else '' for normal element lists.
	tablePartTag(item) {
		const t = item.tagName.toLowerCase();
		return (t === 'tbody' || t === 'tr') ? t : '';
	}

	createPlaceholder(kind, rect, item, cs, tableTag) {
		// Table placeholder: a real <tbody>/<tr> (valid inside the <table>) whose single full-width cell
		// carries the slot height + the drop-indicator look. Skips the element-box styling below.
		if(tableTag) {
			const el = document.createElement(tableTag);
			el.className = `cerb-ui-sortable--${kind}`;
			if(this.opts.placeholderClass) el.classList.add(this.opts.placeholderClass);
			el.setAttribute('aria-hidden', 'true');
			const tr = (tableTag === 'tbody') ? el.appendChild(document.createElement('tr')) : el;
			const td = tr.appendChild(document.createElement('td'));
			td.colSpan = 999; // spans the row; browsers clamp to the real column count
			td.style.boxSizing = 'border-box';
			td.style.height = `${rect.height}px`;
			td.style.padding = '0';
			td.style.borderRadius = '8px';
			if(kind === 'dest') {
				td.style.border = '2px solid var(--cerb-color-action-primary)';
				td.style.background = 'color-mix(in srgb, var(--cerb-color-action-primary) 10%, transparent)';
			} else {
				td.style.border = '2px dashed var(--cerb-color-background-contrast-200)';
				td.style.background = 'var(--cerb-color-background-contrast-240)';
			}
			return el;
		}

		const el = document.createElement('div');
		el.className = `cerb-ui-sortable--${kind}`;
		if(this.opts.placeholderClass) el.classList.add(this.opts.placeholderClass);
		el.style.width  = `${rect.width}px`;
		el.style.height = `${rect.height}px`;
		el.setAttribute('aria-hidden', 'true');

		// Mirror the item's box participation so the placeholder holds the slot in the SAME flow —
		// otherwise a default block <div> dropped into a horizontal / inline-block / floated row forces
		// the row to wrap vertically. A floated item computes display:block but needs its float copied;
		// an inline / inline-block item needs an inline-block placeholder (inline can't be sized).
		if(cs) {
			let disp = cs.display;
			if(disp === 'inline') disp = 'inline-block';
			if(disp === 'list-item') disp = 'block'; // avoid a stray list marker on the placeholder
			if(disp && disp !== 'block') el.style.display = disp;
			if(cs.float && cs.float !== 'none') el.style.float = cs.float;
			// Inline-level placeholders align by the box TOP, not a text baseline: an EMPTY box's baseline
			// sits at its bottom edge, so baseline alignment would lift the placeholder above same-height
			// inline-block items — growing the row and causing visible jitter.
			if(el.style.display && el.style.display.startsWith('inline'))
				el.style.verticalAlign = 'top';
			el.style.marginTop    = cs.marginTop;
			el.style.marginRight  = cs.marginRight;
			el.style.marginBottom = cs.marginBottom;
			el.style.marginLeft   = cs.marginLeft;
			// In a flex layout the item's slot is governed by `flex`, not width — copy it so the placeholder
			// opens a same-size slot (e.g. a full-row widget) instead of shrinking to content. Harmless
			// outside flex (the flex props are simply ignored).
			el.style.flexGrow   = cs.flexGrow;
			el.style.flexShrink = cs.flexShrink;
			el.style.flexBasis  = cs.flexBasis;
		}

		// ghostOrigin: fill the origin slot with a dimmed clone of the item so it's obviously
		// "where this came back from" rather than an empty box competing with the drop indicator.
		if(kind === 'origin' && this.opts.ghostOrigin && item) {
			const ghost = item.cloneNode(true);
			ghost.removeAttribute('id');
			ghost.classList.remove('cerb-ui-sortable--helper');
			ghost.classList.add('cerb-ui-sortable--ghost');
			ghost.style.position = ghost.style.left = ghost.style.top = ghost.style.width = '';
			// strip form controls so the clone never doubles up submitted values mid-drag
			ghost.querySelectorAll('input, select, textarea').forEach(n => n.remove());
			el.classList.add('cerb-ui-sortable--origin-ghost');
			el.appendChild(ghost);
		}

		return el;
	}

	buildHelper(item, _rect) {
		const h = this.opts.helper;
		if(h === 'original') return item;
		if(h === 'clone') {
			const clone = item.cloneNode(true);
			clone.removeAttribute('id');
			const tableTag = this.tablePartTag(item);
			if(tableTag) return this.wrapTableHelper(item, clone);
			clone.classList.add('cerb-ui-sortable--helper');
			return clone;
		}
		const custom = h(item);
		custom.classList.add('cerb-ui-sortable--helper');
		return custom;
	}

	// Wrap a cloned <tbody>/<tr> in a fresh <table> so it renders while floating (a bare table section
	// doesn't), copying the source table's chrome + the row's per-column widths so the floating row lines
	// up with the original.
	wrapTableHelper(item, clone) {
		const tag = item.tagName.toLowerCase();
		const srcTable = item.closest('table');
		const table = document.createElement('table');
		table.className = 'cerb-ui-sortable--helper' + (srcTable ? ' ' + srcTable.className : '');
		if(srcTable) table.style.borderCollapse = getComputedStyle(srcTable).borderCollapse;
		table.style.tableLayout = 'fixed';
		table.style.margin = '0';

		// Match each column's rendered width so the floating row aligns with the original.
		const srcRow = (tag === 'tbody') ? item.querySelector(':scope > tr') : item;
		const cells = srcRow ? srcRow.children : [];
		if(cells.length) {
			const colgroup = document.createElement('colgroup');
			for(const c of cells) {
				const col = document.createElement('col');
				col.style.width = `${c.getBoundingClientRect().width}px`;
				colgroup.appendChild(col);
			}
			table.appendChild(colgroup);
		}

		if(tag === 'tbody') {
			table.appendChild(clone);
		} else {
			const tb = document.createElement('tbody');
			tb.appendChild(clone);
			table.appendChild(tb);
		}
		return table;
	}

	resolveActiveContainer(cx, cy, drag) {
		const ownRect = this.container.getBoundingClientRect();
		if(cx >= ownRect.left && cx <= ownRect.right &&
			cy >= ownRect.top  && cy <= ownRect.bottom) {
			return this;
		}
		// Use LIVE rects for connected containers — a rect cached at drag-start goes stale if a
		// container scrolls mid-drag (e.g. a horizontally-scrolling row), mis-resolving the hovered one.
		for(const { instance } of drag.connectedRects) {
			const rect = instance.container.getBoundingClientRect();
			if(cx >= rect.left && cx <= rect.right &&
				cy >= rect.top  && cy <= rect.bottom) {
				return instance;
			}
		}
		return null;
	}

	updateDestPlaceholder(cx, cy, drag) {
		const targetInst = drag.activeInstance;
		const container  = targetInst.container;

		const items = targetInst.getItems().filter(
			el => el !== drag.item && el !== drag.originEl && el !== drag.destEl,
		);

		const insertBefore = this.computeInsertionPoint(cx, cy, drag, items);

		// Grid mode: don't move anything in flow — just position the absolute insertion bar and record
		// the drop index. computeInsertionPoint() returns a real item (or null = end) in grid mode.
		if(this.opts.grid) {
			drag.gridInsertBefore = insertBefore;
			const idx = insertBefore ? items.indexOf(insertBefore) : items.length;
			drag.currentDestIndex = idx < 0 ? items.length : idx;
			this.positionGridIndicator(drag, insertBefore, items);
			return;
		}

		if(insertBefore) {
			const sameParent = drag.destEl.parentElement === container;
			if(sameParent && drag.destEl.nextElementSibling === insertBefore) return;
			container.insertBefore(drag.destEl, insertBefore);
		} else {
			// End-drop: clamp to just AFTER the last sortable item rather than the container end, so the
			// item can't land past trailing non-sortable siblings (the nav menu's "+" chevron, a board
			// column's add button, etc.). The dragged item is floated to <body>, so it isn't among these.
			const lastItem = items[items.length - 1];
			if(!lastItem) {
				if(drag.destEl.parentElement === container && !drag.destEl.nextElementSibling) return;
				container.appendChild(drag.destEl);
			} else {
				if(lastItem.nextElementSibling === drag.destEl) return; // already right after the last item
				lastItem.after(drag.destEl);
			}
		}

		drag.currentDestIndex = this.getDestIndex(drag);
	}

	// Grid mode: render destEl as a thin vertical bar in the gap before `insertBefore` (or after the
	// last item when null), positioned absolutely within the container (which is position:relative).
	positionGridIndicator(drag, insertBefore, items) {
		const container = drag.activeInstance.container;
		const cRect = container.getBoundingClientRect();
		const d = drag.destEl;

		let left, top, height;

		if(insertBefore) {
			const r = insertBefore.getBoundingClientRect();
			left   = r.left - cRect.left + container.scrollLeft - 4;
			top    = r.top  - cRect.top  + container.scrollTop;
			height = r.height;
		} else {
			const last = items[items.length - 1];
			if(last) {
				const r = last.getBoundingClientRect();
				left   = r.right - cRect.left + container.scrollLeft + 1;
				top    = r.top   - cRect.top  + container.scrollTop;
				height = r.height;
			} else {
				left = 0; top = 0; height = 24;
			}
		}

		d.style.position     = 'absolute';
		d.style.margin       = '0';
		d.style.width        = '3px';
		d.style.height       = `${height}px`;
		d.style.left         = `${left}px`;
		d.style.top          = `${top}px`;
		d.style.background    = 'var(--cerb-color-action-primary)';
		d.style.border        = '0';
		d.style.borderRadius  = '2px';

		if(d.parentElement !== container) container.appendChild(d);
	}

	// Row-aware insertion for wrap/grid/horizontal layouts. Group cells into visual rows (DOM order;
	// cells in a row share a top edge), pick the first row whose bottom edge is past ay (also catches
	// the gap above a row), then within it drop before the first cell whose right edge ax hasn't passed.
	// Past the row's end → before the next row's first cell; past everything → end (null).
	computeRowInsertion(ax, ay, items) {
		const rows = [];
		for(const it of items) {
			const r = it.getBoundingClientRect();
			let row = rows.find(R => Math.abs(R.top - r.top) < r.height / 2);
			if(!row) { row = { top: r.top, bottom: r.bottom, cells: [] }; rows.push(row); }
			row.bottom = Math.max(row.bottom, r.bottom);
			row.cells.push(it);
		}

		const row = rows.find(R => ay < R.bottom);
		if(!row) return null; // below the last row → drop at the end

		for(const it of row.cells) {
			if(ax < it.getBoundingClientRect().right)
				return it;
		}
		const lastInRow = row.cells[row.cells.length - 1];
		return items[items.indexOf(lastInRow) + 1] || null;
	}

	// The point of the dragged item used for drop targeting. Lets a host aim by something other than the
	// cursor when the handle isn't where you visually place the item (e.g. a top-right handle on a wide
	// widget). Corners/centre are computed from the helper's box; 'pointer' is the raw cursor.
	anchorPoint(cx, cy, drag) {
		const mode = this.opts.anchor || (this.opts.grid ? 'top-left' : 'pointer');
		const left = cx - drag.offsetX;
		const top  = cy - drag.offsetY;
		switch(mode) {
			case 'top-left':  return { ax: left, ay: top };
			case 'top-right': return { ax: left + drag.itemWidth, ay: top };
			case 'center':    return { ax: left + drag.itemWidth / 2, ay: top + drag.itemHeight / 2 };
			default:          return { ax: cx, ay: cy }; // 'pointer'
		}
	}

	computeInsertionPoint(cx, cy, drag, items) {
		// Resolve the point of the dragged item that decides the drop target (see the `anchor` option).
		const { ax, ay } = this.anchorPoint(cx, cy, drag);

		// Grid mode AND horizontal/wrapping lists use row-aware insertion: pick the row the anchor is over,
		// then the cell within it. A WRAPPING strip (tag/bubble field, nav menu) thus inserts into the right
		// row instead of treating every item as one long line; a single-row strip is just the one-row case.
		if(this.opts.grid || drag.axis === 'horizontal')
			return this.computeRowInsertion(ax, ay, items);

		if(this.opts.tolerance === 'pointer') {
			// Edge-based (matches the row-aware path): target the item the anchor is within/entering — its
			// bottom is past the anchor — so a drop "takes the place" of whatever you overlap rather than
			// waiting until you've passed its midpoint. EXCEPTION: the LAST item uses its midpoint, so the END
			// (dropping after it) is a generous half-row zone instead of an unreachable sliver between its
			// bottom and the container edge (where the cursor would already read as "outside the container").
			const lastIdx = items.length - 1;
			for(let i = 0; i < items.length; i++) {
				const r = items[i].getBoundingClientRect();
				const threshold = (i === lastIdx) ? (r.top + r.height / 2) : r.bottom;
				if(ay < threshold) return items[i];
			}
			return null;
		}

		// 'intersect': item with maximum overlap area with the helper wins.
		const hr = drag.helperEl.getBoundingClientRect();
		let bestOverlap = 0;
		let bestItem = null;

		for(const it of items) {
			const r  = it.getBoundingClientRect();
			const ox = Math.max(0, Math.min(hr.right, r.right)   - Math.max(hr.left, r.left));
			const oy = Math.max(0, Math.min(hr.bottom, r.bottom) - Math.max(hr.top,  r.top));
			const overlap = ox * oy;
			if(overlap > bestOverlap) {
				bestOverlap = overlap;
				bestItem    = it;
			}
		}

		if(!bestItem) return null;

		const r      = bestItem.getBoundingClientRect();
		const mid    = drag.axis === 'vertical' ? r.top + r.height / 2 : r.left + r.width / 2;
		const cursor = drag.axis === 'vertical' ? cy : cx;

		if(cursor < mid) return bestItem;
		return bestItem.nextElementSibling;
	}

	getDestIndex(drag) {
		const items = drag.activeInstance.getItems();
		let count = 0;
		let node = drag.destEl.previousElementSibling;
		while(node) {
			if(items.includes(node)) count++;
			node = node.previousElementSibling;
		}
		return count;
	}

	commitDrop(drag) {
		const container = drag.activeInstance.container;

		// Grid mode: destEl is an absolute bar, so drop at the recorded insertion node (a real item or
		// null = end) rather than at destEl's DOM position.
		if(this.opts.grid) {
			let ref = drag.gridInsertBefore;
			if(ref === drag.destEl || ref === drag.originEl || ref === drag.item) ref = null;
			container.insertBefore(drag.item, ref || null);
		} else {
			container.insertBefore(drag.item, drag.destEl);
		}
		drag.destEl.remove();
		drag.originEl.remove();

		if(this.opts.helper === 'original') {
			drag.item.classList.remove('cerb-ui-sortable--helper');
			drag.item.style.width = '';
			drag.item.style.left  = '';
			drag.item.style.top   = '';
		} else {
			// item was detached; it's been re-inserted above. Remove the floating clone.
			if(drag.helperEl !== drag.item) drag.helperEl.remove();
		}

		drag.activeInstance.refreshItemClasses();
		if(drag.activeInstance !== this) this.refreshItemClasses();
	}

	teardownDrag(drag, cancelled) {
		document.removeEventListener('pointermove', drag.moveHandler);
		document.removeEventListener('pointerup',   drag.upHandler);
		document.removeEventListener('keydown',     drag.keyHandler);

		if(cancelled) {
			// Restore item to its exact original position using the pre-drag anchor.
			drag.fromContainer.insertBefore(drag.item, drag.restoreAnchor);
			drag.destEl.remove();
			drag.originEl.remove();

			if(this.opts.helper === 'original') {
				drag.item.classList.remove('cerb-ui-sortable--helper');
				drag.item.style.width = '';
				drag.item.style.left  = '';
				drag.item.style.top   = '';
			} else {
				if(drag.helperEl !== drag.item) drag.helperEl.remove();
			}
		}

		this.container.classList.remove('cerb-ui-sortable--active');
		if(drag.activeInstance !== this) {
			drag.activeInstance.container.classList.remove('cerb-ui-sortable--active');
		}
		document.documentElement.classList.remove('cerb-ui-sortable-dragging');

		// onEnd fires for EVERY drag teardown (commit or cancel) — the place for state cleanup (e.g. clearing
		// drop-zone highlighting). makeInfo() still reflects the final state here, before we drop the drag.
		if(this.opts.onEnd) this.opts.onEnd(this.makeInfo());

		this.drag = null;
	}

	makeInfo() {
		const drag = this.drag;
		return {
			item:      drag.item,
			from:      drag.fromContainer,
			to:        drag.activeInstance.container,
			fromIndex: drag.fromIndex,
			toIndex:   drag.currentDestIndex,
		};
	}

	get id() { return this.uid; }
};
