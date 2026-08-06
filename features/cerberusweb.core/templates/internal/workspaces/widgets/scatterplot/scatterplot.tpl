<div id="widget{$widget->id}"></div>

<div style="margin-top:5px;">
{foreach from=$widget->params.series item=series key=series_idx name=series}
{if !empty($series.datasource) && !empty($series.label)}
<div style="display:inline-block;white-space:nowrap;">
	<span style="width:10px;height:10px;display:inline-block;background-color:{$series.line_color};margin:2px;vertical-align:middle;border-radius:10px;"></span>
	<b style="vertical-align:middle;">{if !empty($series.label)}{$series.label}{else}Series #{$smarty.foreach.series.iteration}{/if}</b>
</div>
{/if}
{/foreach}
</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	try {
		const raw = ({json_encode($widget->params.series) nofilter}) || [];
		const series = raw.filter(function(s) { return s.data && s.data.length; }).map(function(s, i) {
			return {
				key: 's' + i,
				name: s.label ? s.label : ('Series #' + (i + 1)),
				color: s.line_color ? s.line_color : '#058DC7',
				x: s.data.map(function(d) { return d.x; }),
				values: s.data.map(function(d) { return d.y; }),
			};
		});

		new CerbUI.ScatterChart(document.getElementById('widget{$widget->id}'), {
			series: series,
			axesIndependent: {if !empty($widget->params.axes_independent)}true{else}false{/if},
			legend: false,
			height: 160,
		});

	} catch(e) {
		if(console && console.error) console.error(e);
	}
});
</script>
