/*
 * CerbUI.Gantt — named rows of horizontal spans on one shared X axis (a Gantt / range chart). Each row is a
 * label plus any number of spans; rows covering the same X range OVERLAP visibly, which is usually the whole
 * point (two lanes sharing a pool, two jobs contending for a window). Built on CerbUI.Chart for the shared
 * lifecycle, palette and tooltip.
 *
 *   new CerbUI.Gantt(el, {
 *     xScale: 'linear',          // 'linear' (numbers) | 'time' (epoch ms)
 *     domain: [1, 26],           // omit to derive from the spans
 *     step:   1,                 // see below; omit for continuous ranges
 *     segment: true,             // with `step`: one block per unit rather than one bar per span
 *     segmentGap: 3,             // px between blocks
 *     rows: [
 *       { label: 'Batch jobs',  color: '#0088e6', spans: [ [1,6], [13,25] ] },
 *       { label: 'Agent turns', color: '#9467bd', spans: [ [7,12], [13,25] ] },
 *     ],
 *     rowHeight: 28, barHeight: 14, labelWidth: 110,
 *     tickFormat: CerbUI.date.strftime('%H:%M'),   // or a num.format pattern fn
 *     ticks: 6, axis: true, tooltip: true,
 *   });
 *
 * SPAN ENDS. Without `step` a span is the half-open interval [start, end) -- the right reading for time,
 * where a job from 09:00 to 10:00 must not overlap one starting at 10:00. With `step` the axis is DISCRETE
 * units of that size and `end` is INCLUSIVE, drawn to end+step -- so slots [1,6] covers six cells, not five.
 * Getting this wrong is a silent off-by-one that shows as a bar one unit short, so pick deliberately.
 *
 * SEGMENTS. `segment: true` draws each unit as its OWN block instead of one bar per span, and cuts the
 * row's track the same way -- so a row over a pool of discrete units reads as the units it HOLDS, with
 * the ones it does not hold left as empty cells. It needs `step` (a continuous axis has no units to cut
 * on) and is ignored without it. Spans stay whole in the data: hover still reports the run, so a caller
 * keeps emitting runs and the chart decides how they are drawn.
 *
 * A span is [start, end] or { start, end, label, color, key }; per-span color beats the row's.
 *
 * It PUBLISHES bubbling events (CerbUI.Chart's namespace):
 *   'cerb-ui-chart:hover'  detail = { row, span, point:{x,y} }
 *   'cerb-ui-chart:leave'
 *   'cerb-ui-chart:click'  detail = same shape as hover
 */
CerbUI.Gantt = class extends CerbUI.Chart {
	// Candidate "nice" time steps, smallest first (ms). Beyond a week the calendar stops being regular, so
	// anything larger falls back to whole days rather than pretending months are a fixed size.
	static TIME_STEPS = [
		1e3, 5e3, 15e3, 30e3,
		6e4, 3e5, 9e5, 18e5,
		36e5, 108e5, 216e5, 432e5,
		864e5, 6048e5
	];

	constructor(el, options = {}) {
		super(el, options);
		if(!this.el) return;

		this.xScaleType = (options.xScale === 'time') ? 'time' : 'linear';
		this.step = (options.step != null) ? Number(options.step) : null;
		this.segment = !!options.segment && !!this.step;
		this.segmentGap = (options.segmentGap != null) ? Number(options.segmentGap) : 3;
		this.rowHeight = options.rowHeight || 28;
		this.barHeight = options.barHeight || 14;
		this.labelWidth = (options.labelWidth != null) ? options.labelWidth : 110;
		this.axis = (options.axis !== false);
		this.tickCount = options.ticks || 6;
		this.padTop = (options.padTop != null) ? options.padTop : 6;
		this.padRight = (options.padRight != null) ? options.padRight : 8;
		this.axisHeight = this.axis ? 22 : 0;

		this.rows = (options.rows || []).map(row => ({
			label: row.label != null ? String(row.label) : '',
			key: row.key,
			color: row.color,
			spans: (row.spans || []).map(s => this._normalizeSpan(s)).filter(Boolean)
		}));

		this.tickFormat = options.tickFormat || (this.xScaleType === 'time'
			? CerbUI.date.strftime('%H:%M')
			: CerbUI.num.format(','));

		this.domain = options.domain || this._deriveDomain();

		// A row-count height, not the base class's 320px default -- two rows should not reserve a chart box.
		this.height = options.height || (this.padTop + this.rows.length * this.rowHeight + this.axisHeight);

		this.el.classList.add('cerb-ui-gantt');
		this.render();
	}

	// Accept [start, end] or { start, end, ... }; drop anything without two finite numbers.
	_normalizeSpan(s) {
		const span = Array.isArray(s) ? { start: s[0], end: s[1] } : Object.assign({}, s);
		span.start = Number(span.start);
		span.end = Number(span.end);
		return (isFinite(span.start) && isFinite(span.end)) ? span : null;
	}

	// The X value a span is DRAWN to: `step` makes `end` inclusive of its own unit.
	_spanEnd(span) {
		return this.step ? span.end + this.step : span.end;
	}

	_deriveDomain() {
		let lo = null, hi = null;

		this.rows.forEach(row => row.spans.forEach(span => {
			const end = this._spanEnd(span);
			if(lo === null || span.start < lo) lo = span.start;
			if(hi === null || end > hi) hi = end;
		}));

		return (lo === null) ? [0, 1] : [lo, hi];
	}

	setRows(rows) {
		this.rows = (rows || []).map(row => ({
			label: row.label != null ? String(row.label) : '',
			key: row.key,
			color: row.color,
			spans: (row.spans || []).map(s => this._normalizeSpan(s)).filter(Boolean)
		}));

		if(!this.options.domain)
			this.domain = this._deriveDomain();

		this.height = this.options.height || (this.padTop + this.rows.length * this.rowHeight + this.axisHeight);
		this.render();
	}

	/*
	 * Nice ticks for a time domain. chart-core's `time` scale is a linear alias, so its own .ticks() would
	 * land on arbitrary millisecond values -- readable ticks have to come from a calendar-aware step.
	 *
	 * Alignment walks up from LOCAL midnight rather than from the epoch: an epoch-aligned hour is :00 only
	 * in whole-hour zones, and would render :30 everywhere on a half-hour offset.
	 */
	_timeTicks(d0, d1, count) {
		const span = d1 - d0;
		const steps = CerbUI.Gantt.TIME_STEPS;
		let step = steps[steps.length - 1];

		for(let i = 0; i < steps.length; i++) {
			if(span / steps[i] <= count) { step = steps[i]; break; }
		}

		const base = new Date(d0);
		base.setHours(0, 0, 0, 0);

		let t = base.getTime();
		while(t < d0) t += step;

		const out = [];
		// Bounded so a pathological domain can't spin here.
		for(let i = 0; t <= d1 && i < 500; i++, t += step)
			out.push(t);

		return out;
	}

	/*
	 * Ticks on a DISCRETE axis name UNITS, not positions. The generic linear ticks are "nice" for continuous
	 * data and will happily place one at 1.5 -- on a three-slot pool that reads as a slot that does not exist.
	 *
	 * Each tick is a unit's START, and stops BEFORE the domain's end because that end is exclusive: on a
	 * 10-unit axis of [1,11] the last unit is 10, and a tick at 11 would label a cell that isn't drawn.
	 */
	_stepTicks(d, step, count) {
		const units = (d[1] - d[0]) / step;
		const stride = Math.max(1, Math.ceil(units / count));

		const out = [];

		for(let v = d[0], i = 0; v < d[1] && i < 500; v += step * stride, i++)
			out.push(v);

		return out;
	}

	_ticks(x) {
		const d = x.domain();

		if(this.xScaleType === 'time')
			return this._timeTicks(d[0], d[1], this.tickCount);

		return this.step ? this._stepTicks(d, this.step, this.tickCount) : x.ticks(this.tickCount);
	}

	/*
	 * The cells a span is DRAWN as: one per step unit when segmented, otherwise the span itself. Returned in
	 * data coordinates, half-open, so the caller can hand either straight to the scale.
	 */
	_spanCells(span) {
		if(!this.segment)
			return [[span.start, this._spanEnd(span)]];

		const out = [];

		// Bounded so a span far wider than its step can't spin here.
		for(let v = span.start, i = 0; v <= span.end && i < 1000; v += this.step, i++)
			out.push([v, v + this.step]);

		return out;
	}

	// The row's background cells. Segmented, the track IS the empty units -- which is what makes a missing
	// block read as a unit this row does not hold rather than as a gap in the drawing.
	_trackCells() {
		if(!this.segment)
			return [this.domain];

		const out = [];

		for(let v = this.domain[0], i = 0; v + this.step <= this.domain[1] && i < 1000; v += this.step, i++)
			out.push([v, v + this.step]);

		return out;
	}

	// A bar rect in DATA coordinates. A segmented cell is inset by half the gap on EACH side so every block
	// is the same width; taking the whole gap off one edge would make a run's end blocks the odd ones out.
	_cellRect(x, a, b, barTop, cls, fill) {
		const sx = x(a), ex = x(b);
		const cell = Math.abs(ex - sx);
		// The gap is capped against the cell so a dense pool degrades into thin blocks rather than into a
		// row of slivers separated by more space than they occupy.
		const inset = this.segment ? Math.min(this.segmentGap, cell * 0.35) / 2 : 0;
		// A degenerate span still gets a visible sliver rather than vanishing silently.
		const w = Math.max(1, cell - inset * 2);

		const attrs = {
			'class': cls,
			x: Math.min(sx, ex) + inset,
			y: barTop,
			width: w,
			height: this.barHeight,
			rx: Math.min(4, this.barHeight / 2, w / 2)
		};

		if(fill)
			attrs.fill = fill;

		return this._svgEl('rect', attrs);
	}

	_svgEl(name, attrs) {
		const node = document.createElementNS(this.NS, name);
		Object.keys(attrs || {}).forEach(k => node.setAttribute(k, attrs[k]));
		return node;
	}

	render() {
		if(!this._svg) return;

		const svg = this._svg;
		svg.replaceChildren();

		const W = this.el.clientWidth;
		if(!W) return;

		const H = this.height;
		svg.setAttribute('width', W);
		svg.setAttribute('height', H);
		svg.setAttribute('viewBox', '0 0 ' + W + ' ' + H);

		const x0 = this.labelWidth, x1 = W - this.padRight;
		if(x1 <= x0) return;

		const x = CerbUI.scale[this.xScaleType]({ domain: this.domain, range: [x0, x1] });
		const rowsBottom = this.padTop + this.rows.length * this.rowHeight;

		// Gridlines first so every bar and label sits above them.
		if(this.axis) {
			this._ticks(x).forEach(t => {
				const px = x(t);

				// Segmented rows already show every unit as its own block, so a gridline adds no division
				// and lands in the gap between two blocks -- reading as a boundary the labels don't name
				// whenever the tick stride skips units (ticks at 1 and 3, a line between 2 and 3).
				if(!this.segment) {
					svg.appendChild(this._svgEl('line', {
						'class': 'cerb-ui-gantt--grid',
						x1: px, x2: px, y1: this.padTop, y2: rowsBottom
					}));
				}

				// The label NAMES a unit, so it sits in the middle of the cell it belongs to. On the
				// boundary it reads as the edge before the block -- unit 1's label under the line to unit
				// 1's left, which scans as though the first block were unit 0.
				const labelPx = this.step ? x(t + (this.step / 2)) : px;

				const label = this._svgEl('text', {
					'class': 'cerb-ui-gantt--tick',
					x: labelPx, y: rowsBottom + 15, 'text-anchor': 'middle'
				});
				label.textContent = this.tickFormat(t);
				svg.appendChild(label);
			});
		}

		this.rows.forEach((row, i) => {
			const top = this.padTop + i * this.rowHeight;
			const mid = top + this.rowHeight / 2;
			const barTop = mid - this.barHeight / 2;

			// Full-width track: without it a row of sparse spans reads as an empty gap rather than a lane.
			this._trackCells().forEach(cell => {
				svg.appendChild(this._cellRect(x, cell[0], cell[1], barTop, 'cerb-ui-gantt--track', null));
			});

			const label = this._svgEl('text', {
				'class': 'cerb-ui-gantt--row-label',
				x: this.labelWidth - 10, y: mid, 'text-anchor': 'end', 'dominant-baseline': 'middle'
			});
			label.textContent = row.label;
			svg.appendChild(label);

			row.spans.forEach(span => {
				const fill = span.color || this._color(i, row);

				this._spanCells(span).forEach(cell => {
					const rect = this._cellRect(x, cell[0], cell[1], barTop, 'cerb-ui-gantt--span', fill);

					// Every block of a run carries the whole span, so hover reads back the run either way.
					rect._row = row;
					rect._span = span;
					svg.appendChild(rect);
				});
			});
		});

		this._bindHover();
	}

	_bindHover() {
		if(this._bound) return;
		this._bound = true;

		const tip = () => this._tooltip();

		this._svg.addEventListener('mousemove', (e) => {
			const rect = e.target && e.target._span ? e.target : null;

			if(!rect) {
				const t = tip();
				if(t) t.hide();
				this._emit('leave', {});
				return;
			}

			const detail = { row: rect._row, span: rect._span, point: { x: e.clientX, y: e.clientY } };
			const t = tip();

			if(t) t.show(this._tipHtml(rect._row, rect._span), e.clientX, e.clientY);

			this._emit('hover', detail);
		});

		this._svg.addEventListener('mouseleave', () => {
			const t = tip();
			if(t) t.hide();
			this._emit('leave', {});
		});

		this._svg.addEventListener('click', (e) => {
			const rect = e.target && e.target._span ? e.target : null;
			if(!rect) return;
			this._emit('click', { row: rect._row, span: rect._span, point: { x: e.clientX, y: e.clientY } });
		});
	}

	_tipHtml(row, span) {
		const esc = (s) => String(s).replace(/[&<>"]/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));
		const title = span.label != null ? span.label : row.label;
		// The label reads back the span as the CALLER wrote it, so an inclusive end prints as the caller's end.
		// Compared AFTER formatting, so a span whose ends differ but render alike ('09:00 - 09:00' under a
		// %H:%M format) collapses too, not just a literal single unit.
		const from = this.tickFormat(span.start);
		const to = this.tickFormat(span.end);
		const range = (from === to) ? from : (from + ' - ' + to);

		return '<div class="cerb-ui-chart-tip--title">' + esc(title) + '</div>'
			+ '<div class="cerb-ui-chart-tip--value">' + esc(range) + '</div>';
	}
};
