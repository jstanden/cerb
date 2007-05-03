{if !empty($log_events)}
<table cellpadding="2" cellspacing="0" width="100%">
	<tr style="background-color:rgb(240,240,240);">
		<td width="0%" nowrap="nowrap" style="border-bottom:1px solid rgb(200,200,200);border-right:1px solid rgb(200,200,200);" align="center"><b>Timestamp</b></td>
		<td width="100%" style="border-bottom:1px solid rgb(200,200,200);"><b>Event</b></td>
	</tr>

	{foreach from=$log_events item=log_item name=log_events}
	<tr>
		<td width="0%" nowrap="nowrap" style="border-right:1px solid rgb(220,220,220);border-bottom:1px solid rgb(220,220,220);" valign="top" align="right">
			[[ Timestamp ]]
		</td>
		<td width="100%" valign="top" style="border-bottom:1px solid rgb(220,220,220);">
			[[ Event ]]
		</td>
	</tr>
	{/foreach}
</table>
{else}
	No events are logged for this ticket.
{/if}