<div id="headerSubMenu">
	<div style="padding-bottom:5px;"></div>
</div>

<div class="block">
<h2>{$translate->_('reports.ui.worker.worker_replies')}</h2>

<form action="{devblocks_url}c=reports&report=report.tickets.worker_replies{/devblocks_url}" method="POST" id="frmRange" name="frmRange">
<input type="hidden" name="c" value="reports">
<b>From:</b> <input type="text" name="start" id="start" size="24" value="{$start}"><button type="button" onclick="devblocksAjaxDateChooser('#start','#divCal');">&nbsp;<span class="cerb-sprite sprite-calendar"></span>&nbsp;</button>
<b>To:</b> <input type="text" name="end" id="end" size="24" value="{$end}"><button type="button" onclick="devblocksAjaxDateChooser('#end','#divCal');">&nbsp;<span class="cerb-sprite sprite-calendar"></span>&nbsp;</button>
<b>Grouping:</b> <select name="report_date_grouping">
	<option value="">-auto-</option>
	<option value="year" {if 'year'==$report_date_grouping}selected="selected"{/if}>Years</option>
	<option value="month" {if 'month'==$report_date_grouping}selected="selected"{/if}>Months</option>
	<option value="day" {if 'day'==$report_date_grouping}selected="selected"{/if}>Days</option>
</select>
<div id="divCal"></div>
<b>{$translate->_('reports.ui.filters.worker')}</b> 
<button type="button" class="chooser_worker" onclick="reportChooserClick(this);"><span class="cerb-sprite sprite-add"></span></button>
{if is_array($filter_worker_ids) && !empty($filter_worker_ids)}
<span class="chooser-container">
	{foreach from=$filter_worker_ids item=filter_worker_id}
	{$filter_worker = $workers.{$filter_worker_id}}
	{if !empty($filter_worker)}
	<div class="bubble" style="padding-right:5px;">{$filter_worker->getName()|escape}<input type="hidden" name="worker_id[]" value="{$filter_worker->id}"><a href="javascript:;" onclick="$(this).parent().remove();"><span class="ui-icon ui-icon-trash" style="display:inline-block;width:14px;height:14px;"></span></a></div>
	{/if}
	{/foreach}
</span>
{/if}
<br>

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
<br>
<button type="submit" id="btnSubmit">{$translate->_('reports.common.run_report')|capitalize}</button>
</form>
</div>

<!-- Chart -->

{if !empty($data)}

<!--[if IE]><script language="javascript" type="text/javascript" src="{devblocks_url}c=resource&plugin=cerberusweb.reports&f=js/jqplot/excanvas.min.js{/devblocks_url}?v={$smarty.const.APP_BUILD}"></script><![endif]-->
<script language="javascript" type="text/javascript" src="{devblocks_url}c=resource&plugin=cerberusweb.reports&f=js/jqplot/jquery.jqplot.min.js{/devblocks_url}?v={$smarty.const.APP_BUILD}"></script>
<script language="javascript" type="text/javascript" src="{devblocks_url}c=resource&plugin=cerberusweb.reports&f=js/jqplot/plugins/jqplot.barRenderer.min.js{/devblocks_url}?v={$smarty.const.APP_BUILD}"></script>
<script language="javascript" type="text/javascript" src="{devblocks_url}c=resource&plugin=cerberusweb.reports&f=js/jqplot/plugins/jqplot.canvasTextRenderer.min.js{/devblocks_url}?v={$smarty.const.APP_BUILD}"></script>
<script language="javascript" type="text/javascript" src="{devblocks_url}c=resource&plugin=cerberusweb.reports&f=js/jqplot/plugins/jqplot.canvasAxisTickRenderer.min.js{/devblocks_url}?v={$smarty.const.APP_BUILD}"></script>
<script language="javascript" type="text/javascript" src="{devblocks_url}c=resource&plugin=cerberusweb.reports&f=js/jqplot/plugins/jqplot.categoryAxisRenderer.min.js{/devblocks_url}?v={$smarty.const.APP_BUILD}"></script>
<script language="javascript" type="text/javascript" src="{devblocks_url}c=resource&plugin=cerberusweb.reports&f=js/jquery.qtip-1.0.0-rc3.min.js{/devblocks_url}?v={$smarty.const.APP_BUILD}"></script>
<link rel="stylesheet" type="text/css" href="{devblocks_url}c=resource&plugin=cerberusweb.reports&f=css/jqplot/jquery.jqplot.min.css{/devblocks_url}?v={$smarty.const.APP_BUILD}" />

<div id="reportLegend" style="margin:5px;"></div>
<div id="reportChart" style="width:98%;height:350px;"></div>

<script type="text/javascript">
{foreach from=$data item=plots key=worker_id}
line{$worker_id} = [{foreach from=$plots key=plot item=freq name=plots}
{$freq}{if !$smarty.foreach.plots.last},{/if}
{/foreach}
];
{/foreach}

var chartData = [
{foreach from=$data item=null key=worker_id name=groups}line{$worker_id}{if !$smarty.foreach.groups.last},{/if}{/foreach}
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

var chartOptions = {
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
		fillAndStroke:true,
		showLine:true,
		showMarker:false,
		markerOptions: {
			size:8,
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
		  	{if count($xaxis_ticks) > 94}show:false,{/if}
		  	showGridline:false,
	        {if count($xaxis_ticks) < 94 && count($xaxis_ticks) > 13}
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

$('#reportChart').bind('jqplotPostDraw',function(event, plot) {
	$legend = $('#reportLegend');
	$legend.html('');
	len = plot.series.length;
	for(series in plot.series) {
		$cell = $('<span style="margin-right:5px;display:inline-block;"><span style="background-color:'+plot.series[series].color+';display:inline-block;padding:0px;margin:2px;width:16px;height:16px;">&nbsp;</span>'+plot.series[series].label+'</span>');
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

var plot1 = $.jqplot('reportChart', chartData, chartOptions);

</script>

{include file="devblocks:cerberusweb.reports::reports/_shared/chart_selector.tpl"}
{/if}

<br>

<!-- Table -->

{if $invalidDate}<div><font color="red"><b>{$translate->_('reports.ui.invalid_date')}</b></font></div>{/if}

{if !empty($data)}
	{foreach from=$data key=worker_id item=plots name=workers}
		<div class="block" style="display:inline-block;">
		{$sum = 0}
		<h2>{$workers.{$worker_id}->getName()}</h2>
		{foreach from=$plots key=plot item=data name=plots}
			{$plot}: {$data}<br>
			{$sum = $sum + $data}
		{/foreach}
		<b>Sum: {$sum}</b><br>
		<b>Mean: {($sum/count($plots))|string_format:"%0.2f"}</b><br>
		</div>
	{/foreach}
{else}
<div><b>No data.</b></div>
{/if}

<br>

<script type="text/javascript">
	function reportChooserClick(button) {
		$chooser=genericAjaxPopup('chooser','c=internal&a=chooserOpen&context=cerberusweb.contexts.worker',null,true,'750');
		$chooser.one('chooser_save', function(event) {
			event.stopPropagation();
			$button = $(button);
			$label = $button.next('span.chooser-container');
			if(0==$label.length)
				$label = $('<span class="chooser-container"></span>').insertAfter($button);
			for(idx in event.labels) {
				if(0==$label.find('input:hidden[value='+event.values[idx]+']').length) {
					$label.append($('<div class="bubble" style="padding-right:5px;">'+event.labels[idx]+'<input type="hidden" name="worker_id[]" value="'+event.values[idx]+'"><a href="javascript:;" onclick="$(this).parent().remove();"><span class="ui-icon ui-icon-trash" style="display:inline-block;width:14px;height:14px;"></span></a></div>'));
				}
			}
		});
	}
</script>