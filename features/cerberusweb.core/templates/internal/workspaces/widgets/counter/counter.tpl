{$metric_value = $widget->params.metric_value}
{$metric_label = DevblocksPlatform::formatNumberAs($metric_value, $widget->params.metric_type)}

<div id="widget{$widget->id}_counter" style="font-family:Arial,Helvetica;font-weight:bold;font-size:32px;color:{$widget->params.color|default:'#57970A'};text-align:center;">
	<span class="counter">{$widget->params.metric_prefix}{$metric_label}{$widget->params.metric_suffix}</span>
</div>

<script type="text/javascript">
$(function() {
try {
	// Scale the text to the width of the column
	var $container = $('DIV#widget{$widget->id}_counter');
	var $counter = $container.find('> span.counter');
	
	var container_width = $container.width();
	
	var counter_width = $counter.width();
	
	var font_size = parseInt($counter.css('fontSize'), 10);
	var multiplier = (container_width / counter_width) * 0.8;
	var font_size = parseInt(font_size * multiplier);
	
	if(font_size < 16)
		font_size = 16;
	
	if(font_size > 36)
		font_size = 36;
	
	$counter.css('fontSize', font_size);
	
} catch(e) {
}
});
</script>