<div style="text-align:center;">
	<canvas id="widget{$widget->id}_axes_canvas" width="300" height="210" style="position:absolute;cursor:crosshair;" class="overlay">
		Your browser does not support HTML5 Canvas.
	</canvas>
	
	<canvas id="widget{$widget->id}_canvas" width="300" height="210">
		Your browser does not support HTML5 Canvas.
	</canvas>
</div>

<div class="subtotals" style="margin-top:5px;min-height:16px;">
{$show_legend = $widget->params['show_legend']}

{foreach from=$widget->params['wedge_labels'] item=label key=idx name=labels}
{if !empty($label)}

{$metric_value = $widget->params['wedge_values'][$idx]}

{if $widget->params.metric_type == 'decimal'}{$decimals=2}{else}{$decimals=0}{/if}
{if $widget->params.metric_type == 'percent'}{$metric_value = floatval($metric_value)}{/if}

{$metric_label = $metric_value}

{if $widget->params.metric_type == 'number' || $widget->params.metric_type == 'decimal'}
	{$metric_value = floatval($metric_value)}
	{$metric_label = $metric_value|number_format:$decimals}
{elseif $widget->params.metric_type == 'seconds'}
	{$metric_value = intval($metric_value)}
	{$metric_label = DevblocksPlatform::strSecsToString($metric_value,2)}
{elseif $widget->params.metric_type == 'bytes'}
	{$metric_label = DevblocksPlatform::strPrettyBytes($metric_value, 2)}
{/if}

<div class="subtotal" style="display:{if !$show_legend}none{else}inline-block{/if};">
	{$color = $widget->params['wedge_colors'][$idx]}
	{if empty($color)}{$color = end($widget->params['wedge_colors'])}{/if}
	<span style="width:10px;height:10px;display:inline-block;background-color:{$color};margin:2px;vertical-align:middle;border-radius:10px;-moz-border-radius:10px;-webkit-border-radius:10px;-o-border-radius:10px;"></span>
	<span class="label" style="font-weight:bold;vertical-align:middle;">{$label}</span> <small>({$widget->params.metric_prefix}{$metric_label}{if $widget->params.metric_type=='percent'}%{/if}{$widget->params.metric_suffix})</small>
</div>
{/if}
{/foreach}
<b>&nbsp;</b>
</div>

<script type="text/javascript">
$(function() {
try {
	$widget = $('#widget{$widget->id}');
	width = $widget.width();
	
	if(width > 0)
		$widget.find('canvas').attr('width', width);
	
	var options = {
		{if !empty($widget->params.wedge_values)}'wedge_values': {json_encode($widget->params.wedge_values) nofilter},{/if}
		{if !empty($widget->params.wedge_colors)}'wedge_colors': {json_encode($widget->params.wedge_colors) nofilter},{/if}
		'radius': 90
	};
	
	var $canvas = $('#widget{$widget->id}_canvas');
	var $overlay = $('#widget{$widget->id}_axes_canvas');
	
	$canvas.devblocksCharts('pie', options);

	$canvas.on('devblocks-chart-mousemove', function(e) {
		var $canvas = $(this);
		var $overlay = $('#widget{$widget->id}_axes_canvas');
		
		var canvas = $overlay.get(0);
		var context = canvas.getContext('2d');
		
		var $widget = $('#widget{$widget->id}');
		
		var chart_height = canvas.height;
		var chart_width = canvas.width;
		
		var options = $(this).data('model');
		var wedges = $(this).data('wedges');
		var piecenter_x = $(this).data('piecenter_x');
		var piecenter_y = $(this).data('piecenter_y');
		var radius = options.radius || 90;
		
		var closest_wedge = e.closest;
		
		context.clearRect(0, 0, canvas.width, canvas.height);
		
		context.beginPath();
		context.moveTo(piecenter_x, piecenter_y);
		color = options.wedge_colors[closest_wedge.index];
		if(undefined == color)
			color = options.wedge_colors[options.wedge_colors.length-1];
		context.fillStyle = color;
		context.strokeStyle = color;
		context.lineWidth = 3;
		context.lineCap = 'round';
		context.arc(piecenter_x, piecenter_y, radius + 12, closest_wedge.start, closest_wedge.length, false);
		context.lineTo(piecenter_x, piecenter_y);
		context.fill();
		
		context.beginPath();
		context.strokeStyle = 'rgba(255,255,255,0.7)';
		context.lineWidth = 15;
		context.lineCap = 'square';
		context.arc(piecenter_x, piecenter_y, radius + 8, closest_wedge.start, closest_wedge.length, false);
		context.stroke();
		
		$subtotals = $widget.find('div.subtotals > div.subtotal');
		
		$labels = $subtotals.find('> span.label');
		$labels
			.css('background-color', '')
			;
		{if !$show_legend}$subtotals.css('display', 'none');{/if}
		$labels.filter(':nth(' + closest_wedge.index + ')')
			{if $show_legend}.css('background-color', 'rgb(255,235,128)'){/if}
			{if !$show_legend}.closest('div.subtotal').css('display', 'inline-block'){/if}
			;
	});
	
	$overlay
		.mousemove(function(e) {
			var $canvas = $('#widget{$widget->id}_canvas');
			$canvas.trigger(e);
		})
		.mouseout(function() {
			$widget = $('#widget{$widget->id}');
			
			$subtotals = $widget.find('div.subtotals > div.subtotal');
			
			$labels = $subtotals.find('> span.label');
			$labels
				.css('background-color', '')
				;
			
			{if !$show_legend}$subtotals.css('display', 'none');{/if}
		})
		;
	
} catch(e) {
}
});
</script>
