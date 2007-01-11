<table cellpadding="0" cellspacing="0" border="0">
	<tr>
		<td nowrap="nowrap" width="0%"><h1>Ticket Search</h1></td>
		<td width="100%"></td>
	</tr>
</table>
<table cellpadding="0" cellspacing="0" border="0">
	<tr>
		<td valign="top" width="0%" nowrap="nowrap">
			{include file="file:$path/search/criteria_list.tpl.php" divName="searchCriteriaDialog"}
			<div id="searchCriteriaDialog" style="visibility:visible;"></div>
		</td>
		<td valign="top" width="0%" nowrap="nowrap"><img src="{devblocks_url}images/spacer.gif{/devblocks_url}" width="5" height="1"></td>
		<td valign="top" width="100%">
			{*foreach from=$search item=ticket}
				{$ticket->mask}: {$ticket->subject}<br>
			{/foreach*}
			<div id="view{$view->id}">{include file="file:$path/dashboards/ticket_view.tpl.php"}</div>
		</td>
	</tr>
</table>