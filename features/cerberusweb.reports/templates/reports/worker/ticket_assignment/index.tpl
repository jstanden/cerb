<div id="headerSubMenu">
	<div style="padding-bottom:5px;">
	</div>
</div>

<h2>{$translate->_('reports.ui.worker.ticket_assignment')}</h2>

<!-- Chart -->

<!--[if IE]><script language="javascript" type="text/javascript" src="{devblocks_url}c=resource&plugin=cerberusweb.reports&f=js/jqplot/excanvas.min.js{/devblocks_url}?v={$smarty.const.APP_BUILD}"></script><![endif]-->
<script language="javascript" type="text/javascript" src="{devblocks_url}c=resource&plugin=cerberusweb.reports&f=js/jqplot/jquery.jqplot.min.js{/devblocks_url}?v={$smarty.const.APP_BUILD}"></script>
<script language="javascript" type="text/javascript" src="{devblocks_url}c=resource&plugin=cerberusweb.reports&f=js/jqplot/plugins/jqplot.barRenderer.min.js{/devblocks_url}?v={$smarty.const.APP_BUILD}"></script>
<script language="javascript" type="text/javascript" src="{devblocks_url}c=resource&plugin=cerberusweb.reports&f=js/jqplot/plugins/jqplot.categoryAxisRenderer.min.js{/devblocks_url}?v={$smarty.const.APP_BUILD}"></script>
<script language="javascript" type="text/javascript" src="{devblocks_url}c=resource&plugin=cerberusweb.reports&f=js/jqplot/plugins/jqplot.pointLabels.min.js{/devblocks_url}?v={$smarty.const.APP_BUILD}"></script>
<link rel="stylesheet" type="text/css" href="{devblocks_url}c=resource&plugin=cerberusweb.reports&f=css/jqplot/jquery.jqplot.min.css{/devblocks_url}?v={$smarty.const.APP_BUILD}" />

<div id="reportChart" style="width:98%;height:{25+(32*{count($data)})}px;"></div>

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

<!-- Table -->

{if $invalidDate}<div><font color="red"><b>{$translate->_('reports.ui.invalid_date')}</b></font></div>{/if}

<table cellspacing="0" cellpadding="2" border="0">
{foreach from=$ticket_assignments item=assigned_tickets key=worker_id}
	<tr>
		<td colspan="3" style="border-bottom:1px solid rgb(200,200,200);padding-right:20px;"><h2>{$workers.$worker_id->first_name} {$workers.$worker_id->last_name}</h2></td>
	</tr>
	{foreach from=$assigned_tickets item=ticket}
	<tr>
		<td style="padding-right:20px;"><a href="{devblocks_url}c=display&id={$ticket->mask}{/devblocks_url}">{$ticket->mask}</a></td>
		<td align="left"><a href="{devblocks_url}c=display&id={$ticket->mask}{/devblocks_url}">{$ticket->subject}</a></td>
		<td>{$ticket->created_date|date_format:"%Y-%m-%d"}</td>
	</tr>
	{/foreach}
	<tr>
		<td colspan="3">&nbsp;</td>
	</tr>
{/foreach}
</table>
