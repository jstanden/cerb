<ul class="submenu">
</ul>
<div style="clear:both;"></div>

<h2>{$translate->_('reports.ui.custom_fields.usage')}</h2>

<form action="{devblocks_url}c=reports&report=report.custom_fields.usage{/devblocks_url}" method="POST" id="frmRange" name="frmRange" style="margin-bottom:10px;">
<input type="hidden" name="c" value="reports">

<select name="field_id" onchange="this.form.btnSubmit.click();">
	{foreach from=$context_manifests item=mft}
		{foreach from=$custom_fields item=f key=field_idx}
			{if 'T' != $f->type && 0==strcasecmp($mft->id,$f->context)}{* Ignore clobs *}
			<option value="{$field_idx}" {if $field_id==$field_idx}selected="selected"{/if}>{$mft->name}:{$f->name}</option>
			{/if}
		{/foreach}
	{/foreach}
</select>

<button type="submit" id="btnSubmit">{$translate->_('reports.common.run_report')|capitalize}</button>
<div id="divCal" style="display:none;position:absolute;z-index:1;"></div>
</form>

<!-- Chart -->

{if !empty($data)}

<!--[if IE]><script language="javascript" type="text/javascript" src="{devblocks_url}c=resource&plugin=cerberusweb.reports&f=js/jqplot/excanvas.min.js{/devblocks_url}?v={$smarty.const.APP_BUILD}"></script><![endif]-->
<script language="javascript" type="text/javascript" src="{devblocks_url}c=resource&plugin=cerberusweb.reports&f=js/jqplot/jquery.jqplot.min.js{/devblocks_url}?v={$smarty.const.APP_BUILD}"></script>
<script language="javascript" type="text/javascript" src="{devblocks_url}c=resource&plugin=cerberusweb.reports&f=js/jqplot/plugins/jqplot.barRenderer.min.js{/devblocks_url}?v={$smarty.const.APP_BUILD}"></script>
<script language="javascript" type="text/javascript" src="{devblocks_url}c=resource&plugin=cerberusweb.reports&f=js/jqplot/plugins/jqplot.categoryAxisRenderer.min.js{/devblocks_url}?v={$smarty.const.APP_BUILD}"></script>
<script language="javascript" type="text/javascript" src="{devblocks_url}c=resource&plugin=cerberusweb.reports&f=js/jqplot/plugins/jqplot.pointLabels.min.js{/devblocks_url}?v={$smarty.const.APP_BUILD}"></script>
<link rel="stylesheet" type="text/css" href="{devblocks_url}c=resource&plugin=cerberusweb.reports&f=css/jqplot/jquery.jqplot.min.css{/devblocks_url}?v={$smarty.const.APP_BUILD}" />

<div id="reportChart" style="width:98%;height:{25+(32*{count($data)})};"></div>

<style type="text/css">
	.jqplot-yaxis-tick {
		white-space:nowrap;	
	}
</style>

<script type="text/javascript">
series1 = [
{foreach from=$data item=plot key=null name=plots}
[{$plot.hits},'{$plot.value|escape}']{if !$smarty.foreach.plots.last},{/if}
{/foreach}
];

var cerbChartStyle = {
	seriesColors: [
		'rgba(115,168,0,0.8)',
		'rgba(207,218,30,0.8)',
		'rgba(249,190,49,0.8)',
		'rgba(244,89,9,0.8)',
		'rgba(238,24,49,0.8)',
		'rgba(189,19,79,0.8)',
		'rgba(50,37,238,0.8)',
		'rgba(87,109,243,0.8)',
		'rgba(116,87,229,0.8)',
		'rgba(143,46,137,0.8)',
		'rgba(241,124,242,0.8)',
		'rgba(180,117,198,0.8)',
		'rgba(196,191,210,0.8)',
		'rgba(18,134,49,0.8)',
		'rgba(44,187,105,0.8)',
		'rgba(184,197,146,0.8)',
		'rgba(46,124,180,0.8)',
		'rgba(84,189,199,0.8)',
		'rgba(24,200,252,0.8)',
		'rgba(254,194,153,0.8)',
		'rgba(213,153,160,0.8)',
		'rgba(244,237,86,0.8)',
		'rgba(204,137,59,0.8)',
		'rgba(157,88,44,0.8)',
		'rgba(108,46,45,0.8)'
	]
};

plot1 = $.jqplot('reportChart', [series1], {
    stackSeries: false,
	legend:{ 
		show:false,
		location:'nw'
	},
	title:{
		show: false,
		text: ''
	},
	grid:{
		shadow: false,
		background:'rgb(255,255,255)',
		borderWidth:0
	},
	seriesColors: cerbChartStyle.seriesColors,	
    seriesDefaults:{
			renderer:$.jqplot.BarRenderer,
	        rendererOptions:{ 
				barDirection: 'horizontal',
				barMargin:3,
				varyBarColor:true,
				highlightMouseOver: false
			},
			shadow: false
	},
	series:[{
		pointLabels:{
			show:true,
			location:'e',
			labelsFromSeries:true,
			seriesLabelIndex:0
		}
	}],
    axes:{
        yaxis:{
		  renderer:$.jqplot.CategoryAxisRenderer,
	      tickOptions:{ 
	        fontSize:'11px',
			mark:null,
		  	showGridline:false
	      },
		}, 
        xaxis:{
		  min:0,
		  padMax:1.2,
		  tickOptions:{
		  	show:false, 
			showLabel:false,
		  	formatString:'%d'
		  }
		}
    },
});	
</script>

{/if}

<br>

<!-- Table -->

{if empty($value_counts)}
	<h3>No data.</h3>
{else}
	{$manifest = $context_manifests.{$f->context}}
	<h2>{$manifest->name}: {$field->name}</h2>
	<table cellpadding="2" cellspacing="2" border="0">
		<tr>
			<td><b>Value</b></td>
			<td><b>Uses</b></td>
		</tr>
	{foreach from=$value_counts item=count key=value}
		<tr>
			<td>{$value|escape}</td>
			<td align="center">{$count}</td>
		</tr>
	{/foreach}
	</table>
{/if}
