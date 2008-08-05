<div id="headerSubMenu">
	<div style="padding-bottom:5px;">
	</div>
</div>

<h2>Average Response Time</h2>

<div id="reportAverageResponseTime"></div>

<script language="javascript" type="text/javascript">
{literal}
YAHOO.util.Event.addListener(window,'load',function(e) {
	genericAjaxGet('reportAverageResponseTime','c=reports&a=action&extid=report.workers.averageresponsetime&extid_a=getAverageResponseTimeReport&age=30');
});
{/literal}
</script>
