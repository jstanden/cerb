/*
 * CerbUI.Distbar — a horizontal stacked bar (specialized chart primitive).
 *
 * Usage:
 *   new CerbUI.Distbar(el, { key: 'objects', palette: 'category10', legend: true });
 *
 * Markup: a .cerb-ui-distbar container of bare `> span` children (segments), each carrying a numeric
 * value under the data-value* namespace — data-value (default) or data-value-{key} (e.g. data-value-size).
 * The component computes the sum, each segment's % share → width, and colors segments by index from the
 * palette. Zero-valued segments are hidden from the bar (no sliver/gap) but still appear in the legend.
 * setKey(key) re-weights for a different metric.
 *
 * legend: true (or an element / selector) generates a matching CerbUI.Legend by cloning each segment's
 * data attributes into legend items — so segments should also carry data-label (+ optional data-text*).
 * The legend shares the bar's key + palette (colors match by order) and follows setKey(); hideZeros: true
 * forwards to it, hiding zero-valued items from the legend too.
 */
CerbUI.Distbar = class {
	static _instances = new WeakMap();
	static from(el) { return CerbUI.Distbar._instances.get(el); }

	constructor(el, options = {}) {
		this.el = (typeof el === 'string') ? document.querySelector(el) : el;
		if(this.el) CerbUI.Distbar._instances.set(this.el, this);
		this.key = options.key || null;
		this.palette = CerbUI.resolvePalette(options.palette);
		this.scale = options.scale || null;
		if(this.scale && options.palette != null) this.scale.usePalette(options.palette); // honor an explicit palette
		this.hideZeros = (options.hideZeros === true); // forwarded to the generated legend
		this.segs = this.el ? Array.from(this.el.querySelectorAll(':scope > span')) : [];
		this.legend = null;

		// Color by a shared scale (consistent across charts) if given, else by index
		this.segs.forEach((s, i) => { s.style.backgroundColor = this._color(s, i); });

		if(options.legend && this.el)
			this._buildLegend(options.legend);

		this.render();
	}

	// A segment's color: shared scale (by data-color-key, falling back to data-label) or palette index
	_color(seg, i) {
		if(this.scale)
			return this.scale.color(seg.dataset.colorKey ?? seg.dataset.label);
		return this.palette[i % this.palette.length];
	}

	getKey() {
		return this.key;
	}

	getLegend() {
		return this.legend;
	}

	setKey(key) {
		this.key = key;
		this.render();
		if(this.legend) this.legend.setKey(key);
	}

	render() {
		const attr = CerbUI.valueAttr(this.key);
		const values = this.segs.map(s => parseFloat(s.dataset[attr]) || 0);
		const sum = values.reduce((a, b) => a + b, 0);
		this.segs.forEach((s, i) => {
			s.style.width = (sum > 0 ? values[i] / sum * 100 : 0) + '%';
			// Hide zero segments so the CSS min-width + flex gap don't paint slivers
			s.style.display = (sum > 0 && values[i] > 0) ? '' : 'none';
		});
	}

	// Clone the segments into a generated legend (sharing key + palette so colors line up)
	_buildLegend(where) {
		const legendEl = document.createElement('div');
		legendEl.className = 'cerb-ui-legend';

		this.segs.forEach(seg => {
			const item = document.createElement('div');
			Object.keys(seg.dataset).forEach(k => { item.dataset[k] = seg.dataset[k]; });
			legendEl.appendChild(item);
		});

		// true -> place right after the bar; otherwise treat as a target element or selector
		const target = (where === true) ? null
			: (typeof where === 'string' ? document.querySelector(where) : where);

		if(target) target.appendChild(legendEl);
		else this.el.insertAdjacentElement('afterend', legendEl);

		this.legend = new CerbUI.Legend(legendEl, { key: this.key, palette: this.palette, scale: this.scale, hideZeros: this.hideZeros });
	}
};
