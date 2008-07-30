<div id="headerSubMenu">
	<div style="padding-bottom:5px;">
	</div>
</div>

<h2>New Tickets</h2>

<div id="reportNewTickets"></div>

<script language="javascript" type="text/javascript">
{literal}
YAHOO.util.Event.addListener(window,'load',function(e) {
	genericAjaxGet('reportNewTickets','c=reports&a=action&extid=report.tickets.new_tickets&extid_a=getNewTicketsReport&age=30');
});
{/literal}
</script>
