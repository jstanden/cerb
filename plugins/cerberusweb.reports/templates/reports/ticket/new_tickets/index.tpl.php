<div id="headerSubMenu">
	<div style="padding-bottom:5px;">
	</div>
</div>

<script language="javascript" type="text/javascript">
{literal}
function drawChart(start, end) {{/literal}
	YAHOO.widget.Chart.SWFURL = "{devblocks_url}c=resource&p=cerberusweb.core&f=scripts/yui/charts/assets/charts.swf{/devblocks_url}?v={$smarty.const.APP_BUILD}";
	{literal}
	if(start==null || start=="") {
		start='-30 days'
	}
	if(end==null || end=="") {
		end='now';
	}
	start=escape(start);
	end=escape(end);
	//[mdf] first let the server tell us how many records to expect so we can make sure the chart height is high enough
	var cObj = YAHOO.util.Connect.asyncRequest('GET', "{/literal}{devblocks_url}ajax.php?c=reports&a=action&extid=report.tickets.new_tickets&extid_a=getTicketChartData{/devblocks_url}{literal}&countonly=1&start="+start+"&end="+end, {
		success: function(o) {
			var groupCount = o.responseText;
			//[mdf] set the chart size based on the number of records we will get from the datasource
			myContainer.style.cssText = 'width:100%;height:'+(30+30*groupCount);;
				
			var myXHRDataSource = new YAHOO.util.DataSource("{/literal}{devblocks_url}ajax.php?c=reports&a=action&extid=report.tickets.new_tickets&extid_a=getTicketChartData{/devblocks_url}{literal}&start="+start+"&end="+end);
			myXHRDataSource.responseType = YAHOO.util.DataSource.TYPE_TEXT; 
			myXHRDataSource.responseSchema = {
				recordDelim: "\n",
				fieldDelim: "\t",
				fields: [ "group", "total" ]
			};
			
			var myChart = new YAHOO.widget.BarChart( "myContainer", myXHRDataSource,
			{
			    xField: "total",
			    yField: "group",
				wmode: "opaque"
			    //polling: 1000
			});
		},
		failure: function(o) {},
		argument:{caller:this}
		}
	);
}{/literal}

</script>

<h2>{$translate->_('reports.ui.ticket.new_tickets')}</h2>


<form action="{devblocks_url}{/devblocks_url}" method="POST" id="frmRange" name="frmRange" onsubmit="return false;">
<input type="hidden" name="c" value="reports">
<input type="hidden" name="a" value="action">
<input type="hidden" name="extid" value="report.tickets.new_tickets">
<input type="hidden" name="extid_a" value="getNewTicketsReport">
{$translate->_('reports.ui.date_from')} <input type="text" name="start" id="start" size="10" value="{$start}"><button type="button" onclick="ajax.getDateChooser('divCal',this.form.start);">&nbsp;<img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/calendar.gif{/devblocks_url}" align="top">&nbsp;</button>
{$translate->_('reports.ui.date_to')} <input type="text" name="end" id="end" size="10" value="{$end}"><button type="button" onclick="ajax.getDateChooser('divCal',this.form.end);">&nbsp;<img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/calendar.gif{/devblocks_url}" align="top">&nbsp;</button>
<button type="button" id="btnSubmit" onclick="genericAjaxPost('frmRange', 'reportNewTickets');drawChart(document.getElementById('start').value, document.getElementById('end').value);">{$translate->_('common.refresh')|capitalize}</button>
<div id="divCal" style="display:none;position:absolute;z-index:1;"></div>
</form>

{$translate->_('reports.ui.date_past')} <a href="javascript:;" onclick="document.getElementById('start').value='-1 year';document.getElementById('end').value='now';document.getElementById('btnSubmit').click();">{$translate->_('reports.ui.filters.1_year')|lower}</a>
| <a href="javascript:;" onclick="document.getElementById('start').value='-6 months';document.getElementById('end').value='now';document.getElementById('btnSubmit').click();">{'reports.ui.filters.n_months'|devblocks_translate:6}</a>
| <a href="javascript:;" onclick="document.getElementById('start').value='-3 months';document.getElementById('end').value='now';document.getElementById('btnSubmit').click();">{'reports.ui.filters.n_months'|devblocks_translate:3}</a>
| <a href="javascript:;" onclick="document.getElementById('start').value='-1 month';document.getElementById('end').value='now';document.getElementById('btnSubmit').click();">{$translate->_('reports.ui.filters.1_month')|lower}</a>
| <a href="javascript:;" onclick="document.getElementById('start').value='-1 week';document.getElementById('end').value='now';document.getElementById('btnSubmit').click();">{$translate->_('reports.ui.filters.1_week')|lower}</a>
| <a href="javascript:;" onclick="document.getElementById('start').value='-1 day';document.getElementById('end').value='now';document.getElementById('btnSubmit').click();">{$translate->_('reports.ui.filters.1_day')|lower}</a>
| <a href="javascript:;" onclick="document.getElementById('start').value='today';document.getElementById('end').value='now';document.getElementById('btnSubmit').click();">{$translate->_('common.today')|lower}</a>
<br>
{if !empty($years)}
	{foreach from=$years item=year name=years}
		{if !$smarty.foreach.years.first} | {/if}<a href="javascript:;" onclick="document.getElementById('start').value='Jan 1 {$year}';document.getElementById('end').value='Dec 31 {$year}';document.getElementById('btnSubmit').click();">{$year}</a>
	{/foreach}
	<br>
{/if}
<br>

<div id="myContainer" style="width:100%;height:400;"></div>

<div id="reportNewTickets"></div>

<script language="javascript" type="text/javascript">
{literal}	
YAHOO.util.Event.addListener(window,'load',function(e) {
	document.getElementById('btnSubmit').click();
});
{/literal}
</script>
