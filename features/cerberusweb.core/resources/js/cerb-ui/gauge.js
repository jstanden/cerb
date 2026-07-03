/*
 * CerbUI.Gauge — a single-value radial gauge (replaces c3's gauge type; the richer sibling of TimeRing). A
 * value maps across a [min,max] domain onto a circular-arc sweep, with an optional set of value thresholds that
 * recolor the fill, and a formatted center value + label. Built on CerbUI.Chart for the shared lifecycle +
 * palette (it doesn't use the tooltip/legend/series machinery).
 *
 *   new CerbUI.Gauge(el, {
 *     value: 72, min: 0, max: 100,
 *     label: 'CPU',                 // sub-label under the value
 *     valueText: '72%',             // preformatted center text (else CerbUI.num.format(format)(value))
 *     format: ',',                  // num.format pattern for the center value
 *     thresholds: [ {value:60, color:'#e0a800'}, {value:80, color:'#d62728'} ], // recolor at/above each value
 *     color: '#2ca02c',             // base arc color (else palette[0])
 *     arc: 270,                     // sweep degrees (gap centered at the bottom); 180 = c3-style semicircle
 *     thickness: 14, size: 180,
 *   });
 *
 *   gauge.setValue(88);            // animate-free update
 */
CerbUI.Gauge = class extends CerbUI.Chart {
	constructor(el, options = {}) {
		super(el, options);
		if(!this.el) return;

		this.value = Number(options.value) || 0;
		this.min = (options.min != null) ? Number(options.min) : 0;
		this.max = (options.max != null) ? Number(options.max) : 100;
		this.label = options.label;
		this.valueText = options.valueText;
		this.fmt = CerbUI.num.format(options.format || ',');
		this.thresholds = (options.thresholds || []).slice().sort((a, b) => a.value - b.value);
		this.baseColor = options.color || this.palette[0];
		this.arcDeg = (options.arc != null) ? options.arc : 270;
		this.thickness = options.thickness || 14;
		this.gaugeSize = options.size || null;
		// Optional end labels at the two arc ends (e.g. the min + max of the range). Shown only when provided.
		this.minLabel = (options.minLabel != null) ? options.minLabel : null;
		this.maxLabel = (options.maxLabel != null) ? options.maxLabel : null;

		this.el.classList.add('cerb-ui-gauge');
		this.render();
	}

	_fraction() {
		const span = (this.max - this.min) || 1;
		let f = (this.value - this.min) / span;
		return Math.max(0, Math.min(1, f));
	}

	// Color for the current value: the highest threshold at/below it, else the base color.
	_valueColor() {
		let c = this.baseColor;
		this.thresholds.forEach(t => { if(this.value >= t.value) c = t.color; });
		return c;
	}

	// Point on the arc at `deg` clockwise from 12 o'clock.
	_pt(cx, cy, r, deg) {
		const a = deg * Math.PI / 180;
		return [cx + r * Math.sin(a), cy - r * Math.cos(a)];
	}

	_arcPath(cx, cy, r, d0, d1) {
		const p0 = this._pt(cx, cy, r, d0), p1 = this._pt(cx, cy, r, d1);
		const large = Math.abs(d1 - d0) > 180 ? 1 : 0;
		return 'M' + p0[0] + ',' + p0[1] + ' A' + r + ',' + r + ' 0 ' + large + ' 1 ' + p1[0] + ',' + p1[1];
	}

	render() {
		if(!this._svg) return;
		const svg = this._svg;
		svg.replaceChildren();

		const W = this.el.clientWidth, H = this.height;
		if(!W) return;

		svg.setAttribute('width', W);
		svg.setAttribute('height', H);
		svg.setAttribute('viewBox', '0 0 ' + W + ' ' + H);

		const size = this.gaugeSize || Math.min(W, H);
		const cx = W / 2, cy = H / 2;
		const r = size / 2 - this.thickness / 2 - 2;

		// Sweep is centered over the top; the gap (360 - arc) sits at the bottom.
		const half = this.arcDeg / 2;
		const startDeg = -half, endDeg = half;

		const track = document.createElementNS(this.NS, 'path');
		track.setAttribute('class', 'cerb-ui-gauge--track');
		track.setAttribute('d', this._arcPath(cx, cy, r, startDeg, endDeg));
		track.setAttribute('fill', 'none');
		track.setAttribute('stroke-width', this.thickness);
		track.setAttribute('stroke-linecap', 'round');
		svg.appendChild(track);

		const f = this._fraction();
		if(f > 0) {
			const valEnd = startDeg + f * this.arcDeg;
			const val = document.createElementNS(this.NS, 'path');
			val.setAttribute('class', 'cerb-ui-gauge--value');
			val.setAttribute('d', this._arcPath(cx, cy, r, startDeg, valEnd));
			val.setAttribute('fill', 'none');
			val.setAttribute('stroke', this._valueColor());
			val.setAttribute('stroke-width', this.thickness);
			val.setAttribute('stroke-linecap', 'round');
			svg.appendChild(val);
		}

		const valueText = (this.valueText != null) ? this.valueText : this.fmt(this.value);
		const t = document.createElementNS(this.NS, 'text');
		t.setAttribute('class', 'cerb-ui-gauge--value-text');
		t.setAttribute('x', cx);
		t.setAttribute('y', cy + (this.label ? 0 : 6));
		t.setAttribute('text-anchor', 'middle');
		t.textContent = valueText;
		svg.appendChild(t);

		if(this.label) {
			const l = document.createElementNS(this.NS, 'text');
			l.setAttribute('class', 'cerb-ui-gauge--label');
			l.setAttribute('x', cx);
			l.setAttribute('y', cy + 20);
			l.setAttribute('text-anchor', 'middle');
			l.textContent = this.label;
			svg.appendChild(l);
		}

		// End labels under the two arc ends (min at the start, max at the end).
		if(this.minLabel != null || this.maxLabel != null) {
			const endLabel = (text, deg, anchor) => {
				const p = this._pt(cx, cy, r, deg);
				const el = document.createElementNS(this.NS, 'text');
				el.setAttribute('class', 'cerb-ui-gauge--end-label');
				el.setAttribute('x', p[0]);
				el.setAttribute('y', p[1] + 13);
				el.setAttribute('text-anchor', anchor);
				el.textContent = text;
				svg.appendChild(el);
			};
			if(this.minLabel != null) endLabel(this.minLabel, startDeg, 'middle');
			if(this.maxLabel != null) endLabel(this.maxLabel, endDeg, 'middle');
		}
	}

	setValue(v) {
		this.value = Number(v) || 0;
		this.render();
		return this;
	}

	setLabel(label) {
		this.label = label;
		this.render();
		return this;
	}
};
