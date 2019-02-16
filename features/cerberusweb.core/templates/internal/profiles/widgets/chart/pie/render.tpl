<div id="widget{$widget->id}"></div>
<div id="widget{$widget->id}Legend" style="text-align:center;line-height:1.5em;"></div>

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
			var chart = null;
			
			config_json.tooltip.format.value = function (value, ratio, id, index) {
				return d3.format(',')(value) + ' (' + d3.format('.1%')(ratio) + ')';
			}
			
			{if $chart_meta_json}
			try {
				var chart_meta = {$chart_meta_json nofilter};
				
				function func_search_by_series(series_id) {
					if(null == chart_meta.series[series_id])
						return;
					
					var series_meta = chart_meta.series[series_id];
					
					if(series_meta.query) {
						var $trigger = $('<div/>')
							.attr('data-context', chart_meta.context)
							.attr('data-query', series_meta.query)
							.cerbSearchTrigger()
							.on('cerb-search-opened', function(e) {
								$(this).remove();
							})
							.click()
							;
					}
				}
				config_json.data.onclick = function(d) {
					func_search_by_series(d.id);
				};
				
			} catch(e) {
				if(console && console.error)
					console.error(e);
			}
			{/if}
			
			if(config_json && config_json.legend && config_json.legend.show) {
				config_json.legend.show = false;
				
				chart = c3.generate(config_json);
				
				d3.select('#widget{$widget->id}Legend').selectAll('div')
					.data(chart.data())
					.enter()
						.append('div')
						.attr('data-id', function (result) { return result.id; })
						.each(function(result, i) {
							var $this = d3.select(this)
								.style('display', 'inline-block')
								.style('cursor', 'pointer')
								.on('mouseover', function (result) {
									chart.focus(result.id);
								})
								.on('mouseout', function (result) {
									chart.revert();
								})
								.on('click', function (result) {
									if(typeof func_search_by_series == "function")
										func_search_by_series(result.id);
								})
								;
							
							var $badge = $this.append('div')
								.style('display', 'inline-block')
								.style('vertical-align', 'middle')
								.style('width', '1em')
								.style('height', '1em')
								.style('background-color', chart.color(result.id))
								;
							
							var $text = $this.append('span')
								.style('vertical-align', 'middle')
								.style('margin', '0px 0px 0px 5px')
								.style('font-weight', 'bold')
								.text(result.id)
								;
							
							var $text = $this.append('span')
								.style('vertical-align', 'middle')
								.style('margin', '0px 1em 0px 2px')
								.text('(' + d3.format(',')(result.values[0].value) + ')')
								;
						})
						;
			} else {
				chart = c3.generate(config_json);
			}
			
		} catch(e) {
			console.error(e);
		}
	});
});
</script>