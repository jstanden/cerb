{if !empty($open_tickets)}
<div style="border-bottom:1px solid rgb(180,180,180);margin-bottom:10px;">
<h2 style="margin-bottom:0px;">My Open Conversations</h2>
</div>

{foreach from=$open_tickets item=ticket}
	<img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/document.gif{/devblocks_url}" align="absmiddle">
	{$ticket.t_updated_date|date_format}: 
	<a href="{devblocks_url}c=history&id={$ticket.t_mask}{/devblocks_url}" style="font-weight:normal;">{$ticket.t_subject}</a>
	<span style="font-size:85%;color:rgb(120,120,120);">({$ticket.t_mask})</span>
	<br>
{/foreach}
{/if}

{if !empty($closed_tickets)}
<div style="border-bottom:1px solid rgb(180,180,180);margin-bottom:10px;">
<h2 style="margin-bottom:0px;">My Closed Conversations</h2>
</div>

{foreach from=$closed_tickets item=ticket}
	<img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/document.gif{/devblocks_url}" align="absmiddle">
	{$ticket.t_updated_date|date_format}: 
	<a href="{devblocks_url}c=history&id={$ticket.t_mask}{/devblocks_url}" style="font-weight:normal;">{$ticket.t_subject}</a>
	<span style="font-size:85%;color:rgb(120,120,120);">({$ticket.t_mask})</span>
	<br>
{/foreach}
{/if}