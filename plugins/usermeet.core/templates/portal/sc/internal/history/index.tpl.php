{if empty($open_tickets) && empty($closed_tickets)}
	<div style="border-bottom:1px solid rgb(180,180,180);margin-bottom:10px;">
	<h1 style="margin-bottom:0px;">{$translate->_('sermeet.ui.portal.sc.public.history.ticket_history')}</h1>
	</div>

      	{assign var=tagged_active_user_email value="<b>"|cat:$active_user->email|cat:"</b>"}
      	{'portal.sc.public.history.no_messages_from_email'|devblocks_translate:$tagged_active_user_email}<br>	
{else}

	{if !empty($open_tickets)}
	<h1>{$translate->_('portal.sc.public.history.my_open_conversations')}</h1>
	{foreach from=$open_tickets item=ticket}
		<img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/document.gif{/devblocks_url}" align="absmiddle">
		{$ticket.t_updated_date|devblocks_date}: 
		<a href="{devblocks_url}c=history&id={$ticket.t_mask}{/devblocks_url}" style="font-weight:normal;">{$ticket.t_subject}</a>
		<span style="font-size:85%;">({$ticket.t_mask})</span><br>
	{/foreach}
	<br>
	{/if}
	
	{if !empty($closed_tickets)}
	<h1>{$translate->_('portal.sc.public.history.my_closed_conversations')}</h1>
	
	{foreach from=$closed_tickets item=ticket}
		<img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/document.gif{/devblocks_url}" align="absmiddle">
		{$ticket.t_updated_date|devblocks_date}: 
		<a href="{devblocks_url}c=history&id={$ticket.t_mask}{/devblocks_url}" style="font-weight:normal;">{$ticket.t_subject}</a>
		<span style="font-size:85%;">({$ticket.t_mask})</span><br>
	{/foreach}
	<br>
	{/if}
	
{/if}
