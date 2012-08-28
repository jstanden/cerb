{$metric_value = $widget->params.metric_value}
{if $widget->params.metric_type == 'decimal'}{$decimals=2}{else}{$decimals=0}{/if}
{if $widget->params.metric_type == 'percent'}{$metric_value = floatval($metric_value)}{/if}

{$metric_label = $metric_value}

{if $widget->params.metric_type == 'number' || $widget->params.metric_type == 'decimal'}
	{$metric_value = floatval($metric_value)}
	{$metric_label = $metric_value|number_format:$decimals}
{elseif $widget->params.metric_type == 'seconds'}
	{$metric_value = intval($metric_value)}
	{$metric_label = DevblocksPlatform::strSecsToString($metric_value,2)}
{/if}

<div id="widget{$widget->id}_counter" style="font-family:Arial,Helvetica;font-weight:bold;font-size:32px;color:{$widget->params.color|default:'#57970A'};text-align:center;">
	<span class="counter">{$widget->params.metric_prefix}{$metric_label}{if $widget->params.metric_type=='percent'}%{/if}{$widget->params.metric_suffix}</span>
</div>

<script type="text/javascript">
try {
	// Scale the text to the width of the column
	$container = $('DIV#widget{$widget->id}_counter');
	$counter = $container.find('> span.counter');
	
	container_width = $container.width();
	
	counter_width = $counter.width();
	
	font_size = parseInt($counter.css('fontSize'), 10);
	multiplier = (container_width / counter_width) * 0.8;
	font_size = parseInt(font_size * multiplier);
	
	if(font_size < 16)
		font_size = 16;
	
	if(font_size > 36)
		font_size = 36;
	
	$counter.css('fontSize', font_size);
	
} catch(e) {
}
</script>