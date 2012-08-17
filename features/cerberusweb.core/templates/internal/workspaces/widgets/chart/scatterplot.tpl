<canvas id="widget{$widget->id}_axes_canvas" width="325" height="125" style="position:absolute;cursor:crosshair;display:none;" class="overlay">
	Your browser does not support HTML5 Canvas.
</canvas>

<canvas id="widget{$widget->id}_canvas" width="325" height="125">
	Your browser does not support HTML5 Canvas.
</canvas>

<div style="margin-top:5px;">
{foreach from=$widget->params.series item=series key=series_idx name=series}
{if !empty($series.view_context)}
<div style="display:inline-block;white-space:nowrap;">
	<span style="width:10px;height:10px;display:inline-block;background-color:{$series.line_color};margin:2px;vertical-align:middle;border-radius:10px;-moz-border-radius:10px;-webkit-border-radius:10px;-o-border-radius:10px;"></span>
	<b style="vertical-align:middle;">{if !empty($series.label)}{$series.label}{else}Series #{$smarty.foreach.series.iteration}{/if}</b>
</div>
{/if}
{/foreach}
</div>

<script type="text/javascript">
try {
	var options = {
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
	
	drawScatterplot($('#widget{$widget->id}_canvas'), options);
	
	$('#widget{$widget->id}_axes_canvas')
		.data('model', options)
		.each(function(e) {
			canvas = $(this).get(0);
			context = canvas.getContext('2d');
			
			options = $(this).data('model');
			
			// Cache
			
			//chart_top = 15;
			margin = 5;
			chart_width = canvas.width - (2 * margin);
			chart_height = canvas.height - (2 * margin);
		
			// Find the min/max values for each axis
			
			x_min = Number.MAX_VALUE;
			x_max = Number.MIN_VALUE;
			y_min = Number.MAX_VALUE;
			y_max = Number.MIN_VALUE;
			
			for(series_idx in options.series) {
				series = options.series[series_idx];
				
				if(null == series.data)
					continue;
				
				for(idx in series.data) {
					data = series.data[idx];
					x = data[0];
					y = data[1];
					
					x_min = Math.min(x_min,x);
					x_max = Math.max(x_max,x);
					y_min = Math.min(y_min,y);
					y_max = Math.max(y_max,y);
				}		
			}
		
			/*
			 * [TODO] This could support different scales per series
			 * [TODO] This could also support sets where min != 0 by calculating max-min and subtracting min from all values
			 */
			xaxis_tick = chart_width / x_max; 
			yaxis_tick = chart_height / y_max; 			
			
			// Cache: Plots chart coords
			
			plots = [];

			for(series_idx in options.series) {
				series = options.series[series_idx];
				
				if(null == series || null == series.data)
					continue;
				
				plots[series_idx] = [];
				
				for(idx in series.data) {
					data = series.data[idx];
					x = data[0];
					y = data[1];
					
					chart_x = (xaxis_tick * x) + margin;
					chart_y = chart_height - (yaxis_tick * y) + margin;
					
					plots[series_idx][idx] = {
						'chart_x': chart_x,
						'chart_y': chart_y,
						'x': x,
						'y': y
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

			context.clearRect(0, 0, 325, 125);

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
						closest.data = plot;
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

			text = closest.data.x + ', ' + closest.data.y;
			bounds = context.measureText(text);
			padding = 2;
			
			context.beginPath();
			context.fillStyle = '#FFF';
			context.fillRect(0,0,bounds.width+2*padding,10+2*padding);
			
			context.beginPath();
			context.fillStyle = series.options.color;
			context.font = "bold 12px Verdana";
			context.fillText(text, padding, 10+padding);
			context.stroke();			
		})
		;
} catch(e) {
}
</script>