<div id="{$el_id}"></div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	try {
		const $widget = $('#{$el_id}');
		const ts = {$ts nofilter};
		const series = {$series nofilter};
		const groups = {if $groups}{$groups nofilter}{else}null{/if};
		const chart_meta = {if $chart_meta_json}{$chart_meta_json nofilter}{else}null{/if};
		const chart_as = '{$chart_as}';
		const xLabel = '{$xaxis_label|escape:'javascript'}';
		const yLabel = '{$yaxis_label|escape:'javascript'}';

		const fmtFor = function(fmt) {
			if(fmt === 'number.seconds') return function(v) { return CerbUI.num.duration(v, 'seconds'); };
			if(fmt === 'number.minutes') return function(v) { return CerbUI.num.duration(v, 'minutes'); };
			return CerbUI.num.grouped;
		};
		const yFmt = fmtFor('{$yaxis_format}');

		// Parse the ts strings to ms for a continuous time axis.
		const timestamps = ts.map(function(s) { return Date.parse(String(s).replace(' ', 'T')); });

		// Map chart_as -> per-series mark type + stacking.
		let type = 'line', stackAll = false;
		if(chart_as === 'spline') type = 'spline';
		else if(chart_as === 'area') { type = 'area'; stackAll = true; }
		else if(chart_as === 'bar') type = 'bar';
		else if(chart_as === 'bar_stacked') { type = 'bar'; stackAll = true; }

		const stackOf = function(label) {
			if(!stackAll) return null;
			if(groups && groups.length) {
				for(let g = 0; g < groups.length; g++) {
					if(groups[g].indexOf(label) !== -1) return 'g' + g;
				}
			}
			return 'g';
		};

		series.forEach(function(s) {
			s.type = type;
			s.stack = stackOf(s.key);
			s.click = ts.map(function(tsStr) {
				const m = (chart_meta && chart_meta.series && chart_meta.series[s.key]) ? chart_meta.series[s.key][tsStr] : null;
				return (m && m.query) ? { context: chart_meta.context, query: m.query } : null;
			});
		});

		new CerbUI.CartesianChart($widget[0], {
			orientation: 'vertical',
			x: { scale: 'time', timestamps: timestamps, tickFormat: CerbUI.date.strftime('{$xaxis_format}'), rotate: -90, label: xLabel || undefined },
			y: { tickFormat: yFmt, grid: true, label: yLabel || undefined },
			series: series,
			legend: {if $show_legend}true{else}false{/if},
			points: {if $show_points}true{else}false{/if},
			height: {$height|intval}
		});

	} catch(e) {
		console.error(e);
	}
});
</script>
