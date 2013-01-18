<div id="history">
	
<div class="header"><h1>{$ticket.t_subject}</h1></div>

<form action="{devblocks_url}c=history{/devblocks_url}" method="post" name="">
<input type="hidden" name="a" value="saveTicketProperties">
<input type="hidden" name="mask" value="{$ticket.t_mask}">
<input type="hidden" name="closed" value="{if $ticket.t_is_closed}1{else}0{/if}">
	<b>{$translate->_('portal.sc.public.history.reference')}</b> {$ticket.t_mask}
	 &nbsp; 
	<b>{$translate->_('common.updated')|capitalize}:</b> <abbr title="{$ticket.t_updated_date|devblocks_date}">{$ticket.t_updated_date|devblocks_prettytime}</abbr>
	 &nbsp; 
	<br>
	{if $show_fields.ticket_change_status}
	<div style="padding:5px;">
		{if $ticket.t_is_closed}
			{if $show_fields.ticket_change_status == 2}
			<button type="button" onclick="this.form.closed.value='0';this.form.submit();"><img src="{devblocks_url}c=resource&p=cerberusweb.support_center&f=images/folder_out.gif{/devblocks_url}" align="top"> {$translate->_('common.reopen')|capitalize}</button>
			{else}
			&nbsp;
			{/if}
		{else}
		<button type="button" onclick="this.form.closed.value='1';this.form.submit();"><img src="{devblocks_url}c=resource&p=cerberusweb.support_center&f=images/folder_ok.gif{/devblocks_url}" align="top"> {$translate->_('common.close')|capitalize}</button>
		{/if}
	</div>
	{/if}
</form>
{if $show_fields.ticket_answer}
<div class="reply">
	<div class="header"><h2>{$translate->_('portal.sc.public.history.reply')}</h2></div>
	<form action="{devblocks_url}c=history{/devblocks_url}" method="post" name="replyForm" enctype="multipart/form-data">
	<input type="hidden" name="a" value="doReply">
	<input type="hidden" name="mask" value="{$ticket.t_mask}">
	
	<b>{'message.header.from'|devblocks_translate|capitalize}:</b>
	{if $show_fields.ticket_answer == 2}
	<select name="from">
		{$contact_addresses = $active_contact->getAddresses()}
		{foreach from=$contact_addresses item=address}
		<option value="{$address->email}" {if 0==strcasecmp($address->id,$active_contact->email_id)}selected="selected"{/if}>{$address->email}</option>
		{/foreach}
	</select>
	{else}
	<input type="text" name="from" />
	{/if}
	<br>
	
	<textarea name="content" rows="10" cols="80" style="width:98%;"></textarea><br>
	
	<fieldset>
		<legend>Attachments:</legend>
		<input type="file" name="attachments[]" class="multi"><br>
	</fieldset>
	
	<button type="submit"><img src="{devblocks_url}c=resource&p=cerberusweb.support_center&f=images/check.gif{/devblocks_url}" align="top"> {$translate->_('portal.public.send_message')}</button>
	</form>
</div>
{/if}
{* Message History *}
{$badge_extensions = DevblocksPlatform::getExtensions('cerberusweb.support_center.message.badge', true)}
{foreach from=$messages item=message key=message_id}
	{assign var=headers value=$message->getHeaders()}
	{assign var=sender value=$message->getSender()}
	<div class="message {if $message->is_outgoing}outbound_message{else}inbound_message{/if}" style="overflow:auto;">

	{foreach from=$badge_extensions item=extension}
		{$extension->render($message)}
	{/foreach}
		
	<span class="header"><b>{$translate->_('message.header.from')|capitalize}:</b>
		{$sender_name = $sender->getName()}
		{if !empty($sender_name)}&quot;{$sender_name}&quot; {/if}&lt;{$sender->email}&gt; 
	</span><br>
	<span class="header"><b>{$translate->_('message.header.to')|capitalize}:</b> {$headers.to}</span><br>
	{if !empty($headers.cc)}<span class="header"><b>{$translate->_('message.header.cc')|capitalize}:</b> {$headers.cc}</span><br>{/if}
	{if !empty($headers.date)}<span class="header"><b>{$translate->_('message.header.date')|capitalize}:</b> {$headers.date}</span><br>{/if}
	<br>
	
	<div style="clear:both;">
	<pre class="email">{$message->getContent()|trim|escape|devblocks_hyperlinks|devblocks_hideemailquotes nofilter}</pre>
	</div>
	
	{if isset($attachments.$message_id)}
		<div style="margin-top:10px;">
		<b>Attachments:</b><br>
		<ul style="margin-top:0px;">
		{foreach from=$attachments.$message_id item=map}
			{$links = $map.links}
			{$files = $map.attachments}
			
			{foreach from=$links item=link}
			{$attachment = $files.{$link->attachment_id}}
			<li>
				<a href="{devblocks_url}c=ajax&a=downloadFile&guid={$link->guid}&name={$attachment->display_name}{/devblocks_url}" target="_blank">{$attachment->display_name}</a>
				( 
					{$attachment->storage_size|devblocks_prettybytes}
					- 
					{if !empty($attachment->mime_type)}{$attachment->mime_type}{else}{$translate->_('display.convo.unknown_format')|capitalize}{/if}
				 )
			</li>
			{/foreach}
		{/foreach}
		</ul>
		</div>
	{/if}
	
	</div>
{/foreach}

</div><!--#history-->