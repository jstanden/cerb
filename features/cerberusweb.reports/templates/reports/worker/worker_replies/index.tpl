<div id="headerSubMenu">
	<div style="padding-bottom:5px;"></div>
</div>

<h2>{$translate->_('reports.ui.worker.worker_replies')}</h2>

<form action="{devblocks_url}c=reports&report=report.tickets.worker_replies{/devblocks_url}" method="POST" id="frmRange" name="frmRange">
<input type="hidden" name="c" value="reports">
From: <input type="text" name="start" id="start" size="24" value="{$start}"><button type="button" onclick="devblocksAjaxDateChooser('#start','#divCal');">&nbsp;<span class="cerb-sprite sprite-calendar"></span>&nbsp;</button>
To: <input type="text" name="end" id="end" size="24" value="{$end}"><button type="button" onclick="devblocksAjaxDateChooser('#end','#divCal');">&nbsp;<span class="cerb-sprite sprite-calendar"></span>&nbsp;</button>
<button type="submit" id="btnSubmit">{$translate->_('common.refresh')|capitalize}</button>
<div id="divCal"></div>
</form>

{$translate->_('reports.ui.date_past')} <a href="javascript:;" onclick="document.getElementById('start').value='-1 year';document.getElementById('end').value='now';$('#btnSubmit').click();">{$translate->_('reports.ui.filters.1_year')|lower}</a>
| <a href="javascript:;" onclick="document.getElementById('start').value='-6 months';document.getElementById('end').value='now';$('#btnSubmit').click();">{'reports.ui.filters.n_months'|devblocks_translate:6}</a>
| <a href="javascript:;" onclick="document.getElementById('start').value='-3 months';document.getElementById('end').value='now';$('#btnSubmit').click();">{'reports.ui.filters.n_months'|devblocks_translate:3}</a>
| <a href="javascript:;" onclick="document.getElementById('start').value='-1 month';document.getElementById('end').value='now';$('#btnSubmit').click();">{$translate->_('reports.ui.filters.1_month')|lower}</a>
| <a href="javascript:;" onclick="document.getElementById('start').value='-1 week';document.getElementById('end').value='now';$('#btnSubmit').click();">{$translate->_('reports.ui.filters.1_week')|lower}</a>
| <a href="javascript:;" onclick="document.getElementById('start').value='-1 day';document.getElementById('end').value='now';$('#btnSubmit').click();">{$translate->_('reports.ui.filters.1_day')|lower}</a>
| <a href="javascript:;" onclick="document.getElementById('start').value='today';document.getElementById('end').value='now';$('#btnSubmit').click();">{$translate->_('common.today')|lower}</a>
<br>
{if !empty($years)}
	{foreach from=$years item=year name=years}
		{if !$smarty.foreach.years.first} | {/if}<a href="javascript:;" onclick="document.getElementById('start').value='Jan 1 {$year}';document.getElementById('end').value='Dec 31 {$year}';$('#btnSubmit').click();">{$year}</a>
	{/foreach}
	<br>
{/if}

<!-- Chart -->

{if !empty($data)}

<!--[if IE]><script language="javascript" type="text/javascript" src="{devblocks_url}c=resource&plugin=cerberusweb.reports&f=js/jqplot/excanvas.min.js{/devblocks_url}?v={$smarty.const.APP_BUILD}"></script><![endif]-->
<script language="javascript" type="text/javascript" src="{devblocks_url}c=resource&plugin=cerberusweb.reports&f=js/jqplot/jquery.jqplot.min.js{/devblocks_url}?v={$smarty.const.APP_BUILD}"></script>
<script language="javascript" type="text/javascript" src="{devblocks_url}c=resource&plugin=cerberusweb.reports&f=js/jqplot/plugins/jqplot.barRenderer.min.js{/devblocks_url}?v={$smarty.const.APP_BUILD}"></script>
<script language="javascript" type="text/javascript" src="{devblocks_url}c=resource&plugin=cerberusweb.reports&f=js/jqplot/plugins/jqplot.canvasTextRenderer.min.js{/devblocks_url}?v={$smarty.const.APP_BUILD}"></script>
<script language="javascript" type="text/javascript" src="{devblocks_url}c=resource&plugin=cerberusweb.reports&f=js/jqplot/plugins/jqplot.canvasAxisTickRenderer.min.js{/devblocks_url}?v={$smarty.const.APP_BUILD}"></script>
<script language="javascript" type="text/javascript" src="{devblocks_url}c=resource&plugin=cerberusweb.reports&f=js/jqplot/plugins/jqplot.categoryAxisRenderer.min.js{/devblocks_url}?v={$smarty.const.APP_BUILD}"></script>
<link rel="stylesheet" type="text/css" href="{devblocks_url}c=resource&plugin=cerberusweb.reports&f=css/jqplot/jquery.jqplot.min.css{/devblocks_url}?v={$smarty.const.APP_BUILD}" />

<div id="reportChart" style="width:98%;height:350px;"></div>

<script type="text/javascript">
{foreach from=$data item=plots key=worker_id}
line{$worker_id} = [{foreach from=$plots key=plot item=freq name=plots}
{$freq}{if !$smarty.foreach.plots.last},{/if}
{/foreach}
];
{/foreach}

chartData = [
{foreach from=$data item=null key=worker_id name=groups}line{$worker_id}{if !$smarty.foreach.groups.last},{/if}{/foreach}
];

chartOptions = {
    stackSeries: true,
	legend:{ 
		show:true,
		location:'nw'
	},
	title:{
		show: false 
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
        rendererOptions:{ 
			highlightMouseOver: false
		},
		shadow: false,
		fill:true,
		fillAndStroke:true,
		//fillAlpha:0.7,
		showLine:true,
		showMarker:false,
		markerOptions: {
			style:'filledCircle',
			shadow:false
		}
	},
    series:[
		{foreach from=$data key=worker_id item=worker name=groups}{ label:'{$workers.$worker_id->getName()|escape}' }{if !$smarty.foreach.groups.last},{/if}{/foreach}
    ],
    axes:{
        xaxis:{
		  renderer:$.jqplot.CategoryAxisRenderer,
	      tickRenderer: $.jqplot.CanvasAxisTickRenderer,
	      tickOptions: {
	        {if count($xaxis_ticks) > 13}
			angle: 90,
			{/if}
	        fontSize: '8pt'
	      },
		  ticks:['{implode("','",$xaxis_ticks)}']
		}, 
        yaxis:{
		  min:0,
		  autoscale:true,
		  tickRenderer: $.jqplot.CanvasAxisTickRenderer,
		  tickOptions:{
		  	formatString:'%d',
			fontSize: '8pt'
		  }
		}
    }
};

plot1 = $.jqplot('reportChart', chartData, chartOptions);	
</script>

{include file="devblocks:cerberusweb.reports::reports/_shared/chart_selector.tpl"}
{/if}

<br>

<!-- Table -->

{if $invalidDate}<div><font color="red"><b>{$translate->_('reports.ui.invalid_date')}</b></font></div>{/if}

{if !empty($worker_counts)}
	<table cellspacing="0" cellpadding="2" border="0">
	{foreach from=$workers item=worker key=worker_id}
		{if !empty($worker_counts.$worker_id)}
		{assign var=counts value=$worker_counts.$worker_id}
		
		<tr>
			<td colspan="3" style="border-bottom:1px solid rgb(200,200,200);"><h2>{$workers.$worker_id->getName()}</h2></td>
		</tr>
		
		{foreach from=$counts item=team_hits key=team_id}
			{if is_numeric($team_id)}
			<tr>
				<td style="padding-right:20px;">{$groups.$team_id->name}</td>
				<td align="right">{$team_hits}</td>
				<td></td>
			</tr>
			{/if}
		{/foreach}
		
		<tr>
			<td></td>
			<td style="border-top:1px solid rgb(200,200,200);" align="right"><b>{$counts.total}</b></td>
			{math assign="replies_per_day" equation="x/y" x=$counts.total y=$age_dur format="%0.2f"}
			<td style="padding-left:10px;"><b>avg: {'reports.ui.average_per_day'|devblocks_translate:$replies_per_day} </b></td>
		</tr>
		
		{/if}
	{/foreach}
	</table>
{else}
<div><b>No data.</b></div>
{/if}
