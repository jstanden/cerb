<fieldset class="peek">
<legend>{$translate->_('reports.ui.ticket.top_contacts')}</legend>

<form action="{devblocks_url}c=pages&page={$page->id}-{$page->name|devblocks_permalink}&report=report.tickets.top_contacts{/devblocks_url}" method="POST" id="frmRange" name="frmRange">
{$translate->_('reports.ui.date_from')} <input type="text" name="start" id="start" size="24" value="{$start}"><button type="button" onclick="devblocksAjaxDateChooser('#start','#divCal');">&nbsp;<span class="cerb-sprite sprite-calendar"></span>&nbsp;</button>
{$translate->_('reports.ui.date_to')} <input type="text" name="end" id="end" size="24" value="{$end}"><button type="button" onclick="devblocksAjaxDateChooser('#end','#divCal');">&nbsp;<span class="cerb-sprite sprite-calendar"></span>&nbsp;</button>
<div id="divCal"></div>

{$translate->_('reports.ui.date_past')} <a href="javascript:;" onclick="$('#start').val('-1 year');$('#end').val('now');$('#btnSubmit').click();">{$translate->_('reports.ui.filters.1_year')|lower}</a>
| <a href="javascript:;" onclick="$('#start').val('-6 months');$('#end').val('now');$('#btnSubmit').click();">{'reports.ui.filters.n_months'|devblocks_translate:6}</a>
| <a href="javascript:;" onclick="$('#start').val('-3 months');$('#end').val('now');$('#btnSubmit').click();">{'reports.ui.filters.n_months'|devblocks_translate:3}</a>
| <a href="javascript:;" onclick="$('#start').val('-1 month');$('#end').val('now');$('#btnSubmit').click();">{$translate->_('reports.ui.filters.1_month')|lower}</a>
| <a href="javascript:;" onclick="$('#start').val('-1 week');$('#end').val('now');$('#btnSubmit').click();">{$translate->_('reports.ui.filters.1_week')|lower}</a>
| <a href="javascript:;" onclick="$('#start').val('-1 day');$('#end').val('now');$('#btnSubmit').click();">{$translate->_('reports.ui.filters.1_day')|lower}</a>
| <a href="javascript:;" onclick="$('#start').val('today');$('#end').val('now');$('#btnSubmit').click();">{$translate->_('common.today')|lower}</a>
<br>
{if !empty($years)}
	{foreach from=$years item=year name=years}
		{if !$smarty.foreach.years.first} | {/if}<a href="javascript:;" onclick="$('#start').val('Jan 1 {$year}');$('#end').val('Dec 31 {$year} 23:59:59');$('#btnSubmit').click();">{$year}</a>
	{/foreach}
	<br>
{/if}
<br>

<label><input type="radio" name="by_address" value="0" {if 0==$by_address}checked="checked"{/if} onclick="$('#btnSubmit').click();"></input>{$translate->_('reports.ui.ticket.top_contacts.by_org')}</label>
<label><input type="radio" name="by_address" value="1" {if 1==$by_address}checked="checked"{/if} onclick="$('#btnSubmit').click();"></input>{$translate->_('reports.ui.ticket.top_contacts.by_address')}</label>

<div>
	<button type="submit" id="btnSubmit">{$translate->_('reports.common.run_report')|capitalize}</button>
</div>
</form>
</fieldset>

<!-- Chart -->

{if !empty($data)}

<!--[if IE]><script language="javascript" type="text/javascript" src="{devblocks_url}c=resource&plugin=cerberusweb.reports&f=js/jqplot/excanvas.min.js{/devblocks_url}?v={$smarty.const.APP_BUILD}"></script><![endif]-->
<script language="javascript" type="text/javascript" src="{devblocks_url}c=resource&plugin=cerberusweb.reports&f=js/jqplot/jquery.jqplot.min.js{/devblocks_url}?v={$smarty.const.APP_BUILD}"></script>
<script language="javascript" type="text/javascript" src="{devblocks_url}c=resource&plugin=cerberusweb.reports&f=js/jqplot/plugins/jqplot.barRenderer.min.js{/devblocks_url}?v={$smarty.const.APP_BUILD}"></script>
<script language="javascript" type="text/javascript" src="{devblocks_url}c=resource&plugin=cerberusweb.reports&f=js/jqplot/plugins/jqplot.pieRenderer.min.js{/devblocks_url}?v={$smarty.const.APP_BUILD}"></script>
<script language="javascript" type="text/javascript" src="{devblocks_url}c=resource&plugin=cerberusweb.reports&f=js/jqplot/plugins/jqplot.canvasTextRenderer.min.js{/devblocks_url}?v={$smarty.const.APP_BUILD}"></script>
<script language="javascript" type="text/javascript" src="{devblocks_url}c=resource&plugin=cerberusweb.reports&f=js/jqplot/plugins/jqplot.canvasAxisTickRenderer.min.js{/devblocks_url}?v={$smarty.const.APP_BUILD}"></script>
<script language="javascript" type="text/javascript" src="{devblocks_url}c=resource&plugin=cerberusweb.reports&f=js/jqplot/plugins/jqplot.canvasAxisLabelRenderer.min.js{/devblocks_url}?v={$smarty.const.APP_BUILD}"></script>
<script language="javascript" type="text/javascript" src="{devblocks_url}c=resource&plugin=cerberusweb.reports&f=js/jqplot/plugins/jqplot.categoryAxisRenderer.min.js{/devblocks_url}?v={$smarty.const.APP_BUILD}"></script>
<link rel="stylesheet" type="text/css" href="{devblocks_url}c=resource&plugin=cerberusweb.reports&f=css/jqplot/jquery.jqplot.min.css{/devblocks_url}?v={$smarty.const.APP_BUILD}" />

<div id="reportLegend" style="margin:5px;"></div>
<div id="reportChart" style="width:98%;height:350px;"></div>

<script type="text/javascript">
{foreach from=$data item=plots key=group_id}
line{$group_id} = [{foreach from=$plots key=plot item=freq name=plots}
{$freq}{if !$smarty.foreach.plots.last},{/if}
{/foreach}
];
{/foreach}

var chartData = [{foreach from=$data item=null key=group_id name=groups}line{$group_id}{if !$smarty.foreach.groups.last},{/if}{/foreach}];

var cerbChartStyle = {
	seriesColors:[
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

var chartOptions = {
    stackSeries: false,
	legend:{ 
		show:false
	},
	title:{
		show: false 
	},
	grid:{
		shadow: false,
		background:'rgb(255,255,255)',
		borderWidth:0
	},
	seriesColors: cerbChartStyle.seriesColors,	
    seriesDefaults:{
		//renderer:$.jqplot.BarRenderer,
        rendererOptions:{ 
			highlightMouseOver: false
		},
		shadow: false,
		fill:true,
		fillAndStroke:false,
		showLine:true,
		showMarker:false,
		markerOptions: {
			style:'filledCircle',
			shadow:false
		}
	},
    series:[
		{foreach from=$data key=group_id item=group name=groups}{ label:'{$labels.$group_id}' }{if !$smarty.foreach.groups.last},{/if}{/foreach}
    ],
    axes:{
        xaxis:{
		  renderer:$.jqplot.CategoryAxisRenderer,
	      tickRenderer: $.jqplot.CanvasAxisTickRenderer,
	      tickOptions: {
		  	showGridline:false,
	        {if count($xaxis_ticks) > 13}
			angle: 90,
			{/if}
	        fontSize: '8pt'
	      },
		  ticks:['{implode("','",$xaxis_ticks) nofilter}']
		}, 
        yaxis:{
		  labelRenderer: $.jqplot.CanvasAxisLabelRenderer,
		  label:'(# tickets opened)',
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

$('#reportChart').bind('jqplotPostDraw',function(event, plot) {
	$legend = $('#reportLegend');
	$legend.html('');
	len = plot.series.length;
	for(series in plot.series) {
		if(navigator.appName == 'Microsoft Internet Explorer') {
			$cell = $('<span style="margin-right:5px;display:inline-block;"><span style="background-color:'+plot.series[series].color.replace('rgba','rgb').replace(',0.8','')+';display:inline-block;padding:0px;margin:2px;width:16px;height:16px;">&nbsp;</span>'+plot.series[series].label+'</span>');
		} else {
			$cell = $('<span style="margin-right:5px;display:inline-block;"><span style="background-color:'+plot.series[series].color+';display:inline-block;padding:0px;margin:2px;width:16px;height:16px;">&nbsp;</span>'+plot.series[series].label+'</span>');
		}
		$legend.append($cell);
	}
});

var plot1 = $.jqplot('reportChart', chartData, chartOptions);
</script>

{include file="devblocks:cerberusweb.reports::reports/_shared/chart_selector.tpl"}
{/if}

<br>

<!-- Table -->

{if !empty($group_counts)}
	<table cellspacing="0" cellpadding="2" border="0">
	{foreach from=$group_counts key=org_id item=org}
		<tr>
			<td colspan="3" style="border-bottom:1px solid rgb(200,200,200);padding-right:20px;"><h2>{$group_counts.$org_id.name}</h2></td>
		</tr>
		{foreach from=$groups key=group_id item=group}
			{assign var=count_group_total value=$group_counts.$org_id.groups.$group_id.total}
			{assign var=count_group_buckets value=$group_counts.$org_id.groups.$group_id.buckets}
			
			{if !empty($count_group_total)}
				<tr>
					<td colspan="3" style="padding-left:10px;padding-right:20px;"><h3 style="margin:0px;">{$groups.$group_id->name}</h3></td>
				</tr>
				
				{if !empty($count_group_buckets.0)}
				<tr>
					<td style="padding-left:20px;padding-right:20px;">{$translate->_('common.inbox')|capitalize}</td>
					<td align="right">{$count_group_buckets.0}</td>
					<td></td>
				</tr>
				{/if}
				
				{foreach from=$group_buckets.$group_id key=bucket_id item=b}
					{if !empty($count_group_buckets.$bucket_id)}
					<tr>
						<td style="padding-left:20px;padding-right:20px;">{$b->name}</td>
						<td align="right">{$count_group_buckets.$bucket_id}</td>
						<td></td>
					</tr>
					{/if}
				{/foreach}

				<tr>
					<td></td>						
					<td align="right" style="border-top:1px solid rgb(200,200,200);"><b>{$count_group_total}</b></td>
					<td style="padding-left:10px;"></td>
				</tr>
			{/if}
		{/foreach}
		<tr>
			<td colspan="2" align="right" style="border-top:1px solid rgb(200,200,200);"><b>{$org.total}</b></td>
			<td style="padding-left:10px;"></td>
		</tr>		
	{/foreach}
	</table>
{else}
	<div><b>No data.</b></div>
{/if}
