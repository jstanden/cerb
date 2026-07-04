<div class="cerb-metrics-explorer cerb-u-flex cerb-u-flex-column cerb-u-gap-2" id="metricsExplorer{$uniqid}"></div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	Devblocks.loadResources({
		'js': [
			'/resource/cerberusweb.core/js/cards/metrics_explorer.js'
		]
	}, function() {
		try {
			var el = document.getElementById('metricsExplorer{$uniqid}');
			var bootstrap = {$bootstrap_json nofilter};
			new CerbMetricsExplorer(el, bootstrap);
		} catch(e) {
			if(console && console.error)
				console.error(e);
		}
	});
});
</script>
