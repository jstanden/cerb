{$headers = $message->getHeaders()}
{$mail_reply_html = DAO_WorkerPref::get($active_worker->id, 'mail_reply_html', 0)}
{$is_html = ($draft && $draft->params.format == 'parsedown') || $mail_reply_html}

<div class="reply_frame {if "inline" == $reply_format}block{/if}" style="width:98%;margin:10px;">

{if $recent_activity}
	<div class="cerb-collision">
		<h1>There is recent activity on this ticket:</h1>
		<table style="margin-left:20px;">
			{foreach from=$recent_activity item=activity}
			<tr>
				<td align="right" style="padding-right:15px;vertical-align:middle;"><b>{$activity.timestamp|devblocks_prettytime}</b></td>
				<td style="vertical-align:middle;">{$activity.message}</td>
			</tr>
			{/foreach}
		</table>
		
		<div style="margin-top:10px;">
			<button type="button" class="cerb-collision--continue"><span class="glyphicons glyphicons-circle-ok" style="color:rgb(0,180,0);"></span> {'common.continue'|devblocks_translate|capitalize}</button>
			<button type="button" class="cerb-collision--cancel"><span class="glyphicons glyphicons-circle-remove" style="color:rgb(180,0,0);"></span> {'common.cancel'|devblocks_translate|capitalize}</button>
		</div>
	</div>
{/if}

<form id="reply{$message->id}_form" onsubmit="return false;" method="post">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="ticket">
<input type="hidden" name="action" value="sendReply">
<input type="hidden" name="id" value="{$message->id}">
<input type="hidden" name="ticket_id" value="{$ticket->id}">
<input type="hidden" name="ticket_mask" value="{$ticket->mask}">
<input type="hidden" name="draft_id" value="{$draft->id}">
<input type="hidden" name="reply_mode" value="">
<input type="hidden" name="format" value="{if $is_html}parsedown{/if}">
<input type="hidden" name="options_gpg_encrypt" value="{if $draft->params.options_gpg_encrypt}1{/if}">
<input type="hidden" name="options_gpg_sign" value="{if $draft->params.options_gpg_sign}1{/if}">

{if $is_forward}<input type="hidden" name="is_forward" value="1">{/if}

<table cellpadding="2" cellspacing="0" border="0" width="100%">
	<tr>
		<td width="100%">
			{if !$reply_transport}
				<div class="help-box">
					<h1>Your message will not be delivered.</h1>
					<p>
						{$sender_address = DAO_Address::get($bucket->getReplyFrom())}
						{if $sender_address}
							<a href="javascript:;" class="cerb-peek-trigger" data-context="{CerberusContexts::CONTEXT_ADDRESS}" data-context-id="{$sender_address->id}">{$sender_address->email}</a> 
							is not configured as a sender address. To send live mail, edit the email address and select <b>"We send email from this address"</b>.
						{else}
							The sender address for this bucket does not have a mail transport configured. 
							To send live email, an administrator must assign a mail transport to the <a href="javascript:;" class="cerb-peek-trigger" data-context="{CerberusContexts::CONTEXT_BUCKET}" data-context-id="{$ticket->bucket_id}">bucket</a> or <a href="javascript:;" class="cerb-peek-trigger" data-context="{CerberusContexts::CONTEXT_GROUP}" data-context-id="{$ticket->group_id}">group</a>.
						{/if}
					</p>
				</div>
			{elseif 'core.mail.transport.null' == $reply_transport->extension_id}
				<div class="error-box">
					<h1>Your message will not be delivered.</h1>
					<p>
						This bucket is configured to discard outgoing messages. 
						To send live email, change the mail transport on the <a href="javascript:;" class="cerb-peek-trigger" data-context="{CerberusContexts::CONTEXT_BUCKET}" data-context-id="{$ticket->bucket_id}">bucket</a> or <a href="javascript:;" class="cerb-peek-trigger" data-context="{CerberusContexts::CONTEXT_GROUP}" data-context-id="{$ticket->group_id}">group</a>.
					</p>
				</div>
			{/if}
			
			<table cellpadding="1" cellspacing="0" border="0" width="100%">
				{if $reply_from && $reply_transport}
				<tr>
					<td width="1%" nowrap="nowrap" align="right" valign="middle"><b>{'message.header.from'|devblocks_translate|capitalize}:</b>&nbsp;</td>
					<td width="99%" align="left">
						{$reply_as} &lt;<a href="javascript:;" class="cerb-peek-trigger" data-context="{CerberusContexts::CONTEXT_ADDRESS}" data-context-id="{$reply_from->id}">{$reply_from->email}</a>&gt; via 
						<a href="javascript:;" class="cerb-peek-trigger" data-context="{CerberusContexts::CONTEXT_MAIL_TRANSPORT}" data-context-id="{$reply_transport->id}">{$reply_transport->name}</a>
					</td>
				</tr>
				{/if}
				
				<tr>
					<td width="1%" nowrap="nowrap" align="right" valign="middle"><a href="javascript:;" class="cerb-recipient-chooser" data-context="{CerberusContexts::CONTEXT_ADDRESS}" data-query=""><b>{'message.header.to'|devblocks_translate|capitalize}</b></a>:&nbsp;</td>
					<td width="99%" align="left">
						<input type="text" size="45" name="to" value="{$to}" placeholder="{if $is_forward}These recipients will receive this forwarded message{else}These recipients will automatically be included in all future correspondence as participants{/if}" class="required" style="width:100%;border:1px solid rgb(180,180,180);padding:2px;">
						{if !$is_forward}
							{if !empty($suggested_recipients)}
								<div id="reply{$message->id}_suggested">
									<a href="javascript:;" onclick="$(this).closest('div').remove();">x</a>
									<b>Consider adding these recipients:</b>
									<ul class="bubbles">
									{foreach from=$suggested_recipients item=sug name=sugs}
										<li><a href="javascript:;" class="suggested">{$sug.full_email}</a></li>
									{/foreach}
									</ul> 
								</div>
							{/if}
						{/if}
					</td>
				</tr>
				
				<tr>
					<td width="1%" nowrap="nowrap" align="right" valign="middle"><a href="javascript:;" class="cerb-recipient-chooser" data-context="{CerberusContexts::CONTEXT_ADDRESS}" data-query="">{'message.header.cc'|devblocks_translate|capitalize}</a>:&nbsp;</td>
					<td width="99%" align="left">
						<input type="text" size="45" name="cc" value="{$cc}" placeholder="These recipients will publicly receive a one-time copy of this message" style="width:100%;border:1px solid rgb(180,180,180);padding:2px;">
					</td>
				</tr>
				
				<tr>
					<td width="1%" nowrap="nowrap" align="right" valign="middle"><a href="javascript:;" class="cerb-recipient-chooser" data-context="{CerberusContexts::CONTEXT_ADDRESS}" data-query="">{'message.header.bcc'|devblocks_translate|capitalize}</a>:&nbsp;</td>
					<td width="99%" align="left">
						<input type="text" size="45" name="bcc" value="{$bcc}" placeholder="These recipients will secretly receive a one-time copy of this message" style="width:100%;border:1px solid rgb(180,180,180);padding:2px;">
					</td>
				</tr>
				
				<tr>
					<td width="1%" nowrap="nowrap" align="right" valign="middle"><b>{'message.header.subject'|devblocks_translate|capitalize}:</b>&nbsp;</td>
					<td width="99%" align="left">
						<input type="text" size="45" name="subject" value="{$subject}" style="width:100%;border:1px solid rgb(180,180,180);padding:2px;" class="required" maxlength="255">
					</td>
				</tr>
				
			</table>
			
			<div id="divDraftStatus{$message->id}"></div>
		</td>
	</tr>
</table>

<div class="cerb-editor-tabs">
	<ul>
		<li data-cerb-tab="editor"><a href="#reply{$message->id}EditorPanel">{'common.editor'|devblocks_translate|capitalize}</a></li>
		<li data-cerb-tab="preview"><a href="#reply{$message->id}EditorPreviewPanel">{'common.preview'|devblocks_translate|capitalize}</a></li>
	</ul>

	<div id="reply{$message->id}EditorPanel">
		{$message_content = $message->getContent()}

		<div class="cerb-code-editor-toolbar">
			{if $toolbar_formatting}
				<button type="button" title="Toggle formatting" class="cerb-code-editor-toolbar-button cerb-editor-toolbar-button--formatting" data-format="{if $is_html}html{else}plaintext{/if}">{if $is_html}Formatting on{else}Formatting off{/if}</button>
	
				<div data-cerb-toolbar class="cerb-code-editor-subtoolbar-format-html" style="display:inline-block;{if !$is_html}display:none;{/if}">
					{DevblocksPlatform::services()->ui()->toolbar()->render($toolbar_formatting)}
				</div>
					
				<div class="cerb-code-editor-toolbar-divider"></div>
			{/if}
			
			{if $toolbar_custom}
				<div data-cerb-toolbar class="cerb-code-editor-subtoolbar-custom" style="display:inline-block;">
					{DevblocksPlatform::services()->ui()->toolbar()->render($toolbar_custom)}
				</div>
				
				<div class="cerb-code-editor-toolbar-divider"></div>
			{/if}

			<button type="button" title="Insert #command" class="cerb-code-editor-toolbar-button cerb-markdown-editor-toolbar-button--commands"><span class="glyphicons glyphicons-sampler"></span></button>
			<button type="button" title="Insert snippet (Ctrl+Shift+Period)" class="cerb-code-editor-toolbar-button cerb-markdown-editor-toolbar-button--snippets"><span class="glyphicons glyphicons-notes-2"></span></button>
			<button type="button" title="Save draft (Ctrl+S)" data-cerb-key-binding="ctrl+s" class="cerb-code-editor-toolbar-button cerb-reply-editor-toolbar-button--save"><span class="glyphicons glyphicons-floppy-save"></span></button>
			<div class="cerb-code-editor-toolbar-divider"></div>

			<button type="button" title="{'common.encrypt'|devblocks_translate|capitalize}" class="cerb-code-editor-toolbar-button cerb-reply-editor-toolbar-button--encrypt {if $draft->params.options_gpg_encrypt}cerb-code-editor-toolbar-button--enabled{/if}"><span class="glyphicons {if $draft->params.options_gpg_encrypt}glyphicons-lock{else}glyphicons-unlock{/if}"></span></button>
			<button type="button" title="{'common.encrypt.sign'|devblocks_translate|capitalize}" class="cerb-code-editor-toolbar-button cerb-reply-editor-toolbar-button--sign {if $draft->params.options_gpg_sign}cerb-code-editor-toolbar-button--enabled{/if}"><span class="glyphicons {if $draft->params.options_gpg_encrypt}glyphicons-user-lock{else}glyphicons-user{/if}"></span></button>

		</div>

		{if $is_forward}
			<textarea name="content" id="reply_{$message->id}" class="reply" style="box-sizing:border-box;">
{if !empty($draft)}{$draft->getParam('content')}{else}


#signature

{'display.reply.forward.banner'|devblocks_translate}
{if isset($headers.subject)}{'message.header.subject'|devblocks_translate|capitalize}: {$headers.subject|cat:"\n"}{/if}
{if isset($headers.from)}{'message.header.from'|devblocks_translate|capitalize}: {$headers.from|cat:"\n"}{/if}
{if isset($headers.date)}{'message.header.date'|devblocks_translate|capitalize}: {$headers.date|cat:"\n"}{/if}
{if isset($headers.to)}{'message.header.to'|devblocks_translate|capitalize}: {$headers.to|cat:"\n"}{/if}

{$message_content|trim}
{/if}
</textarea>
		{else}
			<textarea name="content" id="reply_{$message->id}" class="reply" style="box-sizing:border-box;" autofocus="autofocus">
{if !empty($draft)}{$draft->getParam('content')}{else}
{if 1==$signature_pos || 3==$signature_pos}


#signature
{if 1==$signature_pos}
#cut
{/if}{if in_array($reply_mode,[0,2])}{*Sig above*}


{/if}
{/if}{if in_array($reply_mode,[0,2])}{$quote_sender=$message->getSender()}{$quote_sender_personal=$quote_sender->getName()}{if !empty($quote_sender_personal)}{$reply_personal=$quote_sender_personal}{else}{$reply_personal=$quote_sender->email}{/if}{$reply_date=$message->created_date|devblocks_date:'D, d M Y'}{'display.reply.reply_banner'|devblocks_translate:$reply_date:$reply_personal}
{/if}{if in_array($reply_mode,[0,2])}{$message_content|trim|indent:1:'> '|devblocks_email_quote}
{/if}{if 2==$signature_pos}


#signature
#cut
{/if}{*Sig below*}{/if}
</textarea>
{/if}
	</div>

	<div id="reply{$message->id}EditorPreviewPanel" style="min-height:100px;max-height:400px;overflow:auto;border:1px dotted rgb(150,150,150);padding:5px;"></div>
</div>

<fieldset class="peek reply-attachments" style="margin-top:10px;">
	<legend>{'common.attachments'|devblocks_translate|capitalize}</legend>

	<button type="button" class="chooser_file"><span class="glyphicons glyphicons-paperclip"></span></button>
	<ul class="bubbles chooser-container">
	{if $draft->params.file_ids}
		{foreach from=$draft->params.file_ids item=file_id}
			{$file = DAO_Attachment::get($file_id)}
			{if !empty($file)}
			<li><input type="hidden" name="file_ids[]" value="{$file_id}">{$file->name} ({$file->storage_size|devblocks_prettybytes}) <a href="javascript:;" onclick="$(this).parent().remove();"><span class="glyphicons glyphicons-circle-remove"></span></a></li>
			{/if}
		{/foreach}
	{elseif $is_forward && !empty($forward_attachments)}
		{foreach from=$forward_attachments item=attach}
			<li><input type="hidden" name="file_ids[]" value="{$attach->id}">{$attach->name} ({$attach->storage_size|devblocks_prettybytes}) <a href="javascript:;" onclick="$(this).parent().remove();"><span class="glyphicons glyphicons-circle-remove"></span></a></li>
		{/foreach}
	{/if}
	</ul>
</fieldset>

<fieldset class="peek" data-cerb-reply-html-template style="margin-top:10px;{if !$is_html}display:none;{/if}">
	<legend>{'common.html_mail_template'|devblocks_translate|capitalize}</legend>

	{if $html_templates}
		<select name="html_template_id" style="max-width:150px;" title="{'common.template'|devblocks_translate|capitalize}">
			<optgroup label="{'common.template'|devblocks_translate|capitalize}">
				<option value="">({'common.default'|devblocks_translate|capitalize})</option>
				{foreach from=$html_templates item=html_template}
					<option value="{$html_template->id}" {if $draft->params.html_template_id==$html_template->id}selected="selected"{/if}>{$html_template->name}</option>
				{/foreach}
			</optgroup>
		</select>
	{/if}
</fieldset>

<fieldset class="peek">
	<legend>{'common.properties'|devblocks_translate|capitalize}</legend>

	<table cellpadding="2" cellspacing="0" border="0" id="replyStatus{$message->id}">
		<tr>
			<td nowrap="nowrap" valign="top">
				<div>
					<b>{'common.status'|devblocks_translate|capitalize}:</b>

					<label {if $pref_keyboard_shortcuts}title="(Ctrl+Shift+O)"{/if}><input type="radio" name="status_id" value="{Model_Ticket::STATUS_OPEN}" class="status_open" onclick="toggleDiv('replyOpen{$message->id}','block');toggleDiv('replyClosed{$message->id}','none');" {if (empty($draft) && 'open'==$mail_status_reply) || $draft->params.status_id==Model_Ticket::STATUS_OPEN}checked="checked"{/if}> {'status.open'|devblocks_translate|capitalize}</label>
					<label {if $pref_keyboard_shortcuts}title="(Ctrl+Shift+W)"{/if}><input type="radio" name="status_id" value="{Model_Ticket::STATUS_WAITING}" class="status_waiting" onclick="toggleDiv('replyOpen{$message->id}','block');toggleDiv('replyClosed{$message->id}','block');" {if (empty($draft) && 'waiting'==$mail_status_reply) || $draft->params.status_id==Model_Ticket::STATUS_WAITING}checked="checked"{/if}> {'status.waiting'|devblocks_translate|capitalize}</label>
					{if $active_worker->hasPriv('core.ticket.actions.close') || ($ticket->status_id == Model_Ticket::STATUS_CLOSED)}<label {if $pref_keyboard_shortcuts}title="(Ctrl+Shift+C)"{/if}><input type="radio" name="status_id" value="{Model_Ticket::STATUS_CLOSED}" class="status_closed" onclick="toggleDiv('replyOpen{$message->id}','none');toggleDiv('replyClosed{$message->id}','block');" {if (empty($draft) && 'closed'==$mail_status_reply) || $draft->params.status_id==Model_Ticket::STATUS_CLOSED}checked="checked"{/if}> {'status.closed'|devblocks_translate|capitalize}</label>{/if}
					<br>

					<div id="replyClosed{$message->id}" style="display:{if (empty($draft) && 'open'==$mail_status_reply) || (!empty($draft) && $draft->params.status_id==Model_Ticket::STATUS_OPEN)}none{else}block{/if};margin:5px 0px 10px 20px;">
						<div style="display:flex;flex-flow:row wrap;">
							<div style="flex:1 1 45%;padding-right:10px;">
								<b>{'display.reply.next.resume'|devblocks_translate}</b>
							</div>
							<div style="flex:1 1 45%;">
								{'display.reply.next.resume_eg'|devblocks_translate}
							</div>
						</div>

						<input type="text" name="ticket_reopen" size="55" value="{if !empty($draft)}{$draft->params.ticket_reopen}{elseif !empty($ticket->reopen_at)}{$ticket->reopen_at|devblocks_date}{/if}"><br>
						{'display.reply.next.resume_blank'|devblocks_translate}<br>
					</div>
				</div>

				<div style="margin-top:5px;">
					<b>{'common.move'|devblocks_translate|capitalize}:</b>

					<select name="group_id">
						{foreach from=$groups item=group key=group_id}
						<option value="{$group_id}" {if $active_worker->isGroupMember($group_id)}member="true"{/if} {if $ticket->group_id == $group_id}selected="selected"{/if}>{$group->name}</option>
						{/foreach}
					</select>
					<select class="ticket-reply-bucket-options" style="display:none;">
						{foreach from=$buckets item=bucket key=bucket_id}
						<option value="{$bucket_id}" group_id="{$bucket->group_id}">{$bucket->name}</option>
						{/foreach}
					</select>
					<select name="bucket_id">
						{foreach from=$buckets item=bucket key=bucket_id}
							{if $bucket->group_id == $ticket->group_id}
							<option value="{$bucket_id}" {if $ticket->bucket_id == $bucket_id}selected="selected"{/if}>{$bucket->name}</option>
							{/if}
						{/foreach}
					</select>
				</div>

				<div style="margin-top:5px;">
					<b>{'common.owner'|devblocks_translate|capitalize}:</b>
					<button type="button" class="chooser-abstract" data-field-name="owner_id" data-context="{CerberusContexts::CONTEXT_WORKER}" data-single="true" data-query="isDisabled:n" data-autocomplete="" data-autocomplete-if-empty="true"><span class="glyphicons glyphicons-search"></span></button>
					<ul class="bubbles chooser-container">
						{$owner = $workers.{$ticket->owner_id}}
						{if $owner}
						<li><img class="cerb-avatar" src="{devblocks_url}c=avatars&context=worker&context_id={$owner->id}{/devblocks_url}?v={$owner->updated}"><input type="hidden" name="owner_id" value="{$owner->id}"><a href="javascript:;" class="cerb-peek-trigger no-underline" data-context="{CerberusContexts::CONTEXT_WORKER}" data-context-id="{$owner->id}">{$owner->getName()}</a></li>
						{/if}
					</ul>
				</div>

				<div style="margin-top:5px;">
					<b>{'common.watchers'|devblocks_translate|capitalize}:</b>
					<span>
						{$watchers_btn_domid = uniqid()}
						{include file="devblocks:cerberusweb.core::internal/watchers/context_follow_button.tpl" object_watchers=$object_watchers context=CerberusContexts::CONTEXT_TICKET context_id=$ticket->id watchers_btn_domid=$watchers_btn_domid watchers_group_id=$ticket->group_id watchers_bucket_id=$ticket->bucket_id}
					</span>
				</div>
			</td>
		</tr>
	</table>
</fieldset>

{$custom_fieldsets_available = DAO_CustomFieldset::getUsableByActorByContext($active_worker, CerberusContexts::CONTEXT_TICKET)}

{if $custom_fields || $custom_fieldsets_available}
<fieldset class="peek" style="{if $custom_fieldsets_available}padding-bottom:0px;{/if}">
	<legend>
		<label>
			{'common.update'|devblocks_translate|capitalize}
		</label>
	</legend>

	<div style="padding-bottom:10px;{if $custom_fields}{else}display:none;{/if}">
	{if !empty($custom_fields)}
		{include file="devblocks:cerberusweb.core::internal/custom_fields/bulk/form.tpl" bulk=true custom_fields_expanded=$draft->params.custom_fields}
	{/if}
	</div>

	{include file="devblocks:cerberusweb.core::internal/custom_fieldsets/peek_custom_fieldsets.tpl" context=CerberusContexts::CONTEXT_TICKET context_id=$ticket->id bulk=true custom_fieldsets_available=$custom_fieldsets_available}
</fieldset>
{/if}

<fieldset class="peek">
	<legend>
		<label>
			<input type="checkbox" class="cerb-reply-deliver-later-toggle" value="1" {if $draft->params.send_at}checked="checked"{/if}>
			Deliver later
		</label>
	</legend>

	<div style="{if $draft->params.send_at}{else}display:none;{/if}">
		<b>When should the message be delivered?</b> (leave blank to send immediately)<br>
		<input type="text" name="send_at" size="64" style="width:89%;" placeholder="now" value="{if !empty($draft)}{$draft->params.send_at}{/if}">
	</div>
</fieldset>

<div id="reply{$message->id}_buttons">
	<button type="button" class="send split-left" title="{if $pref_keyboard_shortcuts}(Ctrl+Shift+Enter){/if}"><span class="glyphicons glyphicons-send"></span> {if $is_forward}{'display.ui.forward'|devblocks_translate|capitalize}{else}{'display.ui.send_message'|devblocks_translate}{/if}</button><!--
	--><button type="button" class="split-right" onclick="$(this).next('ul').toggle();"><span class="glyphicons glyphicons-chevron-down" style="font-size:12px;color:white;"></span></button>
	<ul class="cerb-popupmenu cerb-float" style="margin-top:-5px;">
		<li><a href="javascript:;" class="send">{if $is_forward}{'display.ui.forward'|devblocks_translate}{else}{'display.ui.send_message'|devblocks_translate}{/if}</a></li>
		{if $active_worker->hasPriv('core.mail.save_without_sending')}<li><a href="javascript:;" class="save">{'display.ui.save_nosend'|devblocks_translate}</a></li>{/if}
	</ul>
	<button type="button" class="draft"><span class="glyphicons glyphicons-disk-save"></span> {'display.ui.continue_later'|devblocks_translate}</button>
	<button type="button" class="discard"><span class="glyphicons glyphicons-circle-remove" style="color:rgb(200,0,0);"></span> {'display.ui.discard'|devblocks_translate|capitalize}</button>
</div>
</form>

</div>

<script type="text/javascript">
$(function() {
	if(draftAutoSaveInterval == undefined)
		var draftAutoSaveInterval = null;

	var $frm = $('#reply{$message->id}_form');
	var $reply = $frm.closest('div.reply_frame');

	$frm.find('.cerb-editor-tabs').tabs({
		activate: function(event, ui) {
		},
		beforeActivate: function(event, ui) {
			if(ui.newTab.attr('data-cerb-tab') !== 'preview')
				return;

			Devblocks.getSpinner().appendTo(ui.newPanel.html(''));

			var formData = new FormData();
			formData.set('c', 'profiles');
			formData.set('a', 'invoke');
			formData.set('module', 'ticket');
			formData.set('action', 'previewReplyMessage');
			formData.set('id', $frm.find('input[name=id]').val());
			formData.set('format', $frm.find('input[name=format]').val());
			formData.set('group_id', $frm.find('select[name=group_id]').val());
			formData.set('bucket_id', $frm.find('select[name=bucket_id]').val());
			formData.set('html_template_id', $frm.find('select[name=html_template_id]').val());
			formData.set('content', $frm.find('textarea[name=content]').val());

			genericAjaxPost(formData, null, null, function(html) {
				ui.newPanel.html(html);
			});
		}
	});

	{if $recent_activity}
	$frm.hide();
	{/if}
	
	$reply.on('cerb-reply--close', function(e) {
		e.stopPropagation();
		
		{if 'inline' == $reply_format}
		$reply.parent().empty();
		{else}
		genericAjaxPopupClose($popup);
		{/if}
	});
	
	var onReplyFormInit = function() {
		var $collisions = $reply.find('.cerb-collision');
		
		// Collision detection
		
		$collisions.find('.cerb-collision--continue').on('click', function(e) {
			$collisions.remove();
			
			$frm.fadeIn();

			// Save a draft now
			$editor_toolbar_button_save_draft.click();
			
			// Start draft auto-save timer every 30 seconds
			if(null != draftAutoSaveInterval) {
				clearTimeout(draftAutoSaveInterval);
				draftAutoSaveInterval = null;
			}
			draftAutoSaveInterval = setInterval("$('#reply{$message->id}_form .cerb-reply-editor-toolbar-button--save').click();", 30000);
			
			// Move cursor
			editor.focus();
		});
		
		$collisions.find('.cerb-collision--cancel').on('click', function(e) {
			$reply.triggerHandler('cerb-reply--close');
		});
		
		// Disable ENTER submission on the FORM text input
		$frm
			.find('input:text')
			.keydown(function(e) {
				if(13 === e.which)
					e.preventDefault();
			})
			;
		
		$frm.find('.cerb-peek-trigger').cerbPeekTrigger();
		$frm.find('button.chooser-abstract').cerbChooserTrigger();
		
		// Chooser for To/Cc/Bcc recipients
		$frm.find('a.cerb-recipient-chooser')
			.click(function(e) {
				e.stopPropagation();
				var $trigger = $(this);
				var $input = $trigger.closest('tr').find('td:nth(1) input:text');
				
				var context = $trigger.attr('data-context');
				var query = $trigger.attr('data-query');
				var query_req = $trigger.attr('data-query-required');
				var chooser_url = 'c=internal&a=invoke&module=records&action=chooserOpen&context=' + encodeURIComponent(context);
				
				if(typeof query == 'string' && query.length > 0) {
					chooser_url += '&q=' + encodeURIComponent(query);
				}
				
				if(typeof query_req == 'string' && query_req.length > 0) {
					chooser_url += '&qr=' + encodeURIComponent(query_req);
				}
				
				$input.focus();
				
				var $chooser = genericAjaxPopup(Devblocks.uniqueId(), chooser_url, null, true, '90%');
				
				$chooser.one('chooser_save', function(event) {
					event.stopPropagation();
					
					if(typeof event.values == "object" && event.values.length > 0) {
						var val = $input.val();
						if(val.length > 0 && val.trim().substr(-1) != ',') {
							var new_val = val + ', ' + event.labels.join(', ');
							$input.val(new_val);
						} else {
							var new_val = val + (val.length == 0 || val.substr(-1) == ' ' ? '' : ' ') + event.labels.join(', ');
							$input.val(new_val);
						}
					}
				});
			})
		;
		
		// Autocompletes

		ajax.emailAutoComplete('#reply{$message->id}_form input[name=to]', { multiple: true } );
		ajax.emailAutoComplete('#reply{$message->id}_form input[name=cc]', { multiple: true } );
		ajax.emailAutoComplete('#reply{$message->id}_form input[name=bcc]', { multiple: true } );
		
		$frm.find('input:text[name=to], input:text[name=cc], input:text[name=bcc]').focus(function(event) {
			$('#reply{$message->id}_suggested').appendTo($(this).closest('td'));
		});
		
		// Drag/drop attachments
		
		var $attachments = $frm.find('fieldset.reply-attachments');
		$attachments.cerbAttachmentsDropZone();
		
		// Group and bucket
		
		$frm.find('select[name=group_id]').on('change', function(e) {
			var $select = $(this);
			var group_id = $select.val();
			var $bucket_options = $select.siblings('select.ticket-reply-bucket-options').find('option')
			var $bucket = $select.siblings('select[name=bucket_id]');
			
			$bucket.children().remove();
			
			$bucket_options.each(function() {
				var parent_id = $(this).attr('group_id');
				if(parent_id == '*' || parent_id == group_id)
					$(this).clone().appendTo($bucket);
			});
			
			$bucket.focus();
		});

		// Text editor

		var $editor = $('#reply_{$message->id}')
			.cerbTextEditor()
			.cerbTextEditorAutocompleteReplies({
				'mode': 'reply'
			})
			;

		var editor = $editor[0];

		var $editor_toolbar = $frm.find('.cerb-code-editor-toolbar')
			.cerbTextEditorToolbarMarkdown()
			;

		// Paste images

		$editor.cerbTextEditorInlineImagePaster({
			attachmentsContainer: $attachments,
			toolbar: $editor_toolbar
		})

		$editor_toolbar.find('.cerb-reply-editor-toolbar-button--encrypt')
			.click(function(event) {
				var $button = $(this);
				var $hidden = $frm.find('> input:hidden[name=options_gpg_encrypt]');
				var $icon = $button.find('span.glyphicons');

				if('1' === $hidden.val()) {
					$hidden.val(0);
					$button
						.removeClass('cerb-code-editor-toolbar-button--enabled')
						.addClass('cerb-code-editor-toolbar-button--disabled')
					;
					$icon
						.removeClass('glyphicons-lock')
						.addClass('glyphicons-unlock')
					;
				} else {
					$hidden.val(1);
					$button
						.removeClass('cerb-code-editor-toolbar-button--disabled')
						.addClass('cerb-code-editor-toolbar-button--enabled')
					;
					$icon
						.removeClass('glyphicons-unlock')
						.addClass('glyphicons-lock')
					;

					// Enable signing
					if(!$editor_toolbar_button_sign.hasClass('cerb-code-editor-toolbar-button--enabled')) {
						$editor_toolbar_button_sign.click();
					}
				}
			})
			;

		var $editor_toolbar_button_sign = $editor_toolbar.find('.cerb-reply-editor-toolbar-button--sign')
			.click(function() {
				var $button = $(this);
				var $hidden = $frm.find('> input:hidden[name=options_gpg_sign]');
				var $icon = $button.find('span.glyphicons');

				if('1' === $hidden.val()) {
					$hidden.val(0);
					$button
						.removeClass('cerb-code-editor-toolbar-button--enabled')
						.addClass('cerb-code-editor-toolbar-button--disabled')
					;
					$icon
						.removeClass('glyphicons-user-lock')
						.addClass('glyphicons-user')
					;
				} else {
					$hidden.val(1);
					$button
						.removeClass('cerb-code-editor-toolbar-button--disabled')
						.addClass('cerb-code-editor-toolbar-button--enabled')
					;
					$icon
						.removeClass('glyphicons-user')
						.addClass('glyphicons-user-lock')
					;
				}
			})
			;

		var $editor_toolbar_button_save_draft = $editor_toolbar.find('.cerb-reply-editor-toolbar-button--save')
			.click(function(event) {
				event.stopPropagation();
				var $this = $(this);

				if($this.attr('disabled'))
					return;

				$this.attr('disabled','disabled');

				var formData = new FormData($frm[0]);
				formData.set('c', 'profiles');
				formData.set('a', 'invoke');
				formData.set('module', 'draft');
				formData.set('action', 'saveDraftReply');
				formData.set('is_ajax', '1');

				genericAjaxPost(formData,null,'',
					function(obj) {
						$this.removeAttr('disabled');

						if(!obj)
							return;

						if(obj.error) {
							$('#divDraftStatus{$message->id}').html(obj.error);

						} else if (obj.html && obj.draft_id) {
							$('#divDraftStatus{$message->id}').html(obj.html);
							$frm.find('input[name=draft_id]').val(obj.draft_id);
						}
					}
				);
			})
			;

		// Toolbar

		$reply.find('[data-cerb-toolbar]')
			.cerbToolbar({
				caller: {
					name: 'cerb.toolbar.mail.reply',
					params: {
						selected_text: ''
					}
				},
				start: function(formData) {
					formData.set('caller[params][selected_text]', $editor.cerbTextEditor('getSelection'));
				},
				done: function(e) {
					if(e.type !== 'cerb-interaction-done')
						return;

					if(!e.eventData || !e.eventData.exit)
						return;

					if (e.eventData.exit === 'error') {
						// [TODO] Show error

					} else if(e.eventData.exit === 'return' && e.eventData.return.snippet) {
						$editor.cerbTextEditor('replaceSelection', e.eventData.return.snippet);
						setTimeout(function() { $editor.focus(); }, 25);
					}
				}
			})
		;

		// Formatting

		$editor_toolbar.find('.cerb-editor-toolbar-button--formatting').on('click', function() {
			var $button = $(this);

			if('html' === $button.attr('data-format')) {
				$editor_toolbar.triggerHandler($.Event('cerb-editor-toolbar-formatting-set', { enabled: false }));
			} else {
				$editor_toolbar.triggerHandler($.Event('cerb-editor-toolbar-formatting-set', { enabled: true }));
			}
		});

		$editor_toolbar.on('cerb-editor-toolbar-formatting-set', function(e) {
			var $button = $editor_toolbar.find('.cerb-editor-toolbar-button--formatting');

			if(e.enabled) {
				$frm.find('input:hidden[name=format]').val('parsedown');
				$button.attr('data-format', 'html');
				$button.text('Formatting on');
				$editor_toolbar.find('.cerb-code-editor-subtoolbar-format-html').css('display','inline-block');
				$frm.find('[data-cerb-reply-html-template]').show();
			} else {
				$frm.find('input:hidden[name=format]').val('');
				$button.attr('data-format', 'plaintext');
				$button.text('Formatting off');
				$editor_toolbar.find('.cerb-code-editor-subtoolbar-format-html').css('display','none');
				$frm.find('[data-cerb-reply-html-template]').hide();
			}
		});

		// Upload image
		$editor_toolbar.on('cerb-editor-toolbar-image-inserted', function(event) {
			event.stopPropagation();

			var new_event = $.Event('cerb-chooser-save', {
				labels: event.labels,
				values: event.values
			});

			$reply.find('button.chooser_file').triggerHandler(new_event);

			$editor.cerbTextEditor('insertText', '![inline-image](' + event.url + ')');

			setTimeout(function() {
				$editor.focus();
			}, 100);
		});

		// Commands
		$editor_toolbar.find('.cerb-markdown-editor-toolbar-button--commands').on('click', function(e) {
			$editor.cerbTextEditor('insertText', '#');
			$editor.autocomplete('search');
		});

		// Snippets
		$editor_toolbar.on('cerb-editor-toolbar-snippet-inserted', function(event) {
			if(undefined == event.snippet_id)
				return;

			// Now we need to read in each snippet as either 'raw' or 'parsed' via Ajax
			var formData = new FormData();
			formData.set('c', 'profiles');
			formData.set('a', 'invoke');
			formData.set('module', 'snippet');
			formData.set('action', 'paste');
			formData.set('id', event.snippet_id);
			formData.set('context_ids[cerberusweb.contexts.ticket]', '{$ticket->id}');
			formData.set('context_ids[cerberusweb.contexts.worker]', '{$active_worker->id}');

			genericAjaxPost(formData, null, null, function(json) {
				// If the content has placeholders, use that popup instead
				if (json.has_prompts) {
					var $popup_paste = genericAjaxPopup('snippet_paste', 'c=profiles&a=invoke&module=snippet&action=getPrompts&id=' + encodeURIComponent(json.id) + '&context_id=' + encodeURIComponent(json.context_id), null, false, '50%');

					$popup_paste.bind('snippet_paste', function (event) {
						if (null == event.text)
							return;

						$editor.cerbTextEditor('insertText', event.text);
					});

				} else {
					$editor.cerbTextEditor('insertText', json.text);
				}
			});
		});

		// Snippets
		var $editor_toolbar_button_snippets = $editor_toolbar.find('.cerb-markdown-editor-toolbar-button--snippets').on('click', function () {
			var context = 'cerberusweb.contexts.snippet';
			var chooser_url = 'c=internal&a=invoke&module=records&action=chooserOpen&qr=' + encodeURIComponent('type:[plaintext,ticket,worker]') + '&single=1&context=' + encodeURIComponent(context);

			var $chooser = genericAjaxPopup(Devblocks.uniqueId(), chooser_url, null, true, '90%');

			$chooser.on('chooser_save', function (event) {
				if (!event.values || 0 === event.values.length)
					return;

				var snippet_id = event.values[0];

				if (null == snippet_id)
					return;

				$editor_toolbar.triggerHandler(new $.Event('cerb-editor-toolbar-snippet-inserted', {
					'snippet_id': snippet_id
				}));
			});
		});

		// Dates
		
		$frm.find('input[name=send_at]')
			.cerbDateInputHelper()
			;
			
		$frm.find('input[name=ticket_reopen]')
			.cerbDateInputHelper({
				submit: function(e) {
					$('#reply{$message->id}_buttons a.send').click();
				}
			})
			
		// Insert suggested on click
		
		$('#reply{$message->id}_suggested').find('a.suggested').click(function(e) {
			var $this = $(this);
			var $sug = $this.text();
			
			var $to = $this.closest('td').find('input:text:first');
			var $val = $to.val();
			var $len = $val.length;
			
			var $last = null;

			if($len>0)
				$last = $val.substring($len-1);
			
			if(0 === $len || $last === ' ')
				$to.val($val+$sug);
			else if($last === ',')
				$to.val($val + ' '+$sug);
			else $to.val($val + ', '+$sug);
				$to.focus();
			
			var $ul = $this.closest('ul');
			$this.closest('li').remove();

			if(0 === $ul.find('li').length)
				$ul.closest('div').remove();
		});

		// Deliver later

		$frm.find('.cerb-reply-deliver-later-toggle').on('click', function(e) {
			e.stopPropagation();

			var $checkbox = $(this);
			var $div = $checkbox.closest('fieldset').find('> div');
			
			if($checkbox.is(':checked')) {
				$div
					.show()
					.find('input:text')
						.focus()
				;
			} else {
				$div
					.hide()
					.find('input:text')
						.val('')
				;
			}
		});

		// Focus
		
		{if !$recent_activity}
			{if !$is_forward}
				$editor.focus();
			{else}
				$frm.find('input:text[name=to]').focus();
			{/if}
		{/if}
		
		// Reply action buttons
		
		var $buttons = $('#reply{$message->id}_buttons');

		$buttons.find('button.send').on('click', function(e) {
			if(e.originalEvent && e.originalEvent.detail && e.originalEvent.detail > 1)
				return;
			
			$buttons.find('a.send').click();
		});
		
		$buttons.find('button.discard').on('click', function(e) {
			e.stopPropagation();

			window.onbeforeunload = null;
			
			if(confirm('Are you sure you want to discard this reply?')) {
				if(null != draftAutoSaveInterval) { 
					clearTimeout(draftAutoSaveInterval);
					draftAutoSaveInterval = null; 
				}
				
				var draft_id = $frm.find('input:hidden[name=draft_id]').val();

				var formData = new FormData();
				formData.set('c', 'profiles');
				formData.set('a', 'invoke');
				formData.set('module', 'draft');
				formData.set('action', 'deleteDraft');
				formData.set('draft_id', draft_id);

				genericAjaxPost(formData, '', '', function(o) {
					$reply.trigger('cerb-reply-discard');
					
					$('#draft'+encodeURIComponent(draft_id)).remove();
					$reply.triggerHandler('cerb-reply--close');
				});
			}
		});
		
		$buttons.find('a.send').click(function(e) {
			if(e.originalEvent && e.originalEvent.detail && e.originalEvent.detail > 1)
				return;
			
			var $button = $(this);

			Devblocks.clearAlerts();
			showLoadingPanel();
			$button.closest('td').hide();

			var formData = new FormData($frm[0]);
			formData.set('c', 'profiles');
			formData.set('a', 'invoke');
			formData.set('module', 'ticket');
			formData.set('action', 'validateReplyJson');

			// Validate via Ajax before sending
			genericAjaxPost(formData, '', '', function(json) {
				if(json && json.status) {
					if(null != draftAutoSaveInterval) {
						clearTimeout(draftAutoSaveInterval);
						draftAutoSaveInterval = null;
					}
					
					$frm.find('input:hidden[name=reply_mode]').val('');
					
					genericAjaxPost($frm, '', null, function(json) {
						hideLoadingPanel();

						var event = new $.Event('cerb-reply-sent', {
							record: json
						});
						$reply.trigger(event);
						
						$reply.triggerHandler('cerb-reply--close');
					});
					
				} else {
					Devblocks.createAlertError(json.message);
					hideLoadingPanel();
					$button.closest('td').show();
				}
			});
		});
		
		$buttons.find('a.save').on('click', function(e) {
			if(e.originalEvent && e.originalEvent.detail && e.originalEvent.detail > 1)
				return;
			
			var $button = $(this);

			Devblocks.clearAlerts();
			showLoadingPanel();
			$button.closest('td').hide();

			var formData = new FormData($frm[0]);
			formData.set('c', 'profiles');
			formData.set('a', 'invoke');
			formData.set('module', 'ticket');
			formData.set('action', 'validateReplyJson');

			// Validate via Ajax before saving
			genericAjaxPost(formData, '', '', function(json) {
				if(json && json.status) {
					if(null != draftAutoSaveInterval) {
						clearTimeout(draftAutoSaveInterval);
						draftAutoSaveInterval = null;
					}

					$frm.find('input:hidden[name=reply_mode]').val('save');

					genericAjaxPost($frm, '', null, function(json) {
						hideLoadingPanel();

						var event = new $.Event('cerb-reply-saved', {
							record: json
						});
						$reply.trigger(event);

						$reply.triggerHandler('cerb-reply--close');
					});

				} else {
					Devblocks.createAlertError(json.message);
					hideLoadingPanel();
					$button.closest('td').show();
				}
			});
		});
		
		$buttons.find('.draft').click(function(e) {
			if(e.originalEvent && e.originalEvent.detail && e.originalEvent.detail > 1)
				return;

			var $button = $(this);

			Devblocks.clearAlerts();
			showLoadingPanel();
			$button.closest('td').hide();

			var formData = new FormData($frm[0]);
			formData.set('c', 'profiles');
			formData.set('a', 'invoke');
			formData.set('module', 'ticket');
			formData.set('action', 'validateReplyJson');

			// Validate via Ajax before saving
			genericAjaxPost(formData, '', '', function(json) {
				if(json && json.status) {
					if(null != draftAutoSaveInterval) {
						clearTimeout(draftAutoSaveInterval);
						draftAutoSaveInterval = null;
					}

					var formData = new FormData($frm[0]);
					formData.set('c', 'profiles');
					formData.set('a', 'invoke');
					formData.set('module', 'draft');
					formData.set('action', 'saveDraftReply');

					genericAjaxPost(formData, '', null, function(json) {
						hideLoadingPanel();
						$button.closest('td').show();

						var event = new $.Event('cerb-reply-draft', {
							record: json
						});
						$reply.trigger(event);

						$reply.triggerHandler('cerb-reply--close');
					});

				} else {
					Devblocks.createAlertError(json.message);
					hideLoadingPanel();
					$button.closest('td').show();
				}
			});
		});
		
		// Focus 
		{if $recent_activity}
			$collisions.find('button:first').focus();
			
		{else}
			$editor_toolbar_button_save_draft.click(); // save now
			if(null != draftAutoSaveInterval) {
				clearTimeout(draftAutoSaveInterval);
				draftAutoSaveInterval = null;
			}
			// and every 30 sec
			draftAutoSaveInterval = setInterval(function() {
				$('#reply{$message->id}_form .cerb-reply-editor-toolbar-button--save').click();
			}, 30000);
		{/if}
		
		// Files
		$frm.find('button.chooser_file').each(function() {
			ajax.chooserFile(this,'file_ids');
		});
		
		// Menu
		$frm.find('button.send')
			.siblings('ul.cerb-popupmenu')
			.hover(
				function(e) { }, 
				function(e) { $(this).hide(); }
			)
			.find('> li')
			.click(function(e) {
				$(this).closest('ul.cerb-popupmenu').hide();
				
				e.stopPropagation();
				if(!$(e.target).is('li'))
				return;
				
				$(this).find('a').trigger('click');
			})
		;
		
		// Shortcuts

		{if $pref_keyboard_shortcuts}
			// Send focus
			$editor.bind('keydown', 'ctrl+return alt+return meta+return', function(e) {
				e.preventDefault();
				$frm.find('button.send').focus();
			});

			// Send now
			$editor.bind('keydown', 'ctrl+shift+return alt+shift+return meta+shift+return', function(e) {
				e.preventDefault();
				$frm.find('button.send').click();
			});

			// Status Close
			$editor.bind('keydown', 'ctrl+shift+c', function(e) {
				e.preventDefault();
				try {
					var $reply_status = $('#replyStatus{$message->id}');
					var $radio = $reply_status.find('input:radio[name=status_id]');
					$radio.filter('.status_closed').click();
					$reply_status
						.find('input:text[name=ticket_reopen]')
						.select()
						.focus()
					;
				} catch(ex) { }
			});

			// Status Open
			$editor.bind('keydown', 'ctrl+shift+o', function(e) {
				e.preventDefault();
				try {
					var $reply_status = $('#replyStatus{$message->id}');
					var $radio = $reply_status.find('input:radio[name=status_id]');
					$radio.filter('.status_open').click();
					$reply_status
						.find('select[name=group_id]')
						.select()
						.focus()
					;
				} catch(ex) { }
			});

			// Status Waiting
			$editor.bind('keydown', 'ctrl+shift+w', function(e) {
				e.preventDefault();
				try {
					var $reply_status = $('#replyStatus{$message->id}');
					var $radio = $reply_status.find('input:radio[name=status_id]');
					$radio.filter('.status_waiting').click();
					$reply_status
						.find('input:text[name=ticket_reopen]')
						.select()
						.focus()
					;
				} catch(ex) { }
			});

			// Insert signature
			$editor.bind('keydown', 'ctrl+shift+g', function(e) {
				e.preventDefault();
				try {
                    $editor.cerbTextEditor('insertText', '#signature\n');
				} catch(ex) { }
			});

			// Insert snippet
			$editor.bind('keydown', 'ctrl+shift+i', function(e) {
				e.preventDefault();
				try {
                    $editor_toolbar_button_snippets.click();
				} catch(ex) { }
			});

			// Fix line endings
			$editor.bind('keydown', 'ctrl+shift+l', function(e) {
				e.preventDefault();
				try {
					$editor.val($editor.val().replaceAll("\n\n\n","\n\n"));
				} catch(e) { }
			});

			// Reformat quotes
			$editor.bind('keydown', 'ctrl+shift+q', function(e) {
				e.preventDefault();
				try {
					var txt = $editor.val();

					var lines = txt.split("\n");

					var bins = [];
					var last_prefix = null;
					var wrap_to = 76;

					// Sort lines into bins
					for(i in lines) {
						var line = lines[i];
						var matches = line.match(/^((\> )+)/);
						var prefix = '';

						if(matches)
							prefix = matches[1];

						if(prefix !== last_prefix)
							bins.push({ prefix:prefix, lines:[] });

						// Strip the prefix
						line = line.substring(prefix.length);

						idx = Math.max(bins.length-1, 0);
						bins[idx].lines.push(line);

						last_prefix = prefix;
					}

					// Rewrap quoted blocks
					for(var i in bins) {
						prefix = bins[i].prefix;
						var l = 0;
						var bail = 25000; // prevent infinite loops

						if(prefix.length === 0)
							continue;

						while(undefined !== bins[i].lines[l] && bail > 0) {
							line = bins[i].lines[l];
							var boundary = Math.max(0, wrap_to-prefix.length);

							if(line.length > 0 && boundary > 0 && line.length > boundary) {
								// Try to split on a space
								var pos = line.lastIndexOf(' ', boundary);
								var break_word = (-1 === pos);

								var overflow = line.substring(break_word ? boundary : (pos+1));
								bins[i].lines[l] = line.substring(0, break_word ? boundary : pos);

								// If we don't have more lines, add a new one
								if(overflow) {
									if(undefined !== bins[i].lines[l+1]) {
										if(bins[i].lines[l+1].length === 0) {
											bins[i].lines.splice(l+1,0,overflow);
										} else {
											bins[i].lines[l+1] = overflow + " " + bins[i].lines[l+1];
										}
									} else {
										bins[i].lines.push(overflow);
									}
								}
							}

							l++;
							bail--;
						}
					}

					var out = "";

					for(i in bins) {
						for(l in bins[i].lines) {
							out += bins[i].prefix + bins[i].lines[l] + "\n";
						}
					}

					$editor.val($.trim(out));

				} catch(ex) { }
			});
		{/if}

		{* Run custom jQuery scripts from VA behavior *}
		
		{if !empty($jquery_scripts)}
		$('#reply{$message->id}_form').closest('div.reply_frame').each(function(e) {
			{foreach from=$jquery_scripts item=jquery_script}
			try {
				{$jquery_script nofilter}
			} catch(e) { }
			{/foreach}
		});
		{/if}
	}
	
	{if !$reply_format}
		var $popup = genericAjaxPopupFind($reply);
		
		$popup.one('popup_open',function() {
			$popup.dialog('option','title','{if $is_forward}{'display.ui.forward'|devblocks_translate|capitalize}{else}{'common.reply'|devblocks_translate|capitalize}{/if}');
			$popup.css('overflow', 'inherit');
			
			// Close confirmation
			
			$popup.on('dialogbeforeclose', function(e) {
				var keycode = e.keyCode || e.which;
				if(keycode === 27)
					return confirm('{'warning.core.editor.close'|devblocks_translate}');
			});
		});
	{/if}
	
	onReplyFormInit();
});
</script>