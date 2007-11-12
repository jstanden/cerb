{if empty($open_tickets) && empty($closed_tickets)}
	<h1>Ticket History</h1>
	
	We don't have a record of any messages sent from <b>{$active_user->email}</b>.<br>
{else}

	{if !empty($open_tickets)}
	<h1>My Open Conversations</h1>
	{foreach from=$open_tickets item=ticket}
		<img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/document.gif{/devblocks_url}" align="absmiddle">
		{$ticket.t_updated_date|date_format}: 
		<a href="{devblocks_url}c=history&id={$ticket.t_mask}{/devblocks_url}" style="font-weight:normal;">{$ticket.t_subject}</a>
		<span style="font-size:85%;">({$ticket.t_mask})</span>
		<br>
	{/foreach}
	{/if}
	
	{if !empty($closed_tickets)}
	<h1>My Closed Conversations</h1>
	
	{foreach from=$closed_tickets item=ticket}
		<img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/document.gif{/devblocks_url}" align="absmiddle">
		{$ticket.t_updated_date|date_format}: 
		<a href="{devblocks_url}c=history&id={$ticket.t_mask}{/devblocks_url}" style="font-weight:normal;">{$ticket.t_subject}</a>
		<span style="font-size:85%;">({$ticket.t_mask})</span>
		<br>
	{/foreach}
	{/if}
	
{/if}