<div id="widget{$widget->id}"></div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	try {
		const $widget = $('#widget{$widget->id}');
		const categories = {$categories nofilter};
		const series = {$series nofilter};
		const chart_meta = {if $chart_meta_json}{$chart_meta_json nofilter}{else}null{/if};
		const stacked = {if $stacked}true{else}false{/if};

		const fmtFor = function(fmt) {
			if(fmt === 'number.seconds') return function(v) { return CerbUI.num.duration(v, 'seconds'); };
			if(fmt === 'number.minutes') return function(v) { return CerbUI.num.duration(v, 'minutes'); };
			if(fmt === 'number') return CerbUI.num.format(',');
			return null;
		};

		const yFmt = fmtFor('{$yaxis_format}') || CerbUI.num.format(',');
		const xFmt = fmtFor('{$xaxis_format}');

		series.forEach(function(s) {
			s.type = 'bar';
			if(s.key === 'hits') s.color = '#1f77b4';
			if(stacked) s.stack = 'g';
			s.click = categories.map(function(cat) {
				let m = null;
				if(chart_meta && chart_meta.series) {
					if(s.key === 'hits' && chart_meta.series['hits']) m = chart_meta.series['hits'][cat];
					else if(chart_meta.series[cat]) m = chart_meta.series[cat][s.key];
				}
				return (m && m.query) ? { context: chart_meta.context, query: m.query } : null;
			});
		});

		new CerbUI.CartesianChart($widget[0], {
			orientation: 'horizontal',
			x: { categories: categories, tickFormat: xFmt, width: 150 },
			y: { tickFormat: yFmt, grid: true },
			series: series,
			legend: stacked,
			height: {$height|intval}
		});

	} catch(e) {
		console.error(e);
	}
});
</script>
