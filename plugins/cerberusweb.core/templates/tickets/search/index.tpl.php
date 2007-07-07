<table cellpadding="0" cellspacing="0" width="100%" style="padding-bottom:5px;">
<tr>
	<td style="padding-right:5px;" width="0%" nowrap="nowrap"><h1>Search</h1></td>
	<td width="0%" nowrap="nowrap">
		{include file="file:$path/tickets/menu.tpl.php"}
	</td>
	<td align="right" width="100%">
		<form action="{devblocks_url}{/devblocks_url}" method="post">
		<input type="hidden" name="c" value="tickets">
		<input type="hidden" name="a" value="doQuickSearch">
		<span id="tourHeaderQuickLookup"><b>Quick Search:</b></span> <select name="type">
			<option value="mask">Ticket ID</option>
			<option value="req">Requester</option>
			<option value="subject">Subject</option>
			<option value="content">Content</option>
		</select><input type="text" name="query" size="24"><input type="submit" value="go!">
		</form>
	</td>
</tr>
</table>

<table cellpadding="0" cellspacing="0" border="0">
	<tr>
		<td valign="top" width="0%" nowrap="nowrap">
			<div id="tourDashboardSearchCriteria"></div>
			{include file="file:$path/tickets/search/criteria_list.tpl.php" divName="searchCriteriaDialog"}
			<div id="searchCriteriaDialog" style="visibility:visible;"></div>
		</td>
		<td valign="top" width="0%" nowrap="nowrap"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/spacer.gif{/devblocks_url}" width="5" height="1"></td>
		<td valign="top" width="100%">
			<div id="view{$view->id}">{include file="file:$path/tickets/ticket_view.tpl.php"}</div>
		</td>
	</tr>
</table>