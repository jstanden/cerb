{$show_image = empty($widget->params.chart_display) || 'image' == $widget->params.chart_display}
{$show_table = empty($widget->params.chart_display) || 'table' == $widget->params.chart_display}

{if $show_image}
<div class="chart-tooltip" style="margin-top:2px;">&nbsp;</div>

<canvas id="widget{$widget->id}_axes_canvas" width="300" height="125" style="position:absolute;cursor:crosshair;" class="overlay">
	Your browser does not support HTML5 Canvas.
</canvas>

<canvas id="widget{$widget->id}_canvas" width="300" height="125">
	Your browser does not support HTML5 Canvas.
</canvas>
{/if}

{if !$show_table}
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
{/if}

{$x_subtotals = DevblocksPlatform::importVar($widget->params.x_subtotals, 'array', [])}

{if $show_table}
<table cellspacing="0" cellpadding="2">
<thead>
<tr>
	<td></td>
	{foreach from=$widget->params.series item=series}
	<td style="border-bottom:1px solid rgb(200,200,200);"><b style="color:{$series.line_color};">{$series.label}</b></td>
	{/foreach}
	
	{foreach from=$x_subtotals.data item=data key=func}
	<td align="center"><b>{$func}</b></td>
	{/foreach}
</tr>
</thead>

<tbody>
{foreach from=$widget->params.series.0.data item=data key=idx}
	<tr>
		<td align="right">{$data.x_label}</td>
		{foreach from=$widget->params.series item=series key=series_idx}
		{if $series.data}
		<td align="center"><span style="color:{$series.line_color};{if $series.data.$idx.y_label}font-weight:bold;{else}opacity:0.5;{/if}">{$series.data.$idx.y_label}</span></td>
		{/if}
		{/foreach}
		
		{foreach from=$x_subtotals.data item=subtotals key=func}
		<td style="padding-left:5px;border-left:1px solid rgb(200,200,200);" align="center">
			{if $x_subtotals.format}
			{DevblocksPlatform::formatNumberAs($subtotals[$data.x]['value'], $x_subtotals.format)}
			{else}
			{$subtotals[$data.x]['value']}
			{/if}
		</td>
		{/foreach}
	</tr>
{/foreach}

{foreach from=$widget->params.subtotals item=subtotals key=func}
	<tr>
		<td>
			<b>{$func}</b>
		</td>
		{foreach from=$subtotals key=series_idx item=subtotal}
		<td style="border-top:1px solid rgb(200,200,200);{if $smarty.foreach.sums.last}padding-left:5px; border-left:1px solid rgb(200,200,200);{/if}" align="center">
			{if $subtotal.format}
			{DevblocksPlatform::formatNumberAs($subtotal.value, $subtotal.format)}
			{else}
			{$subtotal.value}
			{/if}
		</td>
		{/foreach}
	</tr>
{/foreach}

</tbody>
</table>
{/if}

{if $show_image}
<script type="text/javascript">
$(function() {
try {
	var $widget = $('#workspaceWidget{$widget->id}');
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
		
		var $label = $('<span style="background-color:rgb(240,240,240);padding:2px 2px 2px 7px;font-weight:bold;"/>');
		$label.append($('<span style="margin-right:5px;"/>').text(options.series[0].data[closest.data.index].x_label+':'));
		
		for(series_idx in options.series) {
			var series = options.series[series_idx];
			var index = closest.data.index;

			if(null == series || null == series.data)
				continue;

			var $metric_label = $('<span/>').css('color',series.options.color).css('margin-right','5px').text(series.data[index].y_label);
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
{/if}
