{if $invalidDate}<font color="red"><b>Invalid Date specified.  Please try again.</b></font>{/if}
<br>


	<table cellspacing="0" cellpadding="2" border="0">
	{foreach from=$ticket_assignments item=assigned_tickets key=worker_id}
	
	
			<tr>
				<td colspan="3" style="border-bottom:1px solid rgb(200,200,200);padding-right:20px;"><h2>{$workers.$worker_id->first_name} {$workers.$worker_id->last_name}</h2></td>
			</tr>
	
			{foreach from=$assigned_tickets item=ticket}
			<tr>
				<td style="padding-right:20px;"><a href="{devblocks_url}c=display&a=browse&id={$ticket->mask}{/devblocks_url}">{$ticket->mask}</a></td>
				<td align="left"><a href="{devblocks_url}c=display&a=browse&id={$ticket->mask}{/devblocks_url}">{$ticket->subject}</a></td>
				<td>{$ticket->created_date|date_format:"%Y-%m-%d"}</td>
			</tr>
			{/foreach}
		
	{/foreach}
	</table>


