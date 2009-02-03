<div id="headerSubMenu">
	<div style="padding-bottom:5px;">
	</div>
</div>

<script language="javascript" type="text/javascript">
{literal}
function drawChart() {{/literal}
	YAHOO.widget.Chart.SWFURL = "{devblocks_url}c=resource&p=cerberusweb.core&f=scripts/yui/charts/assets/charts.swf{/devblocks_url}?v={$smarty.const.APP_BUILD}";
	{literal}
	//[mdf] first let the server tell us how many records to expect so we can make sure the chart height is high enough
	var cObj = YAHOO.util.Connect.asyncRequest('GET', "{/literal}{devblocks_url}ajax.php?c=reports&a=action&extid=report.tickets.waiting_tickets&extid_a=getWaitingTicketsChart{/devblocks_url}{literal}&countonly=1", {
		success: function(o) {
			var groupCount = o.responseText;

			//[mdf] set the chart size based on the number of records we will get from the datasource
			myContainer.style.cssText = 'width:100%;height:'+(30+30*groupCount);;
				
			var myXHRDataSource = new YAHOO.util.DataSource("{/literal}{devblocks_url}ajax.php?c=reports&a=action&extid=report.tickets.waiting_tickets&extid_a=getWaitingTicketsChart{/devblocks_url}{literal}");
			myXHRDataSource.responseType = YAHOO.util.DataSource.TYPE_TEXT; 
			myXHRDataSource.responseSchema = {
				recordDelim: "\n",
				fieldDelim: "\t",
				fields: [
					"group",
					{key:"total", parser:"number"}
				]
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

<h2>{$translate->_('reports.ui.ticket.top_contacts')}</h2>


<form action="{devblocks_url}{/devblocks_url}" method="POST" id="frmRange" name="frmRange" onsubmit="return false;">
<input type="hidden" name="c" value="reports">
<input type="hidden" name="a" value="action">
<input type="hidden" name="extid" value="report.tickets.waiting_tickets">
<input type="hidden" name="extid_a" value="getWaitingTicketsReport">
<button type="button" id="btnSubmit" onclick="genericAjaxPost('frmRange', 'report');drawChart();">{$translate->_('common.refresh')|capitalize}</button>
</form>

<br>

<div id="myContainer" style="width:100%;height:400;"></div>

<div id="report"></div>

<script language="javascript" type="text/javascript">
{literal}
YAHOO.util.Event.addListener(window,'load',function(e) {
	document.getElementById('btnSubmit').click();
});
{/literal}
</script>
