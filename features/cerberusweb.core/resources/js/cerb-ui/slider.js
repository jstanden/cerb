/*
 * CerbUI.Slider — a draggable 0..N value slider (successor to the legacy jQuery-UI .slider()).
 *
 * The host provides a container plus a hidden <input> to read/write; the component builds the
 * track/range/thumb internally and keeps the input in sync.
 *
 * Markup:
 *   <div class="cerb-ui-slider" id="importanceSlider">
 *     <input type="hidden" name="importance" value="50">
 *   </div>
 *
 * Usage:
 *   new CerbUI.Slider(el, {
 *     min: 0, max: 100, step: 1,         // bounds + granularity
 *     midpoint: 50,                       // optional: delta coloring (below/at/above) + a center tick
 *     invert: false,                      // swap low/high colors (below=red, above=green)
 *     onInput:  function(value) { … },    // live, while dragging
 *     onChange: function(value) { … },    // committed (pointer up / keyboard)
 *   });
 *   sl.getValue();  sl.setValue(75);  sl.setDisabled(true);
 *
 * Positioning is percentage-based, so a slider initialized while hidden (bulk popups, custom-field
 * rows) still renders correctly once revealed; drag geometry is measured at pointerdown.
 */
CerbUI.Slider = class {
	static _instances = new WeakMap();
	static _tooltip = null; // one shared value tooltip (only one slider is hovered/dragged at a time)
	static from(el) { return CerbUI.Slider._instances.get(el); }

	constructor(el, options = {}) {
		this.el = (typeof el === 'string') ? document.querySelector(el) : el;
		if(!this.el) return;
		CerbUI.Slider._instances.set(this.el, this);

		this.options = options;
		this.min = (options.min != null) ? Number(options.min) : 0;
		this.max = (options.max != null) ? Number(options.max) : 100;
		this.step = (options.step != null && Number(options.step) > 0) ? Number(options.step) : 1;
		this.midpoint = (options.midpoint != null) ? Number(options.midpoint) : null;
		this.invert = !!options.invert;
		this.showBubble = (options.bubble !== false);

		// Read/write through an existing hidden input, or create one if a name was given
		this.input = this.el.querySelector('input');
		if(!this.input && options.name) {
			this.input = document.createElement('input');
			this.input.type = 'hidden';
			this.input.name = options.name;
			this.el.appendChild(this.input);
		}

		let initial = options.value;
		if(initial == null) initial = this.input ? this.input.value : this.min;

		this._buildDom();

		this.el.classList.add('cerb-ui-slider');
		this._value = this._clamp(Number(initial) || 0);
		this._render();
		this.setDisabled(!!options.disabled);

		this._bind();
	}

	_buildDom() {
		this.track = document.createElement('div');
		this.track.className = 'cerb-ui-slider--track';

		this.range = document.createElement('div');
		this.range.className = 'cerb-ui-slider--range';
		this.track.appendChild(this.range);

		if(this.midpoint != null) {
			this.tick = document.createElement('span');
			this.tick.className = 'cerb-ui-slider--midpoint';
			this.track.appendChild(this.tick);
		}

		this.thumb = document.createElement('div');
		this.thumb.className = 'cerb-ui-slider--thumb';
		this.thumb.tabIndex = this.options.disabled ? -1 : 0;
		this.thumb.setAttribute('role', 'slider');
		this.thumb.setAttribute('aria-valuemin', this.min);
		this.thumb.setAttribute('aria-valuemax', this.max);

		this.track.appendChild(this.thumb);
		this.el.appendChild(this.track);
	}

	_bind() {
		this._onPointerDown = (e) => {
			if(this.disabled) return;
			e.preventDefault();
			this.thumb.focus();
			this._startDrag(e);
		};

		this._onKeyDown = (e) => {
			if(this.disabled) return;
			let v = this._value, handled = true;
			let page = Math.max(this.step, Math.round((this.max - this.min) / 10));
			switch(e.key) {
				case 'ArrowLeft': case 'ArrowDown': v -= this.step; break;
				case 'ArrowRight': case 'ArrowUp': v += this.step; break;
				case 'PageDown': v -= page; break;
				case 'PageUp': v += page; break;
				case 'Home': v = this.min; break;
				case 'End': v = this.max; break;
				default: handled = false;
			}
			if(!handled) return;
			e.preventDefault();
			if(this._set(v)) { this._emit('onInput'); this._commit(); }
		};

		// Value tooltip on hover + keyboard focus (drag toggles it directly in _startDrag)
		this._onEnter = () => { this._hover = true; this._updateTip(); };
		this._onLeave = () => { this._hover = false; this._updateTip(); };
		this._onFocus = () => { this._focus = true; this._updateTip(); };
		this._onBlur = () => { this._focus = false; this._updateTip(); };

		// Press anywhere on the track (or the thumb) jumps the value and begins a drag
		this.track.addEventListener('pointerdown', this._onPointerDown);
		this.thumb.addEventListener('keydown', this._onKeyDown);
		this.thumb.addEventListener('mouseenter', this._onEnter);
		this.thumb.addEventListener('mouseleave', this._onLeave);
		this.thumb.addEventListener('focus', this._onFocus);
		this.thumb.addEventListener('blur', this._onBlur);
	}

	// Lazily create the shared value tooltip
	_tip() {
		if(!CerbUI.Slider._tooltip && window.CerbUI && CerbUI.Tooltip)
			CerbUI.Slider._tooltip = new CerbUI.Tooltip();
		return CerbUI.Slider._tooltip;
	}

	// Show/refresh the value tooltip while hovered, focused, or dragging; hide it otherwise. Anchored mode
	// draws the "tip" arrow at the handle; interactive:false keeps it a passive hover tip (not a callout).
	_updateTip() {
		if(!this.showBubble) return;
		let active = this._hover || this._focus || this.el.classList.contains('cerb-ui-slider--dragging');
		if(active) {
			let tip = this._tip();
			if(tip) tip.anchor(String(this._value), this.thumb, { my: 'center bottom', at: 'center top', interactive: false });
		} else {
			let tip = CerbUI.Slider._tooltip;
			if(tip && tip._owner === this.thumb) tip.hide();
		}
	}

	// Track the pointer outside the control while held (capture so move/up keep coming even if the
	// cursor leaves the track or a native selection/drag would otherwise steal the gesture).
	_startDrag(e) {
		let el = this.track;
		this.el.classList.add('cerb-ui-slider--dragging');
		this._updateTip();

		let run = (ev) => { this._moveToClientX(ev.clientX, true); };
		let end = (ev) => {
			el.removeEventListener('pointermove', run);
			el.removeEventListener('pointerup', end);
			el.removeEventListener('pointercancel', end);
			if(el.hasPointerCapture && el.hasPointerCapture(e.pointerId)) el.releasePointerCapture(e.pointerId);
			this.el.classList.remove('cerb-ui-slider--dragging');
			this._commit();
			this._updateTip();
		};

		if(el.setPointerCapture) { try { el.setPointerCapture(e.pointerId); } catch(ex) {} }
		el.addEventListener('pointermove', run);
		el.addEventListener('pointerup', end);
		el.addEventListener('pointercancel', end);
		run(e); // place on the initial press
	}

	_moveToClientX(clientX, live) {
		let rect = this.track.getBoundingClientRect();
		if(!rect.width) return;
		let pct = (clientX - rect.left) / rect.width;
		let v = this.min + pct * (this.max - this.min);
		if(this._set(v) && live) this._emit('onInput');
	}

	_clamp(v) {
		v = Math.round((v - this.min) / this.step) * this.step + this.min;
		return Math.min(this.max, Math.max(this.min, v));
	}

	// Set the value + re-render; returns true if it changed
	_set(v) {
		v = this._clamp(v);
		if(v === this._value) return false;
		this._value = v;
		this._render();
		return true;
	}

	_render() {
		let span = (this.max - this.min) || 1;
		let pct = ((this._value - this.min) / span) * 100;

		this.thumb.style.left = pct + '%';
		this.thumb.setAttribute('aria-valuenow', this._value);
		this.thumb.title = this._value;
		this._updateTip();

		if(this.midpoint != null) {
			let midPct = ((this.midpoint - this.min) / span) * 100;
			this.tick.style.left = midPct + '%';

			let below = this._value < this.midpoint;
			let above = this._value > this.midpoint;

			if(above) {
				this.range.style.left = midPct + '%';
				this.range.style.width = (pct - midPct) + '%';
			} else if(below) {
				this.range.style.left = pct + '%';
				this.range.style.width = (midPct - pct) + '%';
			} else {
				this.range.style.left = midPct + '%';
				this.range.style.width = '0%';
			}

			this.el.classList.toggle('cerb-ui-slider--below', below);
			this.el.classList.toggle('cerb-ui-slider--at', !below && !above);
			this.el.classList.toggle('cerb-ui-slider--above', above);
			this.el.classList.toggle('cerb-ui-slider--invert', this.invert);
		} else {
			this.range.style.left = '0%';
			this.range.style.width = pct + '%';
		}
	}

	_emit(name) {
		if(typeof this.options[name] === 'function')
			this.options[name](this._value, this);
	}

	// Persist to the hidden input + fire onChange
	_commit() {
		if(this.input) this.input.value = this._value;
		this._emit('onChange');
	}

	getValue() {
		return this._value;
	}

	setValue(v, {fireCallback = false} = {}) {
		if(this._set(Number(v))) {
			if(this.input) this.input.value = this._value;
			if(fireCallback) this._emit('onChange');
		}
		return this;
	}

	setDisabled(disabled) {
		this.disabled = !!disabled;
		this.el.classList.toggle('cerb-ui-slider--disabled', this.disabled);
		this.thumb.tabIndex = this.disabled ? -1 : 0;
		return this;
	}

	destroy() {
		this.track.removeEventListener('pointerdown', this._onPointerDown);
		this.thumb.removeEventListener('keydown', this._onKeyDown);
		this.thumb.removeEventListener('mouseenter', this._onEnter);
		this.thumb.removeEventListener('mouseleave', this._onLeave);
		this.thumb.removeEventListener('focus', this._onFocus);
		this.thumb.removeEventListener('blur', this._onBlur);
		let tip = CerbUI.Slider._tooltip;
		if(tip && tip._owner === this.thumb) tip.hide();
	}
};
