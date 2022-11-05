{$div_id = uniqid('widget')}
<div id="{$div_id}"></div>

<script type="text/javascript">
$(function() {
	Devblocks.loadResources({
		'css': [
			'/resource/devblocks.core/js/c3/c3.min.css'
		],
		'js': [
			'/resource/devblocks.core/js/d3/d3.v5.min.js',
			'/resource/devblocks.core/js/c3/c3.min.js',
			'/resource/devblocks.core/js/humanize-duration.js'
		]
	}, function() {
		try {
			let chart_json = {$chart_json nofilter};
			chart_json.bindto = '#{$div_id}';
			
			let axes = ['x','y','y2'];

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

			for(let i = 0; i < axes.length; i++) {
				let axis = axes[i];
				
				if(chart_json.axis[axis].tick) {
					if(chart_json.axis[axis].tick.hasOwnProperty('format_options')) {
						let format_as = chart_json.axis[axis].tick['format_options']['as'];
						let format_params = chart_json.axis[axis].tick['format_options']['params'];
						
						if('date' === format_as) {
							chart_json.axis[axis].tick.format = d3.timeFormat(format_params['pattern']);
							
						} else if('duration' === format_as) {
							chart_json.axis[axis].tick.format = function(n) {
								let precision = format_params['precision'];
								let unit = format_params['unit'];
								
								if('seconds' === unit) {
									n = parseInt(n) * 1000;
								} else if('minutes' === unit) {
									n = parseInt(n) * 60000;
								} else if('hours' === unit) {
									n = parseInt(n) * 3600000;
								} else {
									n = parseInt(n);
								}
								
								return shortEnglishHumanizer(n, { largest: precision });
							};
							
						} else if('number' === format_as) {
							chart_json.axis[axis].tick.format = d3.format(format_params['pattern']);
						}
					}
				}
			}
			
			let format_pct = d3.format('.1%');
			let format_num = d3.format(',');
			
			if(chart_json.tooltip.show && -1 !== $.inArray(chart_json.data.type, ['donut','gauge','pie'])) {
				chart_json.tooltip.format = {
					value: function (value, ratio) {
						if(ratio) {
							return format_num(value) + ' (' + format_pct(ratio) + ')';
						} else {
							return format_num(value);
						}
					}
				}
			}
			
			// Click events
			chart_json.data.onclick = function(d, el) {
				if(!chart_json.data.hasOwnProperty('click_search') || 'object' != typeof chart_json.data['click_search'])
					return;
				
				if(!chart_json.data['click_search'].hasOwnProperty(d.id + '__click'))
					return;
				
				let queries = chart_json.data['click_search'][d.id + '__click'];
				let query = null;
				
				if(!queries)
					return;
				
				if(1 === queries.length) {
					query = queries[0];
				} else {
					query = queries[d.index];
				}
				
				if(!query)
					return;
				
				$('<div/>')
					.attr('data-context', query.substring(0, query.indexOf(' ')))
					.attr('data-query', query.substring(query.indexOf(' ')+1))
					.cerbSearchTrigger()
					.on('cerb-search-opened', function(e) {
						$(this).remove();
					})
					.click()
				;
			};
			
            c3.generate(chart_json);
            
		} catch(e) {
			$('#{$div_id}').text(e.message);
			
			if(console && 'function' == typeof console.error)
				console.error(e);
		}
	});
});
</script>