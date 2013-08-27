<div class="chart-tooltip" style="margin-top:2px;">&nbsp;</div>

<canvas id="widget{$widget->id}_axes_canvas" width="325" height="125" style="position:absolute;cursor:crosshair;display:none;" class="overlay">
	Your browser does not support HTML5 Canvas.
</canvas>

<canvas id="widget{$widget->id}_canvas" width="325" height="125">
	Your browser does not support HTML5 Canvas.
</canvas>

<div style="margin-top:5px;">
{foreach from=$widget->params.series item=series key=series_idx name=series}
{if !empty($series.datasource) && !empty($series.label)}
<div style="display:inline-block;white-space:nowrap;">
	<span style="width:10px;height:10px;display:inline-block;background-color:{$series.line_color};margin:2px;vertical-align:middle;border-radius:10px;-moz-border-radius:10px;-webkit-border-radius:10px;-o-border-radius:10px;"></span>
	<b style="vertical-align:middle;">{if !empty($series.label)}{$series.label}{else}Series #{$smarty.foreach.series.iteration}{/if}</b>
</div>
{/if}
{/foreach}
</div>

<script type="text/javascript">
try {
	$widget = $('#widget{$widget->id}');
	width = $widget.width();
	$widget.find('canvas').attr('width', width);
	
	var options = {
		series:[
			{foreach from=$widget->params['series'] item=series key=series_idx name=series}
			{literal}{{/literal}
				'options': {
					'line_color': '{$series.line_color|default:'#058DC7'}',
					'fill_color': '{$series.fill_color|default:'rgba(5,141,199,0.15)'}'
				},
				'data': {json_encode($series.data) nofilter}
			{literal}}{/literal}
			{if !$smarty.foreach.series.last},{/if}
			{/foreach}
		]
	};
	
	$('#widget{$widget->id}_canvas').devblocksCharts('line', options);
	
	$('#widget{$widget->id}_axes_canvas')
		.data('model', options)
		.each(function(e) {
			canvas = $(this).get(0);
			context = canvas.getContext('2d');

			options = $(this).data('model');
			
			var margin = 5;
			var chart_width = canvas.width;
			var chart_height = canvas.height - (2 * margin);
			
			var max_value = 0;
			var min_value = 0;
		
			// Cache: Find the max y-value across every series
		
			for(series_idx in options.series) {
				for(idx in options.series[series_idx].data) {
					value = options.series[series_idx].data[idx].y;
					
					max_value = Math.max(value, max_value);
					min_value = Math.min(value, min_value);
				}
			}

			var range = Math.abs(max_value - min_value);
			
			var zero_ypos = Math.floor(chart_height * (max_value/range)) + margin - (0 == margin % 2 ? 0 : 0.5);

			// Cache: Plots chart coords
			
			plots = [];
			
			for(series_idx in options.series) {
				series = options.series[series_idx];

				if(null == series.data)
					continue;
				
				plots[series_idx] = [];
				
				count = series.data.length;
				xtick_width = chart_width / (count-1);
				ytick_height = chart_height / range;
				
				for(idx in series.data) {
					point = series.data[idx];
					
					chart_x = idx * xtick_width;
					
					value_yheight = Math.floor(ytick_height * Math.abs(point.y));
					
					if(point.y >= 0) {
						chart_y = zero_ypos - value_yheight;
						
					} else {
						chart_y = zero_ypos + value_yheight;
						
					}
					
					len = plots[series_idx].length;
					
					plots[series_idx][len] = {
						'chart_x': chart_x,
						'chart_y': chart_y,
						'data': point
					};
				}
			}
			
			$(this).data('plots', plots);
		})
		.mousemove(function(e) {
			canvas = $(this).get(0);
			context = canvas.getContext('2d');
			
			options = $(this).data('model');
			plots = $(this).data('plots');

			context.clearRect(0, 0, canvas.width, canvas.height);
			
			var x = 0, y = 0;
			
			if(undefined != e.offsetX) {
				x = e.offsetX;
				y = e.offsetY;
				
			} else if(undefined != e.layerX) {
				x = e.layerX;
				y = e.layerY;
				
			} else if(null != e.originalEvent && undefined != e.originalEvent.layerX) {
				x = e.originalEvent.layerX;
				y = e.originalEvent.layerY;
			}
			
			closest = {
				'dist': 1000,
				'chart_x': 0,
				'chart_y': 0,
				'data': [],
				'series_idx': null
			};

			for(series_idx in plots) {
				count = plots[series_idx].length;
				series = options.series[series_idx];
				
				for(idx in plots[series_idx]) {
					plot = plots[series_idx][idx];

					dist = Math.sqrt(Math.pow(x-plot.chart_x,2) + Math.pow(y-plot.chart_y,2));
					
					if(dist < closest.dist) {
						closest.dist = dist;
						closest.data = plot.data;
						closest.chart_x = plot.chart_x;
						closest.chart_y = plot.chart_y;
						closest.series_idx = series_idx;
					}
				}
			}
			
			if(null == options.series[closest.series_idx])
				return;
			
			series = options.series[closest.series_idx];
			
			context.beginPath();
			context.fillStyle = series.options.line_color;
			context.arc(closest.chart_x, closest.chart_y, 5, 0, 2 * Math.PI, false);
			context.fill();

			$label = $('<span style="padding:2px;font-weight:bold;background-color:rgb(240,240,240);">'+closest.data.x_label+': <span style="color:'+series.options.line_color+'">'+closest.data.y_label+'</span></span>');
			
			$tooltip = $(this).siblings('DIV.chart-tooltip');
			$tooltip.html('').append($label);
			
		})
		.mouseout(function(e) {
			$tooltip = $(this).siblings('DIV.chart-tooltip');
			$tooltip.html('&nbsp;');
		})
		;
	
} catch(e) {
}
</script>
