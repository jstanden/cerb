<table cellpadding="0" cellspacing="0" border="0" width="100%">
	<tr>
		<td width="1%" nowrap="nowrap">
			<canvas id="widget{$widget->id}_canvas" width="200" height="125">
				Your browser does not support HTML5 Canvas.
			</canvas>
		</td>
		<td width="99%" valign="top">
			<div style="margin-top:5px;">
			{foreach from=$widget->params['threshold_labels'] item=label key=idx name=labels}
			{if !empty($label)}
			<div style="">
				<span style="width:10px;height:10px;display:inline-block;background-color:{$widget->params['threshold_colors'][$idx]};margin:2px;vertical-align:middle;border-radius:10px;-moz-border-radius:10px;-webkit-border-radius:10px;-o-border-radius:10px;"></span>
				<b style="vertical-align:middle;">{$label}</b>
			</div>
			{/if}
			{/foreach}
			</div>
		</td>
	</tr>
</table>

<script type="text/javascript">
try {
	{$metric_value = $widget->params.metric_value}
	{if $widget->params.metric_type == 'decimal'}{$decimals=2}{else}{$decimals=0}{/if}
	{if $widget->params.metric_type == 'percent'}{$metric_value = floatval($metric_value)}{/if}
	
	{$metric_label = $metric_value}
	
	{if $widget->params.metric_type == 'number' || $widget->params.metric_type == 'decimal'}
		{$metric_label = $metric_value|number_format:$decimals}
	{elseif $widget->params.metric_type == 'seconds'}
		{$metric_label = DevblocksPlatform::strSecsToString($metric_value,2)}
	{elseif $widget->params.metric_type == 'bytes'}
		{$metric_label = DevblocksPlatform::strPrettyBytes($metric_value, 2)}
	{/if}
	
	drawGauge($('#widget{$widget->id}_canvas'), {
		{if !empty($widget->params.threshold_values)}'threshold_values': {json_encode($widget->params.threshold_values) nofilter},{/if}
		/*{if !empty($widget->params.threshold_labels)}'threshold_labels': {json_encode($widget->params.threshold_labels) nofilter},{/if}*/
		{if !empty($widget->params.threshold_colors)}'threshold_colors': {json_encode($widget->params.threshold_colors) nofilter},{/if}
		{if !empty($metric_value)}'metric': {floatval($metric_value)},{/if}
		'metric_label': "{$widget->params.metric_prefix}{$metric_label}{if $widget->params.metric_type=='percent'}%{/if}{$widget->params.metric_suffix}",
		/*'metric_compare': 173,*/
		'legend': false,
		'radius': 90
	});
	
} catch(e) {
}
</script>
