<div class="chart-tooltip" style="margin-top:2px;">&nbsp;</div>

<canvas id="widget{$widget->id}_axes_canvas" width="300" height="125" style="position:absolute;cursor:crosshair;" class="overlay">
	Your browser does not support HTML5 Canvas.
</canvas>

<canvas id="widget{$widget->id}_canvas" width="300" height="125">
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
$(function() {
try {
	var $widget = $('#widget{$widget->id}');
	var width = $widget.width();
	
	if(width > 0)
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
	
	var $canvas = $('#widget{$widget->id}_canvas');
	var $overlay = $('#widget{$widget->id}_axes_canvas');
	
	$canvas.devblocksCharts('bar', options);
	
	$canvas.on('devblocks-chart-mousemove', function(e) {
		var $canvas = $(this);
		var $overlay = $('#widget{$widget->id}_axes_canvas');
		
		var canvas = $overlay.get(0);
		var context = canvas.getContext('2d');
		
		var chart_height = canvas.height;
		var chart_width = canvas.width;
		
		var options = $canvas.data('model');
		var xtick_width = $canvas.data('xtick_width');
		var closest = e.closest;

		context.clearRect(0, 0, canvas.width, canvas.height);

		context.beginPath();
		context.fillStyle = 'rgba(255,255,255,0.4)';
		context.moveTo(closest.data.chart_x, chart_height);
		context.lineTo(closest.data.chart_x, 0);
		var chart_x = Math.floor(closest.data.chart_x+xtick_width-1);
		context.lineTo(chart_x, 0);
		context.lineTo(chart_x, chart_height);
		context.fill();
		
		var $label = $('<span style="background-color:rgb(240,240,240);padding:2px 2px 2px 7px;font-weight:bold;"><span style="margin-right:5px;">'+options.series[0].data[closest.data.index].x_label+':</span></span>');
		
		for(series_idx in options.series) {
			var series = options.series[series_idx];
			var index = closest.data.index;

			if(null == series || null == series.data)
				continue;

			var $metric_label = $('<span style="color:'+series.options.color+';margin-right:5px;">'+series.data[index].y_label+'</span>');
			$label.append($metric_label);
		}
		
		var $tooltip = $canvas.siblings('DIV.chart-tooltip');
		$tooltip.html('').append($label);
	});
	
	$('#widget{$widget->id}_axes_canvas')
		.mousemove(function(e) {
			$('#widget{$widget->id}_canvas').trigger(e);
		})
		.mouseout(function(e) {
			var $tooltip = $(this).siblings('DIV.chart-tooltip');
			$tooltip.html('&nbsp;');
		})
		;	
	
} catch(e) {
}
});
</script>
