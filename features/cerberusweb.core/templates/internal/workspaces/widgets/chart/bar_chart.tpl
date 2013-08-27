<div class="chart-tooltip" style="margin-top:2px;">&nbsp;</div>

<canvas id="widget{$widget->id}_axes_canvas" width="325" height="125" style="position:absolute;cursor:crosshair;display:none;" class="overlay">
	Your browser does not support HTML5 Canvas.
</canvas>

<canvas id="widget{$widget->id}_canvas" width="325" height="125">
	Your browser does not support HTML5 Canvas.
</canvas>

<div style="margin-top:5px;">
{foreach from=$widget->params.series item=series key=series_idx name=series}
{if 0 != strlen($series.label)}
<div style="display:inline-block;white-space:nowrap;">
	<span style="width:10px;height:10px;display:inline-block;background-color:{$series.line_color};margin:2px;vertical-align:middle;border-radius:10px;-moz-border-radius:10px;-webkit-border-radius:10px;-o-border-radius:10px;"></span>
	<b style="vertical-align:middle;">{if 0 != strlen($series.label)}{$series.label}{else}Series #{$smarty.foreach.series.iteration}{/if}</b>
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
	
	$('#widget{$widget->id}_canvas').devblocksCharts('bar', options);
	
	$('#widget{$widget->id}_axes_canvas')
		.data('model', options)
		.each(function(e) {
			canvas = $(this).get(0);
			context = canvas.getContext('2d');
			
			options = $(this).data('model');
			
			chart_height = canvas.height;
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
			
			chart_height = canvas.height;
			chart_width = canvas.width;
			
			options = $(this).data('model');
			plots = $(this).data('plots');
			xtick_width = $(this).data('xtick_width');

			context.clearRect(0, 0, canvas.width, canvas.height);
			
			var x = 0;
			
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
			context.moveTo(closest.plot.chart_x, chart_height);
			context.lineTo(closest.plot.chart_x, 0);
			chart_x = Math.floor(closest.plot.chart_x+xtick_width-1);
			context.lineTo(chart_x, 0);
			context.lineTo(chart_x, chart_height);
			context.fill();
			
			$label = $('<span style="background-color:rgb(240,240,240);padding:2px 2px 2px 7px;font-weight:bold;border:1px solid '+series.options.fill_color+';"><span style="margin-right:5px;color:'+series.options.line_color+'">'+options.series[0].data[closest.plot.index].x_label+':</span></span>');
			
			for(series_idx in options.series) {
				series = options.series[series_idx];
				index = closest.plot.index;

				if(null == series || null == series.data)
					continue;

				$metric_label = $('<span style="color:'+series.options.color+';margin-right:5px;">'+series.data[index].y_label+'</span>');
				$label.append($metric_label);
			}
			
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
