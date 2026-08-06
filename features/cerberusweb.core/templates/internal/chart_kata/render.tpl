{if !$chart_id}{$chart_id = uniqid('chart_')}{/if}
<div id="{$chart_id}"></div>
<div id="{$chart_id}_Legend" class="cerb-chart-kata--legend"></div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	var CERB_CHART_ID = '{$chart_id}';
	var CERB_CHART_CONFIG = {$chart_json|default:'null' nofilter};
{literal}
	try {
		const config = CERB_CHART_CONFIG || {};
		const chartEl = document.getElementById(CERB_CHART_ID);
		const legendEl = document.getElementById(CERB_CHART_ID + '_Legend');
		if(!chartEl) return;

		// A format descriptor {as:'date'|'number'|'duration', params} -> a formatter function.
		const fmtFor = function(fo) {
			if(!fo) return CerbUI.num.grouped;
			const p = fo.params || {};
			if(fo.as === 'date') return CerbUI.date.strftime(p.pattern || '%Y-%m-%d');
			if(fo.as === 'number') return CerbUI.num.format(p.pattern || ',');
			if(fo.as === 'duration') return function(v) { return CerbUI.num.duration(v, p.unit); };
			return CerbUI.num.grouped;
		};

		// x-axis formatter: time/linear axes get a formatter, but a CATEGORY axis keeps its raw string labels
		// (running a group name like "Demo" through a number formatter yields NaN).
		const xFmtFor = function(x) {
			x = x || {};
			if(x.format) return fmtFor(x.format);
			if(x.scale === 'time') return CerbUI.date.strftime('%Y-%m-%d %H:%M');
			if(x.scale === 'linear') return CerbUI.num.grouped;
			return null;
		};

		// Drill-through: a "<context> <query>" string -> {context, query}, aligned per data point.
		const parseClick = function(q) {
			if(typeof q !== 'string') return null;
			const sp = q.indexOf(' ');
			return sp < 0 ? null : { context: q.slice(0, sp), query: q.slice(sp + 1) };
		};
		const buildClick = function(arr, n) {
			if(!arr || !arr.length) return null;
			if(arr.length === 1) { const c = parseClick(arr[0]); const out = []; for(let i = 0; i < n; i++) out.push(c); return out; }
			return arr.map(parseClick);
		};
		const drill = function(click) {
			if(!click || !click.query) return;
			$('<div/>').attr('data-context', click.context || '').attr('data-query', click.query)
				.cerbSearchTrigger().on('cerb-search-opened', function() { $(this).remove(); }).click();
		};

		let chart = null;

		if(config.kind === 'pie') {
			chart = new CerbUI.PieChart(chartEl, {
				type: config.type,
				slices: (config.slices || []).map(function(sl) { return { label: sl.label, value: sl.value, color: sl.color, click: parseClick(sl.click) }; }),
				legend: (config.legend || {}).show !== false,
				tooltip: { ratios: (config.tooltip || {}).ratios !== false },
				palette: config.palette || undefined,
				height: config.height,
			});

		} else if(config.kind === 'gauge') {
			const gfmt = fmtFor(config.format);
			chart = new CerbUI.Gauge(chartEl, {
				value: config.value, min: config.min, max: config.max,
				valueText: gfmt(config.value),
				palette: config.palette || undefined,
			});

		} else if(config.kind === 'scatter') {
			const sx = config.x || {}, sy = config.y || {};
			chart = new CerbUI.ScatterChart(chartEl, {
				x: { tickFormat: fmtFor(sx.format), rotate: sx.rotate, label: sx.label },
				y: { tickFormat: fmtFor(sy.format), grid: true, label: sy.label },
				series: (config.series || []).map(function(s) {
					return { key: s.key, name: s.name, color: s.color, x: s.x, values: s.values, click: buildClick(s.click, (s.values || []).length) };
				}),
				legend: (config.legend || {}).show !== false && (config.series || []).length > 1,
				palette: config.palette || undefined,
				height: config.height,
			});

		} else {
			// cartesian
			const x = config.x || {}, y = config.y || {}, tt = config.tooltip || {};
			const xopt = { scale: x.scale, tickFormat: xFmtFor(x), rotate: x.rotate, label: x.label, multiline: x.multiline };
			if(x.scale === 'time') xopt.timestamps = (x.values || []).map(function(s) { return Date.parse(String(s).replace(' ', 'T')); });
			else if(x.scale === 'category') xopt.categories = x.categories || [];
			else xopt.values = (x.values || []).map(Number);

			const legendStyle = (config.legend || {}).style;

			const opt = {
				orientation: config.orientation,
				x: xopt,
				y: { tickFormat: fmtFor(y.format), grid: y.grid !== false, label: y.label },
				series: (config.series || []).map(function(s) {
					return { key: s.key, name: s.name, type: s.type, axis: s.axis, stack: s.stack, color: s.color, values: s.values, click: buildClick(s.click, (s.values || []).length) };
				}),
				legend: legendStyle === 'compact',   // compact = the component's built-in legend; table = custom below
				tooltip: { grouped: tt.grouped !== false, ratios: !!tt.ratios, sum: tt.grouped !== false },
				palette: config.palette || undefined,
				height: config.height,
			};
			if(config.y2) opt.y2 = { tickFormat: fmtFor(config.y2.format), label: config.y2.label };

			chart = new CerbUI.CartesianChart(chartEl, opt);

			if(legendEl && legendStyle === 'table')
				buildTableLegend(config, legendEl, chart, xopt, fmtFor, xFmtFor, parseClick, drill);
		}

	} catch(e) {
		if(console && console.error) console.error(e);
		const el = document.getElementById(CERB_CHART_ID);
		if(el) el.textContent = e.message;
	}

	// A rich table legend: per-series row + total, optional per-x data grid, sum/avg/min/max/count stat rows,
	// and a y2 split. Reads the CerbUI config (not a c3 instance); hover-focuses + drills via the chart.
	function computeStat(stat, vals, fmt) {
		if(!vals.length) return '';
		if(stat === 'count') return '' + vals.length;
		let r;
		if(stat === 'sum' || stat === 'avg') { r = vals.reduce(function(a, b) { return a + b; }, 0); if(stat === 'avg') r = r / vals.length; }
		else if(stat === 'min') r = Math.min.apply(null, vals);
		else if(stat === 'max') r = Math.max.apply(null, vals);
		else return '';
		return fmt(r);
	}

	function buildTableLegend(config, container, chart, xopt, fmtFor, xFmtFor, parseClick, drill) {
		const $c = $(container).empty();
		const allSeries = config.series || [];
		if(!allSeries.length) return;

		const showData = !!(config.legend || {}).data;
		const stats = (config.legend || {}).stats || [];
		const yFmt = fmtFor((config.y || {}).format);
		const y2Fmt = config.y2 ? fmtFor(config.y2.format) : yFmt;
		const xFmt = xFmtFor(config.x || {});

		let xLabels = [];
		if(xopt.scale === 'category') xLabels = (xopt.categories || []).slice();
		else if(xopt.scale === 'time') xLabels = (xopt.timestamps || []).map(function(t) { return xFmt(t); });
		else xLabels = (xopt.values || []).map(function(v) { return xFmt(v); });

		const colorOf = function(s) {
			const ci = chart.series.findIndex(function(x) { return x.key === s.key; });
			return (ci >= 0) ? chart._seriesColor(ci, chart.series[ci]) : (s.color || '#888');
		};

		const $table = $('<table/>').addClass('cerb-chart-kata--legend-table').appendTo($c);

		const buildTbody = function(axisSeries, axisLabel, fmt) {
			if(!axisSeries.length) return;
			const $thead = $('<thead/>').appendTo($table);
			const $htr = $('<tr/>').appendTo($thead);
			$('<th/>').text(axisLabel || '').appendTo($htr);
			$('<th/>').appendTo($htr);
			if(showData) xLabels.forEach(function(xl) { $('<th/>').text(xl).attr('title', xl).appendTo($htr); });

			const $tbody = $('<tbody/>').appendTo($table);
			axisSeries.forEach(function(s) {
				const $tr = $('<tr/>').appendTo($tbody);
				const $name = $('<td/>').addClass('cerb-chart-kata--legend-name').appendTo($tr);
				$('<span/>').addClass('cerb-chart-kata--legend-swatch').css('background-color', colorOf(s)).appendTo($name);
				$('<span/>').text(s.name).appendTo($name);
				$name.on('mouseover', function() { if(chart.focusSeries) chart.focusSeries(s.key); })
					.on('mouseout', function() { if(chart.revert) chart.revert(); });

				const total = (s.values || []).reduce(function(a, b) { return a + (Number(b) || 0); }, 0);
				$('<td/>').addClass('cerb-chart-kata--legend-total').attr('data-value', total).text(fmt(total)).appendTo($tr);

				if(showData) {
					const clicks = s.click;
					(s.values || []).forEach(function(v, i) {
						const $td = $('<td/>').attr('data-value', v).text(fmt(Number(v) || 0)).appendTo($tr);
						const c = clicks && (clicks.length === 1 ? clicks[0] : clicks[i]);
						const pc = c ? parseClick(c) : null;
						if(pc && pc.query) $td.addClass('cerb-chart-kata--legend-clickable').on('click', function() { drill(pc); });
					});
				}
			});

			stats.forEach(function(stat) {
				const $tr = $('<tr/>').addClass('cerb-chart-kata--legend-stat').appendTo($tbody);
				$('<td/>').text(stat).appendTo($tr);
				const columns = [axisSeries.map(function(s) { return (s.values || []).reduce(function(a, b) { return a + (Number(b) || 0); }, 0); })];
				if(showData) xLabels.forEach(function(_, i) { columns.push(axisSeries.map(function(s) { return Number((s.values || [])[i]) || 0; })); });
				columns.forEach(function(vals) { $('<td/>').text(computeStat(stat, vals, fmt)).appendTo($tr); });
			});
		};

		buildTbody(allSeries.filter(function(s) { return s.axis !== 'y2'; }), (config.y || {}).label || '', yFmt);
		buildTbody(allSeries.filter(function(s) { return s.axis === 'y2'; }), (config.y2 || {}).label || 'y2', y2Fmt);
	}
{/literal}
});
</script>
