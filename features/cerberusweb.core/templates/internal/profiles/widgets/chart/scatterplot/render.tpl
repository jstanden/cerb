{$is_date_formatted = array_intersect([$xaxis_format,$yaxis_format],['number.minutes','number.seconds'])}
<div id="widget{$widget->id}"></div>

<script type="text/javascript">
$(function() {
	Devblocks.loadResources({
		'css': [
			'/resource/devblocks.core/js/c3/c3.min.css'
		],
		'js': [
			{if $is_date_formatted}
			'/resource/devblocks.core/js/momentjs/moment.js',
			'/resource/devblocks.core/js/momentjs/moment-duration-format.js',
			{/if}
			'/resource/devblocks.core/js/d3/d3.v5.min.js',
			'/resource/devblocks.core/js/c3/c3.min.js'
		]
	}, function() {
		try {
			var $widget = $('#widget{$widget->id}');
			
			var config_json = {$config_json nofilter};
			
			{if $is_date_formatted}
				var format_seconds = function(secs) {
					var duration = moment.duration(secs, 'seconds');
					var formatted = duration.format("y[y],w[w],d[d],h[h],m[m],s[s]", {
						largest: 2
					});
					return formatted;
				};
				
				var format_minutes = function(minutes) {
					var duration = moment.duration(minutes, 'minutes');
					var formatted = duration.format("y[y],w[w],d[d],h[h],m[m]", {
						largest: 2
					});
					return formatted;
				};
			{/if}
			
			{if $xaxis_format == 'number.seconds'}
			config_json.axis.x.tick.format = format_seconds;
			{else if $yaxis_format == 'number.minutes'}
			config_json.axis.x.tick.format = format_minutes;
			{else}
			config_json.axis.x.tick.format = d3.format(',');
			{/if}
			
			{if $yaxis_format == 'number.seconds'}
			config_json.axis.y.tick.format = format_seconds;
			{else if $yaxis_format == 'number.minutes'}
			config_json.axis.y.tick.format = format_minutes;
			{else}
			config_json.axis.y.tick.format = d3.format(',');
			{/if}
			
			var chart = c3.generate(config_json);

		} catch(e) {
			console.error(e);
		}
	});
});
</script>