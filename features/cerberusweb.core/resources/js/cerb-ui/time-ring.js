/*
 * CerbUI.TimeRing — a small SVG progress/countdown ring with a center value + key (e.g. "7s" / "next").
 *
 * The component is intentionally "dumb": it renders the ring and exposes setters; something on the page
 * (or, later, a single shared orchestrator that rings register with) drives the updates on an interval.
 * There is no internal timer.
 *
 * Usage:
 *   const ring = new CerbUI.TimeRing(el, { fraction: 0.9, value: '7s', key: 'next' });
 *   ring.setFraction(0.5);          // 0..1 of the ring filled
 *   ring.setLabel('1:17', 'next');  // center value + key
 *
 * Markup: an empty container; the SVG + center text are generated (like Legend builds its inner DOM):
 *   <div class="cerb-ui-time-ring"></div>
 * Color: the progress arc is currentColor (set `color` to recolor); the track is a muted token.
 */
CerbUI.TimeRing = class {
	static _instances = new WeakMap();
	static from(el) { return CerbUI.TimeRing._instances.get(el); }

	constructor(el, options = {}) {
		this.el = (typeof el === 'string') ? document.querySelector(el) : el;
		if(!this.el) return;
		CerbUI.TimeRing._instances.set(this.el, this);
		this.el.classList.add('cerb-ui-time-ring'); // ensure position:relative so the center text stays inside

		const size = options.size || 38;
		const stroke = options.stroke || 3.5;
		const r = (size - stroke) / 2; // keep the stroke inside the viewBox
		const c = size / 2;
		this.circumference = 2 * Math.PI * r;

		const NS = 'http://www.w3.org/2000/svg';
		const svg = document.createElementNS(NS, 'svg');
		svg.setAttribute('width', size);
		svg.setAttribute('height', size);
		svg.setAttribute('viewBox', '0 0 ' + size + ' ' + size);

		const circle = (cls) => {
			const el = document.createElementNS(NS, 'circle');
			el.setAttribute('class', cls);
			el.setAttribute('cx', c);
			el.setAttribute('cy', c);
			el.setAttribute('r', r);
			el.setAttribute('fill', 'none');
			el.setAttribute('stroke-width', stroke);
			return el;
		};

		const track = circle('cerb-ui-time-ring--track');
		this._prog = circle('cerb-ui-time-ring--prog');
		this._prog.setAttribute('stroke-linecap', 'round');
		this._prog.setAttribute('stroke-dasharray', this.circumference);
		this._prog.setAttribute('stroke-dashoffset', this.circumference);
		svg.append(track, this._prog);

		const text = document.createElement('div');
		text.className = 'cerb-ui-time-ring--text';
		this._value = document.createElement('span');
		this._value.className = 'cerb-ui-time-ring--value';
		this._key = document.createElement('span');
		this._key.className = 'cerb-ui-time-ring--key';
		text.append(this._value, this._key);

		this.el.append(svg, text);

		this.setFraction(options.fraction || 0);
		if(options.value != null) this.setValue(options.value);
		if(options.key != null) this.setKey(options.key);
	}

	// 0..1 of the ring filled (clockwise from 12 o'clock)
	setFraction(f) {
		this._fraction = Math.max(0, Math.min(1, f));
		if(this._prog)
			this._prog.setAttribute('stroke-dashoffset', this.circumference * (1 - this._fraction));
		return this;
	}

	getFraction() {
		return this._fraction;
	}

	setValue(v) {
		if(this._value) this._value.textContent = v;
		return this;
	}

	setKey(k) {
		if(this._key) this._key.textContent = k;
		return this;
	}

	setLabel(value, key) {
		this.setValue(value);
		if(key != null) this.setKey(key);
		return this;
	}
};
