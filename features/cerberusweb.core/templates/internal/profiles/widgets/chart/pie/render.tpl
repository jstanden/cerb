<div id="widget{$widget->id}"></div>

<script type="text/javascript">
$(function() {
	Devblocks.loadResources({
		'css': [
			'/resource/devblocks.core/js/c3/c3.min.css'
		],
		'js': [
			'/resource/devblocks.core/js/d3/d3.v5.min.js',
			'/resource/devblocks.core/js/c3/c3.min.js'
		]
	}, function() {
		try {
			var $widget = $('#widget{$widget->id}');
			
			var config_json = {$config_json nofilter};
			
			config_json.tooltip.format.value = function (value, ratio, id, index) {
				return d3.format(',')(value) + ' (' + d3.format('.1%')(ratio) + ')';
			}
			
			var chart = c3.generate(config_json);
			
		} catch(e) {
			console.error(e);
		}
	});
});
</script>