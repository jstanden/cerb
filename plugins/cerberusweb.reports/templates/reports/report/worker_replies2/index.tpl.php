<div id="headerSubMenu">
	<div style="padding-bottom:5px;">
	</div>
</div>

<h2>Worker Replies 2</h2>

<div id="reportWorkerReplies2"></div>
<div id="myContainer">
</div>
<script language="javascript" type="text/javascript">
//YAHOO.widget.Chart.SWFURL = "http://yui.yahooapis.com/2.5.2/build/charts/assets/charts.swf"; 
YAHOO.widget.Chart.SWFURL = "{devblocks_url}c=resource&p=cerberusweb.core&f=scripts/yui/charts/assets/charts.swf{/devblocks_url}?v={$smarty.const.APP_BUILD}";
{literal}
var myXHRDataSource = new YAHOO.widget.DS_XHR("http://localhost/cerberus4/ajax.php?c=reports&a=action&extid=report.tickets.worker_replies2&extid_a=getWorkerReplies2Report&age=30", ["\n", "\t"] );
myXHRDataSource.responseType = YAHOO.widget.DS_XHR.TYPE_FLAT; 
myXHRDataSource.responseSchema =
{
    fields: [ "worker","replies" ]
};

var myChart = new YAHOO.widget.ColumnChart( "myContainer", myXHRDataSource,
{
    xField: "worker",
    yField: "replies"
});
{/literal}
</script>
