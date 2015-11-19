<div id="history">
	
<div class="header"><h1>{$ticket.t_subject}</h1></div>

<form action="{devblocks_url}c=history{/devblocks_url}" method="post" name="">
<input type="hidden" name="a" value="saveTicketProperties">
<input type="hidden" name="mask" value="{$ticket.t_mask}">
<input type="hidden" name="closed" value="{if $ticket.t_is_closed}1{else}0{/if}">
<input type="hidden" name="_csrf_token" value="{$session->csrf_token}">
	<b>{'portal.sc.public.history.reference'|devblocks_translate}</b> {$ticket.t_mask}
	 &nbsp; 
	<b>{'common.updated'|devblocks_translate|capitalize}:</b> <abbr title="{$ticket.t_updated_date|devblocks_date}">{$ticket.t_updated_date|devblocks_prettytime}</abbr>
	 &nbsp; 
	<br>
	
	<div style="padding:5px;">
		{if $ticket.t_is_closed}
		<button type="button" onclick="this.form.closed.value='0';this.form.submit();"><span class="glyphicons glyphicons-circle-ok"></span> {'common.reopen'|devblocks_translate|capitalize}</button>
		{else}
		<button type="button" onclick="this.form.closed.value='1';this.form.submit();"><span class="glyphicons glyphicons-circle-ok"></span> {'common.close'|devblocks_translate|capitalize}</button>
		{/if}
	</div>
</form>

{* Message History *}
{$badge_extensions = DevblocksPlatform::getExtensions('cerberusweb.support_center.message.badge', true)}
{foreach from=$messages item=message key=message_id}
	{$headers = $message->getHeaders()}
	{$sender = $message->getSender()}
	{$sender_contact = $sender->getContact()}
	{$sender_worker = DAO_Worker::get($message->worker_id)}
	
	<div class="message {if $message->is_outgoing}outbound_message{else}inbound_message{/if}" style="overflow:auto;">

	<div style="float:left;margin:0px 10px 10px 0px;">
		{if $message->is_outgoing && $message->worker_id}
		<img class="cerb-avatar" src="{devblocks_url}c=avatar&context=worker&context_id={$sender_worker->id}{/devblocks_url}?v={$sender_worker->updated}" style="height:64px;width:64px;border-radius:32px;">
		{elseif !$message->is_outgoing && $sender_contact}
		<img class="cerb-avatar" src="{devblocks_url}c=avatar&context=contact&context_id={$sender_contact->id}{/devblocks_url}?v={$sender_contact->updated_at}" style="height:64px;width:64px;border-radius:32px;">
		{else}
		<img class="cerb-avatar" src="{devblocks_url}c=avatar&context=address&context_id={$sender->id}{/devblocks_url}?v={$sender->updated}" style="height:64px;width:64px;border-radius:32px;">
		{/if}
	</div>
	
	{if $message->is_outgoing}
	<b style="color:rgb(180,0,0);line-height:18px;vertical-align:top;margin-right:5px;">received</b>
	{else}
	<b style="color:rgb(0,120,0);line-height:18px;vertical-align:top;margin-right:5px;">sent</b>
	{/if}
	
	{if $sender_worker}
		<span style="font-size:18px;font-weight:bold;">{$sender_worker->getName()}</span>
	{elseif $sender_contact}
		{$sender_contact_org = $sender_contact->getOrg()}
		<span style="font-size:18px;font-weight:bold;margin-right:5px;">{$sender_contact->getName()}</span>
		
		{if $sender_contact_org}
		<span>{$sender_contact_org->name}</span>
		{/if}
	{else}
		{$sender_org = $sender->getOrg()}
		{$name = $sender->getName()}
		{if $name}
		<span style="font-size:18px;font-weight:bold;margin-right:5px;">{$name}</span>
		{else}
		<span style="font-size:18px;font-weight:bold;margin-right:5px;">{$sender->email}</span>
		{/if}
		
		{if $sender_org}
		<span>{$sender_org->name}</span>
		{/if}
	{/if}
	<br>
	
	<span class="header"><b>{'message.header.from'|devblocks_translate|capitalize}:</b> {$headers.from}</span><br>
	<span class="header"><b>{'message.header.to'|devblocks_translate|capitalize}:</b> {$headers.to}</span><br>
	{if !empty($headers.cc)}<span class="header"><b>{'message.header.cc'|devblocks_translate|capitalize}:</b> {$headers.cc}</span><br>{/if}
	{if !empty($headers.date)}<span class="header"><b>{'message.header.date'|devblocks_translate|capitalize}:</b> {$headers.date}</span><br>{/if}
	<br>
	
	<div style="clear:both;">
	
	<div class="email">{$message->getContent()|trim|escape|devblocks_hyperlinks|devblocks_hideemailquotes nofilter}</div>

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
				<a href="{devblocks_url}c=ajax&a=downloadFile&guid={$link->guid}&name={$attachment->display_name|escape:'url'}{/devblocks_url}" target="_blank">{$attachment->display_name}</a>
				( 
					{$attachment->storage_size|devblocks_prettybytes}
					- 
					{if !empty($attachment->mime_type)}{$attachment->mime_type}{else}{'display.convo.unknown_format'|devblocks_translate|capitalize}{/if}
				 )
			</li>
			{/foreach}
		{/foreach}
		</ul>
		</div>
	{/if}
	
	<button type="button" onclick="var $div = $(this).next('div.reply').toggle(); $div.find('textarea').focus();"><span class="glyphicons glyphicons-share"></span> Reply</button>
	
	<div class="reply" style="display:none;margin-left:15px;">
		<div class="header"><h2>{'portal.sc.public.history.reply'|devblocks_translate}</h2></div>
		<form action="{devblocks_url}c=history{/devblocks_url}" method="post" enctype="multipart/form-data">
		<input type="hidden" name="a" value="doReply">
		<input type="hidden" name="mask" value="{$ticket.t_mask}">
		<input type="hidden" name="_csrf_token" value="{$session->csrf_token}">
		
		<b>{'message.header.from'|devblocks_translate|capitalize}:</b> 
		<select name="from">
			{$contact_addresses = $active_contact->getEmails()}
			{foreach from=$contact_addresses item=address}
			<option value="{$address->email}" {if 0==strcasecmp($address->id,$active_contact->primary_email_id)}selected="selected"{/if}>{$address->email}</option>
			{/foreach}
		</select>
		<br>
		
		<textarea name="content" rows="15" cols="80" style="width:98%;">{$message->getContent()|trim|indent:1:'> '|devblocks_email_quote}</textarea><br>
		
		<fieldset style="margin:10px 0px 0px 0px;border:0;">
			<legend>Attachments:</legend>
			<input type="file" name="attachments[]" class="multi"><br>
		</fieldset>
		
		<button type="submit"><span class="glyphicons glyphicons-send"></span> {'portal.public.send_message'|devblocks_translate}</button>
		<button type="button" onclick="$(this).closest('div.reply').fadeOut();"><span class="glyphicons glyphicons-circle-remove"></span> {'common.cancel'|devblocks_translate|capitalize}</button>
		</form>
	</div>
	
	</div>
{/foreach}

</div><!--#history-->