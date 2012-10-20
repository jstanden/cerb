<canvas id="widget{$widget->id}_axes_canvas" width="325" height="125" style="position:absolute;cursor:crosshair;display:none;" class="overlay">
	Your browser does not support HTML5 Canvas.
</canvas>

<canvas id="widget{$widget->id}_canvas" width="325" height="125">
	Your browser does not support HTML5 Canvas.
</canvas>

<div style="margin-top:5px;">
{foreach from=$widget->params.series item=series key=series_idx name=series}
{if !empty($series.label)}
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
	
	drawChart($('#widget{$widget->id}_canvas'), options);
	
	$('#widget{$widget->id}_axes_canvas')
		.data('model', options)
		.each(function(e) {
			canvas = $(this).get(0);
			context = canvas.getContext('2d');

			options = $(this).data('model');
			
			chart_top = 15;
			
			chart_width = canvas.width;
			chart_height = canvas.height - chart_top;

			max_value = 0;
		
			// Cache: Find the max y-value across every series
		
			for(series_idx in options.series) {
				for(idx in options.series[series_idx].data) {
					value = options.series[series_idx].data[idx].y;
					if(value > max_value)
						max_value = value;
				}
			}
			
			$(this).data('max_value', max_value);
			
			// Cache: Plots chart coords
			
			plots = [];
			
			for(series_idx in options.series) {
				series = options.series[series_idx];

				if(null == series.data)
					continue;
				
				plots[series_idx] = [];
				
				count = series.data.length;
				xtick_width = chart_width / (count-1);
				ytick_height = chart_height / max_value;
				
				for(idx in series.data) {
					point = series.data[idx];
					
					chart_x = idx * xtick_width;
					chart_y = chart_height - (ytick_height * point.y) + chart_top - (context.lineWidth/2 + 1.25);
					
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
			max_value = $(this).data('max_value');

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

			text = closest.data.x_label + ': ' + closest.data.y_label;
			bounds = context.measureText(text);
			padding = 2;
			
			context.beginPath();
			context.fillStyle = '#FFF';
			context.fillRect(0,0,bounds.width+2*padding,10+2*padding);
			context.fillStyle = series.options.fill_color;
			context.moveTo(0,0);
			context.fillRect(0,0,bounds.width+2*padding,10+2*padding);
			
			context.beginPath();
			context.fillStyle = series.options.line_color;
			context.font = "12px Verdana";
			context.fillText(text, padding, 10+padding);
			context.stroke();
		})
		;
	
} catch(e) {
}
</script>
