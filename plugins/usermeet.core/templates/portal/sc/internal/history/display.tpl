<div style="border-bottom:1px solid rgb(180,180,180);margin-bottom:10px;">
<h1 style="margin-bottom:0px;">{$translate->_('portal.sc.public.history.ticket_history')}</h1>
</div>

<h2>{$translate->_('ticket.subject')|capitalize}: {$ticket.t_subject|escape}</h2>

<form action="{devblocks_url}{/devblocks_url}" method="post" name="">
<input type="hidden" name="a" value="saveTicketProperties">
<input type="hidden" name="mask" value="{$ticket.t_mask}">
<input type="hidden" name="closed" value="{if $ticket.t_is_closed}1{else}0{/if}">
<span style="font-size:90%;">
<b>{$translate->_('portal.sc.public.history.reference')}</b> {$ticket.t_mask}
 &nbsp; 
<b>{$translate->_('ticket.updated')|capitalize}:</b> {$ticket.t_updated_date|devblocks_date}
 &nbsp; 
<b>{$translate->_('portal.sc.public.history.action')}</b> 
</span>
{if $ticket.t_is_closed}
<button type="button" onclick="this.form.closed.value='0';this.form.submit();"><img src="{devblocks_url}c=resource&p=usermeet.core&f=images/folder_out.gif{/devblocks_url}" align="top"> {$translate->_('common.reopen')|capitalize}</button>
{else}
<button type="button" onclick="this.form.closed.value='1';this.form.submit();"><img src="{devblocks_url}c=resource&p=usermeet.core&f=images/folder_ok.gif{/devblocks_url}" align="top"> {$translate->_('common.close')|capitalize}</button>
{/if}
</form>

{foreach from=$messages item=message}
	{assign var=headers value=$message->getHeaders()}
	<div style="margin:5px;padding:5px;">
	<h2 style="margin:0px;color:rgb(80,80,80);">{$translate->_('message.header.from')|capitalize}: {$headers.from|escape}</h2>
	<b>{$translate->_('message.header.to')|capitalize}:</b> {$headers.to|escape}<br>
	{if !empty($headers.cc)}<b>{$translate->_('message.header.cc')|capitalize}:</b> {$headers.cc|escape}<br>{/if}
	{if !empty($headers.date)}<b>{$translate->_('message.header.date')|capitalize}:</b> {$headers.date|escape}<br>{/if}
	<br>
	{$message->getContent()|trim|escape|nl2br}
	{*<div style="padding-left:20px;padding-top:5px;">&raquo; <a href="#reply" style="font-size:85%;">{$translate->_('portal.sc.public.history.reply')}</a></div>*}
	</div>
{/foreach}

<a name="reply"></a>
<div style="border-bottom:1px solid rgb(180,180,180);margin-bottom:10px;">
<h2 style="margin-bottom:0px;">{$translate->_('portal.sc.public.history.add_message')}</h2>
</div>
<form action="{devblocks_url}{/devblocks_url}" method="post" name="replyForm">
<input type="hidden" name="a" value="doReply">
<input type="hidden" name="mask" value="{$ticket.t_mask}">

<textarea name="content" rows="10" cols="80" style="width:98%;"></textarea><br>
<button type="submit"><img src="{devblocks_url}c=resource&p=usermeet.core&f=images/check.gif{/devblocks_url}" align="top"> {$translate->_('portal.public.send_message')}</button>
</form>
