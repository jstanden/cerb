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
			'/resource/devblocks.core/js/c3/c3.min.js'
		]
	}, function() {
		try {
			let chart_json = {$chart_json nofilter};
			chart_json.bindto = '#{$div_id}';
			
			let format_pct = d3.format('.1%');
			let format_num = d3.format(',');
			
			if(chart_json.tooltip.show) {
				chart_json.tooltip.format = {
					value: function (value, ratio, id, index) {
						if(ratio) {
							return format_num(value) + ' (' + format_pct(ratio) + ')';
						} else {
							return format_num(value);
						}
					}
				}
			}
			
            c3.generate(chart_json);
            
		} catch(e) {
			if(console && 'function' == typeof console.error)
				console.error(e);
		}
	});
});
</script>