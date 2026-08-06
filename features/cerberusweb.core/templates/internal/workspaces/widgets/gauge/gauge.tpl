<div style="text-align:center;">
	<div id="widget{$widget->id}" style="display:inline-block;width:200px;height:150px;"></div>
</div>

<span style="margin:5px 0px 0px 0px;display:inline-block;vertical-align:top;">
{foreach from=$widget->params['threshold_labels'] item=label key=idx name=labels}
{if !empty($label)}
<span>
	<span style="width:10px;height:10px;display:inline-block;background-color:{$widget->params['threshold_colors'][$idx]};margin:2px;vertical-align:middle;border-radius:10px;"></span>
	<b style="vertical-align:middle;">{$label}</b>
</span>
{/if}
{/foreach}
</span>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	try {
		{$metric_value = $widget->params.metric_value|default:0}
		{$metric_min = $widget->params.metric_min|default:0}
		{$metric_max = end($widget->params.threshold_values)|default:100}
		{$metric_label = DevblocksPlatform::formatNumberAs($metric_value, $widget->params.metric_type)}
		{$metric_label_min = DevblocksPlatform::formatNumberAs($metric_min, $widget->params.metric_type)}
		{$metric_label_max = DevblocksPlatform::formatNumberAs($metric_max, $widget->params.metric_type)}

		const values = ({json_encode($widget->params.threshold_values) nofilter}) || [];
		const colors = ({json_encode($widget->params.threshold_colors) nofilter}) || [];
		const thresholds = values.map(function(v, i) { return { value: v, color: colors[i] }; });

		new CerbUI.Gauge(document.getElementById('widget{$widget->id}'), {
			value: {floatval($metric_value)},
			min: {floatval($metric_min)},
			max: {floatval($metric_max)},
			thresholds: thresholds,
			valueText: '{$widget->params.metric_prefix|escape:'javascript'}{$metric_label|escape:'javascript'}{$widget->params.metric_suffix|escape:'javascript'}',
			minLabel: '{$metric_label_min|escape:'javascript'}',
			maxLabel: '{$metric_label_max|escape:'javascript'}',
			height: 150,
		});

	} catch(e) {
		if(console && console.error) console.error(e);
	}
});
</script>
