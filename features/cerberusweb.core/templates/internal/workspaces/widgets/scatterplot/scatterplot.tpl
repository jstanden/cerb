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
		axes_independent: {if !empty($widget->params.axes_independent)}true{else}false{/if},
		series:[
			{foreach from=$widget->params['series'] item=series key=series_idx name=series}
			{literal}{{/literal}
				'options': {
					'color': '{$series.line_color|default:'#058DC7'}', // Blue rgb(5,141,199)
				},
				'data': {json_encode($series.data) nofilter}
			{literal}}{/literal}
			{if !$smarty.foreach.series.last},{/if}
			{/foreach}
		]
	};
	
	$('#widget{$widget->id}_canvas').devblocksCharts('scatterplot', options);
	
	$('#widget{$widget->id}_axes_canvas')
		.data('model', options)
		.each(function(e) {
			canvas = $(this).get(0);
			context = canvas.getContext('2d');
			
			options = $(this).data('model');
			
			// Cache
			
			margin = 5;
			chart_width = canvas.width - (2 * margin);
			chart_height = canvas.height - (2 * margin);
		
			// Stats for the entire dataset
			
			stats = {
				x_min: Number.MAX_VALUE,
				x_max: Number.MIN_VALUE,
				y_min: Number.MAX_VALUE,
				y_max: Number.MIN_VALUE,
				x_range: 0,
				y_range: 0
			}
			
			for(series_idx in options.series) {
				series = options.series[series_idx];
				
				if(null == series.data)
					continue;
				
				for(idx in series.data) {
					data = series.data[idx];
					x = data.x;
					y = data.y;
					
					stats.x_min = Math.min(stats.x_min,x);
					stats.x_max = Math.max(stats.x_max,x);
					stats.y_min = Math.min(stats.y_min,y);
					stats.y_max = Math.max(stats.y_max,y);
				}		
			}
		
			stats.x_range = Math.abs(stats.x_max - stats.x_min);
			stats.y_range = Math.abs(stats.y_max - stats.y_min);
			
			// Stats for each series
			
			var series_stats = [];
			
			for(series_idx in options.series) {
				series = options.series[series_idx];
				
				if(null == series.data)
					continue;
				
				minmax = {
					x_min: Number.MAX_VALUE,
					x_max: Number.MIN_VALUE,
					y_min: Number.MAX_VALUE,
					y_max: Number.MIN_VALUE,
					x_range: 0,
					y_range: 0
				}
				
				for(idx in series.data) {
					data = series.data[idx];
					x = data.x;
					y = data.y;
		
					minmax.x_min = Math.min(minmax.x_min, x);
					minmax.x_max = Math.max(minmax.x_max, x);
					minmax.y_min = Math.min(minmax.y_min, y);
					minmax.y_max = Math.max(minmax.y_max, y);
				}
				
				minmax.x_range = Math.abs(minmax.x_max - minmax.x_min);
				minmax.y_range = Math.abs(minmax.y_max - minmax.y_min);
				
				series_stats[series_idx] = minmax;
			}
			
			// Cache: Plots chart coords
			
			plots = [];

			for(series_idx in options.series) {
				series = options.series[series_idx];
				
				if(options.axes_independent) {
					stat = series_stats[series_idx];
				} else {
					stat = stats;
				}
				
				if(null == series || null == series.data)
					continue;

				xaxis_tick = (stat.x_range != 0) ? (chart_width / stat.x_range) : chart_width;
				yaxis_tick = (stat.y_range != 0) ? (chart_height / stat.y_range) : chart_height;
				
				plots[series_idx] = [];
				
				for(idx in series.data) {
					data = series.data[idx];
					x = data.x - stat.x_min;
					y = data.y - stat.y_min;
					
					chart_x = (xaxis_tick * x) + margin;
					chart_y = chart_height - (yaxis_tick * y) + margin;
					
					plots[series_idx][idx] = {
						'chart_x': chart_x,
						'chart_y': chart_y,
						'data': data
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
			context.fillStyle = series.options.color;
			context.arc(closest.chart_x, closest.chart_y, 5, 0, 2 * Math.PI, false);
			context.fill();

			$label = $('<span style="padding:2px;font-weight:bold;background-color:rgb(240,240,240);">' +closest.data.x_label +': <span style="color:'+series.options.color+'">'+closest.data.y_label+'</span>');

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