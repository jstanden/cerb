<div id="headerSubMenu">
	<div style="padding-bottom:5px;">
	</div>
</div>

<h1>Reports</h1>

<table cellpadding="5" cellspacing="0" border="0" width="100%">
	<tr>
		<td valign="top">
			<div id="reportNewTickets"></div>
		</td>
		<td valign="top">
			<div id="reportWorkerReplies"></div>
		</td>
	</tr>
	
	<!-- 
	<tr>
		<td valign="top">
			<div id="reportAverageResponseTime"></div>
		</td>
		<td valign="top">
			<div id="xxxxx"></div>
		</td>
	</tr>
	 -->
	
	<tr>
		<td><br></td>
		<td><br></td>
	</tr>
	
</table>

<script>
{literal}
YAHOO.util.Event.addListener(window,'load',function(e) {
	genericAjaxGet('reportNewTickets','c=reports&a=getNewTicketsReport&age=30');
	genericAjaxGet('reportWorkerReplies','c=reports&a=getWorkerRepliesReport&age=30');
	//genericAjaxGet('reportAverageResponseTime','c=reports&a=getAverageResponseTimeReport&age=30');
});
{/literal}
</script>

