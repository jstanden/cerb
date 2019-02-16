{$is_date_formatted = array_intersect([$yaxis_format],['number.minutes','number.seconds'])}

<div id="widget{$widget->id}"></div>

<script type="text/javascript">
$(function() {
	Devblocks.loadResources({
		'css': [
			'/resource/devblocks.core/js/c3/c3.min.css'
		],
		'js': [
			{if $is_date_formatted}
			'/resource/devblocks.core/js/humanize-duration.js',
			{/if}
			'/resource/devblocks.core/js/d3/d3.v5.min.js',
			'/resource/devblocks.core/js/c3/c3.min.js'
		]
	}, function() {
		try {
			var $widget = $('#widget{$widget->id}');
			
			var chart = null;
			var config_json = {$config_json nofilter};
			
			{if $chart_meta_json}
				var chart_meta = {$chart_meta_json nofilter};
				
				if(chart_meta.series) {
					config_json.data.onclick = function(d, i) {
						try {
							if(!config_json.data.json && !config_json.data.json)
								return;
							
							if(!chart_meta.series[d.id])
								return;
							
							var ts = config_json.data.json.ts[d.index];
							var series_meta = chart_meta.series[d.id][ts];
							
							var $trigger = $('<div/>')
								.attr('data-context', chart_meta.context)
								.attr('data-query', series_meta.query)
								.cerbSearchTrigger()
								.on('cerb-search-opened', function(e) {
									$(this).remove();
								})
								.click()
								;
							
						} catch(e) {
							if(console && console.error)
								console.error(e);
						}
					};
				}
				
			{else}
				var chart_meta = {};
			{/if}
			
			{if $is_date_formatted}
				var shortEnglishHumanizer = humanizeDuration.humanizer({
					language: 'shortEn',
					spacer: '',
					languages: {
						shortEn: {
							y: () => 'y',
							mo: () => 'mo',
							w: () => 'w',
							d: () => 'd',
							h: () => 'h',
							m: () => 'm',
							s: () => 's',
							ms: () => 'ms',
						}
					}
				});
			
				var format_seconds = function(secs) {
					return shortEnglishHumanizer(secs * 1000, { largest:2 });
				};
				
				var format_minutes = function(minutes) {
					return shortEnglishHumanizer(minutes * 60 * 1000, { largest:2 });
				};
			{/if}
			
			{if $yaxis_format == 'number.seconds'}
			config_json.axis.y.tick.format = format_seconds;
			{else if $yaxis_format == 'number.minutes'}
			config_json.axis.y.tick.format = format_minutes;
			{else}
			config_json.axis.y.tick.format = d3.format(',');
			{/if}
			
			chart = c3.generate(config_json);
		
		} catch(e) {
			console.error(e);
		}
	});
});
</script>