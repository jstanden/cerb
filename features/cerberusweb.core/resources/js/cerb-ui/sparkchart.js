/*
 * CerbUI.Sparkchart — a compact, categorical, multi-series chart (the "spark" family; precursor to a full
 * charting lib). Plots bar + line series against shared x categories; each series scales independently
 * (own min/max AND units). Bars are 0-based; lines range over their own min→max. The component never
 * formats data — it renders `series[].text[i]` (preformatted by the backend) and uses `values[]` only for
 * geometry. Categorical, not time-based: "time" is just pre-binned category labels. Every category gets a
 * 1px neutral baseline tick under all series (so a no-activity bin still reads as "present", like a line at x=0). Bar series sharing a
 * `stack` key stack cumulatively and share one scale (the max of their per-category sums); line series
 * sharing a `scaleGroup` key share one min→max (so a min/avg/max band nests instead of each line filling
 * the full height on its own scale).
 *
 * Usage:
 *   new CerbUI.Sparkchart(el, {
 *     categories: ['10:00', …, 'now'],            // x labels (tooltip + extent captions)
 *     series: [
 *       { type:'bar',  label:'done',         values:[…], text:['27', …], stack:'msgs' },
 *       { type:'bar',  label:'failed',       values:[…], text:['3', …],  stack:'msgs' }, // stacks on 'done'
 *       { type:'line', label:'avg duration', values:[…], text:['172ms', …] },
 *     ],
 *     caption:  ['24h ago', 'now'],  // extents below the plot: [start,end] at the ends, a single string centered, omit = none
 *     ticks:    true,                // one tick per category below the bars (default true); false = a bare sparkline
 *     height:   56,                  // plot height, px (smaller = compact)
 *     barWidth: 0.6,                 // bar width as a fraction of the category band (gap = the rest)
 *     tooltip:  true,                // built-in hover tooltip (one shared instance across charts)
 *     tooltipLabels: false,          // force series labels in the tooltip even for a single series (default: only when >1)
 *     palette:  'category10',        // series colored by index (or set series[].color)
 *     scale:    sharedScale,         // a CerbUI.colorScale() to color series by label (matches a legend)
 *   });
 *
 * It always PUBLISHES bubbling events on the chart element (use these to drive your own UI, or with
 * tooltip:false to fully own the interaction):
 *   'cerb-ui-sparkchart:hover'  detail = { index, category, point:{x,y}, series:[{type,label,color,value,text}] }
 *   'cerb-ui-sparkchart:leave'
 *   'cerb-ui-sparkchart:click'  detail = same shape as hover
 */
CerbUI.Sparkchart = class {
	static _instances = new WeakMap();
	static from(el) { return CerbUI.Sparkchart._instances.get(el); }

	constructor(el, options = {}) {
		this.el = (typeof el === 'string') ? document.querySelector(el) : el;
		if(!this.el) return;
		CerbUI.Sparkchart._instances.set(this.el, this);

		this.categories = options.categories || [];
		this.series = options.series || [];
		this.palette = CerbUI.resolvePalette(options.palette);
		this.scale = options.scale || null; // optional shared color scale (color by series label, like Legend)
		if(this.scale && options.palette != null) this.scale.usePalette(options.palette); // honor an explicit palette
		this.height = options.height || 56;
		this.barWidth = (options.barWidth != null) ? options.barWidth : 0.6;
		this.ticks = (options.ticks !== false);
		this.caption = options.caption; // [start, end] | 'centered string' | omitted = none
		this.tooltipEnabled = (options.tooltip !== false);
		this.tooltipLabels = (options.tooltipLabels === true); // force labels even for a single series
		this.NS = 'http://www.w3.org/2000/svg';

		this.el.classList.add('cerb-ui-sparkchart');

		this._svg = document.createElementNS(this.NS, 'svg');
		this._svg.setAttribute('class', 'cerb-ui-sparkchart--plot');
		this._svg.setAttribute('preserveAspectRatio', 'none');
		this.el.appendChild(this._svg);

		this._buildCaption();

		this._bindHover();

		if(window.ResizeObserver) {
			this._ro = new ResizeObserver(() => this.render());
			this._ro.observe(this.el);
		}
		this.render();
	}

	// Extent captions below the plot: a [start, end] pair at the ends, or a single string centered. Omitted = none.
	_buildCaption() {
		if(this.caption == null || this.caption === '') return;
		const cap = document.createElement('div');
		cap.className = 'cerb-ui-sparkchart--caption';
		if(Array.isArray(this.caption)) {
			const a = document.createElement('span'); a.textContent = this.caption[0] || '';
			const b = document.createElement('span'); b.textContent = this.caption[1] || '';
			cap.append(a, b);
		} else {
			cap.classList.add('cerb-ui-sparkchart--caption-center');
			const s = document.createElement('span'); s.textContent = this.caption;
			cap.appendChild(s);
		}
		this.el.appendChild(cap);
	}

	_color(i, s) {
		if(s.color) return s.color;
		return this.scale ? this.scale.color(s.label) : this.palette[i % this.palette.length];
	}

	render() {
		const W = this.el.clientWidth;
		if(!W) return; // not laid out yet (ResizeObserver will call us again)
		const H = this.height;
		const N = this.categories.length;
		this._N = N;
		this._band = N ? W / N : W;
		const pad = 2; // keep strokes off the top/bottom edges
		const tickLen = 4, tickGap = 3;
		const tickArea = this.ticks ? (tickGap + tickLen) : 0; // reserved strip at the bottom for ticks
		const chartH = H - pad * 2 - tickArea;
		const baseline = pad + chartH;

		const svg = this._svg;
		svg.setAttribute('width', W);
		svg.setAttribute('height', H);
		svg.setAttribute('viewBox', '0 0 ' + W + ' ' + H);
		svg.replaceChildren();

		// hover highlight band (drawn under the data)
		this._hl = document.createElementNS(this.NS, 'rect');
		this._hl.setAttribute('class', 'cerb-ui-sparkchart--hover');
		this._hl.setAttribute('x', 0);
		this._hl.setAttribute('y', 0);
		this._hl.setAttribute('width', this._band);
		this._hl.setAttribute('height', H);
		this._hl.style.display = 'none';
		svg.appendChild(this._hl);

		// Stacked-bar groups: bars sharing a `stack` key share one scale (the max of per-category sums)
		// and stack up. `offset` accumulates the drawn pixel height per category as we render the group.
		const stacks = {};
		this.series.forEach((s) => {
			if(s.type === 'line' || !s.stack) return;
			const g = stacks[s.stack] || (stacks[s.stack] = { sums: [] });
			(s.values || []).forEach((v, i) => { g.sums[i] = (g.sums[i] || 0) + Math.max(0, v); });
		});
		Object.keys(stacks).forEach((k) => {
			const g = stacks[k];
			g.max = Math.max.apply(null, g.sums.concat(0)) || 1;
			g.offset = [];
		});

		// Line scale groups: lines sharing a `scaleGroup` key share one min→max, so e.g. a min/avg/max
		// band of the same quantity nests correctly instead of each line filling the full plot height.
		const lineScales = {};
		this.series.forEach((s) => {
			if(s.type === 'line' && s.scaleGroup) {
				const g = lineScales[s.scaleGroup] || (lineScales[s.scaleGroup] = { min: Infinity, max: -Infinity });
				(s.values || []).forEach((v) => { if(v < g.min) g.min = v; if(v > g.max) g.max = v; });
			}
		});

		// Shared baseline: a 1px neutral floor tick (bar width) per category, drawn under every series so
		// bars/lines paint on top. A no-activity bin shows just this tick — like a line resting at x=0.
		{
			const bw = this._band * this.barWidth;
			for(let i = 0; i < N; i++) {
				const base = document.createElementNS(this.NS, 'rect');
				base.setAttribute('class', 'cerb-ui-sparkchart--baseline');
				base.setAttribute('x', (i + 0.5) * this._band - bw / 2);
				base.setAttribute('y', baseline - 1);
				base.setAttribute('width', bw);
				base.setAttribute('height', 1);
				svg.appendChild(base);
			}
		}

		this.series.forEach((s, si) => {
			const color = this._color(si, s);
			const vals = s.values || [];

			if(s.type === 'line') {
				const grp = s.scaleGroup ? lineScales[s.scaleGroup] : null;
				const min = grp ? grp.min : Math.min.apply(null, vals);
				const max = grp ? grp.max : Math.max.apply(null, vals);
				const range = (max - min) || 1;
				const points = vals.map((v, i) => {
					const x = (i + 0.5) * this._band;
					const y = pad + (1 - (v - min) / range) * chartH;
					return x + ',' + y;
				}).join(' ');
				const line = document.createElementNS(this.NS, 'polyline');
				line.setAttribute('class', 'cerb-ui-sparkchart--line');
				line.setAttribute('points', points);
				line.setAttribute('stroke', color);
				svg.appendChild(line);
			} else { // bar — 0-based, optionally stacked within its `stack` group
				const grp = s.stack ? stacks[s.stack] : null;
				const max = grp ? grp.max : (Math.max.apply(null, vals.concat(0)) || 1);
				const bw = this._band * this.barWidth;
				vals.forEach((v, i) => {
					const h = Math.max(0, (v / max) * chartH);
					const off = grp ? (grp.offset[i] || 0) : 0;
					const bar = document.createElementNS(this.NS, 'rect');
					bar.setAttribute('class', 'cerb-ui-sparkchart--bar');
					bar.setAttribute('x', (i + 0.5) * this._band - bw / 2);
					bar.setAttribute('y', pad + (chartH - off - h));
					bar.setAttribute('width', bw);
					bar.setAttribute('height', h);
					bar.setAttribute('fill', color);
					svg.appendChild(bar);
					if(grp) grp.offset[i] = off + h;
				});
			}
		});

		// One tick per category, centered under the bars (matches the caption extents)
		if(this.ticks) {
			for(let i = 0; i < N; i++) {
				const x = (i + 0.5) * this._band;
				const tick = document.createElementNS(this.NS, 'line');
				tick.setAttribute('class', 'cerb-ui-sparkchart--tick');
				tick.setAttribute('x1', x);
				tick.setAttribute('x2', x);
				tick.setAttribute('y1', baseline + tickGap);
				tick.setAttribute('y2', baseline + tickGap + tickLen);
				svg.appendChild(tick);
			}
		}
	}

	// Category index under a pointer event, clamped to range
	_indexFromEvent(e) {
		const rect = this.el.getBoundingClientRect();
		const idx = Math.floor((e.clientX - rect.left) / this._band);
		return { idx: Math.max(0, Math.min(this._N - 1, idx)), rect };
	}

	// Event/tooltip payload for a category
	_detailAt(idx, rect) {
		return {
			index: idx,
			category: this.categories[idx],
			point: { x: rect.left + (idx + 0.5) * this._band, y: rect.top },
			series: this.series.map((s, si) => ({
				type: s.type,
				label: s.label,
				color: this._color(si, s),
				value: (s.values || [])[idx],
				text: (s.text || [])[idx],
			})),
		};
	}

	// One shared tooltip across all sparkcharts (only one is ever visible) — lazily created
	_tooltip() {
		if(!this.tooltipEnabled || !CerbUI.Tooltip) return null;
		if(!CerbUI.Sparkchart._sharedTooltip) CerbUI.Sparkchart._sharedTooltip = new CerbUI.Tooltip();
		return CerbUI.Sparkchart._sharedTooltip;
	}

	// Tooltip body: a category title + a swatch·[label]·value row per series. The label is shown only
	// for multi-series charts (a single series' label would just be noise next to its value).
	_tooltipContent(detail) {
		const box = document.createElement('div');
		const title = document.createElement('div');
		title.className = 'cerb-ui-sparkchart-tip--title';
		title.textContent = detail.category;
		box.appendChild(title);

		const showLabels = this.tooltipLabels || detail.series.length > 1;

		detail.series.forEach((s) => {
			const row = document.createElement('div');
			row.className = 'cerb-ui-sparkchart-tip--row';

			const swatch = document.createElement('span');
			swatch.className = 'cerb-ui-sparkchart-tip--swatch';
			swatch.style.backgroundColor = s.color;
			row.appendChild(swatch);

			if(showLabels && s.label) {
				const label = document.createElement('span');
				label.className = 'cerb-ui-sparkchart-tip--label';
				label.textContent = s.label;
				row.appendChild(label);
			}

			const value = document.createElement('span');
			value.className = 'cerb-ui-sparkchart-tip--value';
			value.textContent = (s.text != null) ? s.text : s.value;
			row.appendChild(value);

			box.appendChild(row);
		});
		return box;
	}

	_bindHover() {
		this._idx = null;

		// Store handler refs so destroy() can remove them (else re-rendering on the same el leaks
		// listeners — old instances keep answering hover with stale data).
		this._onMove = (e) => {
			if(!this._N) return;
			const { idx, rect } = this._indexFromEvent(e);
			if(idx === this._idx) return; // snap to band — only react when the category changes
			this._idx = idx;

			if(this._hl) {
				this._hl.style.display = '';
				this._hl.setAttribute('x', idx * this._band);
			}

			const detail = this._detailAt(idx, rect);
			this.el.dispatchEvent(new CustomEvent('cerb-ui-sparkchart:hover', { detail: detail, bubbles: true }));

			const tip = this._tooltip();
			if(tip) tip.show(this._tooltipContent(detail), detail.point.x, detail.point.y, this.el);
		};

		this._onLeave = () => {
			this._idx = null;
			if(this._hl) this._hl.style.display = 'none';
			this.el.dispatchEvent(new CustomEvent('cerb-ui-sparkchart:leave', { bubbles: true }));

			const tip = this._tooltip();
			if(tip) tip.hide();
		};

		this._onClick = (e) => {
			if(!this._N) return;
			const { idx, rect } = this._indexFromEvent(e);
			this.el.dispatchEvent(new CustomEvent('cerb-ui-sparkchart:click', { detail: this._detailAt(idx, rect), bubbles: true }));
		};

		this.el.addEventListener('mousemove', this._onMove);
		this.el.addEventListener('mouseleave', this._onLeave);
		this.el.addEventListener('click', this._onClick);
	}

	// Tear down listeners, the ResizeObserver, and the SVG so the el can be safely re-rendered or removed
	destroy() {
		if(this._ro) { this._ro.disconnect(); this._ro = null; }
		if(this._onMove) this.el.removeEventListener('mousemove', this._onMove);
		if(this._onLeave) this.el.removeEventListener('mouseleave', this._onLeave);
		if(this._onClick) this.el.removeEventListener('click', this._onClick);

		const tip = this._tooltip();
		if(tip) tip.hide();

		this.el.replaceChildren();
		this.el.classList.remove('cerb-ui-sparkchart');
		CerbUI.Sparkchart._instances.delete(this.el);
	}
};
