/*
 * CerbUI.Timeblocks — a GitHub-style activity calendar heatmap. Rows are calendar days, columns are the 24
 * hours of the day; each cell is tinted from the page background toward green by its `value`. It replaces
 * the old inline d3-v7 `CerbCalendarHoursByDay` renderer (no charting library — hand-built SVG).
 *
 * Data is the same "timeblocks" shape the widget backend already emits: a flat list of {date, value}, one
 * per active (day, hour) bucket. `date` is anything `new Date()` accepts (unix-ms or an ISO string). Days
 * are placed by their offset from the earliest day, so gaps render as blank rows; missing (day, hour)
 * buckets are simply not drawn. Values scale into a 12-step quantized color ramp; an all-zero dataset renders
 * unfilled (like the original) rather than solid green.
 *
 * Usage:
 *   new CerbUI.Timeblocks(el, {
 *     data:     [ { date: 1719936000000, value: 27 }, … ],  // one entry per active day+hour bucket
 *     cellSize: 22,        // px per cell (and per hour/day step)
 *     colorTo:  'rgb(19,134,3)',  // ramp endpoint; ramp starts at --cerb-color-background
 *     tooltip:  true,      // built-in hover tooltip (one shared instance); false = events-only
 *   });
 *
 * It PUBLISHES bubbling events on the element (drive your own UI, or with tooltip:false fully own hover):
 *   'cerb-ui-timeblocks:hover'  detail = { date, value, hour, day, point:{x,y} }
 *   'cerb-ui-timeblocks:leave'
 *   'cerb-ui-timeblocks:click'  detail = same shape as hover
 */
CerbUI.Timeblocks = class {
	static _instances = new WeakMap();
	static from(el) { return CerbUI.Timeblocks._instances.get(el); }

	// Layout constants (px), matched to the original d3 renderer's geometry
	static DAY_LABEL_RIGHT = 115; // right edge of the left (day) label gutter — labels are anchored here
	static CELLS_ORIGIN_X = 131;  // first hour column's left edge
	static CELLS_ORIGIN_Y = 45;   // first day row's top (the band above holds the rotated hour labels)

	constructor(el, options = {}) {
		this.el = (typeof el === 'string') ? document.querySelector(el) : el;
		if(!this.el) return;
		CerbUI.Timeblocks._instances.set(this.el, this);

		this.data = options.data || [];
		this.cellSize = options.cellSize || 22;
		this.colorTo = options.colorTo || 'rgb(19,134,3)';
		this.tooltipEnabled = (options.tooltip !== false);
		this.NS = 'http://www.w3.org/2000/svg';

		// One formatter reused for day labels + tooltip titles ("Wed, Jul 02"); year/hour appended manually.
		this._fmtDay = new Intl.DateTimeFormat('en-US', { weekday: 'short', month: 'short', day: '2-digit' });

		this.el.classList.add('cerb-ui-timeblocks');

		this._svg = document.createElementNS(this.NS, 'svg');
		this._svg.setAttribute('class', 'cerb-ui-timeblocks--plot');
		this.el.appendChild(this._svg);

		this._bindHover();
		this.render();
	}

	// ── color helpers (replacing d3.interpolateRgb / quantize / scaleQuantize / rgb().darker) ──

	// Parse "#rgb", "#rrggbb", or "rgb(r,g,b)" into [r,g,b]; default white on anything unrecognized.
	_parseColor(str) {
		str = (str || '').trim();
		if(str.charAt(0) === '#') {
			let hex = str.slice(1);
			if(hex.length === 3) hex = hex.replace(/./g, c => c + c);
			const n = parseInt(hex, 16);
			if(hex.length === 6 && !isNaN(n)) return [(n >> 16) & 255, (n >> 8) & 255, n & 255];
		}
		const m = str.match(/rgba?\(\s*(\d+)[,\s]+(\d+)[,\s]+(\d+)/i);
		if(m) return [+m[1], +m[2], +m[3]];
		return [255, 255, 255];
	}

	_lerp(a, b, t) {
		return [
			Math.round(a[0] + (b[0] - a[0]) * t),
			Math.round(a[1] + (b[1] - a[1]) * t),
			Math.round(a[2] + (b[2] - a[2]) * t),
		];
	}

	// d3's rgb.darker(k) multiplies each channel by 0.7^k
	_darker(rgb, k) {
		const f = Math.pow(0.7, k);
		return [Math.round(rgb[0] * f), Math.round(rgb[1] * f), Math.round(rgb[2] * f)];
	}

	_rgb(c) { return 'rgb(' + c[0] + ',' + c[1] + ',' + c[2] + ')'; }

	// ── date-label helpers (replacing d3.timeFormat) ──

	_dayLabel(d, withYear) {
		const base = this._fmtDay.format(d); // "Wed, Jul 02"
		return withYear ? (d.getFullYear() + ' ' + base) : base;
	}

	_title(d, value) {
		const hh = ('0' + d.getHours()).slice(-2);
		return this._fmtDay.format(d) + ' ' + d.getFullYear() + ' ' + hh + ':00' + '\n' + value;
	}

	render() {
		const svg = this._svg;
		svg.replaceChildren();

		// Parse rows once: keep the Date, value, hour (0-23) and day-offset from the earliest day.
		const points = this.data.map(d => {
			const date = new Date(d.date);
			return { date: date, value: +d.value || 0, hour: date.getHours() };
		});
		this._points = points;

		if(!points.length) {
			svg.setAttribute('viewBox', '0 0 1 1');
			return;
		}

		// Earliest day at midnight — day rows are offsets from here (gaps become blank rows)
		let minTime = Infinity;
		points.forEach(p => { if(p.date.getTime() < minTime) minTime = p.date.getTime(); });
		const firstDay = new Date(minTime);
		firstDay.setHours(0, 0, 0, 0);
		const firstDayMs = firstDay.getTime();

		let maxDayIndex = 0;
		points.forEach(p => {
			const dm = new Date(p.date); dm.setHours(0, 0, 0, 0);
			p.day = Math.floor((dm.getTime() - firstDayMs) / 86400000);
			if(p.day > maxDayIndex) maxDayIndex = p.day;
		});
		const rows = maxDayIndex + 1;

		// Value extent → 12-step ramp from the page background toward green. All-zero data uses [0,60] so an
		// empty grid stays unfilled (matches the original's "no-activity" behavior) rather than reading solid.
		let vMin = Infinity, vMax = -Infinity;
		points.forEach(p => { if(p.value < vMin) vMin = p.value; if(p.value > vMax) vMax = p.value; });
		if(vMin === 0 && vMax === 0) { vMin = 0; vMax = 60; }

		const CLASSES = 12;
		let from = [255, 255, 255];
		if(typeof getComputedStyle === 'function')
			from = this._parseColor(getComputedStyle(document.documentElement).getPropertyValue('--cerb-color-background'));
		const to = this._parseColor(this.colorTo);
		const ramp = [];
		for(let i = 0; i < CLASSES; i++) ramp.push(this._lerp(from, to, CLASSES === 1 ? 0 : i / (CLASSES - 1)));
		const fillFor = (v) => {
			if(vMax === vMin) return ramp[0];
			let idx = Math.floor((v - vMin) / (vMax - vMin) * CLASSES);
			if(idx < 0) idx = 0; else if(idx >= CLASSES) idx = CLASSES - 1;
			return ramp[idx];
		};

		const cs = this.cellSize;
		const originX = CerbUI.Timeblocks.CELLS_ORIGIN_X;
		const originY = CerbUI.Timeblocks.CELLS_ORIGIN_Y;
		const width = Math.max(675, originX + 24 * cs + 12);
		const height = 60 + rows * cs;

		svg.setAttribute('width', width);
		svg.setAttribute('height', height);
		svg.setAttribute('viewBox', '0 0 ' + width + ' ' + height);
		svg.setAttribute('preserveAspectRatio', 'xMinYMin meet');
		// fill in the inline style (not a presentation attribute) so the CSS var() resolves — this tints the
		// axis text; cells set their own fill attribute and are unaffected.
		svg.setAttribute('style', 'max-width:100%; height:auto; fill:var(--cerb-color-text);');
		svg.setAttribute('font-family', 'sans-serif');

		// Left axis: one label per day row (year-prefixed on the first row or any Jan 1)
		for(let r = 0; r < rows; r++) {
			const rowDate = new Date(firstDayMs + r * 86400000);
			const withYear = (r === 0 || (rowDate.getMonth() === 0 && rowDate.getDate() === 1));
			const t = document.createElementNS(this.NS, 'text');
			t.setAttribute('class', 'cerb-ui-timeblocks--daylabel');
			t.setAttribute('text-anchor', 'end');
			t.setAttribute('font-size', '14');
			t.setAttribute('x', CerbUI.Timeblocks.DAY_LABEL_RIGHT);
			t.setAttribute('y', 60 + r * cs);
			t.textContent = this._dayLabel(rowDate, withYear);
			svg.appendChild(t);
		}

		// Top axis: rotated hour labels every 2 hours (00:00, 02:00, …). Centered (text-anchor middle) in the
		// band ABOVE the cells — the label reads bottom-to-top and stays clear of the grid regardless of its
		// rendered width, as long as it's shorter than the band (originY).
		for(let h = 0; h < 24; h++) {
			if(h % 2 !== 0) continue;
			const t = document.createElementNS(this.NS, 'text');
			t.setAttribute('class', 'cerb-ui-timeblocks--hourlabel');
			t.setAttribute('text-anchor', 'middle');
			t.setAttribute('font-size', '14');
			const x = originX + h * cs + cs / 2;
			t.setAttribute('transform', 'translate(' + x + ',' + (originY / 2) + ') rotate(-90)');
			t.textContent = ('0' + h).slice(-2) + ':00';
			svg.appendChild(t);
		}

		// Cells: one rect per active (day, hour) bucket
		points.forEach((p, i) => {
			const fill = fillFor(p.value);
			const rect = document.createElementNS(this.NS, 'rect');
			rect.setAttribute('class', 'cerb-ui-timeblocks--cell');
			rect.setAttribute('width', cs);
			rect.setAttribute('height', cs);
			rect.setAttribute('x', originX + p.hour * cs);
			rect.setAttribute('y', originY + p.day * cs);
			rect.setAttribute('fill', this._rgb(fill));
			rect.setAttribute('stroke', this._rgb(this._darker(fill, 0.5)));
			rect.setAttribute('stroke-width', 0.5);
			rect.dataset.cerbIndex = i;
			if(!this.tooltipEnabled || !CerbUI.Tooltip) {
				const title = document.createElementNS(this.NS, 'title');
				title.textContent = this._title(p.date, p.value);
				rect.appendChild(title);
			}
			svg.appendChild(rect);
		});
	}

	// One shared tooltip across all timeblocks charts (only one visible at a time) — lazily created
	_tooltip() {
		if(!this.tooltipEnabled || !CerbUI.Tooltip) return null;
		if(!CerbUI.Timeblocks._sharedTooltip) CerbUI.Timeblocks._sharedTooltip = new CerbUI.Tooltip();
		return CerbUI.Timeblocks._sharedTooltip;
	}

	_tooltipContent(p) {
		const box = document.createElement('div');
		const title = document.createElement('div');
		title.className = 'cerb-ui-timeblocks-tip--title';
		const hh = ('0' + p.date.getHours()).slice(-2);
		title.textContent = this._fmtDay.format(p.date) + ' ' + p.date.getFullYear() + ' ' + hh + ':00';
		box.appendChild(title);
		const val = document.createElement('div');
		val.className = 'cerb-ui-timeblocks-tip--value';
		val.textContent = p.value;
		box.appendChild(val);
		return box;
	}

	_cellFromEvent(e) {
		const rect = e.target.closest ? e.target.closest('.cerb-ui-timeblocks--cell') : null;
		if(!rect || rect.dataset.cerbIndex == null) return null;
		return this._points[+rect.dataset.cerbIndex] || null;
	}

	_detail(p) {
		return { date: p.date, value: p.value, hour: p.hour, day: p.day };
	}

	_bindHover() {
		this._hovered = null;

		this._onOver = (e) => {
			const p = this._cellFromEvent(e);
			if(!p || p === this._hovered) return;
			this._hovered = p;
			const detail = this._detail(p);
			detail.point = { x: e.clientX, y: e.clientY };
			this.el.dispatchEvent(new CustomEvent('cerb-ui-timeblocks:hover', { detail: detail, bubbles: true }));
			const tip = this._tooltip();
			if(tip) tip.show(this._tooltipContent(p), e.clientX, e.clientY, this.el);
		};

		this._onMove = (e) => {
			if(!this._hovered) return;
			const tip = this._tooltip();
			if(tip) tip.move(e.clientX, e.clientY);
		};

		this._onOut = (e) => {
			if(this._cellFromEvent(e)) return; // moved to another cell — handled by _onOver
			this._hovered = null;
			this.el.dispatchEvent(new CustomEvent('cerb-ui-timeblocks:leave', { bubbles: true }));
			const tip = this._tooltip();
			if(tip) tip.hide();
		};

		this._onClick = (e) => {
			const p = this._cellFromEvent(e);
			if(!p) return;
			e.stopPropagation();
			const detail = this._detail(p);
			detail.point = { x: e.clientX, y: e.clientY };
			this.el.dispatchEvent(new CustomEvent('cerb-ui-timeblocks:click', { detail: detail, bubbles: true }));
		};

		this.el.addEventListener('mouseover', this._onOver);
		this.el.addEventListener('mousemove', this._onMove);
		this.el.addEventListener('mouseout', this._onOut);
		this.el.addEventListener('click', this._onClick);
	}

	// Tear down listeners + the SVG so the el can be safely re-rendered or removed
	destroy() {
		if(this._onOver) this.el.removeEventListener('mouseover', this._onOver);
		if(this._onMove) this.el.removeEventListener('mousemove', this._onMove);
		if(this._onOut) this.el.removeEventListener('mouseout', this._onOut);
		if(this._onClick) this.el.removeEventListener('click', this._onClick);
		const tip = this._tooltip();
		if(tip) tip.hide();
		this.el.replaceChildren();
		this.el.classList.remove('cerb-ui-timeblocks');
		CerbUI.Timeblocks._instances.delete(this.el);
	}
};
