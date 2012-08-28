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
	$widget = $('#widget{$widget->id}');
	width = $widget.width();
	$widget.find('canvas').attr('width', width);
	
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
	
	drawBarGraph($('#widget{$widget->id}_canvas'), options);	
	
	$('#widget{$widget->id}_axes_canvas')
		.data('model', options)
		.each(function(e) {
			canvas = $(this).get(0);
			context = canvas.getContext('2d');
			
			options = $(this).data('model');
			
			chart_top = 15;
			chart_height = canvas.height - chart_top;
			chart_width = canvas.width;
			
			// Cache: Plots chart coords
			
			plots = [];
			
			series_idx =  0;
			series = options.series[series_idx];

			if(null == series.data)
				return;
			
			plots[series_idx] = [];
			
			// [TODO] This should make sure all series are the same length
			count = series.data.length;
			xtick_width = Math.floor(chart_width / count);
			
			$(this).data('xtick_width', xtick_width);
			
			chart_x = 0;
			
			for(idx in series.data) {
				point = series.data[idx];
				
				plots[plots.length] = {
					'chart_x': chart_x,
					'index': idx
				};

				chart_x = Math.floor(chart_x + xtick_width);
			}

			$(this).data('plots', plots);
		})
		.mousemove(function(e) {
			canvas = $(this).get(0);
			context = canvas.getContext('2d');
			
			chart_top = 15;
			chart_height = canvas.height - chart_top;
			chart_width = canvas.width;
			
			options = $(this).data('model');
			plots = $(this).data('plots');
			xtick_width = $(this).data('xtick_width');

			context.clearRect(0, 0, canvas.width, canvas.height);
			
			var x = 0;
			
			if(undefined != e.offsetX) {
				x = e.offsetX;
				
			} else if(undefined != e.layerX) {
				x = e.layerX;
				
			} else if(null != e.originalEvent && undefined != e.originalEvent.layerX) {
				x = e.originalEvent.layerX;
			}

			closest = {
				'dist': 1000,
				'plot': null
			};

			for(idx in plots) {
				plot = plots[idx];

				dist = Math.abs(x-(plot.chart_x+(xtick_width/2)));
				
				if(dist < closest.dist) {
					closest.dist = dist;
					closest.plot = plot;
				}
			}

			if(null == closest.plot)
				return;

			context.beginPath();
			context.fillStyle = 'rgba(255,255,255,0.4)';
			context.moveTo(closest.plot.chart_x, chart_height + chart_top);
			context.lineTo(closest.plot.chart_x, chart_top);
			chart_x = Math.floor(closest.plot.chart_x+xtick_width-1);
			context.lineTo(chart_x, chart_top);
			context.lineTo(chart_x, chart_height + chart_top);
			context.fill();
			
			text = options.series[0].data[closest.plot.index].x_label + ': '; // Label
			bounds = context.measureText(text);
			padding = 2;

			text_x = padding;
			
			context.beginPath();
			context.fillStyle = '#FFF';
			context.fillRect(0,0,chart_width,chart_top);
			
			context.beginPath();
			context.fillStyle = '#34434E';
			context.font = "12px Verdana";
			context.fillText(text, text_x, 10+padding);
			context.stroke();
			
			text_x += bounds.width + padding;
			
			for(series_idx in options.series) {
				series = options.series[series_idx];
				index = closest.plot.index;

				if(null == series || null == series.data)
					continue;
				
				text = series.data[index].y_label; // Label
				bounds = context.measureText(text);
				
				context.beginPath();
				context.fillStyle = series.options.color;
				context.fillText(text, text_x, 10+padding);
				context.stroke();
				
				text_x += bounds.width + 5;
			}
			
		})
		;	
	
} catch(e) {
}
</script>
