/*
 * CerbUI.PieChart — a pie / donut chart (the first concrete chart on the CerbUI.Chart base). Replaces the c3
 * pie/donut widgets. Slices are colored by a shared CerbUI.ColorScale (keyed by label) so the chart and its
 * legend match. Hover shows "value (pct%)" and focuses the slice; clicking a slice or a legend row drills
 * through to Cerb search (the `click` payload).
 *
 * Usage:
 *   new CerbUI.PieChart(el, {
 *     type: 'pie' | 'donut',                                  // donut = center hole
 *     slices: [ { label, value, text?, color?, click?:{context?, query} }, … ],
 *     legend: true,                                           // render a matching CerbUI.Legend below the chart
 *     tooltip: { ratios: true },                              // 'value (pct%)' on hover (ratios default on)
 *     valueFormat: ',',                                       // CerbUI.num.format pattern for values
 *     height: 320, palette?, scale?,
 *   });
 *
 * Publishes bubbling 'cerb-ui-chart:{hover,leave,click}' on the element.
 */
CerbUI.PieChart = class extends CerbUI.Chart {
	constructor(el, options = {}) {
		super(el, options);
		if(!this.el) return;

		this.type = (options.type === 'donut') ? 'donut' : 'pie';
		this.slices = (options.slices || []).map(s => ({
			label: s.label != null ? String(s.label) : '',
			value: Math.max(0, Number(s.value) || 0),
			text: s.text,
			color: s.color,
			click: s.click || null,
		}));
		this.ratios = !(options.tooltip && options.tooltip.ratios === false); // default on
		this.legendEnabled = !!options.legend; // off unless explicitly enabled (the widget passes show_legend)
		this._fmt = CerbUI.num.format(options.valueFormat || ',');

		// A keyed scale so slice colors and legend colors line up (shared, stable per label).
		this.scale = this.scale || CerbUI.colorScale(this.palette);

		this.el.classList.add('cerb-ui-piechart');

		this._buildLegend();
		this.render();
	}

	_total() {
		return this.slices.reduce((a, s) => a + s.value, 0);
	}

	// Build a CerbUI.Legend once (component-owned), sharing this.scale so colors match the slices. Wire the
	// legend rows to the same hover-focus + click-drill-through behavior as the slices.
	_buildLegend() {
		if(!this.legendEnabled || !CerbUI.Legend) return;
		const legendEl = document.createElement('div');
		legendEl.className = 'cerb-ui-legend cerb-ui-chart--legend';
		this.slices.forEach(s => {
			const item = document.createElement('div');
			item.dataset.label = s.label;
			item.dataset.value = s.value;
			if(s.text != null) item.dataset.text = s.text;
			legendEl.appendChild(item);
		});
		this.el.appendChild(legendEl);
		this._legend = new CerbUI.Legend(legendEl, { scale: this.scale, percent: true });

		this._legendRows = Array.from(legendEl.querySelectorAll(':scope > div'));
		this._legendRows.forEach((row, i) => {
			if(this.slices[i].click) row.style.cursor = 'pointer';
			row.addEventListener('mouseover', () => this._focus(i));
			row.addEventListener('mouseout', () => this._revert());
			row.addEventListener('click', () => this._activate(i));
		});
	}

	// Angle 0 = 12 o'clock, increasing clockwise (matches c3). r=outer, ri=inner (0 for a pie).
	_arcPath(cx, cy, r, ri, a0, a1) {
		const large = (a1 - a0) > Math.PI ? 1 : 0;
		const x0 = cx + r * Math.sin(a0), y0 = cy - r * Math.cos(a0);
		const x1 = cx + r * Math.sin(a1), y1 = cy - r * Math.cos(a1);
		if(ri > 0) {
			const xi1 = cx + ri * Math.sin(a1), yi1 = cy - ri * Math.cos(a1);
			const xi0 = cx + ri * Math.sin(a0), yi0 = cy - ri * Math.cos(a0);
			return 'M' + x0 + ',' + y0 + ' A' + r + ',' + r + ' 0 ' + large + ' 1 ' + x1 + ',' + y1 +
				' L' + xi1 + ',' + yi1 + ' A' + ri + ',' + ri + ' 0 ' + large + ' 0 ' + xi0 + ',' + yi0 + ' Z';
		}
		return 'M' + cx + ',' + cy + ' L' + x0 + ',' + y0 + ' A' + r + ',' + r + ' 0 ' + large + ' 1 ' + x1 + ',' + y1 + ' Z';
	}

	// A full ring/disc (single 100% slice) — an <A> arc can't draw start==end, so use two half-arcs (evenodd
	// punches the donut hole).
	_fullPath(cx, cy, r, ri) {
		const disc = (rad) => 'M' + cx + ',' + (cy - rad) + ' A' + rad + ',' + rad + ' 0 1 1 ' + cx + ',' + (cy + rad) +
			' A' + rad + ',' + rad + ' 0 1 1 ' + cx + ',' + (cy - rad) + ' Z';
		return ri > 0 ? (disc(r) + ' ' + disc(ri)) : disc(r);
	}

	render() {
		if(!this._svg) return;
		const svg = this._svg;
		svg.replaceChildren();
		this._paths = [];

		const W = this.el.clientWidth;
		const H = this.height;
		if(!W) return; // not laid out yet — ResizeObserver will call again

		svg.setAttribute('width', W);
		svg.setAttribute('height', H);
		svg.setAttribute('viewBox', '0 0 ' + W + ' ' + H);

		const total = this._total();
		if(total <= 0) return;

		const cx = W / 2, cy = H / 2;
		const pad = 6;
		const r = Math.max(0, Math.min(W, H) / 2 - pad);
		const ri = (this.type === 'donut') ? r * 0.55 : 0;

		const positives = this.slices.filter(s => s.value > 0);
		let acc = 0;

		this.slices.forEach((s, i) => {
			if(s.value <= 0) { this._paths[i] = null; return; }
			const frac = s.value / total;
			const a0 = acc * 2 * Math.PI;
			acc += frac;
			const a1 = acc * 2 * Math.PI;

			const path = document.createElementNS(this.NS, 'path');
			path.setAttribute('class', 'cerb-ui-piechart--slice');
			path.setAttribute('d', (positives.length === 1) ? this._fullPath(cx, cy, r, ri) : this._arcPath(cx, cy, r, ri, a0, a1));
			if(positives.length === 1 && ri > 0) path.setAttribute('fill-rule', 'evenodd');
			path.setAttribute('fill', this._color(i, s));
			if(s.click) path.style.cursor = 'pointer';
			path.dataset.cerbIndex = i;
			svg.appendChild(path);
			this._paths[i] = path;
		});

		this._bindSlices();
	}

	_bindSlices() {
		// The svg persists across renders — remove prior handlers so listeners (and drill-throughs) don't stack.
		if(this._onClick) {
			this._svg.removeEventListener('mouseover', this._onOver);
			this._svg.removeEventListener('mousemove', this._onMove);
			this._svg.removeEventListener('mouseout', this._onOut);
			this._svg.removeEventListener('click', this._onClick);
		}
		const total = this._total();

		this._onOver = (e) => {
			const path = e.target.closest ? e.target.closest('.cerb-ui-piechart--slice') : null;
			if(!path || path.dataset.cerbIndex == null) return;
			const i = +path.dataset.cerbIndex;
			const s = this.slices[i];
			this._focus(i);
			const detail = { index: i, label: s.label, value: s.value, ratio: total > 0 ? s.value / total : 0, click: s.click };
			detail.point = { x: e.clientX, y: e.clientY };
			this._emit('hover', detail);
			const tip = this._tooltip();
			if(tip) tip.show(this._tooltipContent(s, detail.ratio), e.clientX, e.clientY, this.el);
		};

		this._onMove = (e) => {
			const tip = this._tooltip();
			if(tip && this._focused != null) tip.move(e.clientX, e.clientY);
		};

		this._onOut = (e) => {
			const path = e.target.closest ? e.target.closest('.cerb-ui-piechart--slice') : null;
			if(path) return; // moving between slices — the next _onOver handles it
			this._revert();
			this._emit('leave', {});
			const tip = this._tooltip();
			if(tip) tip.hide();
		};

		this._onClick = (e) => {
			const path = e.target.closest ? e.target.closest('.cerb-ui-piechart--slice') : null;
			if(!path || path.dataset.cerbIndex == null) return;
			e.stopPropagation();
			this._activate(+path.dataset.cerbIndex);
		};

		this._svg.addEventListener('mouseover', this._onOver);
		this._svg.addEventListener('mousemove', this._onMove);
		this._svg.addEventListener('mouseout', this._onOut);
		this._svg.addEventListener('click', this._onClick);
	}

	// Emit a click event + drill through to search if the slice carries a click payload.
	_activate(i) {
		const s = this.slices[i];
		if(!s) return;
		this._emit('click', { index: i, label: s.label, value: s.value, click: s.click });
		if(s.click && s.click.query) this._search(s.click.context, s.click.query);
	}

	// Highlight slice i (dim the others); legend + slice hover share this.
	_focus(i) {
		this._focused = i;
		this._paths.forEach((p, j) => { if(p) p.style.opacity = (j === i) ? '1' : '0.3'; });
	}

	_revert() {
		this._focused = null;
		this._paths.forEach(p => { if(p) p.style.opacity = ''; });
	}

	_tooltipContent(s, ratio) {
		const box = document.createElement('div');
		const title = document.createElement('div');
		title.className = 'cerb-ui-chart-tip--title';
		title.textContent = s.label;
		box.appendChild(title);

		const value = document.createElement('div');
		value.className = 'cerb-ui-chart-tip--value';
		const valStr = (s.text != null) ? s.text : this._fmt(s.value);
		value.textContent = this.ratios ? (valStr + ' (' + CerbUI.num.percent(ratio) + ')') : valStr;
		box.appendChild(value);
		return box;
	}
};
