/*
 * CerbUI.Legend — a color-matched key for a distribution (standalone; pairs with Distbar by order).
 *
 * Usage:
 *   new CerbUI.Legend(el, { key: 'objects', palette: 'category10' });
 *
 * Markup: a .cerb-ui-legend container whose direct child <div>s are the items. Each item carries only data:
 *   data-label   — the series name
 *   data-type    — swatch shape: 'bar' (default, a square chip) or 'line' (a short rule); matches the
 *                  Sparkchart series type, so a legend reads the same vocabulary you hand the chart
 *   data-value*  — numeric value(s): data-value (default) or data-value-{key} (e.g. data-value-size)
 *   data-text*   — optional formatted display: data-text / data-text-{key} (e.g. data-text-size="2.1 GB")
 * The component generates the inner DOM (swatch + label + value + muted %), colors the swatch by index
 * (same order as a matching distbar), and computes the percentage. setKey(key) switches the metric.
 *
 * Options: key, palette, scale (a CerbUI.colorScale() to color by label across charts),
 *   percent (default true; false hides the % — e.g. a label-only key or value-only stats stack), and
 *   hideZeros (default false; true hides zero-valued items — follows setKey() like the distbar).
 * Layout: add the .cerb-ui-legend--vertical class for a stacked column (e.g. a per-row stats stack);
 *   an item with no value data renders just its swatch + label.
 */
CerbUI.Legend = class {
	static _instances = new WeakMap();
	static from(el) { return CerbUI.Legend._instances.get(el); }

	constructor(el, options = {}) {
		this.el = (typeof el === 'string') ? document.querySelector(el) : el;
		if(this.el) CerbUI.Legend._instances.set(this.el, this);
		this.key = options.key || null;
		this.palette = CerbUI.resolvePalette(options.palette);
		this.scale = options.scale || null;
		if(this.scale && options.palette != null) this.scale.usePalette(options.palette); // honor an explicit palette
		this.percent = (options.percent !== false); // show each item's % of the sum (pairs with a distbar)
		this.hideZeros = (options.hideZeros === true); // hide zero-valued items per the current key
		this.items = this.el ? Array.from(this.el.querySelectorAll(':scope > div')) : [];

		// Build each item's inner DOM once; color the swatch by a shared scale (if given) else by index
		this.items.forEach((it, i) => {
			const swatch = document.createElement('span');
			swatch.className = 'cerb-ui-legend--swatch';
			if(it.dataset.type === 'line') swatch.classList.add('cerb-ui-legend--swatch-line'); // short rule vs square chip
			swatch.style.backgroundColor = this.scale
				? this.scale.color(it.dataset.colorKey ?? it.dataset.label)
				: this.palette[i % this.palette.length];

			const label = document.createElement('span');
			label.className = 'cerb-ui-legend--label';
			label.textContent = it.dataset.label || '';

			const value = document.createElement('b');
			value.className = 'cerb-ui-legend--value';

			const muted = document.createElement('span');
			muted.className = 'cerb-ui-legend--muted';

			it.append(swatch, label, value, muted);
			it._value = value;
			it._muted = muted;
		});

		this.render();
	}

	getKey() {
		return this.key;
	}

	setKey(key) {
		this.key = key;
		this.render();
	}

	render() {
		const valueAttr = CerbUI.valueAttr(this.key);
		const textAttr = CerbUI.textAttr(this.key);
		const values = this.items.map(it => parseFloat(it.dataset[valueAttr]) || 0);
		const sum = values.reduce((a, b) => a + b, 0);

		this.items.forEach((it, i) => {
			const text = it.dataset[textAttr] ?? it.dataset[valueAttr] ?? '';
			it._value.textContent = text;
			// % only when there's a value to take a percentage of and the caller wants it
			it._muted.textContent = (this.percent && text !== '') ? ((sum > 0 ? Math.round(values[i] / sum * 100) : 0) + '%') : '';
			if(this.hideZeros) it.style.display = (values[i] > 0) ? '' : 'none';
		});
	}
};
