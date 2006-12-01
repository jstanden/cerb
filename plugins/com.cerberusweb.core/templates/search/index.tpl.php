<table cellpadding="0" cellspacing="0" border="0">
	<tr>
		<td nowrap="nowrap" width="0%"><h1>Ticket Search</h1></td>
		<td width="100%">
		<!---
			<img src="images/spacer.gif" width="10" height="1">
			<a href="#">reset criteria</a> |
			<a href="#">save</a> |
			<a href="#">load</a>
		--->
		</td>
	</tr>
</table>
<table cellpadding="0" cellspacing="0" border="0">
	<tr>
		<td valign="top" width="0%" nowrap="nowrap">{include file="file:$path/search/criteria_list.tpl.php"}</td>
		<td valign="top" width="0%" nowrap="nowrap"><img src="images/spacer.gif" width="5" height="1"></td>
		<td valign="top" width="100%">
			{foreach from=$search item=ticket}
				{$ticket->mask}: {$ticket->subject}<br>
			{/foreach}
		</td>
	</tr>
</table>
