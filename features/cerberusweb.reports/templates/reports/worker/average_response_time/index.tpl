<div id="headerSubMenu">
	<div style="padding-bottom:5px;">
	</div>
</div>

<h2>{$translate->_('reports.ui.worker.response_time')}</h2>

<div id="reportAverageResponseTime" style="background-color:rgb(255,255,255);"></div>

<script language="javascript" type="text/javascript">
{literal}
YAHOO.util.Event.addListener(window,'load',function(e) {
	genericAjaxGet('reportAverageResponseTime','c=reports&a=action&extid=report.workers.averageresponsetime&extid_a=getAverageResponseTimeReport&age=30');
});
{/literal}
</script>
