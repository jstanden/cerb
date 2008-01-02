<h1>Subject: {$ticket.t_subject|escape}</h1>

<form action="{devblocks_url}{/devblocks_url}" method="post" name="">
<input type="hidden" name="a" value="saveTicketProperties">
<input type="hidden" name="mask" value="{$ticket.t_mask}">
<input type="hidden" name="closed" value="{if $ticket.t_is_closed}1{else}0{/if}">
<span style="font-size:90%;">
<b>Reference:</b> {$ticket.t_mask}
 &nbsp; 
<b>Updated:</b> {$ticket.t_updated_date|date_format}
 &nbsp; 
<b>Action:</b> 
</span>
{if $ticket.t_is_closed}
<button type="button" onclick="this.form.closed.value='0';this.form.submit();"><img src="{devblocks_url}c=resource&p=usermeet.sc&f=images/folder_out.gif{/devblocks_url}" align="top"> Re-open</button>
{else}
<button type="button" onclick="this.form.closed.value='1';this.form.submit();"><img src="{devblocks_url}c=resource&p=usermeet.sc&f=images/folder_ok.gif{/devblocks_url}" align="top"> Close</button>
{/if}
</form>

{foreach from=$messages item=message}
	{assign var=headers value=$message->getHeaders()}
	<div style="margin:5px;padding:5px;">
	<div style="border-bottom:1px solid rgb(180,180,180);">
	<h2 style="margin:0px;color:rgb(80,80,80);">From: {$headers.from|escape}</h2>
	</div>
	<b>To:</b> {$headers.to|escape}<br>
	{if !empty($headers.cc)}<b>Cc:</b> {$headers.cc|escape}<br>{/if}
	{if !empty($headers.date)}<b>Date:</b> {$headers.date|escape}<br>{/if}
	<br>
	{$message->getContent()|trim|escape:"htmlall"|nl2br}
	<div style="padding-left:20px;padding-top:5px;">&raquo; <a href="#reply" style="font-size:85%;">Reply</a></div>
	</div>
{/foreach}

<a name="reply"></a>
<div style="border-bottom:1px solid rgb(180,180,180);margin-bottom:10px;">
<h2 style="margin-bottom:0px;">Add a Message</h2>
</div>
<form action="{devblocks_url}{/devblocks_url}" method="post" name="replyForm">
<input type="hidden" name="a" value="doReply">
<input type="hidden" name="mask" value="{$ticket.t_mask}">

<textarea name="content" rows="10" cols="80" style="width:98%;"></textarea><br>
<button type="submit"><img src="{devblocks_url}c=resource&p=usermeet.core&f=images/check.gif{/devblocks_url}" align="top"> Send Message</button>
</form>