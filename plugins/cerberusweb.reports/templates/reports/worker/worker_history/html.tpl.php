{if $invalidDate}<font color="red"><b>Invalid Date specified.  Please try again.</b></font>{/if}
<br>


	<table cellspacing="0" cellpadding="2" border="0">
	{foreach from=$tickets_replied item=replied_tickets key=day}
	
	
			<tr>
				<td colspan="3" style="border-bottom:1px solid rgb(200,200,200);padding-right:20px;"><h2>{$day}</h2></td>
			</tr>
	
			{foreach from=$replied_tickets item=ticket}
			<tr>
				<!--  <td style="padding-right:20px;"><a href="{devblocks_url}c=display&a=browse&id={$ticket->mask}{/devblocks_url}">{$ticket->mask}</a></td> -->
				<td align="left"><a href="{devblocks_url}c=display&a=browse&id={$ticket->mask}{/devblocks_url}">{$ticket->subject}</a></td>
				<td style="padding-right:20px;"><a href="javascript:;" onclick="genericAjaxPanel('c=contacts&a=showAddressPeek&email={$ticket->email}&view_id=0',this,false,'500px',ajax.cbAddressPeek);">{$ticket->email}</a></td>
				<!-- <td>{$ticket->created_date|devblocks_date}</td>-->
			</tr>
			{/foreach}
		
	{/foreach}
	</table>


