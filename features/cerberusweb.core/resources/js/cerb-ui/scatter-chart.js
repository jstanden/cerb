/*
 * CerbUI.ScatterChart — an XY scatterplot (point clouds on two continuous linear axes). Its own class rather
 * than a CartesianChart mode because each series is an independent set of (x, y) points (own x values, own
 * length) — not the shared-x model the cartesian bar/line chart assumes. Built on CerbUI.Chart for the shared
 * plumbing (palette/scale, tooltip, events, drill-through, lifecycle) and reuses CerbUI.scale.linear /
 * CerbUI.ticks.linear for the axes.
 *
 *   new CerbUI.ScatterChart(el, {
 *     x: { label?, tickFormat?, rotate?, min?, max? },
 *     y: { label?, tickFormat?, grid?:true, min?, max? },
 *     series: [ { key, name, color?, x:[…], values:[…], click?:[{context,query}] } ],  // or points:[[x,y],…]
 *     legend?, tooltip?, radius?, height?, palette?, scale?,
 *   });
 *
 * Hover snaps to the nearest point (name + formatted x,y). Publishes 'cerb-ui-chart:{hover,leave,click}'.
 */
CerbUI.ScatterChart = class extends CerbUI.Chart {
	constructor(el, options = {}) {
		super(el, options);
		if(!this.el) return;

		this.xcfg = options.x || {};
		this.ycfg = options.y || {};
		this.series = (options.series || []).map((s, i) => {
			let points = s.points;
			if(!points) {
				const xs = s.x || [], ys = s.values || [];
				points = xs.map((x, j) => [Number(x) || 0, Number(ys[j]) || 0]);
			} else {
				points = points.map(p => [Number(p[0]) || 0, Number(p[1]) || 0]);
			}
			return {
				key: s.key != null ? String(s.key) : ('s' + i),
				name: (s.name != null) ? s.name : (s.key != null ? String(s.key) : ('Series ' + (i + 1))),
				color: s.color,
				points: points,
				click: s.click || null,
			};
		});
		this.xFmt = (typeof this.xcfg.tickFormat === 'function') ? this.xcfg.tickFormat : CerbUI.num.grouped;
		this.yFmt = (typeof this.ycfg.tickFormat === 'function') ? this.ycfg.tickFormat : CerbUI.num.grouped;
		this.legendEnabled = !!options.legend;
		this.radius = options.radius || 4;
		this.axesIndependent = !!options.axesIndependent; // each series scaled to its own x/y range
		this.scale = this.scale || CerbUI.colorScale(this.palette);

		this.el.classList.add('cerb-ui-scatter-chart');

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
			legendEl.appendChild(item);
		});
		this.el.appendChild(legendEl);
		this._legend = new CerbUI.Legend(legendEl, { scale: this.scale, percent: false });
		this._legendRows = Array.from(legendEl.querySelectorAll(':scope > div'));
		this._legendRows.forEach((row, si) => {
			row.addEventListener('mouseover', () => this._focusSeries(si));
			row.addEventListener('mouseout', () => this._revert());
		});
	}

	_seriesColor(si, s) {
		if(s.color) return s.color;
		return this.scale ? this.scale.color(s.key) : this.palette[si % this.palette.length];
	}

	// A padded [min,max] over an accessor across the given points, honoring explicit cfg.min/max.
	_domainOf(points, accessor, cfg) {
		let lo = Infinity, hi = -Infinity;
		points.forEach(p => { const v = accessor(p); if(v < lo) lo = v; if(v > hi) hi = v; });
		if(!isFinite(lo)) { lo = 0; hi = 1; }
		const span = (hi - lo) || Math.abs(hi) || 1;
		lo -= span * 0.05; hi += span * 0.05;
		if(cfg.min != null) lo = cfg.min;
		if(cfg.max != null) hi = cfg.max;
		return [lo, hi];
	}

	_allPoints() {
		const all = [];
		this.series.forEach(s => s.points.forEach(p => all.push(p)));
		return all;
	}

	render() {
		if(!this._svg) return;
		const svg = this._svg;
		svg.replaceChildren();
		this._pts = [];
		this._pointEls = [];

		const W = this.el.clientWidth, H = this.height;
		if(!W || !this.series.length) return;

		svg.setAttribute('width', W);
		svg.setAttribute('height', H);
		svg.setAttribute('viewBox', '0 0 ' + W + ' ' + H);

		const mLeft = 58, mRight = 14, mTop = 8;
		// Size the bottom margin: rotated tick labels extend downward by ~their length; add room for the x label.
		let mBottom;
		if(this.xcfg.rotate) {
			const axisPoints = this.axesIndependent ? ((this.series[0] && this.series[0].points) || this._allPoints()) : this._allPoints();
			const xd = this._domainOf(axisPoints, p => p[0], this.xcfg);
			const maxChars = CerbUI.ticks.linear(xd[0], xd[1], 6).reduce((m, t) => Math.max(m, ('' + this.xFmt(t)).length), 0);
			mBottom = Math.min(150, 24 + Math.round(maxChars * 6.2)) + (this.xcfg.label ? 16 : 0);
		} else {
			mBottom = 34 + (this.xcfg.label ? 16 : 0);
		}
		this._plot = { left: mLeft, top: mTop, right: W - mRight, bottom: H - mBottom };

		const xRange = [mLeft, W - mRight], yRange = [H - mBottom, mTop];
		const makeScales = (pts) => ({
			x: CerbUI.scale.linear({ domain: this._domainOf(pts, p => p[0], this.xcfg), range: xRange }),
			y: CerbUI.scale.linear({ domain: this._domainOf(pts, p => p[1], this.ycfg), range: yRange }),
		});

		let seriesScales;
		if(this.axesIndependent) {
			// Each series scaled to its own range; the axis ticks reflect the first series (mixed scales
			// have no single meaningful axis) — values are read from the hover tooltip.
			seriesScales = this.series.map(s => makeScales(s.points));
			const first = seriesScales[0] || makeScales(this._allPoints());
			this.xScale = first.x; this.yScale = first.y;
		} else {
			const shared = makeScales(this._allPoints());
			this.xScale = shared.x; this.yScale = shared.y;
			seriesScales = this.series.map(() => shared);
		}

		this._drawAxes();

		this.series.forEach((s, si) => {
			const color = this._seriesColor(si, s);
			const sc = seriesScales[si];
			s.points.forEach((p, ci) => {
				const cx = sc.x(p[0]), cy = sc.y(p[1]);
				const dot = document.createElementNS(this.NS, 'circle');
				dot.setAttribute('class', 'cerb-ui-scatter-chart--point');
				dot.setAttribute('cx', cx); dot.setAttribute('cy', cy); dot.setAttribute('r', this.radius);
				dot.setAttribute('fill', color);
				dot.dataset.series = si; dot.dataset.cat = ci;
				if(s.click && s.click[ci] && s.click[ci].query) dot.style.cursor = 'pointer';
				svg.appendChild(dot);
				this._pts.push({ sx: cx, sy: cy, si: si, ci: ci });
				this._pointEls.push(dot);
			});
		});

		this._bindEvents();
	}

	_drawAxes() {
		const svg = this._svg;
		const grid = (this.ycfg.grid !== false);
		// y axis (left) + horizontal gridlines
		this.yScale.ticks(5).forEach(t => {
			const y = this.yScale(t);
			if(grid) {
				const line = document.createElementNS(this.NS, 'line');
				line.setAttribute('class', 'cerb-ui-scatter-chart--grid');
				line.setAttribute('x1', this._plot.left); line.setAttribute('x2', this._plot.right);
				line.setAttribute('y1', y); line.setAttribute('y2', y);
				svg.appendChild(line);
			}
			const lbl = document.createElementNS(this.NS, 'text');
			lbl.setAttribute('class', 'cerb-ui-scatter-chart--tick');
			lbl.setAttribute('x', this._plot.left - 6); lbl.setAttribute('y', y + 4); lbl.setAttribute('text-anchor', 'end');
			lbl.textContent = this.yFmt(t);
			svg.appendChild(lbl);
		});
		// x axis (bottom)
		this.xScale.ticks(6).forEach(t => {
			const x = this.xScale(t);
			const lbl = document.createElementNS(this.NS, 'text');
			lbl.setAttribute('class', 'cerb-ui-scatter-chart--tick');
			lbl.setAttribute('x', x); lbl.setAttribute('y', this._plot.bottom + 14);
			lbl.setAttribute('text-anchor', this.xcfg.rotate ? 'end' : 'middle');
			if(this.xcfg.rotate) lbl.setAttribute('transform', 'rotate(' + this.xcfg.rotate + ',' + x + ',' + (this._plot.bottom + 14) + ')');
			lbl.textContent = this.xFmt(t);
			svg.appendChild(lbl);
		});
		// axis labels
		if(this.ycfg.label) {
			const cy = (this._plot.top + this._plot.bottom) / 2;
			const t = document.createElementNS(this.NS, 'text');
			t.setAttribute('class', 'cerb-ui-scatter-chart--axis-label');
			t.setAttribute('x', 12); t.setAttribute('y', cy); t.setAttribute('text-anchor', 'middle');
			t.setAttribute('transform', 'rotate(-90,12,' + cy + ')');
			t.textContent = this.ycfg.label;
			svg.appendChild(t);
		}
		if(this.xcfg.label) {
			const cx = (this._plot.left + this._plot.right) / 2;
			const t = document.createElementNS(this.NS, 'text');
			t.setAttribute('class', 'cerb-ui-scatter-chart--axis-label');
			t.setAttribute('x', cx); t.setAttribute('y', this.height - 4); t.setAttribute('text-anchor', 'middle');
			t.textContent = this.xcfg.label;
			svg.appendChild(t);
		}
	}

	// Nearest point to a screen position, within a pixel threshold.
	_nearest(sx, sy) {
		let best = null, bd = 40 * 40; // 40px radius
		this._pts.forEach(p => { const d = (p.sx - sx) * (p.sx - sx) + (p.sy - sy) * (p.sy - sy); if(d < bd) { bd = d; best = p; } });
		return best;
	}

	_bindEvents() {
		// The svg persists across renders — remove prior handlers so listeners (and drill-throughs) don't stack.
		if(this._onClick) {
			this._svg.removeEventListener('mousemove', this._onMove);
			this._svg.removeEventListener('mouseleave', this._onLeave);
			this._svg.removeEventListener('click', this._onClick);
		}
		this._onMove = (e) => {
			const rect = this._svg.getBoundingClientRect();
			const p = this._nearest(e.clientX - rect.left, e.clientY - rect.top);
			const tip = this._tooltip();
			if(!p) { if(tip) tip.hide(); this._emit('leave', {}); return; }
			const s = this.series[p.si], pt = s.points[p.ci];
			this._emit('hover', { series: s.key, index: p.ci, x: pt[0], y: pt[1], point: { x: e.clientX, y: e.clientY } });
			if(tip) tip.show(this._tooltipContent(s, pt), e.clientX, e.clientY, this.el);
		};
		this._onLeave = () => { const tip = this._tooltip(); if(tip) tip.hide(); this._emit('leave', {}); };
		this._onClick = (e) => {
			const mark = e.target.closest ? e.target.closest('.cerb-ui-scatter-chart--point') : null;
			if(!mark || mark.dataset.series == null) return;
			const s = this.series[+mark.dataset.series], ci = +mark.dataset.cat;
			e.stopPropagation();
			const click = s.click && s.click[ci];
			this._emit('click', { series: s.key, index: ci, click: click });
			if(click && click.query) this._search(click.context, click.query);
		};
		this._svg.addEventListener('mousemove', this._onMove);
		this._svg.addEventListener('mouseleave', this._onLeave);
		this._svg.addEventListener('click', this._onClick);
	}

	_tooltipContent(s, pt) {
		const box = document.createElement('div');
		const title = document.createElement('div');
		title.className = 'cerb-ui-chart-tip--title';
		title.textContent = s.name;
		box.appendChild(title);
		const val = document.createElement('div');
		val.className = 'cerb-ui-chart-tip--value';
		val.textContent = this.xFmt(pt[0]) + ', ' + this.yFmt(pt[1]);
		box.appendChild(val);
		return box;
	}

	_focusSeries(si) {
		this._focused = si;
		(this._pointEls || []).forEach(m => { m.style.opacity = (+m.dataset.series === si) ? '1' : '0.2'; });
	}

	_revert() {
		this._focused = null;
		(this._pointEls || []).forEach(m => { m.style.opacity = ''; });
	}
};
