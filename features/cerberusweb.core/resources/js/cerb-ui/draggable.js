/*
 * CerbUI.Draggable — pointer-drag a copy of an element out toward a CerbUI.Droppable (jQuery-UI-style, zero-dep).
 *
 * Unlike CerbUI.Sortable (which REORDERS — the original moves), Draggable leaves the source in place: the
 * default `helper:'clone'` floats a tilted copy and the original never moves. It's the palette → canvas half of
 * drag-and-drop (the node library of a builder); the Droppable owns the drop. Attach it to a single element, or
 * to a container with an `items` selector for many draggables sharing one config.
 *
 * Mechanics: pointerdown (respecting `handle`) → move past `distance` → a `--helper` clone floats under the
 * pointer (position:fixed) → live CerbUI.Droppable zones are hit-tested; the topmost accepting one highlights →
 * on release an accepting zone receives the drop (its onDrop may return false to reject) → otherwise the helper
 * reverts to the source and vanishes. Esc cancels. The original DOM is untouched the whole time.
 *
 * Usage:
 *   new CerbUI.Draggable(paletteBodyEl, {
 *     items: '.cerb-ui-sidebar--item',   // selector for draggable children (null = the element itself)
 *     data:  (li) => ({...li.dataset}),  // the payload handed to the droppable (default: the item's data-*)
 *     onStop: (item, {dropped, droppable, payload}) => { ... },
 *   });
 *
 * CSS lives in cerb.css (.cerb-ui-draggable*) — this component never injects styles.
 */
CerbUI.Draggable = class {
	static _instances = new WeakMap();
	static from(el) { return CerbUI.Draggable._instances.get(el); }

	static _DEFAULTS = {
		items: null,        // selector for draggable children; null = the element itself is the draggable
		handle: '',         // a drag-handle selector within an item (drag only starts from it)
		helper: 'clone',    // 'clone' | 'original' | (item) => HTMLElement  (palette default: clone → original stays)
		tilt: true,         // rotate+scale the floating helper (the Sortable look)
		distance: 5,        // px the pointer must move before a drag activates
		data: null,         // (item) => any; null = collect the item's data-* into an object (the drop payload)
		onStart: null,      // (item, e) when a drag begins
		onMove: null,       // (item, e) on each move
		onStop: null,       // (item, { dropped, droppable, payload }) when it ends
	};

	constructor(el, opts = {}) {
		this.el = (typeof el === 'string') ? document.querySelector(el) : el;
		if(!this.el) return;
		this.opts = Object.assign({}, CerbUI.Draggable._DEFAULTS, opts);
		this.drag = null;
		this._onPointerDown = this._onPointerDown.bind(this);
		this.el.classList.add('cerb-ui-draggable');
		this._applyItemClasses();
		this.el.addEventListener('pointerdown', this._onPointerDown);
		CerbUI.Draggable._instances.set(this.el, this);
	}

	// Mark draggable items so CSS can give them the grab cursor. Re-run after external DOM changes via refresh().
	_applyItemClasses() {
		if(!this.opts.items) { this.el.classList.add('cerb-ui-draggable--item'); return; }
		Array.from(this.el.querySelectorAll(this.opts.items)).forEach(it => it.classList.add('cerb-ui-draggable--item'));
	}

	refresh() { this._applyItemClasses(); return this; }

	// The draggable item that owns a pointer target (the element itself, or the INNERMOST matching child — so a
	// nested item tree drags the clicked node, not an enclosing ancestor; flat palettes resolve the same item).
	_itemFor(target) {
		if(!this.opts.items) return (this.el === target || this.el.contains(target)) ? this.el : null;
		if(!(target instanceof Element)) return null;
		const item = target.closest(this.opts.items);
		return (item && this.el.contains(item)) ? item : null;
	}

	_onPointerDown(e) {
		if(e.button !== 0) return;
		const item = this._itemFor(e.target);
		if(!item) return;
		if(this.opts.handle) {
			const handle = e.target.closest(this.opts.handle);
			if(!handle || !item.contains(handle)) return;
		}
		e.preventDefault();
		this._beginPreDrag(e, item);
	}

	// Wait for the pointer to clear the distance threshold before committing to a drag (so taps stay clicks).
	_beginPreDrag(startEvent, item) {
		const startX = startEvent.clientX, startY = startEvent.clientY;
		const onMove = (e) => {
			if(Math.hypot(e.clientX - startX, e.clientY - startY) >= this.opts.distance) {
				document.removeEventListener('pointermove', onMove);
				document.removeEventListener('pointerup', onCancel);
				this._activate(e, item);
			}
		};
		const onCancel = () => {
			document.removeEventListener('pointermove', onMove);
			document.removeEventListener('pointerup', onCancel);
		};
		document.addEventListener('pointermove', onMove);
		document.addEventListener('pointerup', onCancel);
	}

	_payloadFor(item) {
		if(typeof this.opts.data === 'function') return this.opts.data(item);
		const out = {};
		const ds = item.dataset || {};
		for(const k in ds) out[k] = ds[k];
		return out;
	}

	_buildHelper(item, rect) {
		const h = this.opts.helper;
		let el;
		if(h === 'original') {
			el = item;
		} else if(typeof h === 'function') {
			el = h(item);
		} else { // 'clone'
			el = item.cloneNode(true);
			el.removeAttribute('id');
			el.querySelectorAll('[id]').forEach(n => n.removeAttribute('id'));
		}
		el.classList.add('cerb-ui-draggable--helper');
		if(this.opts.tilt) el.classList.add('cerb-ui-draggable--tilt');
		el.style.width = rect.width + 'px';
		el.style.height = rect.height + 'px'; // lock both dims to drag-time (border-box) so the clone matches the source box
		el.style.left = rect.left + 'px';
		el.style.top = rect.top + 'px';
		return el;
	}

	_activate(e, item) {
		const rect = item.getBoundingClientRect();
		const payload = this._payloadFor(item);
		const helper = this._buildHelper(item, rect);
		if(helper !== item) document.body.appendChild(helper); // a clone floats; an 'original' helper floats in place

		const offsetX = e.clientX - rect.left;
		const offsetY = e.clientY - rect.top;

		const move = (me) => this._onMove(me);
		const up = (ue) => this._onUp(ue);
		const key = (ke) => { if(ke.key === 'Escape') this._cancel(); };
		document.addEventListener('pointermove', move);
		document.addEventListener('pointerup', up);
		document.addEventListener('keydown', key);

		this.drag = { item, helper, payload, offsetX, offsetY, over: null, overValid: false, move, up, key };
		document.body.classList.add('cerb-ui-draggable-dragging');
		if(typeof this.opts.onStart === 'function') this.opts.onStart(item, e);
	}

	_info(e) {
		const d = this.drag;
		return { item: d.item, helper: d.helper, payload: d.payload, clientX: e ? e.clientX : 0, clientY: e ? e.clientY : 0, draggable: this };
	}

	_onMove(e) {
		const d = this.drag;
		if(!d) return;
		d.helper.style.left = (e.clientX - d.offsetX) + 'px';
		d.helper.style.top = (e.clientY - d.offsetY) + 'px';
		if(typeof this.opts.onMove === 'function') this.opts.onMove(d.item, e);

		// The topmost live zone under the pointer (regardless of accept) + whether it accepts this drag. An
		// accepting zone gets the valid hover; an unacceptable one gets reject() (its optional rejection overlay).
		const zone = this._hitZone(e.clientX, e.clientY);
		const valid = zone ? zone.accepts(d.item, d.payload) : false;
		if(zone !== d.over || valid !== d.overValid) {
			if(d.over) d.over.leave(this._info(e));
			d.over = zone;
			d.overValid = valid;
			if(zone) (valid ? zone.enter(this._info(e)) : zone.reject(this._info(e)));
			document.body.classList.toggle('cerb-ui-draggable-no', !!zone && !valid);
		}
	}

	// The topmost live Droppable under the pointer (later in creation order = on top); accept is checked by caller.
	_hitZone(cx, cy) {
		const zones = (window.CerbUI && CerbUI.Droppable) ? CerbUI.Droppable.all() : [];
		let found = null;
		for(const z of zones) {
			const r = z.rect();
			if(cx >= r.left && cx <= r.right && cy >= r.top && cy <= r.bottom) found = z;
		}
		return found;
	}

	_onUp(e) {
		const d = this.drag;
		if(!d) return;
		let dropped = false;
		let droppable = null;
		if(d.over && d.overValid) {
			dropped = d.over.drop(this._info(e)); // drop() clears the hover itself
			droppable = dropped ? d.over : null;
		} else if(d.over) {
			d.over.leave(this._info(e)); // clear a reject hover
		}
		this._teardown(dropped, droppable);
	}

	_cancel() {
		if(!this.drag) return;
		if(this.drag.over) this.drag.over.leave(this._info(null));
		this._teardown(false, null);
	}

	_teardown(dropped, droppable) {
		const d = this.drag;
		if(!d) return;
		document.removeEventListener('pointermove', d.move);
		document.removeEventListener('pointerup', d.up);
		document.removeEventListener('keydown', d.key);
		document.body.classList.remove('cerb-ui-draggable-dragging', 'cerb-ui-draggable-no');

		const finish = () => {
			if(d.helper === d.item) {
				d.item.classList.remove('cerb-ui-draggable--helper', 'cerb-ui-draggable--tilt');
				d.item.style.width = d.item.style.left = d.item.style.top = '';
			} else {
				d.helper.remove();
			}
		};

		if(dropped) {
			finish();
		} else {
			// Reject / cancel: snap the helper back to the source, then clean up (one fixed timeout — no reliance
			// on transitionend, which may not fire).
			const rect = d.item.getBoundingClientRect();
			d.helper.classList.add('cerb-ui-draggable--reverting');
			d.helper.classList.remove('cerb-ui-draggable--tilt');
			d.helper.style.left = rect.left + 'px';
			d.helper.style.top = rect.top + 'px';
			setTimeout(finish, 180);
		}

		if(typeof this.opts.onStop === 'function') this.opts.onStop(d.item, { dropped, droppable, payload: d.payload });
		this.drag = null;
	}

	destroy() {
		if(this.drag) this._cancel();
		this.el.removeEventListener('pointerdown', this._onPointerDown);
		this.el.classList.remove('cerb-ui-draggable');
		CerbUI.Draggable._instances.delete(this.el);
	}
};
