{$show_image = empty($widget->params.chart_display) || 'image' == $widget->params.chart_display}
{$show_table = empty($widget->params.chart_display) || 'table' == $widget->params.chart_display}

{if $show_image}
<div id="widget{$widget->id}"></div>
{/if}

{if !$show_table}
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
{/if}

{if $show_table}
<div>
{foreach from=$widget->params.series item=series key=series_idx name=series}
	{if $series.data}
	<div style="display:inline-block;margin-right:10px;vertical-align:top;">
	<table cellpadding="2" cellspacing="0" style="margin-top:5px;">
		<thead>
			<tr>
				<td colspan="2" align="center"><b style="color:{$series.line_color};">{$series.label}</b></td>
			</tr>
		</thead>
		{foreach from=$series.data item=data}
			<tr>
				<td valign="middle" align="right">{$data.x_label}</td>
				<td valign="middle" align="center" style="padding-left:5px;"><b style="color:{$series.line_color};">{$data.y_label}</b></td>
			</tr>
		{/foreach}
		{foreach from=$widget->params.subtotals item=subtotals key=func}
		<tr>
			<td>
				<b>{$func}</b>
			</td>
			{$subtotal = $subtotals.$series_idx}
			<td style="border-top:1px solid rgb(200,200,200);{if $smarty.foreach.sums.last}padding-left:5px; border-left:1px solid rgb(200,200,200);{/if}" align="center">
				{if $subtotal}
					{if $subtotal.format}
					{DevblocksPlatform::formatNumberAs($subtotal.value, $subtotal.format)}
					{else}
					{$subtotal.value}
					{/if}
				{else}
				--
				{/if}
			</td>
		</tr>
		{/foreach}
	</table>
	</div>
	{/if}
{/foreach}
</div>
{/if}

{if $show_image}
<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	try {
		const raw = ({json_encode($widget->params.series) nofilter}) || [];
		const withData = raw.filter(function(s) { return s.data && s.data.length; });
		const categories = withData.length ? withData[0].data.map(function(d) { return d.x_label; }) : [];
		const series = withData.map(function(s, i) {
			return { key: 's' + i, name: s.label ? s.label : ('Series #' + (i + 1)), type: 'line', color: s.line_color, values: s.data.map(function(d) { return d.y; }) };
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