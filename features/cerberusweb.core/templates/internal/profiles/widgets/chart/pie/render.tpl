<div id="widget{$widget->id}"></div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	try {
		const $widget = $('#widget{$widget->id}');
		const data = {$data nofilter};
		const chart_meta = {if $chart_meta_json}{$chart_meta_json nofilter}{else}null{/if};

		const slices = (data || []).map(function(d) {
			const label = d[0];
			const meta = (chart_meta && chart_meta.series && chart_meta.series[label]) ? chart_meta.series[label] : null;
			return {
				label: label,
				value: d[1],
				click: meta ? { context: meta.context ? meta.context : chart_meta.context, query: meta.query } : null
			};
		});

		new CerbUI.PieChart($widget[0], {
			type: '{$chart_as}',
			slices: slices,
			legend: {if $show_legend}true{else}false{/if},
			tooltip: { ratios: true },
			height: {$height|intval}
		});

	} catch(e) {
		console.error(e);
	}
});
</script>
