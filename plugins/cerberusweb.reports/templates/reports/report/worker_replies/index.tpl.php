<div id="headerSubMenu">
	<div style="padding-bottom:5px;">
	</div>
</div>

<h2>Worker Replies</h2>

<div id="reportWorkerReplies"></div>

<script language="javascript" type="text/javascript">
{literal}
YAHOO.util.Event.addListener(window,'load',function(e) {
	genericAjaxGet('reportWorkerReplies','c=reports&a=action&extid=report.tickets.worker_replies&extid_a=getWorkerRepliesReport&age=30');
});
{/literal}
</script>
