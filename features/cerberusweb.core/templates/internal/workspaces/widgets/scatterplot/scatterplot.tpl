<div class="chart-tooltip" style="margin-top:2px;">&nbsp;</div>

<canvas id="widget{$widget->id}_axes_canvas" width="300" height="125" style="position:absolute;cursor:crosshair;" class="overlay">
	Your browser does not support HTML5 Canvas.
</canvas>

<canvas id="widget{$widget->id}_canvas" width="300" height="125">
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
$(function() {
try {
	var $widget = $('#widget{$widget->id}');
	var width = $widget.width();
	
	if(width > 0)
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
	
	var $canvas = $('#widget{$widget->id}_canvas');
	var $overlay = $('#widget{$widget->id}_axes_canvas')
	
	$canvas.devblocksCharts('scatterplot', options);
	
	$canvas.on('devblocks-chart-mousemove', function(e) {
		var $canvas = $(this);
		var $overlay = $('#widget{$widget->id}_axes_canvas');
		
		var canvas = $overlay.get(0);
		var context = canvas.getContext('2d');
		
		var chart_height = canvas.height;
		var chart_width = canvas.width;
		
		var options = $(this).data('model');

		context.clearRect(0, 0, canvas.width, canvas.height);

		var closest = e.closest;
		var series = options.series[closest.series_idx];

		var chart_x = Math.floor(closest.chart_x) + 0.5;
		var chart_y = Math.floor(closest.chart_y) + 0.5;
		
		if(context.setLineDash !== undefined)
			context.setLineDash([3,2]);
		
		// Draw a vertical line through the point
		context.beginPath();
		context.strokeStyle = series.options.color;
		context.lineWidth = 1;
		context.moveTo(chart_x, 0);
		context.lineTo(chart_x, canvas.height);
		context.stroke();
		
		// Draw a horizontal line through the point
		context.beginPath();
		context.strokeStyle = series.options.color;
		context.lineWidth = 1;
		context.moveTo(0, chart_y);
		context.lineTo(canvas.width, chart_y);
		context.stroke();
		
		// Highlight the point
		context.beginPath();
		context.fillStyle = series.options.color;
		context.arc(closest.chart_x, closest.chart_y, 5, 0, 2 * Math.PI, false);
		context.fill();

		var $label = $('<span style="padding:2px;font-weight:bold;background-color:rgb(240,240,240);">' +closest.data.x_label +': <span style="color:'+series.options.color+'">'+closest.data.y_label+'</span>');

		var $tooltip = $(this).siblings('DIV.chart-tooltip');
		$tooltip.html('').append($label);
	});
	
	$overlay
		.mousemove(function(e) {
			var $canvas = $('#widget{$widget->id}_canvas');
			$canvas.trigger(e);
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