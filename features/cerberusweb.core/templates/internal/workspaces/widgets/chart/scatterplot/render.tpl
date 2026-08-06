<div id="{$el_id}"></div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	try {
		const $widget = $('#{$el_id}');
		const series = {$series nofilter};

		const fmtFor = function(fmt) {
			if(fmt === 'number.seconds') return function(v) { return CerbUI.num.duration(v, 'seconds'); };
			if(fmt === 'number.minutes') return function(v) { return CerbUI.num.duration(v, 'minutes'); };
			return CerbUI.num.format(',');
		};

		new CerbUI.ScatterChart($widget[0], {
			x: { tickFormat: fmtFor('{$xaxis_format}'), rotate: -90, label: '{$xaxis_label|escape:'javascript'}' || undefined },
			y: { tickFormat: fmtFor('{$yaxis_format}'), grid: true, label: '{$yaxis_label|escape:'javascript'}' || undefined },
			series: series,
			legend: series.length > 1,
			height: {$height|intval}
		});

	} catch(e) {
		console.error(e);
	}
});
</script>
