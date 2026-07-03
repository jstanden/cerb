/*
 * CerbUI.CartesianChart — the cartesian workhorse of the chart family (bars, lines, splines, areas over a
 * shared axis; the successor to c3's line/bar/area/scatter). Built on CerbUI.Chart (palette/scale, shared
 * tooltip, events, drill-through, lifecycle).
 *
 * The x-axis is either a BAND axis (categories) or a CONTINUOUS axis (time/linear); `orientation` decides where
 * the x + value axes sit on screen ('vertical' = x along the bottom, 'horizontal' = the "rotated" bar chart).
 * Every mark renderer works in (band-position, value) space via _pointCenter(i)/_fullBandwidth(), so neither
 * axis kind nor orientation is a per-mark concern.
 *
 *   new CerbUI.CartesianChart(el, {
 *     orientation: 'vertical' | 'horizontal',
 *     x: { scale:'category'|'time'|'linear', categories?|timestamps?|values?, label?, tickFormat?, rotate? },
 *     y: { label?, tickFormat?, grid?:true, min?, max? },
 *     series: [ { key, name, type:'bar'|'line'|'spline'|'area', color?, stack?, values:[…],
 *                 click?: [ {context, query}, … ] } ],
 *     legend?, points?, tooltip?, height?, palette?, scale?,
 *   });
 *
 * Series sharing a `stack` key accumulate (stacked bars/areas/lines); unstacked bar series dodge side-by-side.
 * Publishes bubbling 'cerb-ui-chart:{hover,leave,click}'.
 */
CerbUI.CartesianChart = class extends CerbUI.Chart {
	constructor(el, options = {}) {
		super(el, options);
		if(!this.el) return;

		this.orientation = (options.horizontal || options.orientation === 'horizontal') ? 'horizontal' : 'vertical';
		this.xcfg = options.x || {};
		this.ycfg = options.y || {};
		this.y2cfg = options.y2 || null; // optional second (right-hand) value axis
		this.xMode = (this.xcfg.scale === 'time' || this.xcfg.scale === 'linear') ? this.xcfg.scale : 'category';

		// x points: category labels, or numeric positions (timestamps in ms for 'time'). Index-aligned to values.
		if(this.xMode === 'category') {
			this.xPoints = (this.xcfg.categories || []).map(String);
		} else {
			this.xPoints = (this.xcfg.timestamps || this.xcfg.values || []).map(Number);
		}
		this._N = this.xPoints.length;

		this.series = (options.series || []).map((s, i) => ({
			key: s.key != null ? String(s.key) : ('s' + i),
			name: (s.name != null) ? s.name : (s.key != null ? String(s.key) : ('Series ' + (i + 1))),
			type: ['line', 'spline', 'area'].includes(s.type) ? s.type : 'bar',
			axis: (s.axis === 'y2') ? 'y2' : 'y',
			color: s.color,
			stack: (s.stack != null) ? String(s.stack) : null,
			values: (s.values || []).map(v => Number(v) || 0),
			click: s.click || null,
		}));
		this.hasY2 = !!this.y2cfg && this.series.some(s => s.axis === 'y2');
		this.xFmt = (typeof this.xcfg.tickFormat === 'function') ? this.xcfg.tickFormat : null;
		this.yFmt = (typeof this.ycfg.tickFormat === 'function') ? this.ycfg.tickFormat : CerbUI.num.format(',');
		this.y2Fmt = (this.y2cfg && typeof this.y2cfg.tickFormat === 'function') ? this.y2cfg.tickFormat : CerbUI.num.format(',');
		this.legendEnabled = !!options.legend;
		this.points = !!options.points;
		this.tooltipRatios = !!(options.tooltip && options.tooltip.ratios); // per-series % of the x-total
		this.tooltipSum = !!(options.tooltip && options.tooltip.sum);        // a Sum row under the series
		this.scale = this.scale || CerbUI.colorScale(this.palette);

		this.el.classList.add('cerb-ui-cartesian-chart');

		this._buildLegend();
		this.render();
	}

	_buildLegend() {
		if(!this.legendEnabled || !CerbUI.Legend) return;
		const legendEl = document.createElement('div');
		legendEl.className = 'cerb-ui-legend cerb-ui-chart--legend';
		this.series.forEach(s => {
			const item = document.createElement('div');
			item.dataset.label = s.name;
			item.dataset.colorKey = s.key;
			item.dataset.type = (s.type === 'bar') ? 'bar' : 'line';
			legendEl.appendChild(item);
		});
		this.el.appendChild(legendEl);
		this._legend = new CerbUI.Legend(legendEl, { scale: this.scale, percent: false });

		this._legendRows = Array.from(legendEl.querySelectorAll(':scope > div'));
		this._legendRows.forEach((row, si) => {
			// Color the swatch from the chart's own _seriesColor so the legend always matches the marks —
			// CerbUI.Legend otherwise colors by scale/order, which diverges when a series has an explicit color.
			// (CerbUI.Legend appends the swatch as the item's first child.)
			const sw = row.children[0];
			if(sw) sw.style.backgroundColor = this._seriesColor(si, this.series[si]);
			row.addEventListener('mouseover', () => this._focusSeries(si));
			row.addEventListener('mouseout', () => this._revert());
		});
	}

	_seriesColor(si, s) {
		if(s.color) return s.color;
		return this.scale ? this.scale.color(s.key) : this.palette[si % this.palette.length];
	}

	// Stacking bases/tops per series + per-axis value max, and dodge columns for bar series.
	_layout() {
		const stackOffsets = {}; // keyed by "axis:stack" so a y2 series never stacks onto a y series
		let dataMax = 0, dataMax2 = 0;
		this.series.forEach(s => {
			s._base = []; s._top = [];
			for(let i = 0; i < this._N; i++) {
				const v = Math.max(0, s.values[i] || 0);
				let base = 0;
				if(s.stack != null) {
					const k = s.axis + ':' + s.stack;
					const off = stackOffsets[k] || (stackOffsets[k] = []);
					base = off[i] || 0;
					off[i] = base + v;
				}
				s._base[i] = base;
				s._top[i] = base + v;
				if(s.axis === 'y2') { if(s._top[i] > dataMax2) dataMax2 = s._top[i]; }
				else if(s._top[i] > dataMax) dataMax = s._top[i];
			}
		});
		const colIndex = {};
		let cols = 0;
		this.series.forEach((s, si) => {
			if(s.type !== 'bar') { s._col = 0; return; }
			const k = (s.stack != null) ? ('stack:' + s.axis + ':' + s.stack) : ('series:' + si);
			if(!(k in colIndex)) colIndex[k] = cols++;
			s._col = colIndex[k];
		});
		this._barCols = Math.max(1, cols);
		this._dataMax = dataMax;
		this._dataMax2 = dataMax2;
	}

	// Position of data point i ALONG THE BAND AXIS (screen mapping to x/y happens in _rect/_point).
	_pointCenter(i) {
		return (this.xMode === 'category') ? this.bandScale.center(this.xPoints[i]) : this.xScale(this.xPoints[i]);
	}

	// Width available to a category/point group (band width, or the min gap between continuous points).
	_fullBandwidth() {
		return (this.xMode === 'category') ? this.bandScale.bandwidth() : this._minGap;
	}

	// The value scale a series plots against (right-hand y2 scale, else the left y scale).
	_valueScaleFor(s) {
		return (s.axis === 'y2' && this.valueScale2) ? this.valueScale2 : this.valueScale;
	}

	_rect(bandPos, bandSize, v0, v1, scale) {
		scale = scale || this.valueScale;
		const a = scale(v0), b = scale(v1);
		if(this.orientation === 'horizontal') return { x: Math.min(a, b), y: bandPos, w: Math.abs(b - a), h: bandSize };
		return { x: bandPos, y: Math.min(a, b), w: bandSize, h: Math.abs(b - a) };
	}

	// Screen [x,y] for a value at a band-axis position.
	_point(center, value, scale) {
		const vp = (scale || this.valueScale)(value);
		return (this.orientation === 'horizontal') ? [vp, center] : [center, vp];
	}

	render() {
		if(!this._svg) return;
		const svg = this._svg;
		svg.replaceChildren();
		this._marks = [];

		const W = this.el.clientWidth;
		const H = this.height;
		if(!W || !this._N) return;

		svg.setAttribute('width', W);
		svg.setAttribute('height', H);
		svg.setAttribute('viewBox', '0 0 ' + W + ' ' + H);

		this._layout();

		const horizontal = (this.orientation === 'horizontal');
		const mTop = 8;
		const mRight = (this.hasY2 && !horizontal) ? 52 : 14; // room for the right-hand y2 ticks
		const mLeft = horizontal ? (this.xcfg.width || 150) : 58;
		// Rotated x labels extend downward by ~their text length; size the bottom margin so they don't
		// overflow the (overflow:visible) svg onto whatever sits below it (e.g. the legend).
		let mBottom;
		if(horizontal) {
			mBottom = 30;
		} else if(this.xcfg.rotate) {
			const maxChars = this._xTickIndices().reduce((m, i) => Math.max(m, ('' + this._xLabel(i)).length), 0);
			mBottom = Math.min(140, 24 + Math.round(maxChars * 6.2));
		} else {
			mBottom = 34;
		}
		this._plot = { left: mLeft, top: mTop, right: W - mRight, bottom: H - mBottom };

		const bandRange = horizontal ? [mTop, H - mBottom] : [mLeft, W - mRight];
		if(this.xMode === 'category') {
			this.bandScale = CerbUI.scale.band({ domain: this.xPoints, range: bandRange, padding: 0.2 });
		} else {
			let lo = Infinity, hi = -Infinity;
			this.xPoints.forEach(t => { if(t < lo) lo = t; if(t > hi) hi = t; });
			if(this.series.some(s => s.type === 'bar')) {
				// Bars: pad the domain by half a data-step on each side so edge buckets sit inside the
				// plot with room for a full bar (a continuous scale otherwise pins min/max to the edges,
				// clipping the first/last bar). Two consecutive months land at ~25%/~75%, not the edges.
				const pts = this.xPoints.slice().sort((a, b) => a - b);
				let step = Infinity;
				for(let i = 1; i < pts.length; i++) { const d = pts[i] - pts[i - 1]; if(d > 0 && d < step) step = d; }
				if(!isFinite(step) || step <= 0) step = (hi - lo) || 1;
				this.xScale = CerbUI.scale.time({ domain: [lo - step / 2, hi + step / 2], range: bandRange });
			} else {
				// Lines/areas: small proportional padding so end points aren't flush against the plot edges.
				const padFrac = 0.03;
				this.xScale = CerbUI.scale.time({ domain: [lo, hi], range: [bandRange[0] + (bandRange[1] - bandRange[0]) * padFrac, bandRange[1] - (bandRange[1] - bandRange[0]) * padFrac] });
			}
			// Min gap between consecutive point centers (for bar widths + hover band).
			let gap = Infinity;
			for(let i = 1; i < this._N; i++) gap = Math.min(gap, Math.abs(this._pointCenter(i) - this._pointCenter(i - 1)));
			this._minGap = isFinite(gap) ? gap : (bandRange[1] - bandRange[0]);
		}

		const vmin = (this.ycfg.min != null) ? this.ycfg.min : 0;
		const vmax = (this.ycfg.max != null) ? this.ycfg.max : (this._dataMax || 1);
		const valueRange = horizontal ? [mLeft, W - mRight] : [H - mBottom, mTop];
		this.valueScale = CerbUI.scale.linear({ domain: [vmin, vmax], range: valueRange });

		if(this.hasY2) {
			const v2min = (this.y2cfg.min != null) ? this.y2cfg.min : 0;
			const v2max = (this.y2cfg.max != null) ? this.y2cfg.max : (this._dataMax2 || 1);
			this.valueScale2 = CerbUI.scale.linear({ domain: [v2min, v2max], range: valueRange });
		} else {
			this.valueScale2 = null;
		}

		this._drawGridAndValueAxis();
		if(this.hasY2) this._drawY2Axis();
		this._drawXAxis();
		this._drawAxisLabels();

		this._hl = document.createElementNS(this.NS, 'rect');
		this._hl.setAttribute('class', 'cerb-ui-cartesian-chart--hover');
		this._hl.style.display = 'none';
		svg.appendChild(this._hl);

		// Draw order: bars, then areas, then lines/splines, then dots (so points sit on top).
		this.series.forEach((s, si) => { if(s.type === 'bar') this._drawBar(si, s); });
		this.series.forEach((s, si) => { if(s.type === 'area') this._drawArea(si, s); });
		this.series.forEach((s, si) => { if(s.type === 'line' || s.type === 'spline') this._drawLine(si, s, s.type === 'spline'); });
		this.series.forEach((s, si) => { if(s.type !== 'bar') this._drawPoints(si, s); });

		this._bindEvents();
	}

	_drawGridAndValueAxis() {
		const svg = this._svg, horizontal = (this.orientation === 'horizontal');
		const ticks = this.valueScale.ticks(5);
		const grid = (this.ycfg.grid !== false);
		ticks.forEach(t => {
			const vp = this.valueScale(t);
			if(grid) {
				const line = document.createElementNS(this.NS, 'line');
				line.setAttribute('class', 'cerb-ui-cartesian-chart--grid');
				if(horizontal) { line.setAttribute('x1', vp); line.setAttribute('x2', vp); line.setAttribute('y1', this._plot.top); line.setAttribute('y2', this._plot.bottom); }
				else { line.setAttribute('x1', this._plot.left); line.setAttribute('x2', this._plot.right); line.setAttribute('y1', vp); line.setAttribute('y2', vp); }
				svg.appendChild(line);
			}
			const label = document.createElementNS(this.NS, 'text');
			label.setAttribute('class', 'cerb-ui-cartesian-chart--tick');
			if(horizontal) { label.setAttribute('x', vp); label.setAttribute('y', this._plot.bottom + 14); label.setAttribute('text-anchor', 'middle'); }
			else { label.setAttribute('x', this._plot.left - 6); label.setAttribute('y', vp + 4); label.setAttribute('text-anchor', 'end'); }
			label.textContent = this.yFmt(t);
			svg.appendChild(label);
		});
	}

	// Right-hand y2 axis ticks + optional label (vertical charts only).
	_drawY2Axis() {
		if(this.orientation === 'horizontal') return;
		const svg = this._svg;
		this.valueScale2.ticks(5).forEach(t => {
			const y = this.valueScale2(t);
			const label = document.createElementNS(this.NS, 'text');
			label.setAttribute('class', 'cerb-ui-cartesian-chart--tick');
			label.setAttribute('x', this._plot.right + 6);
			label.setAttribute('y', y + 4);
			label.setAttribute('text-anchor', 'start');
			label.textContent = this.y2Fmt(t);
			svg.appendChild(label);
		});
		if(this.y2cfg.label) {
			const cy = (this._plot.top + this._plot.bottom) / 2;
			const x = this.el.clientWidth - 4;
			const t = document.createElementNS(this.NS, 'text');
			t.setAttribute('class', 'cerb-ui-cartesian-chart--axis-label');
			t.setAttribute('x', x); t.setAttribute('y', cy); t.setAttribute('text-anchor', 'middle');
			t.setAttribute('transform', 'rotate(-90,' + x + ',' + cy + ')');
			t.textContent = this.y2cfg.label;
			svg.appendChild(t);
		}
	}

	// Indices of the x points to label (every category, or a thinned ~8 subset for a continuous axis).
	_xTickIndices() {
		if(this.xMode === 'category') return this.xPoints.map((_, i) => i);
		const target = 8;
		const stepN = Math.max(1, Math.ceil(this._N / target));
		const out = [];
		for(let i = 0; i < this._N; i += stepN) out.push(i);
		if(out[out.length - 1] !== this._N - 1) out.push(this._N - 1); // always label the last point
		return out;
	}

	_xLabel(i) {
		if(this.xMode === 'category') return this.xFmt ? this.xFmt(this.xPoints[i]) : this.xPoints[i];
		return this.xFmt ? this.xFmt(this.xPoints[i]) : '' + this.xPoints[i];
	}

	_drawXAxis() {
		const svg = this._svg, horizontal = (this.orientation === 'horizontal');
		const maxChars = Math.max(4, Math.floor((this.xcfg.width || 150) / 7));
		this._xTickIndices().forEach(i => {
			const center = this._pointCenter(i);
			let txt = '' + this._xLabel(i);
			const label = document.createElementNS(this.NS, 'text');
			label.setAttribute('class', 'cerb-ui-cartesian-chart--tick');
			if(horizontal) {
				if(txt.length > maxChars) txt = txt.slice(0, maxChars - 1) + '…';
				label.setAttribute('x', this._plot.left - 8);
				label.setAttribute('y', center + 4);
				label.setAttribute('text-anchor', 'end');
			} else {
				label.setAttribute('x', center);
				label.setAttribute('y', this._plot.bottom + 14);
				label.setAttribute('text-anchor', this.xcfg.rotate ? 'end' : 'middle');
				if(this.xcfg.rotate) label.setAttribute('transform', 'rotate(' + this.xcfg.rotate + ',' + center + ',' + (this._plot.bottom + 14) + ')');
			}
			label.textContent = txt;
			svg.appendChild(label);
		});
	}

	_drawAxisLabels() {
		const svg = this._svg, horizontal = (this.orientation === 'horizontal');
		const add = (text, x, y, rotate) => {
			const t = document.createElementNS(this.NS, 'text');
			t.setAttribute('class', 'cerb-ui-cartesian-chart--axis-label');
			t.setAttribute('x', x); t.setAttribute('y', y); t.setAttribute('text-anchor', 'middle');
			if(rotate) t.setAttribute('transform', 'rotate(-90,' + x + ',' + y + ')');
			t.textContent = text;
			svg.appendChild(t);
		};
		const cx = (this._plot.left + this._plot.right) / 2;
		const cy = (this._plot.top + this._plot.bottom) / 2;
		if(this.xcfg.label) horizontal ? add(this.xcfg.label, 12, cy, true) : add(this.xcfg.label, cx, this.height - 4, false);
		if(this.ycfg.label) horizontal ? add(this.ycfg.label, cx, this.height - 4, false) : add(this.ycfg.label, 12, cy, true);
	}

	_drawBar(si, s) {
		const svg = this._svg;
		// Cap the group so sparse points (a wide _minGap on a continuous axis, or few
		// categories) don't yield absurdly wide bars; the capped group stays centered on the point.
		const MAX_BAR = 64;
		const groupW = Math.min(this._fullBandwidth(), (MAX_BAR / 0.86) * this._barCols);
		const colW = groupW / this._barCols;
		const barW = colW * 0.86;
		const color = this._seriesColor(si, s);
		const scale = this._valueScaleFor(s);
		for(let i = 0; i < this._N; i++) {
			if(s._top[i] <= s._base[i]) continue;
			const lead = this._pointCenter(i) - groupW / 2 + s._col * colW + (colW - barW) / 2;
			const r = this._rect(lead, barW, s._base[i], s._top[i], scale);
			const rect = document.createElementNS(this.NS, 'rect');
			rect.setAttribute('class', 'cerb-ui-cartesian-chart--bar');
			rect.setAttribute('x', r.x); rect.setAttribute('y', r.y);
			rect.setAttribute('width', Math.max(0, r.w)); rect.setAttribute('height', Math.max(0, r.h));
			rect.setAttribute('fill', color);
			rect.dataset.series = si; rect.dataset.cat = i;
			if(s.click && s.click[i] && s.click[i].query) rect.style.cursor = 'pointer';
			svg.appendChild(rect);
			this._marks.push(rect);
		}
	}

	_linePoints(s) {
		const scale = this._valueScaleFor(s);
		const pts = [];
		for(let i = 0; i < this._N; i++) pts.push(this._point(this._pointCenter(i), s._top[i], scale));
		return pts;
	}

	// Catmull-Rom -> cubic bezier smoothing for splines.
	_splinePath(pts) {
		if(pts.length < 2) return pts.length ? ('M' + pts[0][0] + ',' + pts[0][1]) : '';
		let d = 'M' + pts[0][0] + ',' + pts[0][1];
		for(let i = 0; i < pts.length - 1; i++) {
			const p0 = pts[i - 1] || pts[i], p1 = pts[i], p2 = pts[i + 1], p3 = pts[i + 2] || p2;
			const c1x = p1[0] + (p2[0] - p0[0]) / 6, c1y = p1[1] + (p2[1] - p0[1]) / 6;
			const c2x = p2[0] - (p3[0] - p1[0]) / 6, c2y = p2[1] - (p3[1] - p1[1]) / 6;
			d += ' C' + c1x + ',' + c1y + ' ' + c2x + ',' + c2y + ' ' + p2[0] + ',' + p2[1];
		}
		return d;
	}

	_drawLine(si, s, smooth) {
		const svg = this._svg;
		const pts = this._linePoints(s);
		const color = this._seriesColor(si, s);
		let el;
		if(smooth) {
			el = document.createElementNS(this.NS, 'path');
			el.setAttribute('d', this._splinePath(pts));
			el.setAttribute('fill', 'none');
		} else {
			el = document.createElementNS(this.NS, 'polyline');
			el.setAttribute('points', pts.map(p => p.join(',')).join(' '));
		}
		el.setAttribute('class', 'cerb-ui-cartesian-chart--line');
		el.setAttribute('stroke', color);
		el.dataset.series = si;
		svg.appendChild(el);
		this._marks.push(el);
	}

	_drawArea(si, s) {
		const svg = this._svg;
		const color = this._seriesColor(si, s);
		const scale = this._valueScaleFor(s);
		const top = [], base = [];
		for(let i = 0; i < this._N; i++) { top.push(this._point(this._pointCenter(i), s._top[i], scale)); base.push(this._point(this._pointCenter(i), s._base[i], scale)); }
		let d = 'M' + top[0][0] + ',' + top[0][1];
		for(let i = 1; i < top.length; i++) d += ' L' + top[i][0] + ',' + top[i][1];
		for(let i = base.length - 1; i >= 0; i--) d += ' L' + base[i][0] + ',' + base[i][1];
		d += ' Z';
		const area = document.createElementNS(this.NS, 'path');
		area.setAttribute('class', 'cerb-ui-cartesian-chart--area');
		area.setAttribute('d', d);
		area.setAttribute('fill', color);
		area.dataset.series = si;
		svg.appendChild(area);
		this._marks.push(area);
	}

	// Dots on line/spline/area points: visible when `points` is on, otherwise transparent hit targets for
	// per-series drill-through.
	_drawPoints(si, s) {
		const svg = this._svg;
		const color = this._seriesColor(si, s);
		const scale = this._valueScaleFor(s);
		for(let i = 0; i < this._N; i++) {
			const p = this._point(this._pointCenter(i), s._top[i], scale);
			const dot = document.createElementNS(this.NS, 'circle');
			dot.setAttribute('class', 'cerb-ui-cartesian-chart--dot');
			dot.setAttribute('cx', p[0]); dot.setAttribute('cy', p[1]); dot.setAttribute('r', this.points ? 3 : 5);
			dot.setAttribute('fill', this.points ? color : 'transparent');
			dot.dataset.series = si; dot.dataset.cat = i;
			if(s.click && s.click[i] && s.click[i].query) dot.style.cursor = 'pointer';
			svg.appendChild(dot);
			this._marks.push(dot);
		}
	}

	// Data index under a pointer position along the band axis.
	_indexAt(coord) {
		if(this.xMode === 'category') {
			const start = this.bandScale.range()[0], step = this.bandScale.step();
			return Math.max(0, Math.min(this._N - 1, Math.floor((coord - start) / step)));
		}
		let best = 0, bd = Infinity;
		for(let i = 0; i < this._N; i++) { const d = Math.abs(this._pointCenter(i) - coord); if(d < bd) { bd = d; best = i; } }
		return best;
	}

	_bindEvents() {
		const horizontal = (this.orientation === 'horizontal');
		// The svg persists across renders — remove prior handlers so listeners (and drill-throughs) don't stack.
		if(this._onClick) {
			this._svg.removeEventListener('mousemove', this._onMove);
			this._svg.removeEventListener('mouseleave', this._onLeave);
			this._svg.removeEventListener('click', this._onClick);
		}
		this._onMove = (e) => {
			const rect = this._svg.getBoundingClientRect();
			const coord = horizontal ? (e.clientY - rect.top) : (e.clientX - rect.left);
			const i = this._indexAt(coord);
			if(i !== this._hoverIdx) { this._hoverIdx = i; this._paintHover(i); }
			const detail = this._detailAt(i);
			detail.point = { x: e.clientX, y: e.clientY };
			this._emit('hover', detail);
			const tip = this._tooltip();
			if(tip) tip.show(this._tooltipContent(i), e.clientX, e.clientY, this.el);
		};
		this._onLeave = () => {
			this._hoverIdx = null;
			if(this._hl) this._hl.style.display = 'none';
			this._emit('leave', {});
			const tip = this._tooltip();
			if(tip) tip.hide();
		};
		this._onClick = (e) => {
			const mark = e.target.closest ? e.target.closest('[data-series]') : null;
			if(!mark || mark.dataset.series == null || mark.dataset.cat == null) return;
			const s = this.series[+mark.dataset.series], i = +mark.dataset.cat;
			e.stopPropagation();
			const click = s.click && s.click[i];
			this._emit('click', { series: s.key, index: i, value: s.values[i], click: click });
			if(click && click.query) this._search(click.context, click.query);
		};
		this._svg.addEventListener('mousemove', this._onMove);
		this._svg.addEventListener('mouseleave', this._onLeave);
		this._svg.addEventListener('click', this._onClick);
	}

	_paintHover(i) {
		if(!this._hl) return;
		const center = this._pointCenter(i), w = this._fullBandwidth();
		this._hl.style.display = '';
		if(this.orientation === 'horizontal') {
			this._hl.setAttribute('x', this._plot.left); this._hl.setAttribute('width', this._plot.right - this._plot.left);
			this._hl.setAttribute('y', center - w / 2); this._hl.setAttribute('height', w);
		} else {
			this._hl.setAttribute('y', this._plot.top); this._hl.setAttribute('height', this._plot.bottom - this._plot.top);
			this._hl.setAttribute('x', center - w / 2); this._hl.setAttribute('width', w);
		}
	}

	_detailAt(i) {
		return {
			index: i,
			x: this.xPoints[i],
			series: this.series.map((s, si) => ({ key: s.key, name: s.name, type: s.type, color: this._seriesColor(si, s), value: s.values[i] })),
		};
	}

	_tooltipContent(i) {
		const box = document.createElement('div');
		const title = document.createElement('div');
		title.className = 'cerb-ui-chart-tip--title';
		title.textContent = '' + this._xLabel(i);
		box.appendChild(title);

		// Subtotal across y-axis (non-zero) series, for ratios + the Sum row.
		let subtotal = 0;
		this.series.forEach(s => { if(s.axis !== 'y2') subtotal += (s.values[i] || 0); });

		this.series.forEach((s, si) => {
			const v = s.values[i] || 0;
			if(v === 0) return;
			const row = document.createElement('div');
			row.className = 'cerb-ui-chart-tip--row';
			const sw = document.createElement('span');
			sw.className = 'cerb-ui-chart-tip--swatch';
			sw.style.backgroundColor = this._seriesColor(si, s);
			row.appendChild(sw);
			if(this.series.length > 1) {
				const lbl = document.createElement('span');
				lbl.className = 'cerb-ui-chart-tip--label';
				lbl.textContent = s.name;
				row.appendChild(lbl);
			}
			const val = document.createElement('span');
			val.className = 'cerb-ui-chart-tip--value';
			val.textContent = (s.axis === 'y2' ? this.y2Fmt : this.yFmt)(v);
			row.appendChild(val);
			if(this.tooltipRatios && s.axis !== 'y2' && subtotal > 0) {
				const pct = document.createElement('span');
				pct.className = 'cerb-ui-chart-tip--pct';
				pct.textContent = CerbUI.num.percent(v / subtotal);
				row.appendChild(pct);
			}
			box.appendChild(row);
		});

		if(this.tooltipSum && subtotal > 0 && this.series.filter(s => s.axis !== 'y2' && (s.values[i] || 0) !== 0).length > 1) {
			const row = document.createElement('div');
			row.className = 'cerb-ui-chart-tip--row cerb-ui-chart-tip--sum';
			const lbl = document.createElement('span');
			lbl.className = 'cerb-ui-chart-tip--label';
			lbl.textContent = 'Sum';
			const val = document.createElement('span');
			val.className = 'cerb-ui-chart-tip--value';
			val.textContent = this.yFmt(subtotal);
			row.append(lbl, val);
			box.appendChild(row);
		}
		return box;
	}

	_focusSeries(si) {
		this._focused = si;
		this._marks.forEach(m => { m.style.opacity = (m.dataset.series != null && +m.dataset.series === si) ? '1' : '0.25'; });
	}

	_revert() {
		this._focused = null;
		this._marks.forEach(m => { m.style.opacity = ''; });
	}

	// Public focus/revert by series key — lets an external legend (e.g. the chart-KATA stats table) drive
	// the same highlight the built-in legend uses.
	focusSeries(key) {
		const i = this.series.findIndex(s => s.key === key);
		if(i >= 0) this._focusSeries(i);
		return this;
	}

	revert() {
		this._revert();
		return this;
	}
};
