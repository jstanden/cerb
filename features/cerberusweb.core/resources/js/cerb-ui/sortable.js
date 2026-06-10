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
 *   connectWith      array of other Sortable container elements for cross-list dragging (resolved lazily)
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
			onStart:          null,
			onStop:           null,
			onSorted:         null,
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

	refreshItemClasses() {
		if(this.opts.handle) {
			for(const item of this.getItems()) {
				item.classList.remove('cerb-ui-sortable--item');
				const handle = item.querySelector(this.opts.handle);
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
		const raw = this.opts.items.trimStart();
		const sel = /^[>+~]/.test(raw) ? `:scope ${raw}` : raw;
		return Array.from(this.container.querySelectorAll(sel)).filter(
			el =>
				el.parentElement === this.container &&
				!el.classList.contains('cerb-ui-sortable--origin') &&
				!el.classList.contains('cerb-ui-sortable--dest'),
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

		const target = e.target;

		// Find the sortable item that was clicked — avoids relative-selector issues
		// with closest() by scanning the live item list instead.
		const item = this.getItems().find(el => el === target || el.contains(target));
		if(!item) return;

		// Enforce handle constraint
		if(this.opts.handle) {
			const handle = target.closest(this.opts.handle);
			if(!handle || !item.contains(handle)) return;
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
		const originEl = this.createPlaceholder('origin', itemRect, item);
		const destEl   = this.createPlaceholder('dest',   itemRect);

		// Insert originEl where item is now (before the item in the DOM).
		item.parentElement.insertBefore(originEl, item);

		const helperEl = this.buildHelper(item, itemRect);

		if(this.opts.helper === 'original') {
			// The item itself becomes the floating helper.
			item.classList.add('cerb-ui-sortable--helper');
			item.style.width = `${itemRect.width}px`;
			item.style.left  = `${itemRect.left}px`;
			item.style.top   = `${itemRect.top}px`;
			// item stays in the DOM after originEl; position:fixed removes it from flow.
		} else {
			// Clone / custom: detach the original item so it doesn't consume space.
			// restoreAnchor already points past it, so the slot is held by originEl.
			item.remove();
			helperEl.style.left  = `${itemRect.left}px`;
			helperEl.style.top   = `${itemRect.top}px`;
			helperEl.style.width = `${itemRect.width}px`;
			document.body.appendChild(helperEl);
		}

		// destEl starts immediately after originEl (same logical slot).
		// syncOriginVisibility() will hide originEl so only 1 placeholder shows.
		originEl.after(destEl);

		// Detect layout axis from the current item set.
		const preItems = this.getItems().filter(el => el !== item);
		const axis = this.detectOrientation(preItems);

		// Cache connected container rects once for the drag lifetime.
		const connectedRects = this.opts.connectWith
			.map(el => ({ instance: CerbUI.Sortable.from(el), rect: el.getBoundingClientRect() }))
			.filter(r => r.instance != null);

		const offsetX = e.clientX - itemRect.left;
		const offsetY = e.clientY - itemRect.top;

		this.container.classList.add('cerb-ui-sortable--active');

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
			axis,
			currentDestIndex: fromIndex,
			moveHandler, upHandler, keyHandler,
		};

		// Hide originEl while destEl is at the same slot (avoids double-height gap).
		this.syncOriginVisibility(this.drag);

		if(this.opts.onStart) this.opts.onStart(this.makeInfo());
	}

	onDragMove(e) {
		const drag = this.drag;
		if(!drag) return;

		const cx = e.clientX;
		const cy = e.clientY;

		// Move the helper element.
		if(this.opts.helper === 'original') {
			drag.item.style.left = `${cx - drag.offsetX}px`;
			drag.item.style.top  = `${cy - drag.offsetY}px`;
		} else {
			drag.helperEl.style.left = `${cx - drag.offsetX}px`;
			drag.helperEl.style.top  = `${cy - drag.offsetY}px`;
		}

		const newActive = this.resolveActiveContainer(cx, cy, drag);

		if(newActive === null) {
			// Outside all containers: retract the dest indicator and show origin slot.
			if(drag.destEl.parentElement) {
				drag.destEl.remove();
				drag.activeInstance.container.classList.remove('cerb-ui-sortable--active');
				this.syncOriginVisibility(drag);
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
		this.syncOriginVisibility(drag);
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

	/*
	 * Hide originEl when destEl sits immediately after it (same logical slot,
	 * so only 1 placeholder is visible). Show it once they've separated.
	 * Also show it when destEl has been retracted (cursor outside containers).
	 */
	syncOriginVisibility(drag) {
		const atOrigin = drag.originEl.nextElementSibling === drag.destEl
			&& drag.destEl.parentElement !== null;
		drag.originEl.style.display = atOrigin ? 'none' : '';
	}

	createPlaceholder(kind, rect, item) {
		const el = document.createElement('div');
		el.className = `cerb-ui-sortable--${kind}`;
		if(this.opts.placeholderClass) el.classList.add(this.opts.placeholderClass);
		el.style.width  = `${rect.width}px`;
		el.style.height = `${rect.height}px`;
		el.setAttribute('aria-hidden', 'true');

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
			clone.classList.add('cerb-ui-sortable--helper');
			clone.removeAttribute('id');
			return clone;
		}
		const custom = h(item);
		custom.classList.add('cerb-ui-sortable--helper');
		return custom;
	}

	resolveActiveContainer(cx, cy, drag) {
		const ownRect = this.container.getBoundingClientRect();
		if(cx >= ownRect.left && cx <= ownRect.right &&
			cy >= ownRect.top  && cy <= ownRect.bottom) {
			return this;
		}
		for(const { instance, rect } of drag.connectedRects) {
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

		const sameParent = drag.destEl.parentElement === container;
		const sameNext   = drag.destEl.nextElementSibling === insertBefore;
		if(sameParent && sameNext) return;

		if(insertBefore) {
			container.insertBefore(drag.destEl, insertBefore);
		} else {
			container.appendChild(drag.destEl);
		}

		drag.currentDestIndex = this.getDestIndex(drag);
	}

	computeInsertionPoint(cx, cy, drag, items) {
		if(this.opts.tolerance === 'pointer') {
			for(const it of items) {
				const r   = it.getBoundingClientRect();
				const mid = drag.axis === 'vertical'
					? r.top  + r.height / 2
					: r.left + r.width  / 2;
				const cursor = drag.axis === 'vertical' ? cy : cx;
				if(cursor < mid) return it;
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

		container.insertBefore(drag.item, drag.destEl);
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
