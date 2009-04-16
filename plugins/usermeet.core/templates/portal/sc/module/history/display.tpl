<div id="history">
	
<div class="header"><h1>{$translate->_('portal.sc.public.history.ticket_history')}</h1></div>

<h2>{$ticket.t_subject|escape}</h2>

<form action="{devblocks_url}c=history{/devblocks_url}" method="post" name="">
<input type="hidden" name="a" value="saveTicketProperties">
<input type="hidden" name="mask" value="{$ticket.t_mask}">
<input type="hidden" name="closed" value="{if $ticket.t_is_closed}1{else}0{/if}">
	<b>{$translate->_('portal.sc.public.history.reference')}</b> {$ticket.t_mask}
	 &nbsp; 
	<b>{$translate->_('ticket.updated')|capitalize}:</b> <abbr title="{$ticket.t_updated_date|devblocks_date}">{$ticket.t_updated_date|devblocks_prettytime}</abbr>
	 &nbsp; 
	<br>
	
	<div style="padding:5px;">
		{if $ticket.t_is_closed}
		<button type="button" onclick="this.form.closed.value='0';this.form.submit();"><img src="{devblocks_url}c=resource&p=usermeet.core&f=images/folder_out.gif{/devblocks_url}" align="top"> {$translate->_('common.reopen')|capitalize}</button>
		{else}
		<button type="button" onclick="this.form.closed.value='1';this.form.submit();"><img src="{devblocks_url}c=resource&p=usermeet.core&f=images/folder_ok.gif{/devblocks_url}" align="top"> {$translate->_('common.close')|capitalize}</button>
		{/if}
	</div>
</form>

<div class="reply">
	<div class="header"><h2>{$translate->_('portal.sc.public.history.reply')}</h2></div>
	<form action="{devblocks_url}c=history{/devblocks_url}" method="post" name="replyForm">
	<input type="hidden" name="a" value="doReply">
	<input type="hidden" name="mask" value="{$ticket.t_mask}">
	
	<textarea name="content" rows="10" cols="80" style="width:98%;"></textarea><br>
	<button type="submit"><img src="{devblocks_url}c=resource&p=usermeet.core&f=images/check.gif{/devblocks_url}" align="top"> {$translate->_('portal.public.send_message')}</button>
	</form>
</div>

{* Message History *}
{foreach from=$messages item=message}
	{assign var=headers value=$message->getHeaders()}
	<div class="message {if $message->is_outgoing}outbound_message{else}inbound_message{/if}">
	<span class="header"><b>{$translate->_('message.header.from')|capitalize}:</b> {$headers.from|escape}</span><br>
	<span class="header"><b>{$translate->_('message.header.to')|capitalize}:</b> {$headers.to|escape}</span><br>
	{if !empty($headers.cc)}<span class="header"><b>{$translate->_('message.header.cc')|capitalize}:</b> {$headers.cc|escape}</span><br>{/if}
	{if !empty($headers.date)}<span class="header"><b>{$translate->_('message.header.date')|capitalize}:</b> {$headers.date|escape}</span><br>{/if}
	<br>
	{$message->getContent()|trim|escape|nl2br}
	</div>
{/foreach}

</div><!--#history-->