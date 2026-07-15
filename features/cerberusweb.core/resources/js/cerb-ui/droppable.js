/*
 * CerbUI.Droppable — marks an element as a drop zone for CerbUI.Draggable (jQuery-UI-style, zero-dependency).
 *
 * A Droppable registers itself in a shared static registry so any Draggable can find it by hit-test during a
 * drag — no explicit wiring between the two. While a *valid* drag (one this zone `accept`s) hovers, the zone
 * gets `.cerb-ui-droppable--over` and (by default) a built-in `--overlay` highlight; on release it fires
 * `onDrop(info)`. Returning false from `onDrop` rejects the drop (the Draggable reverts its helper).
 *
 * `onOver` fires once on entry; use `onMove` for per-move tracking (a live drop-point preview).
 *
 * Usage:
 *   new CerbUI.Droppable(canvasEl, {
 *     accept: '.cerb-ui-tile',                 // selector | (item,payload)=>bool | null = accept all
 *     onDrop: (info) => { canvas.append(build(info.payload)); },  // return false to reject
 *   });
 *
 * info = { item, helper, payload, clientX, clientY, draggable }  (the same object the Draggable passes around).
 * CSS lives in cerb.css (.cerb-ui-droppable*) — this component never injects styles (bar the overlay node).
 */
CerbUI.Droppable = class {
	static _instances = new WeakMap();
	static _live = new Set(); // constructed-not-destroyed zones, in creation order — Draggable enumerates these

	static from(el) { return CerbUI.Droppable._instances.get(el); }
	static all() { return Array.from(CerbUI.Droppable._live); }

	static _DEFAULTS = {
		accept: null,      // selector | (item, payload) => bool | null = accept any draggable
		overlay: true,     // show the built-in highlight overlay while a valid drag hovers
		rejectOverlay: false, // also show a (red) overlay while an UNacceptable drag hovers — visible rejection
		hoverClass: '',    // extra class toggled on the zone while a valid drag is over it
		onOver: null,      // (info) when a valid drag ENTERS (once), not on every move — see onMove
		onMove: null,      // (info) on each move while a valid drag hovers (e.g. track a live insertion point)
		onReject: null,    // (info) when an unacceptable drag hovers (paired with rejectOverlay for the visual)
		onOut: null,       // (info) when a valid drag leaves (or the drag ends elsewhere)
		onDrop: null,      // (info) on release over this zone; return false to reject
	};

	constructor(el, opts = {}) {
		this.el = (typeof el === 'string') ? document.querySelector(el) : el;
		if(!this.el) return;
		this.opts = Object.assign({}, CerbUI.Droppable._DEFAULTS, opts);
		this._hover = null; // null | 'accept' | 'reject'
		this._overlayEl = null;
		this._setRelative = false;
		this.el.classList.add('cerb-ui-droppable');
		CerbUI.Droppable._instances.set(this.el, this);
		CerbUI.Droppable._live.add(this);
	}

	// Does this zone accept the given drag? selector match / predicate / accept-all.
	accepts(item, payload) {
		const a = this.opts.accept;
		if(a == null) return true;
		if(typeof a === 'function') return !!a(item, payload);
		if(typeof a === 'string') return !!(item && item.matches && item.matches(a));
		return true;
	}

	rect() { return this.el.getBoundingClientRect(); }

	// ── Internal: driven by CerbUI.Draggable during a drag ──────────────

	// A valid (accepted) drag is hovering.
	enter(info) {
		if(this._hover === 'accept') return;
		if(this._hover) this._clear();
		this._hover = 'accept';
		this.el.classList.add('cerb-ui-droppable--over');
		if(this.opts.hoverClass) this.el.classList.add(this.opts.hoverClass);
		if(this.opts.overlay) this._showOverlay(false);
		if(typeof this.opts.onOver === 'function') this.opts.onOver(info);
	}

	// The valid drag already inside this zone moved. Fires after enter(), on every pointermove, so a zone can
	// track the pointer (e.g. move a caret to the would-be drop point) rather than only knowing it was entered.
	move(info) {
		if(this._hover !== 'accept') return;
		if(typeof this.opts.onMove === 'function') this.opts.onMove(info);
	}

	// An UNacceptable drag is hovering — visible only when rejectOverlay (or a hostile onReject) is set.
	reject(info) {
		if(this._hover === 'reject') return;
		if(this._hover) this._clear();
		this._hover = 'reject';
		this.el.classList.add('cerb-ui-droppable--reject');
		if(this.opts.rejectOverlay) this._showOverlay(true);
		if(typeof this.opts.onReject === 'function') this.opts.onReject(info);
	}

	leave(info) {
		if(!this._hover) return;
		const was = this._hover;
		this._clear();
		if(was === 'accept' && typeof this.opts.onOut === 'function') this.opts.onOut(info);
	}

	// Returns false only if onDrop explicitly returned false (a rejected drop).
	drop(info) {
		this.leave(info);
		const res = (typeof this.opts.onDrop === 'function') ? this.opts.onDrop(info) : undefined;
		return res !== false;
	}

	_clear() {
		this.el.classList.remove('cerb-ui-droppable--over', 'cerb-ui-droppable--reject');
		if(this.opts.hoverClass) this.el.classList.remove(this.opts.hoverClass);
		this._hideOverlay();
		this._hover = null;
	}

	_showOverlay(reject) {
		if(this._overlayEl) return;
		// The overlay is absolutely positioned; give the zone a positioning context if it has none.
		if(getComputedStyle(this.el).position === 'static') {
			this.el.style.position = 'relative';
			this._setRelative = true;
		}
		this._overlayEl = document.createElement('div');
		this._overlayEl.className = 'cerb-ui-droppable--overlay' + (reject ? ' cerb-ui-droppable--overlay-reject' : '');
		this._overlayEl.setAttribute('aria-hidden', 'true');
		this.el.appendChild(this._overlayEl);
	}

	_hideOverlay() {
		if(this._overlayEl) { this._overlayEl.remove(); this._overlayEl = null; }
		if(this._setRelative) { this.el.style.position = ''; this._setRelative = false; }
	}

	destroy() {
		this.leave(null);
		this.el.classList.remove('cerb-ui-droppable');
		CerbUI.Droppable._instances.delete(this.el);
		CerbUI.Droppable._live.delete(this);
	}
};
