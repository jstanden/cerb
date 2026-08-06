{$show_image = empty($widget->params.chart_display) || 'image' == $widget->params.chart_display}
{$show_table = empty($widget->params.chart_display) || 'table' == $widget->params.chart_display}

{if $show_image}
<div id="widget{$widget->id}"></div>
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
<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	try {
		const raw = ({json_encode($widget->params.series) nofilter}) || [];
		const withData = raw.filter(function(s) { return s.data && s.data.length; });
		const categories = withData.length ? withData[0].data.map(function(d) { return d.x_label; }) : [];
		const series = withData.map(function(s, i) {
			return { key: 's' + i, name: s.label ? s.label : ('Series #' + (i + 1)), type: 'bar', stack: 'g', color: s.line_color, values: s.data.map(function(d) { return d.y; }) };
		});

		new CerbUI.CartesianChart(document.getElementById('widget{$widget->id}'), {
			x: { scale: 'category', categories: categories },
			y: { grid: true },
			series: series,
			legend: false,
			height: 160,
		});

	} catch(e) {
		if(console && console.error) console.error(e);
	}
});
</script>
{/if}
