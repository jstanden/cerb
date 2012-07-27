<canvas id="widget{$widget->id}_canvas" width="325" height="125">
	Your browser does not support HTML5 Canvas.
</canvas>

<div style="margin-top:5px;">
{foreach from=$widget->params.series item=series key=series_idx name=series}
{if !empty($series.view_context)}
<div style="display:inline-block;white-space:nowrap;">
	<span style="width:10px;height:10px;display:inline-block;background-color:{$series.line_color};margin:2px;vertical-align:middle;border-radius:10px;-moz-border-radius:10px;-webkit-border-radius:10px;-o-border-radius:10px;"></span>
	<b style="vertical-align:middle;">{if !empty($series.label)}{$series.label}{else}Series #{$smarty.foreach.series.iteration}{/if}</b>
</div>
{/if}
{/foreach}
</div>

<script type="text/javascript">
try {
	drawChart($('#widget{$widget->id}_canvas'), {
// 		'line_color': 'rgba(59,89,152,1)', // Blue
// 		'line_color': 'rgba(107,168,30,1)', // Green
// 		'line_color': 'rgba(203,60,44,1)', // Red
// 		'line_color': 'rgba(237,126,23,1)', // Orange

		series:[
			{foreach from=$widget->params['series'] item=series key=series_idx name=series}
			{literal}{{/literal}
				'options': {
					'line_color': '{$series.line_color|default:'#058DC7'}', // Blue rgb(5,141,199)
					'fill_color': '{$series.fill_color|default:'rgba(5,141,199,0.15)'}'
				},
				'data': {json_encode($series.data) nofilter}
			{literal}}{/literal}
			{if !$smarty.foreach.series.last},{/if}
			{/foreach}
		]
	});	
	
} catch(e) {
}
</script>
