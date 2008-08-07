<div id="headerSubMenu">
	<div style="padding-bottom:5px;"></div>
</div>

<h2>Worker Replies 2</h2>

<div id="reportWorkerReplies2"></div>
<div id="myContainer" style="width:800;height:600;"></div>

<script language="javascript" type="text/javascript">
	YAHOO.widget.Chart.SWFURL = "{devblocks_url}c=resource&p=cerberusweb.core&f=scripts/yui/charts/assets/charts.swf{/devblocks_url}?v={$smarty.const.APP_BUILD}";
	{literal}
	var myXHRDataSource = new YAHOO.util.DataSource("{/literal}{devblocks_url}ajax.php?c=reports&a=action&extid=report.tickets.worker_replies2&extid_a=getWorkerReplies2Report{/devblocks_url}{literal}");
	myXHRDataSource.responseType = YAHOO.util.DataSource.TYPE_TEXT; 
	myXHRDataSource.responseSchema = {
		recordDelim: "\n",
		fieldDelim: "\t",
		fields: [ "worker", "replies" ]
	};
	
	var myChart = new YAHOO.widget.ColumnChart( "myContainer", myXHRDataSource,
	{
	    xField: "worker",
	    yField: "replies"
	    //polling: 1000
	});
	{/literal}
</script>
