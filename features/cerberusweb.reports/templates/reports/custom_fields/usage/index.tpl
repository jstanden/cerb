<div id="headerSubMenu">
	<div style="padding-bottom:5px;"></div>
</div>

<h2>{$translate->_('reports.ui.custom_fields.usage')}</h2>

<form action="{devblocks_url}c=reports&report=report.custom_fields.usage{/devblocks_url}" method="POST" id="frmRange" name="frmRange" style="margin-bottom:10px;">
<input type="hidden" name="c" value="reports">

<select name="field_id" onchange="this.form.btnSubmit.click();">
	{foreach from=$source_manifests item=mft}
		{foreach from=$custom_fields item=f key=field_idx}
			{if 'T' != $f->type && 0==strcasecmp($mft->id,$f->source_extension)}{* Ignore clobs *}
			<option value="{$field_idx}" {if $field_id==$field_idx}selected="selected"{/if}>{$mft->name}:{$f->name}</option>
			{/if}
		{/foreach}
	{/foreach}
</select>

<button type="submit" id="btnSubmit">{$translate->_('common.refresh')|capitalize}</button>
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
	seriesColors: [
		'rgba(115,168,0,0.8)', 
		'rgba(249,190,49,0.8)', 
		'rgba(50,153,187,0.8)', 
		'rgba(191,52,23,0.8)', 
		'rgba(122,103,165,0.8)', 
		'rgba(0,76,102,0.8)', 
		'rgba(196,197,209,0.8)', 
		'rgba(190,232,110,0.8)',
		'rgba(182,0,34,0.8)', 
		'rgba(61,28,33,0.8)' 
	],	
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
	{$manifest = $source_manifests.{$f->source_extension}}
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
