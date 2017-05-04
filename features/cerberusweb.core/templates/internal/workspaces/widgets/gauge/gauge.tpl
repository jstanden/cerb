<div style="text-align:center;">
<canvas id="widget{$widget->id}_canvas" width="200" height="125" style="margin-right:5px;">
	Your browser does not support HTML5 Canvas.
</canvas>
</div>

<span style="margin:5px 0px 0px 0px;display:inline-block;vertical-align:top;">
{foreach from=$widget->params['threshold_labels'] item=label key=idx name=labels}
{if !empty($label)}
<span>
	<span style="width:10px;height:10px;display:inline-block;background-color:{$widget->params['threshold_colors'][$idx]};margin:2px;vertical-align:middle;border-radius:10px;-moz-border-radius:10px;-webkit-border-radius:10px;-o-border-radius:10px;"></span>
	<b style="vertical-align:middle;">{$label}</b>
</span>
{/if}
{/foreach}
</span>

<script type="text/javascript">
$(function() {
try {
	{$metric_value = $widget->params.metric_value}
	{$metric_min = $widget->params.metric_min|default:0}
	{$metric_label = DevblocksPlatform::formatNumberAs($metric_value, $widget->params.metric_type)}	
	{$metric_label_min = DevblocksPlatform::formatNumberAs($metric_min, $widget->params.metric_type)}
	
	var options = {
		{if !empty($widget->params.threshold_values)}
		{$metric_max = end($widget->params.threshold_values)|default:0}
		{$metric_label_max = DevblocksPlatform::formatNumberAs($metric_max, $widget->params.metric_type)}
		'threshold_values': {json_encode($widget->params.threshold_values) nofilter},
		'metric_label_max': "{$metric_label_max}",
		{/if}
		{if !empty($widget->params.threshold_colors)}'threshold_colors': {json_encode($widget->params.threshold_colors) nofilter},{/if}
		{if !empty($metric_min)}'metric_min': {floatval($metric_min)},{/if}
		{if !empty($metric_value)}'metric': {floatval($metric_value)},{/if}
		'metric_label': "{$widget->params.metric_prefix}{$metric_label}{$widget->params.metric_suffix}",
		'metric_label_min': "{$metric_label_min}",
		/*'metric_compare': 173,*/
		'legend': false,
		'radius': 90
	};
	
	$('#widget{$widget->id}_canvas').devblocksCharts('gauge', options);
	
} catch(e) {
}
});
</script>
