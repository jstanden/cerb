/*
 * CerbUI.SplitPane — two content panes separated by a draggable divider (zero-dependency progressive enhancement).
 *
 * Enhances a container holding EXACTLY TWO element children (the two panes) in place, like Sidebar/Accordion.
 * It tags each child as a --pane, inserts a --divider between them, and lays the container out as a flexbox:
 *   orientation:'horizontal' → panes side-by-side (left/right), a vertical divider you drag horizontally
 *   orientation:'vertical'   → panes stacked (top/bottom), a horizontal divider you drag vertically
 * The first pane's size tracks a single ratio (0..1) written to the container as --cerb-ui-splitpane-ratio; the
 * SCSS turns that into the first pane's flex-basis, so a resize is one style write (no per-pane measurement).
 *
 * Because the size is a percentage, a SplitPane built while hidden (a not-yet-shown tab) renders correctly once
 * revealed and auto-tracks container resizes — drag geometry is measured live at pointerdown.
 *
 * Usage:
 *   const sp = new CerbUI.SplitPane(el, {
 *     orientation: 'horizontal',
 *     ratio: 0.5,                          // initial size of the FIRST pane
 *     min: 0.1,                            // clamp (0..1 ratio, or a px number resolved vs the container)
 *     snap: [0.33, 0.5, 0.67],             // ratios the divider catches near
 *     storageKey: 'mySplit',               // persist the ratio across reloads
 *     onResize:    (ratio) => { … },       // live during drag (rAF-throttled)
 *     onResizeEnd: (ratio) => { … },       // on release (persist / re-render a preview here)
 *   });
 *   sp.setRatio(0.33); sp.getRatio(); sp.setOrientation('vertical'); sp.destroy();
 *   CerbUI.SplitPane.from(el);
 *
 * Also dispatches a bubbling `cerb-ui-splitpane:resize` CustomEvent on the container (detail = { ratio, phase }),
 * phase 'move' during a drag and 'end' on release.
 *
 * CSS lives in cerb.css (.cerb-ui-splitpane--*) — this component never injects styles.
 */
CerbUI.SplitPane = class {
	static _instances = new WeakMap();

	static _DEFAULTS = {
		orientation: 'horizontal', // 'horizontal' (L/R, vertical divider) | 'vertical' (T/B, horizontal divider)
		ratio: 0.5,                // initial size of the first pane (0..1)
		min: 0.1,                  // min ratio for EITHER pane; a number >= 1 is treated as pixels
		step: 0.02,                // keyboard nudge (fraction of the container)
		snap: [],                  // ratios the divider snaps to when within snapThreshold
		snapThreshold: 0.02,       // snap catch radius (fraction of the container)
		resetRatio: null,          // double-click divider resets here (defaults to opts.ratio)
		disabled: false,           // render the split but lock the divider
		collapsed: null,           // null | 'first' | 'second' — start with one pane hidden (the other fills 100%)
		storageKey: null,          // localStorage key to persist the ratio across reloads
		onResize: null,            // (ratio, splitpane) live while dragging
		onResizeEnd: null,         // (ratio, splitpane) on pointer up / keyboard commit
		onToggle: null,            // (collapsed, splitpane) after collapse()/expand()/toggle(); collapsed = 'first'|'second'|null
	};

	static from(el) {
		return CerbUI.SplitPane._instances.get(el);
	}

	constructor(el, opts = {}) {
		this.el = (typeof el === 'string') ? document.querySelector(el) : el;
		if(!this.el) return;
		this.opts = Object.assign({}, CerbUI.SplitPane._DEFAULTS, opts);
		this.orientation = (this.opts.orientation === 'vertical') ? 'vertical' : 'horizontal';
		this.disabled = !!this.opts.disabled;
		this._collapsed = null; // 'first' | 'second' | null
		this._raf = 0;

		this._onPointerDown = this._onPointerDown.bind(this);
		this._onDblClick = this._onDblClick.bind(this);
		this._onKeyDown = this._onKeyDown.bind(this);

		this._build();
		if(!this.divider) return; // needs two panes; _build bailed
		CerbUI.SplitPane._instances.set(this.el, this);

		// Persisted ratio (when storageKey set) wins over the option; else the option.
		let start = this._clamp(Number(this.opts.ratio));
		if(this.opts.storageKey) {
			try {
				const v = parseFloat(localStorage.getItem(this.opts.storageKey));
				if(!isNaN(v)) start = this._clamp(v);
			} catch(e) { /* private browsing / quota */ }
		}
		this._ratio = start;
		this._apply();

		// Optional initial collapsed pane (host toggles it later — e.g. reveal a preview from a toolbar button).
		if(this.opts.collapsed) this._setCollapsed(this.opts.collapsed, false);
	}

	// ── Public API ──────────────────────────────────────────────────────────

	getRatio() {
		return this._ratio;
	}

	setRatio(r) {
		this._ratio = this._clamp(Number(r));
		this._apply();
		return this;
	}

	setOrientation(orientation) {
		this.orientation = (orientation === 'vertical') ? 'vertical' : 'horizontal';
		this.el.classList.toggle('cerb-ui-splitpane--vertical', this.orientation === 'vertical');
		this.divider.setAttribute('aria-orientation', this.orientation === 'vertical' ? 'horizontal' : 'vertical');
		this.divider.className = 'cerb-ui-splitpane--divider';
		return this;
	}

	setDisabled(disabled) {
		this.disabled = !!disabled;
		this.el.classList.toggle('cerb-ui-splitpane--disabled', this.disabled);
		this.divider.tabIndex = (this.disabled || this._collapsed) ? -1 : 0;
		this.divider.setAttribute('aria-disabled', this.disabled ? 'true' : 'false');
		return this;
	}

	// Which pane (if any) is fully hidden: 'first' | 'second' | null. (Distinct from ratio — a collapsed pane is
	// hidden along with the divider so the other pane fills 100%; the ratio is preserved and restored on expand.)
	isCollapsed() {
		return this._collapsed;
	}

	// Hide one pane entirely (default the second); the other fills the container. The divider hides too.
	collapse(which = 'second') {
		this._setCollapsed(which === 'first' ? 'first' : 'second', true);
		return this;
	}

	// Restore the two-pane split at the preserved ratio.
	expand() {
		this._setCollapsed(null, true);
		return this;
	}

	// Reveal/hide a pane — the toolbar-toggle pattern (e.g. a reply form or automation editor showing a preview).
	toggle(which = 'second') {
		this._setCollapsed(this._collapsed ? null : (which === 'first' ? 'first' : 'second'), true);
		return this;
	}

	destroy() {
		CerbUI.SplitPane._instances.delete(this.el);
		this.divider.removeEventListener('pointerdown', this._onPointerDown);
		this.divider.removeEventListener('dblclick', this._onDblClick);
		this.divider.removeEventListener('keydown', this._onKeyDown);
		this.divider.remove();
		this.el.classList.remove('cerb-ui-splitpane', 'cerb-ui-splitpane--vertical', 'cerb-ui-splitpane--disabled', 'cerb-ui-splitpane--dragging', 'cerb-ui-splitpane--collapsed-first', 'cerb-ui-splitpane--collapsed-second');
		this.el.style.removeProperty('--cerb-ui-splitpane-ratio');
		for(const p of this.panes) p.classList.remove('cerb-ui-splitpane--pane');
	}

	// ── Build / enhance ─────────────────────────────────────────────────────

	_build() {
		// The two panes are the container's element children (skip the divider if re-enhanced).
		this.panes = Array.from(this.el.children).filter(c => c.nodeType === 1 && !c.classList.contains('cerb-ui-splitpane--divider'));
		if(this.panes.length < 2) return; // nothing to split

		// Keep only the first two as panes; anything past that is left in place after them.
		this.panes = this.panes.slice(0, 2);
		for(const p of this.panes) p.classList.add('cerb-ui-splitpane--pane');

		this.el.classList.add('cerb-ui-splitpane');
		if(this.orientation === 'vertical') this.el.classList.add('cerb-ui-splitpane--vertical');

		this.divider = document.createElement('div');
		this.divider.className = 'cerb-ui-splitpane--divider';
		this.divider.setAttribute('role', 'separator');
		this.divider.setAttribute('aria-orientation', this.orientation === 'vertical' ? 'horizontal' : 'vertical');
		this.divider.tabIndex = this.disabled ? -1 : 0;
		this.divider.setAttribute('aria-disabled', this.disabled ? 'true' : 'false');
		const grip = document.createElement('span');
		grip.className = 'cerb-ui-splitpane--grip';
		grip.setAttribute('aria-hidden', 'true');
		this.divider.appendChild(grip);

		// Insert the divider between the two panes.
		this.panes[0].after(this.divider);

		if(this.disabled) this.el.classList.add('cerb-ui-splitpane--disabled');

		this.divider.addEventListener('pointerdown', this._onPointerDown);
		this.divider.addEventListener('dblclick', this._onDblClick);
		this.divider.addEventListener('keydown', this._onKeyDown);
	}

	// ── Ratio math ────────────────────────────────────────────────────────────

	// Resolve opts.min to a ratio in [0, 0.49]. A value >= 1 is pixels, measured against the current container.
	_minRatio() {
		let m = Number(this.opts.min) || 0;
		if(m >= 1) {
			const rect = this.el.getBoundingClientRect();
			const extent = (this.orientation === 'vertical') ? rect.height : rect.width;
			m = extent ? (m / extent) : 0.1;
		}
		return Math.min(0.49, Math.max(0, m));
	}

	_clamp(r) {
		if(isNaN(r)) r = 0.5;
		const min = this._minRatio();
		return Math.min(1 - min, Math.max(min, r));
	}

	// Snap to any configured ratio (or the midpoint 0.5 is NOT implicit) within the catch radius.
	_snap(r) {
		const snaps = Array.isArray(this.opts.snap) ? this.opts.snap : [];
		for(const s of snaps) {
			if(Math.abs(r - s) <= this.opts.snapThreshold) return this._clamp(s);
		}
		return r;
	}

	// Write the ratio to the container (SCSS turns it into the first pane's flex-basis) + ARIA.
	_apply() {
		this.el.style.setProperty('--cerb-ui-splitpane-ratio', this._ratio);
		const pct = Math.round(this._ratio * 100);
		this.divider.setAttribute('aria-valuenow', pct);
		this.divider.setAttribute('aria-valuemin', 0);
		this.divider.setAttribute('aria-valuemax', 100);
	}

	_persist() {
		if(!this.opts.storageKey) return;
		try { localStorage.setItem(this.opts.storageKey, String(this._ratio)); } catch(e) { /* quota */ }
	}

	_emit(phase) {
		if(phase === 'end' && typeof this.opts.onResizeEnd === 'function') this.opts.onResizeEnd(this._ratio, this);
		if(phase === 'move' && typeof this.opts.onResize === 'function') this.opts.onResize(this._ratio, this);
		this.el.dispatchEvent(new CustomEvent('cerb-ui-splitpane:resize', {
			detail: { ratio: this._ratio, phase },
			bubbles: true,
		}));
	}

	// Hide/show a pane. A collapsed pane + the divider get display:none via a container class (CSS); the ratio is
	// left untouched so expand() restores the prior split. The divider leaves the tab order while collapsed.
	_setCollapsed(which, fire) {
		which = (which === 'first' || which === 'second') ? which : null;
		if(which === this._collapsed) return;
		this._collapsed = which;
		this.el.classList.remove('cerb-ui-splitpane--collapsed-first', 'cerb-ui-splitpane--collapsed-second');
		if(which) this.el.classList.add('cerb-ui-splitpane--collapsed-' + which);
		this.divider.setAttribute('aria-hidden', which ? 'true' : 'false');
		this.divider.tabIndex = (which || this.disabled) ? -1 : 0;
		if(!which) this._apply(); // restore ratio-driven sizing
		if(fire) this._emitToggle();
	}

	_emitToggle() {
		if(typeof this.opts.onToggle === 'function') this.opts.onToggle(this._collapsed, this);
		this.el.dispatchEvent(new CustomEvent('cerb-ui-splitpane:toggle', {
			detail: { collapsed: this._collapsed },
			bubbles: true,
		}));
	}

	// Set ratio from a raw pointer position, throttled to a frame; fires onResize/move.
	_moveTo(clientX, clientY) {
		const rect = this.el.getBoundingClientRect();
		const extent = (this.orientation === 'vertical') ? rect.height : rect.width;
		if(!extent) return;
		const pos = (this.orientation === 'vertical') ? (clientY - rect.top) : (clientX - rect.left);
		let r = this._snap(this._clamp(pos / extent));
		if(r === this._ratio) return;
		this._ratio = r;
		if(this._raf) return;
		this._raf = requestAnimationFrame(() => {
			this._raf = 0;
			this._apply();
			this._emit('move');
		});
	}

	// ── Events ────────────────────────────────────────────────────────────────

	_onPointerDown(e) {
		if(this.disabled) return;
		e.preventDefault();
		// Don't force focus here: a mouse drag would leave the divider :focus-visible (a lingering ring after
		// release). Pointer capture keeps the drag alive without focus; keyboard users still focus it by tabbing.
		this.el.classList.add('cerb-ui-splitpane--dragging');

		const run = (ev) => this._moveTo(ev.clientX, ev.clientY);
		const end = (ev) => {
			this.divider.removeEventListener('pointermove', run);
			this.divider.removeEventListener('pointerup', end);
			this.divider.removeEventListener('pointercancel', end);
			if(this.divider.hasPointerCapture && this.divider.hasPointerCapture(e.pointerId)) this.divider.releasePointerCapture(e.pointerId);
			this.el.classList.remove('cerb-ui-splitpane--dragging');
			if(this._raf) { cancelAnimationFrame(this._raf); this._raf = 0; }
			this._apply();
			this._persist();
			this._emit('end');
		};

		if(this.divider.setPointerCapture) { try { this.divider.setPointerCapture(e.pointerId); } catch(ex) {} }
		this.divider.addEventListener('pointermove', run);
		this.divider.addEventListener('pointerup', end);
		this.divider.addEventListener('pointercancel', end);
	}

	_onDblClick(e) {
		if(this.disabled) return;
		e.preventDefault();
		const reset = (this.opts.resetRatio != null) ? this.opts.resetRatio : this.opts.ratio;
		this._ratio = this._clamp(Number(reset));
		this._apply();
		this._persist();
		this._emit('end');
	}

	_onKeyDown(e) {
		if(this.disabled) return;
		let r = this._ratio, handled = true;
		const step = this.opts.step;
		switch(e.key) {
			case 'ArrowLeft': case 'ArrowUp': r -= step; break;
			case 'ArrowRight': case 'ArrowDown': r += step; break;
			case 'PageUp': r += step * 5; break;
			case 'PageDown': r -= step * 5; break;
			case 'Home': r = this._minRatio(); break;
			case 'End': r = 1 - this._minRatio(); break;
			default: handled = false;
		}
		if(!handled) return;
		e.preventDefault();
		r = this._clamp(r);
		if(r === this._ratio) return;
		this._ratio = r;
		this._apply();
		this._persist();
		this._emit('end');
	}
};
