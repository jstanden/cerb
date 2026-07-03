/*
 * CerbUI chart-core — the pure scale + tick helpers behind the cartesian charts (the minimal subset that
 * replaces the d3 scales/ticks we used). No DOM; safe to unit-test in isolation. Grow as new chart types need
 * more (a time scale + date ticks arrive with the timeseries step).
 *
 *   const y = CerbUI.scale.linear({ domain:[0,100], range:[200,0] }); // note inverted range = pixels down
 *   y(50)            // -> 100
 *   y.ticks(5)       // -> [0,20,40,60,80,100] (nice)
 *   const x = CerbUI.scale.band({ domain:['a','b','c'], range:[0,300], padding:0.2 });
 *   x('b')           // band start px ; x.bandwidth() ; x.step()
 */
CerbUI.scale = {
	// Continuous linear scale (like d3.scaleLinear). domain [d0,d1] -> range [r0,r1] (r may invert for pixels).
	linear: function(opts) {
		const d = opts.domain || [0, 1];
		const r = opts.range || [0, 1];
		let d0 = d[0], d1 = d[1];
		if(d0 === d1) d1 = d0 + 1; // avoid divide-by-zero on a flat domain
		const r0 = r[0], r1 = r[1];
		const fn = function(v) { return r0 + (v - d0) / (d1 - d0) * (r1 - r0); };
		fn.invert = function(px) { return d0 + (px - r0) / (r1 - r0) * (d1 - d0); };
		fn.domain = function() { return [d0, d1]; };
		fn.range = function() { return [r0, r1]; };
		fn.ticks = function(count) { return CerbUI.ticks.linear(d0, d1, count || 5); };
		return fn;
	},

	// Continuous time scale — linear over numeric timestamps (ms). A semantic alias so `x.scale:'time'` reads
	// cleanly; tick generation for time is done by the chart (thinning the pre-binned data timestamps).
	time: function(opts) { return CerbUI.scale.linear(opts); },

	// Ordinal band scale (like d3.scaleBand). domain categories -> evenly spaced bands within range, with
	// padding as a fraction of the step on the OUTSIDE and BETWEEN bands (matches d3's default paddingInner/Outer).
	band: function(opts) {
		const domain = opts.domain || [];
		const r = opts.range || [0, 1];
		const padding = (opts.padding != null) ? opts.padding : 0.1;
		const n = domain.length || 1;
		const r0 = r[0], r1 = r[1];
		const span = r1 - r0;
		// step = span / (n - padding + 2*padding*... ) — use d3's simplified equal inner/outer padding formula
		const step = span / (n + padding * 2 - padding); // = span / (n + padding)
		const bandwidth = step * (1 - padding);
		const start = r0 + step * padding;
		const index = {};
		domain.forEach((c, i) => { index[c] = i; });
		const fn = function(cat) {
			const i = index[cat];
			return (i == null) ? undefined : start + i * step;
		};
		fn.bandwidth = function() { return bandwidth; };
		fn.step = function() { return step; };
		fn.domain = function() { return domain.slice(); };
		fn.range = function() { return [r0, r1]; };
		// Center of a band's index (handy for line marks over a category axis)
		fn.center = function(cat) { const p = fn(cat); return (p == null) ? undefined : p + bandwidth / 2; };
		return fn;
	}
};

CerbUI.ticks = {
	// "Nice" rounded tick values across [min,max] (like d3.ticks): pick a 1/2/5 * 10^k step near the ideal.
	linear: function(min, max, count) {
		count = count || 5;
		if(min === max) return [min];
		if(min > max) { const t = min; min = max; max = t; }
		const rawStep = (max - min) / count;
		// d3's tickIncrement: round the raw step to a 1/2/5 * 10^k "nice" value using sqrt error boundaries.
		const mag = Math.pow(10, Math.floor(Math.log10(rawStep)));
		const error = rawStep / mag;
		let step;
		if(error >= Math.sqrt(50)) step = 10 * mag;
		else if(error >= Math.sqrt(10)) step = 5 * mag;
		else if(error >= Math.sqrt(2)) step = 2 * mag;
		else step = mag;
		const out = [];
		const first = Math.ceil(min / step) * step;
		// Guard against floating-point drift accumulating; round each tick to the step's precision.
		const decimals = Math.max(0, -Math.floor(Math.log10(step)));
		for(let v = first; v <= max + step * 1e-9; v += step) {
			out.push(Number(v.toFixed(decimals)));
		}
		return out;
	}
};
