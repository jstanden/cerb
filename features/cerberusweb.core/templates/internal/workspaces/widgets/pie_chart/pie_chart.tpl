<div id="widget{$widget->id}"></div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	try {
		const $widget = $('#widget{$widget->id}');
		const json = {$data_json nofilter};
		const slices = (json || []).map(function(c) { return { label: c[0], value: c[1] }; });

		new CerbUI.PieChart($widget[0], { type: 'donut', slices: slices, legend: true, height: 250 });

	} catch(e) {
		console.error(e);
	}
});
</script>
