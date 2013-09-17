<fieldset class="peek">
<legend>{'timetracking.ui.reports.time_spent_org'|devblocks_translate}</legend>

<form action="{devblocks_url}c=pages&page={$page->id}-{$page->name|devblocks_permalink}&report=report.timetracking.timespentorg{/devblocks_url}" method="POST" id="frmRange" name="frmRange">
<b>{'timetracking.ui.reports.from'|devblocks_translate}</b> <input type="text" name="start" id="start" size="24" value="{$start}"><button type="button" onclick="devblocksAjaxDateChooser('#start','#divCal');">&nbsp;<span class="cerb-sprite sprite-calendar"></span>&nbsp;</button>
<b>{'timetracking.ui.reports.to'|devblocks_translate}</b> <input type="text" name="end" id="end" size="24" value="{$end}"><button type="button" onclick="devblocksAjaxDateChooser('#end','#divCal');">&nbsp;<span class="cerb-sprite sprite-calendar"></span>&nbsp;</button>
<b>Grouping:</b> <select name="report_date_grouping">
	<option value="">-auto-</option>
	<option value="year" {if 'year'==$report_date_grouping}selected="selected"{/if}>Years</option>
	<option value="month" {if 'month'==$report_date_grouping}selected="selected"{/if}>Months</option>
	<option value="week" {if 'week'==$report_date_grouping}selected="selected"{/if}>Weeks</option>
	<option value="day" {if 'day'==$report_date_grouping}selected="selected"{/if}>Days</option>
	<option value="hour" {if 'hour'==$report_date_grouping}selected="selected"{/if}>Hours</option>
</select>
<div id="divCal"></div>

<b>{'timetracking.ui.reports.past'|devblocks_translate}</b> <a href="javascript:;" onclick="$('#start').val('-1 year');$('#end').val('now');">{'timetracking.ui.reports.filters.1_year'|devblocks_translate|lower}</a>
| <a href="javascript:;" onclick="$('#start').val('-6 months');$('#end').val('now');">{'timetracking.ui.reports.filters.n_months'|devblocks_translate:6}</a>
| <a href="javascript:;" onclick="$('#start').val('-3 months');$('#end').val('now');">{'timetracking.ui.reports.filters.n_months'|devblocks_translate:3}</a>
| <a href="javascript:;" onclick="$('#start').val('-1 month');$('#end').val('now');">{'timetracking.ui.reports.filters.1_month'|devblocks_translate|lower}</a>
| <a href="javascript:;" onclick="$('#start').val('-1 week');$('#end').val('now');">{'timetracking.ui.reports.filters.1_week'|devblocks_translate|lower}</a>
| <a href="javascript:;" onclick="$('#start').val('-1 day');$('#end').val('now');">{'timetracking.ui.reports.filters.1_day'|devblocks_translate|lower}</a>
| <a href="javascript:;" onclick="$('#start').val('today');$('#end').val('now');">{'common.today'|devblocks_translate|lower}</a>
<br>

<b>{'reports.ui.filters.worker'|devblocks_translate}</b> 
<button type="button" class="chooser_worker"><span class="cerb-sprite sprite-view"></span></button>
<ul class="chooser-container bubbles">
{if is_array($filter_worker_ids) && !empty($filter_worker_ids)}
	{foreach from=$filter_worker_ids item=filter_worker_id}
	{$filter_worker = $workers.{$filter_worker_id}}
	{if !empty($filter_worker)}
	<li>{$filter_worker->getName()}<input type="hidden" name="worker_id[]" value="{$filter_worker->id}"><a href="javascript:;" onclick="$(this).parent().remove();"><span class="ui-icon ui-icon-trash" style="display:inline-block;width:14px;height:14px;"></span></a></li>
	{/if}
	{/foreach}
{/if}
</ul>
<br>

<b>{'reports.ui.filters.org'|devblocks_translate}</b> 
<button type="button" class="chooser_org"><span class="cerb-sprite sprite-view"></span></button>
{if is_array($filter_org_ids) && !empty($filter_org_ids)}
<ul class="chooser-container bubbles">
	{foreach from=$filter_org_ids item=filter_org_id}
	{$filter_org = $orgs.{$filter_org_id}}
	{if !empty($filter_org)}
	<li>{$filter_org->name}<input type="hidden" name="org_id[]" value="{$filter_org_id}"><a href="javascript:;" onclick="$(this).parent().remove();"><span class="ui-icon ui-icon-trash" style="display:inline-block;width:14px;height:14px;"></span></a></ul>
	{/if}
	{/foreach}
</ul>
{/if}

<div>
	<button type="submit" id="btnSubmit">{'reports.common.run_report'|devblocks_translate|capitalize}</button>
</div>
</form>
</fieldset>

{if $invalidDate}<div><font color="red"><b>{'timetracking.ui.reports.invalid_date'|devblocks_translate}</b></font></div>{/if}

<!-- Chart -->

{if !empty($chart_data)}

<!--[if IE]><script language="javascript" type="text/javascript" src="{devblocks_url}c=resource&plugin=cerberusweb.reports&f=js/jqplot/excanvas.min.js{/devblocks_url}?v={$smarty.const.APP_BUILD}"></script><![endif]-->
<script language="javascript" type="text/javascript" src="{devblocks_url}c=resource&plugin=cerberusweb.reports&f=js/jqplot/jquery.jqplot.min.js{/devblocks_url}?v={$smarty.const.APP_BUILD}"></script>
<script language="javascript" type="text/javascript" src="{devblocks_url}c=resource&plugin=cerberusweb.reports&f=js/jqplot/plugins/jqplot.barRenderer.min.js{/devblocks_url}?v={$smarty.const.APP_BUILD}"></script>
<script language="javascript" type="text/javascript" src="{devblocks_url}c=resource&plugin=cerberusweb.reports&f=js/jqplot/plugins/jqplot.canvasTextRenderer.min.js{/devblocks_url}?v={$smarty.const.APP_BUILD}"></script>
<script language="javascript" type="text/javascript" src="{devblocks_url}c=resource&plugin=cerberusweb.reports&f=js/jqplot/plugins/jqplot.canvasAxisTickRenderer.min.js{/devblocks_url}?v={$smarty.const.APP_BUILD}"></script>
<script language="javascript" type="text/javascript" src="{devblocks_url}c=resource&plugin=cerberusweb.reports&f=js/jqplot/plugins/jqplot.canvasAxisLabelRenderer.min.js{/devblocks_url}?v={$smarty.const.APP_BUILD}"></script>
<script language="javascript" type="text/javascript" src="{devblocks_url}c=resource&plugin=cerberusweb.reports&f=js/jqplot/plugins/jqplot.categoryAxisRenderer.min.js{/devblocks_url}?v={$smarty.const.APP_BUILD}"></script>
<link rel="stylesheet" type="text/css" href="{devblocks_url}c=resource&plugin=cerberusweb.reports&f=css/jqplot/jquery.jqplot.min.css{/devblocks_url}?v={$smarty.const.APP_BUILD}" />

<div id="reportLegend" style="margin:5px;"></div>
<div id="reportChart" style="width:98%;height:350px;"></div>

<script type="text/javascript">
{foreach from=$chart_data item=plots key=org_id}
line{$org_id} = [{foreach from=$plots key=plot item=freq name=plots}
{$freq}{if !$smarty.foreach.plots.last},{/if}
{/foreach}
];
{/foreach}

chartData = [
{foreach from=$chart_data item=null key=org_id name=orgs}line{$org_id}{if !$smarty.foreach.orgs.last},{/if}{/foreach}
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

chartOptions = {
    stackSeries: true,
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
		renderer:$.jqplot.BarRenderer,
        rendererOptions:{ 
			highlightMouseOver: true,
			barPadding:0,
			barMargin:0
		},
		shadow: false,
		fill:true,
		fillAndStroke:false,
		showLine:true,
		showMarker:false,
		markerOptions: {
			size:8,
			style:'filledCircle',
			shadow:false
		}
	},
    series:[
		{foreach from=$chart_data key=org_id item=org name=orgs}{ label:'{$orgs.$org_id->name}' }{if !$smarty.foreach.orgs.last},{/if}{/foreach}
    ],
    axes:{
        xaxis:{
		  renderer:$.jqplot.CategoryAxisRenderer,
	      tickRenderer: $.jqplot.CanvasAxisTickRenderer,
	      tickOptions: {
		  	{if count($xaxis_ticks) > 94}show:false,{/if}
		  	showGridline:false,
	        {if count($xaxis_ticks) < 94 && count($xaxis_ticks) > 13}
			angle: 90,
			{/if}
	        fontSize: '8pt'
	      },
		  ticks:['{implode("','",$xaxis_ticks) nofilter}']
		}, 
        yaxis:{
		  labelRenderer: $.jqplot.CanvasAxisLabelRenderer,
		  label:'(mins)',
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

var reportTooltip = $('#reportChart').qtip({
	show: {
		when:{ event:'jqplotDataHighlight' }
	},
	hide:{
		when:{ event:'jqplotDataUnhighlight' }
	},
	style:{
		name:'cream',
		tip:{ corner:'bottomLeft' },
		border:{
			radius:3,
			width:5
		}
	},
	position:{
		target:'mouse',
		corner:{
			tooltip:'bottomLeft',
			target:'topMiddle'
		},
		adjust:{
			x:-5
		}
	}
});

$('#reportChart').bind('jqplotDataHighlight',function(event, seriesIndex, pointIndex, data) {
	tooltip = $('#reportChart').qtip("api");
	
	str = "";
	
	if(!plot1 || !plot1.series || !plot1.series[seriesIndex])
		return;
	
	if(plot1.series[seriesIndex].label)
		str += plot1.series[seriesIndex].label;
	
	if(plot1.axes.xaxis.ticks[pointIndex])
		str += "<br>(" + plot1.axes.xaxis.ticks[pointIndex] + ")";
	
	tooltip.updateContent(str, true);
});

plot1 = $.jqplot('reportChart', chartData, chartOptions);	
</script>

{include file="devblocks:cerberusweb.reports::reports/_shared/chart_selector.tpl"}
{/if}

<br>

<!-- Table -->

{if $invalidDate}
	<div><font color="red"><b>{'reports.ui.invalid_date'|devblocks_translate}</b></font></div>
{/if}

{if !empty($view)}
	{include file="devblocks:cerberusweb.core::internal/views/search_and_view.tpl" view=$view}
{/if}

{if !empty($data)}
	{$sums = array()}
	<div>
		<table cellpadding="5" cellspacing="0" border="0">
		<tr>
			<td></td>
			{foreach from=$data key=org_id item=plots}
				<td style="font-weight:bold;" nowrap="nowrap">{$orgs.{$org_id}->name}</td>
			{/foreach}
		</tr>
		{foreach from=$xaxis_ticks item=tick}
		<tr>
			<td style="border-bottom:1px solid rgb(200,200,200);"><b>{$tick}</b></td>
			{foreach from=$data item=plots key=org_id}
				<td align="center" style="border-bottom:1px solid rgb(200,200,200);">
				{if isset($plots.$tick)}
					{$plots.$tick}
					{$sums[$org_id] = intval($sums.$org_id) + $plots.$tick}
				{/if}
				</td>
			{/foreach}
		</tr>
		{/foreach}
		{if count($xaxis_ticks) > 10}
		<tr>
			<td></td>
			{foreach from=$data key=org_id item=plots}
				<td style="font-weight:bold;" nowrap="nowrap">{$orgs.{$org_id}->name}</td>
			{/foreach}
		</tr>
		{/if}
		<tr>
			<td align="right">Sum</td>
			{foreach from=$sums key=org_id item=sum}
				<td align="center">
					<b>{$sum}</b>
				</td>
			{/foreach}
		</tr>
		<tr>
			<td align="right">Mean</td>
			{foreach from=$sums key=org_id item=sum}
				<td align="center">
					<b>{($sum/count($xaxis_ticks))|string_format:"%0.2f"}</b>
				</td>
			{/foreach}
		</tr>
		</table>
	</div>

{else}
	<div><b>No data.</b></div>
{/if}

<script type="text/javascript">
	$('#frmRange button.chooser_worker').each(function(event) {
		ajax.chooser(this,'cerberusweb.contexts.worker','worker_id', { autocomplete:true });
	});
	
	$('#frmRange button.chooser_org').each(function(event) {
		ajax.chooser(this,'cerberusweb.contexts.org','org_id', { autocomplete:true });
	});	
</script>

<br>