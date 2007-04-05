<table cellpadding="0" cellspacing="0">
<tr>
	<td style="padding-right:5px;"><h1>Search</h1></td>
	<td>
		{include file="file:$path/tickets/menu.tpl.php"}
	</td>
</tr>
</table>

<table cellpadding="0" cellspacing="0" border="0">
	<tr>
		<td valign="top" width="0%" nowrap="nowrap">
			{include file="file:$path/tickets/search/criteria_list.tpl.php" divName="searchCriteriaDialog"}
			<div id="searchCriteriaDialog" style="visibility:visible;"></div>
		</td>
		<td valign="top" width="0%" nowrap="nowrap"><img src="{devblocks_url}images/spacer.gif{/devblocks_url}" width="5" height="1"></td>
		<td valign="top" width="100%">
			<div id="view{$view->id}">{include file="file:$path/tickets/ticket_view.tpl.php"}</div>
		</td>
	</tr>
</table>