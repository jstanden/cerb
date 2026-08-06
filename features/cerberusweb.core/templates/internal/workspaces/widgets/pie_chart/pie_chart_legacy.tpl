<div style="text-align:center;">
	<div id="widget{$widget->id}" style="max-width:300px;margin:0 auto;"></div>
</div>

<div class="subtotals" style="margin-top:5px;min-height:16px;">
{$show_legend = $widget->params['show_legend']}

{foreach from=$widget->params['wedge_labels'] item=label key=idx name=labels}
{if !empty($label)}

{$metric_value = $widget->params['wedge_values'][$idx]}
{$metric_label = DevblocksPlatform::formatNumberAs($metric_value, $widget->params.metric_type)}

<div class="subtotal" style="display:{if !$show_legend}none{else}inline-block{/if};">
	{$color = $widget->params['wedge_colors'][$idx]}
	{if empty($color)}{$color = end($widget->params['wedge_colors'])}{/if}
	<span style="width:10px;height:10px;display:inline-block;background-color:{$color};margin:2px;vertical-align:middle;border-radius:10px;"></span>
	<span class="label" style="font-weight:bold;vertical-align:middle;">{$label}</span> <small>({$widget->params.metric_prefix}{$metric_label}{$widget->params.metric_suffix})</small>
</div>
{/if}
{/foreach}
<b>&nbsp;</b>
</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	try {
		const labels = ({json_encode($widget->params.wedge_labels) nofilter}) || [];
		const values = ({json_encode($widget->params.wedge_values) nofilter}) || [];
		const colors = ({json_encode($widget->params.wedge_colors) nofilter}) || [];
		const slices = labels.map(function(label, i) { return { label: label, value: values[i] }; });

		new CerbUI.PieChart(document.getElementById('widget{$widget->id}'), { type: 'pie', slices: slices, palette: colors, legend: false, height: 210 });

	} catch(e) {
		if(console && console.error) console.error(e);
	}
});
</script>
