{$mail_reply_html = DAO_WorkerPref::get($active_worker->id, 'mail_reply_html', 0)}
{$is_html = ($draft && $draft->params.format == 'parsedown') || $mail_reply_html}

<form action="{devblocks_url}{/devblocks_url}" method="POST" id="frmComposePeek{$popup_uniqid}" onsubmit="return false;">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="draft">
<input type="hidden" name="action" value="saveComposePeek">
<input type="hidden" name="view_id" value="{$view_id}">
<input type="hidden" name="draft_id" value="{$draft->id}">
<input type="hidden" name="format" value="{if $is_html}parsedown{/if}">
<input type="hidden" name="options_gpg_encrypt" value="{if $draft->params.options_gpg_encrypt}1{/if}">
<input type="hidden" name="options_gpg_sign" value="{if $draft->params.options_gpg_sign}1{/if}">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<table cellpadding="0" cellspacing="2" border="0" width="98%">
	<tr>
		<td width="0%" nowrap="nowrap" align="right"><b>From:</b>&nbsp;</td>
		<td width="100%">
			<select name="group_id">
				{foreach from=$groups item=group key=group_id}
				{if $active_worker->isGroupMember($group_id)}
				<option value="{$group_id}" member="true" {if $defaults.group_id == $group_id}selected="selected"{/if}>{$group->name}</option>
				{/if}
				{/foreach}
			</select>
			<select class="ticket-peek-bucket-options" style="display:none;">
				{foreach from=$buckets item=bucket key=bucket_id}
				<option value="{$bucket_id}" group_id="{$bucket->group_id}">{$bucket->name}</option>
				{/foreach}
			</select>
			<select name="bucket_id">
				{foreach from=$buckets item=bucket key=bucket_id}
					{if $bucket->group_id == $defaults.group_id}
					<option value="{$bucket_id}" {if $defaults.bucket_id == $bucket_id}selected="selected"{/if}>{$bucket->name}</option>
					{/if}
				{/foreach}
			</select>
		</td>
	</tr>
	<tr>
		<td width="0%" nowrap="nowrap" valign="top" align="right">{'common.organization'|devblocks_translate|capitalize}:&nbsp;</td>
		<td width="100%">
			<input type="text" name="org_name" value="{if !empty($org)}{$org}{else}{$draft->params.org_name}{/if}" style="border:1px solid rgb(180,180,180);padding:2px;width:98%;" placeholder="(optional) Link this ticket to an organization for suggested recipients">
		</td>
	</tr>
	<tr>
		<td width="0%" nowrap="nowrap" valign="top" align="right"><a href="javascript:;" class="cerb-recipient-chooser" data-context="{CerberusContexts::CONTEXT_ADDRESS}" data-query="">{'message.header.to'|devblocks_translate|capitalize}</a>:&nbsp;</td>
		<td width="100%">
			<input type="text" name="to" id="emailinput{$popup_uniqid}" value="{if $to}{$to}{elseif $draft}{$draft->getParam('to')}{/if}" style="border:1px solid rgb(180,180,180);padding:2px;width:98%;" placeholder="These recipients will automatically be included in all future correspondence">

			<div id="compose_suggested{$popup_uniqid}" style="display:none;">
				<a href="javascript:;" onclick="$(this).closest('div').hide();">x</a>
				<b>Consider adding these recipients:</b>
				<ul class="bubbles"></ul>
			</div>
		</td>
	</tr>
	<tr>
		<td width="0%" nowrap="nowrap" valign="top" align="right"><a href="javascript:;" class="cerb-recipient-chooser" data-context="{CerberusContexts::CONTEXT_ADDRESS}" data-query="">{'message.header.cc'|devblocks_translate|capitalize}</a>:&nbsp;</td>
		<td width="100%">
			<input type="text" name="cc" style="width:98%;border:1px solid rgb(180,180,180);padding:2px;" value="{$draft->params.cc}" placeholder="These recipients will publicly receive a copy of this message" autocomplete="off">
		</td>
	</tr>
	<tr>
		<td width="0%" nowrap="nowrap" valign="top" align="right"><a href="javascript:;" class="cerb-recipient-chooser" data-context="{CerberusContexts::CONTEXT_ADDRESS}" data-query="">{'message.header.bcc'|devblocks_translate|capitalize}</a>:&nbsp;</td>
		<td width="100%">
			<input type="text" name="bcc" style="width:98%;border:1px solid rgb(180,180,180);padding:2px;" value="{$draft->params.bcc}" placeholder="These recipients will secretly receive a copy of this message" autocomplete="off">
		</td>
	</tr>
	<tr>
		<td width="0%" nowrap="nowrap" valign="top" align="right"><b>{'message.header.subject'|devblocks_translate|capitalize}:</b>&nbsp;</td>
		<td width="100%">
			<input type="text" name="subject" style="width:98%;border:1px solid rgb(180,180,180);padding:2px;" value="{if $draft}{$draft->getParam('subject')}{/if}" autocomplete="off" maxlength="255">
		</td>
	</tr>
	<tr>
		<td width="100%" colspan="2" style="position:relative;">
			<div id="divDraftStatus{$popup_uniqid}"></div>

			<div class="cerb-editor-tabs">
				<ul>
					<li data-cerb-tab="editor"><a href="#compose{$popup_uniqid}EditorPanel">{'common.editor'|devblocks_translate|capitalize}</a></li>
					<li data-cerb-tab="preview"><a href="#compose{$popup_uniqid}EditorPreviewPanel">{'common.preview'|devblocks_translate|capitalize}</a></li>
				</ul>

				<div id="compose{$popup_uniqid}EditorPanel">
					<div class="cerb-code-editor-toolbar">
					{if $interactions_menu}
						<div id="divComposeInteractions{$popup_uniqid}" style="display:inline-block;">
							{include file="devblocks:cerberusweb.core::events/interaction/interactions_menu.tpl" button_classes="cerb-code-editor-toolbar-button cerb-reply-editor-toolbar-button--interactions"}
						</div>
						<div class="cerb-code-editor-toolbar-divider"></div>
					{/if}

					<button type="button" title="Toggle formatting" class="cerb-code-editor-toolbar-button cerb-editor-toolbar-button--formatting" data-format="{if $is_html}html{else}plaintext{/if}">{if $is_html}Formatting on{else}Formatting off{/if}</button>
					<div class="cerb-code-editor-toolbar-divider"></div>

					<div class="cerb-code-editor-subtoolbar-format-html" style="display:inline-block;{if !$is_html}display:none;{/if}">
						<button type="button" title="Bold (Ctrl+B)" data-cerb-key-binding="ctrl+b" class="cerb-code-editor-toolbar-button cerb-markdown-editor-toolbar-button--bold"><span class="glyphicons glyphicons-bold"></span></button>
						<button type="button" title="Italics (Ctrl+I)" data-cerb-key-binding="ctrl+i" class="cerb-code-editor-toolbar-button cerb-markdown-editor-toolbar-button--italic"><span class="glyphicons glyphicons-italic"></span></button>
						<button type="button" title="Link (Ctrl+K)" data-cerb-key-binding="ctrl+k" class="cerb-code-editor-toolbar-button cerb-markdown-editor-toolbar-button--link"><span class="glyphicons glyphicons-link"></span></button>
						<button type="button" title="Image (Ctrl+M)" data-cerb-key-binding="ctrl+m" class="cerb-code-editor-toolbar-button cerb-markdown-editor-toolbar-button--image"><span class="glyphicons glyphicons-picture"></span></button>
						<button type="button" title="List" class="cerb-code-editor-toolbar-button cerb-markdown-editor-toolbar-button--list"><span class="glyphicons glyphicons-list"></span></button>
						<button type="button" title="Quote (Ctrl+Q)" data-cerb-key-binding="ctrl+q" class="cerb-code-editor-toolbar-button cerb-markdown-editor-toolbar-button--quote"><span class="glyphicons glyphicons-quote"></span></button>
						<button type="button" title="Code (Ctrl+O)" data-cerb-key-binding="ctrl+o" class="cerb-code-editor-toolbar-button cerb-markdown-editor-toolbar-button--code"><span class="glyphicons glyphicons-embed"></span></button>
						<button type="button" title="Table" class="cerb-code-editor-toolbar-button cerb-markdown-editor-toolbar-button--table"><span class="glyphicons glyphicons-table"></span></button>
						<div class="cerb-code-editor-toolbar-divider"></div>
					</div>

					<button type="button" title="Insert #command" class="cerb-code-editor-toolbar-button cerb-markdown-editor-toolbar-button--commands"><span class="glyphicons glyphicons-sampler"></span></button>
					<button type="button" title="Insert snippet (Ctrl+Shift+Period)" class="cerb-code-editor-toolbar-button cerb-markdown-editor-toolbar-button--snippets"><span class="glyphicons glyphicons-notes-2"></span></button>
					<button type="button" title="Save draft (Ctrl+S)" data-cerb-key-binding="ctrl+s" class="cerb-code-editor-toolbar-button cerb-reply-editor-toolbar-button--save"><span class="glyphicons glyphicons-floppy-save"></span></button>
					<div class="cerb-code-editor-toolbar-divider"></div>

					<button type="button" title="{'common.encrypt'|devblocks_translate|capitalize}" class="cerb-code-editor-toolbar-button cerb-reply-editor-toolbar-button--encrypt {if $draft->params.options_gpg_encrypt}cerb-code-editor-toolbar-button--enabled{/if}"><span class="glyphicons {if $draft->params.options_gpg_encrypt}glyphicons-lock{else}glyphicons-unlock{/if}"></span></button>
					<button type="button" title="{'common.encrypt.sign'|devblocks_translate|capitalize}" class="cerb-code-editor-toolbar-button cerb-reply-editor-toolbar-button--sign {if $draft->params.options_gpg_sign}cerb-code-editor-toolbar-button--enabled{/if}"><span class="glyphicons {if $draft->params.options_gpg_sign}glyphicons-user-lock{else}glyphicons-user{/if}"></span></button>

					{if $html_templates}
					<div class="cerb-code-editor-subtoolbar-format-html" style="display:inline-block;{if !$is_html}display:none;{/if}">
						<div class="cerb-code-editor-toolbar-divider"></div>
						<select name="html_template_id" style="max-width:150px;">
							<optgroup label="{'common.template'|devblocks_translate|capitalize}">
								<option value="">({'common.default'|devblocks_translate|capitalize})</option>
								{foreach from=$html_templates item=html_template}
									<option value="{$html_template->id}" {if $draft->params.html_template_id==$html_template->id}selected="selected"{/if}>{$html_template->name}</option>
								{/foreach}
							</optgroup>
						</select>
					</div>
					{/if}
				</div>

				<textarea id="divComposeContent{$popup_uniqid}" name="content" style="box-sizing:border-box;">{if $draft && $draft->getParam('content')}{$draft->getParam('content')}{else}{if $defaults.signature_pos}



#signature
#cut{/if}{/if}</textarea>
				</div>

				<div id="compose{$popup_uniqid}EditorPreviewPanel" style="min-height:100px;max-height:400px;overflow:auto;border:1px dotted rgb(150,150,150);padding:5px;"></div>
			</div>

		</td>
	</tr>
</table>

<fieldset class="peek compose-attachments">
	<legend>{'common.attachments'|devblocks_translate|capitalize}</legend>
	<button type="button" class="chooser_file"><span class="glyphicons glyphicons-paperclip"></span></button>
	<ul class="bubbles chooser-container">
	{if $draft->params.file_ids}
	{foreach from=$draft->params.file_ids item=file_id}
		{$file = DAO_Attachment::get($file_id)}
		{if !empty($file)}
			<li><input type="hidden" name="file_ids[]" value="{$file_id}">{$file->name} ({$file->storage_size} bytes) <a href="javascript:;" onclick="$(this).parent().remove();"><span class="glyphicons glyphicons-circle-remove"></span></a></li>
		{/if} 
	{/foreach}
	{/if}
	</ul>
</fieldset>

<fieldset class="peek">
	<legend>{'common.properties'|devblocks_translate|capitalize}</legend>
	
	<div>
		<b>Status:</b>

		<label {if $pref_keyboard_shortcuts}title="(Ctrl+Shift+O)"{/if}><input type="radio" name="status_id" value="{Model_Ticket::STATUS_OPEN}" class="status_open" {if $defaults.status=='open'}checked="checked"{/if} onclick="toggleDiv('divComposeClosed{$popup_uniqid}','none');"> {'status.open'|devblocks_translate}</label>
		<label {if $pref_keyboard_shortcuts}title="(Ctrl+Shift+W)"{/if}><input type="radio" name="status_id" value="{Model_Ticket::STATUS_WAITING}" class="status_waiting" {if $defaults.status=='waiting'}checked="checked"{/if} onclick="toggleDiv('divComposeClosed{$popup_uniqid}','block');"> {'status.waiting'|devblocks_translate}</label>
		{if $active_worker->hasPriv('core.ticket.actions.close')}<label {if $pref_keyboard_shortcuts}title="(Ctrl+Shift+C)"{/if}><input type="radio" name="status_id" value="{Model_Ticket::STATUS_CLOSED}" class="status_closed" {if $defaults.status=='closed'}checked="checked"{/if} onclick="toggleDiv('divComposeClosed{$popup_uniqid}','block');"> {'status.closed'|devblocks_translate}</label>{/if}

		<div id="divComposeClosed{$popup_uniqid}" style="display:{if $defaults.status=='open'}none{else}block{/if};margin:5px 0px 10px 20px;">
			<b>{'display.reply.next.resume'|devblocks_translate}</b><br>
			{'display.reply.next.resume_eg'|devblocks_translate}<br>
			<input type="text" name="ticket_reopen" size="64" class="input_date" value="{$draft->params.ticket_reopen}"><br>
			{'display.reply.next.resume_blank'|devblocks_translate}<br>
		</div>
	</div>

	<div style="margin-top:5px;">
		<b>{'common.owner'|devblocks_translate|capitalize}:</b>

		<ul class="bubbles chooser-container">
			{foreach from=$workers item=v key=k}
				{if !$v->is_disabled && $draft->params.owner_id == $v->id}
					<li><img class="cerb-avatar" src="{devblocks_url}c=avatars&context=worker&context_id={$v->id}{/devblocks_url}?v={$v->updated}"><input type="hidden" name="owner_id" value="{$v->id}"><a href="javascript:;" class="cerb-peek-trigger no-underline" data-context="{CerberusContexts::CONTEXT_WORKER}" data-context-id="{$v->id}">{$v->getName()}</a></li>
				{/if}
			{/foreach}
		</ul>
		<button type="button" class="chooser-abstract" data-context="{CerberusContexts::CONTEXT_WORKER}" data-query="isDisabled:n" data-field-name="owner_id" data-autocomplete="isDisabled:n" data-autocomplete-if-empty="true" data-single="true"><span class="glyphicons glyphicons-search"></span></button>
	</div>

	<div style="margin-top:5px;">
		<b>{'common.watchers'|devblocks_translate|capitalize}:</b>

		<ul class="bubbles chooser-container">
			{if is_array($draft->params.watcher_ids)}
			{foreach from=$workers item=v key=k}
				{if !$v->is_disabled && in_array($v->id,$draft->params.watcher_ids)}
					<li><img class="cerb-avatar" src="{devblocks_url}c=avatars&context=worker&context_id={$v->id}{/devblocks_url}?v={$v->updated}"><input type="hidden" name="watcher_ids[]" value="{$v->id}"><a href="javascript:;" class="cerb-peek-trigger no-underline" data-context="{CerberusContexts::CONTEXT_WORKER}" data-context-id="{$v->id}">{$v->getName()}</a></li>
				{/if}
			{/foreach}
			{/if}
		</ul>
		<button type="button" class="chooser-abstract" data-context="{CerberusContexts::CONTEXT_WORKER}" data-query="isDisabled:n" data-field-name="watcher_ids[]" data-autocomplete="isDisabled:n"><span class="glyphicons glyphicons-search"></span></button>
	</div>

	<div style="margin-top:5px;">
		<b>{'common.options'|devblocks_translate|capitalize}:</b><br>

		<div style="padding-left:10px;">
			<label>
				<input type="checkbox" name="options_dont_send" value="1" {if $draft->params.options_dont_send}checked="checked"{/if}>
				Start a new conversation without sending a copy of this message to the recipients
			</label>
		</div>
	</div>
</fieldset>

{$custom_field_values = $draft->params.custom_fields}
{$custom_fieldsets_available = DAO_CustomFieldset::getUsableByActorByContext($active_worker, CerberusContexts::CONTEXT_TICKET)}

{if $custom_fields || $custom_fieldsets_available}
<fieldset class="peek" style="{if $custom_fieldsets_available}padding-bottom:0px;{/if}">
	<legend>
		<label>
			{'common.update'|devblocks_translate|capitalize}
		</label>
	</legend>

	<div style="{if $custom_fields}{else}display:none;{/if}">
		{if !empty($custom_fields)}
			{include file="devblocks:cerberusweb.core::internal/custom_fields/bulk/form.tpl" bulk=false}
		{/if}
	</div>

	{include file="devblocks:cerberusweb.core::internal/custom_fieldsets/peek_custom_fieldsets.tpl" context=CerberusContexts::CONTEXT_TICKET bulk=false custom_fieldsets_available=$custom_fieldsets_available}
</fieldset>
{/if}

<fieldset class="peek">
	<legend>
		<label>
			<input type="checkbox" class="cerb-compose-deliver-later-toggle" value="1" {if $draft->params.send_at}checked="checked"{/if}>
			Deliver later
		</label>
	</legend>

	<div style="{if $draft->params.send_at}{else}display:none;{/if}">
		<b>When should the message be delivered?</b> (leave blank to send immediately)<br>
		<input type="text" name="send_at" size="64" style="width:89%;" placeholder="now" value="{if !empty($draft)}{$draft->params.send_at}{/if}">
	</div>
</fieldset>

<div class="status"></div>

<div class="help-box submit-no-recipients" style="display:none;">
	<h1>You haven't specified any recipients.</h1>
	<p>
		A new ticket will be created without sending any email.
		This is normal if you're working on an issue and you plan to add an email address later (e.g. phone call).
	</p>
	<p>
		If this isn't what you meant to do, add a recipient in the <b>To:</b> field above.
	</p>
</div>

<div class="submit-normal">
	<button type="button" class="submit" title="{if $pref_keyboard_shortcuts}(Ctrl+Shift+Enter){/if}"><span class="glyphicons glyphicons-send"></span> {'display.ui.send_message'|devblocks_translate}</button>
	<button type="button" class="draft"><span class="glyphicons glyphicons-disk-save"></span> {'display.ui.continue_later'|devblocks_translate}</button>
	<button type="button" class="discard"><span class="glyphicons glyphicons-circle-remove" style="color:rgb(180,0,0);"></span> {'common.discard'|devblocks_translate|capitalize}</button>
</div>
</form>

<script type="text/javascript">
$(function() {
	if(undefined === draftComposeAutoSaveInterval)
		var draftComposeAutoSaveInterval = null;

	var $popup = genericAjaxPopupFind('#frmComposePeek{$popup_uniqid}');

	$popup.one('popup_open',function(event,ui) {
		$popup.dialog('option','title','{'mail.send_mail'|devblocks_translate|capitalize|escape:'javascript' nofilter}');
		
		var $frm = $('#frmComposePeek{$popup_uniqid}');
		
		// Close confirmation
		
		$popup.on('dialogbeforeclose', function(e, ui) {
			var keycode = e.keyCode || e.which;
			if(27 === keycode)
				return confirm('{'warning.core.editor.close'|devblocks_translate}');
		});

		$popup.find('.cerb-editor-tabs').tabs({
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
		
		// Autocompletes

		ajax.emailAutoComplete('#frmComposePeek{$popup_uniqid} input[name=to]', { multiple: true } );
		ajax.emailAutoComplete('#frmComposePeek{$popup_uniqid} input[name=cc]', { multiple: true } );
		ajax.emailAutoComplete('#frmComposePeek{$popup_uniqid} input[name=bcc]', { multiple: true } );

		ajax.orgAutoComplete('#frmComposePeek{$popup_uniqid} input:text[name=org_name]');
		
		// Date helpers

		$frm.find('input[name=send_at]')
			.cerbDateInputHelper()
			;

		$frm.find('input[name=ticket_reopen]')
			.cerbDateInputHelper()
			;
		
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
		
		$frm.find('button.chooser-abstract').cerbChooserTrigger();
		
		$frm.find('button.chooser_file').each(function() {
			ajax.chooserFile(this,'file_ids');
		});
		
		// Drag/drop attachments
		
		var $attachments = $frm.find('fieldset.compose-attachments');
		$attachments.cerbAttachmentsDropZone();
		
		// Text editor
		
		var $editor = $frm.find('textarea[name=content]')
			.cerbTextEditor()
			.cerbTextEditorAutocompleteReplies({
				'mode': 'compose'
			})
			;

		var $editor_toolbar = $frm.find('.cerb-code-editor-toolbar')
			.cerbTextEditorToolbarMarkdown()
			;

		// Paste images

		$editor.cerbTextEditorInlineImagePaster({
			attachmentsContainer: $attachments,
			toolbar: $editor_toolbar
		});

		$editor_toolbar.find('.cerb-reply-editor-toolbar-button--encrypt')
			.click(function() {
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

		var $editor_toolbar_button_save_draft = $frm.find('.cerb-reply-editor-toolbar-button--save').click(function(e) {
			var $this = $(this);

			if(!$this.is(':visible')) {
				clearTimeout(draftComposeAutoSaveInterval);
				draftComposeAutoSaveInterval = null;
				return;
			}

			if($this.attr('disabled'))
				return;

			$this.attr('disabled','disabled');

			var formData = new FormData($frm[0]);
			formData.set('c', 'profiles');
			formData.set('a', 'invoke');
			formData.set('module', 'draft');
			formData.set('action', 'saveDraftCompose');

			genericAjaxPost(
				formData,
				null,
				'',
				function(json) {
					$this.removeAttr('disabled');

					var obj = $.parseJSON(json);

					if(!obj)
						return;

					if(obj.error) {
						$('#divDraftStatus{$popup_uniqid}').html(obj.error);

					} else if(obj.html && obj.draft_id) {
						$('#divDraftStatus{$popup_uniqid}').html(obj.html);
						$frm.find('input[name=draft_id]').val(obj.draft_id);
					}
				}
			);
		});

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
			} else {
				$frm.find('input:hidden[name=format]').val('');
				$button.attr('data-format', 'plaintext');
				$button.text('Formatting off');
				$editor_toolbar.find('.cerb-code-editor-subtoolbar-format-html').css('display','none');
			}
		});

		// Upload image
		$editor_toolbar.on('cerb-editor-toolbar-image-inserted', function(event) {
			event.stopPropagation();

			var new_event = $.Event('cerb-chooser-save', {
				labels: event.labels,
				values: event.values
			});

			$popup.find('button.chooser_file').triggerHandler(new_event);

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

			var formData = new FormData();
			formData.set('c', 'profiles');
			formData.set('a', 'invoke');
			formData.set('module', 'snippet');
			formData.set('action', 'paste');
			formData.set('id', event.snippet_id);
			formData.set('context_ids[cerberusweb.contexts.worker]', '{$active_worker->id}');

			genericAjaxPost(formData, null, null, function (json) {
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
			var chooser_url = 'c=internal&a=invoke&module=records&action=chooserOpen&qr=' + encodeURIComponent('type:[plaintext,worker]') + '&single=1&context=' + encodeURIComponent(context);

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

		// Group and bucket
		$frm.find('select[name=group_id]').on('change', function(e) {
			var $select = $(this);
			var group_id = $select.val();
			var $bucket_options = $select.siblings('select.ticket-peek-bucket-options').find('option')
			var $bucket = $select.siblings('select[name=bucket_id]');
			
			$bucket.children().remove();
			
			$bucket_options.each(function() {
				var parent_id = $(this).attr('group_id');
				if(parent_id === '*' || parent_id === group_id)
					$(this).clone().appendTo($bucket);
			});
			
			$bucket.focus();
		});
		
		$frm.find('input:text[name=to]').on('change keyup', function(event) {
			var $input = $(this);
			
			if($input.val().length > 0) {
				$frm.find('div.submit-no-recipients').hide();
				
			} else {
				$frm.find('div.submit-no-recipients').show();
				
			}
		}).trigger('change');
		
		$frm.find('input:text[name=to], input:text[name=cc], input:text[name=bcc]').focus(function(event) {
			$('#compose_suggested{$popup_uniqid}').appendTo($(this).closest('td'));
		});
		
		$frm.find('input:text[name=org_name]').bind('autocompletechange',function(event, ui) {
			genericAjaxGet('', 'c=profiles&a=invoke&module=org&action=getTopContactsByOrgJson&org_name=' + encodeURIComponent($(this).val()), function(json) {
				var $sug = $('#compose_suggested{$popup_uniqid}');
				
				$sug.find('ul.bubbles li').remove();
				
				if(0 === json.length) {
					$sug.hide();
					return;
				}
				
				for(i in json) {
					var label = '';
					if(null != json[i].name && json[i].name.length > 0) {
						label += json[i].name + " ";
						label += "&lt;" + json[i].email + '&gt;';
					} else {
						label += json[i].email;
					}
					
					$sug.find('ul.bubbles').append($("<li><a href=\"javascript:;\" class=\"suggested\">" + label + "</a></li>"));
				}
				
				// Insert suggested on click
				$sug.find('a.suggested').click(function(e) {
					var $this = $(this);
					var $sug = $this.text();
					
					var $to = $this.closest('td').find('input:text:first');
					var $val = $to.val();
					var $len = $val.length;
					
					var $last = null;
					if($len>0)
						$last = $val.substring($len-1);
					
					if(0===$len || $last===' ')
						$to.val($val+$sug);
					else if($last==',')
						$to.val($val + ' '+$sug);
					else $to.val($val + ', '+$sug);
						$to.focus();
					
					var $ul = $this.closest('ul');
					$this.closest('li').remove();
					if(0===$ul.find('li').length)
						$ul.closest('div').remove();
					
					$to.trigger('change');
				});
				
				$sug.show();
			});
		});
		
		// Date entry
		
		$frm.find('> fieldset:nth(1) input.input_date').cerbDateInputHelper();
		
		if(null != draftComposeAutoSaveInterval) {
			clearTimeout(draftComposeAutoSaveInterval);
			draftComposeAutoSaveInterval = null;
		}

		// Deliver later

		$frm.find('.cerb-compose-deliver-later-toggle').on('click', function(e) {
			e.stopPropagation();

			var $div = $(this).closest('fieldset').find('> div');

			$div
				.toggle()
				.find('input:text')
				.focus()
			;
		});

		draftComposeAutoSaveInterval = setInterval(function() {
			$editor_toolbar_button_save_draft.click();
		}, 30000); // and every 30 sec
		
		// Interactions
		{if $interactions_menu}
		var $interaction_container = $('#divComposeInteractions{$popup_uniqid}');
		{include file="devblocks:cerberusweb.core::events/interaction/interactions_menu.js.tpl"}
		{/if}
		
		// Shortcuts

		{if $pref_keyboard_shortcuts}
			// Send focus
			$editor.bind('keydown', 'ctrl+return alt+return meta+return', function(e) {
				e.preventDefault();
				try {
					$frm.find('button.submit').focus();
				} catch(ex) { }
			});

			// Send
			$editor.bind('keydown', 'ctrl+shift+return alt+shift+return meta+shift+return', function(e) {
				e.preventDefault();
				try {
					$frm.find('button.submit').click();
				} catch(ex) { }
			});

			// Status closed
			$editor.bind('keydown', 'ctrl+shift+c', function(e) {
				e.preventDefault();
				try {
					var $radio = $frm.find('input:radio[name=status_id]');
					$radio.filter('.status_closed').click();
					$frm
						.find('input:text[name=ticket_reopen]')
						.select()
						.focus()
					;
				} catch(ex) { }
			});

			// Status open
			$editor.bind('keydown', 'ctrl+shift+o', function(e) {
				e.preventDefault();
				try {
					var $radio = $frm.find('input:radio[name=status_id]');
					$radio.filter('.status_open').click().focus();
				} catch(ex) { }
			});

			// Status waiting
			$editor.bind('keydown', 'ctrl+shift+w', function(e) {
				e.preventDefault();
				try {
					var $radio = $frm.find('input:radio[name=status_id]');
					$radio.filter('.status_waiting').click();
					$frm
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

			// Bot interaction
			$editor.bind('keydown', 'ctrl+shift+b', function(e) {
				e.preventDefault();
				try {
					$editor_toolbar.find('.cerb-reply-editor-toolbar-button--interactions').click();
				} catch(ex) { }
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

						if(prefix != last_prefix)
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
						var bail = 75000; // prevent infinite loops

						if(prefix.length === 0)
							continue;

						while(undefined !== bins[i].lines[l] && bail > 0) {
							line = bins[i].lines[l];
							var boundary = wrap_to-prefix.length;

							if(line.length > boundary) {
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

		$frm.find(':input:text:first').focus().select();
		
		$popup.on('popup_saved', function() {
			hideLoadingPanel();
		});
		
		$frm.find('button.submit').click(function() {
			var $status = $frm.find('div.status').html('').hide();
			$status.text('').hide();

			Devblocks.clearAlerts();
			showLoadingPanel();

			var formData = new FormData($frm[0]);
			formData.set('c', 'profiles');
			formData.set('a', 'invoke');
			formData.set('module', 'draft');
			formData.set('action', 'validateComposeJson');

			// Validate via Ajax before sending
			genericAjaxPost(formData, '', '', function(json) {
				if(json && json.status) {
					if(null != draftComposeAutoSaveInterval) {
						clearTimeout(draftComposeAutoSaveInterval);
						draftComposeAutoSaveInterval = null;
					}

					genericAjaxPost($frm, null, null,
						function(json) {
							$popup.trigger('popup_saved');

							var post_event = $.Event('compose_save', {
								record: json
							});
							genericAjaxPopupClose($popup, post_event);
						}
					);

				} else {
					hideLoadingPanel();
					Devblocks.createAlertError(json.message);
				}
			});
		});

		$frm.find('.draft').click(function(e) {
			if(e.originalEvent && e.originalEvent.detail && e.originalEvent.detail > 1)
				return;

			Devblocks.clearAlerts();
			showLoadingPanel();

			var formData = new FormData($frm[0]);
			formData.set('c', 'profiles');
			formData.set('a', 'invoke');
			formData.set('module', 'draft');
			formData.set('action', 'validateComposeJson');

			// Validate via Ajax before sending
			genericAjaxPost(formData, '', '', function(json) {
				if(json && json.status) {
					if(null != draftComposeAutoSaveInterval) {
						clearTimeout(draftComposeAutoSaveInterval);
						draftComposeAutoSaveInterval = null;
					}

					var formData = new FormData($frm[0]);
					formData.set('c', 'profiles');
					formData.set('a', 'invoke');
					formData.set('module', 'draft');
					formData.set('action', 'saveDraftCompose');

					genericAjaxPost(
						formData,
						null,
						'',
						function() {
							hideLoadingPanel();
							genericAjaxGet('view{$view_id}','c=internal&a=invoke&module=worklists&action=refresh&id={$view_id}');
							genericAjaxPopupClose($popup);
						}
					);

				} else {
					hideLoadingPanel();
					Devblocks.createAlertError(json.message);
				}
			});
		});

		$frm.find('button.discard').on('click', function(e) {
			e.stopPropagation();

			window.onbeforeunload = null;

			if(confirm('Are you sure you want to discard this message?')) {
				if(null != draftComposeAutoSaveInterval) {
					clearTimeout(draftComposeAutoSaveInterval);
					draftComposeAutoSaveInterval = null;
				}

				var draft_id = $frm.find('input:hidden[name=draft_id]').val();

				var formData = new FormData();
				formData.set('c', 'profiles');
				formData.set('a', 'invoke');
				formData.set('module', 'draft');
				formData.set('action', 'deleteDraft');
				formData.set('draft_id', draft_id);

				genericAjaxPost(formData, '', '', function(o) {
					genericAjaxGet('view{$view_id}', 'c=internal&a=invoke&module=worklists&action=refresh&id={$view_id}');
					genericAjaxPopupClose($popup);
				});
			}
		});

		{if $org || $draft->params.org_name}
		$frm.find('input:text[name=org_name]').trigger('autocompletechange');
		{/if}

		{* Run custom jQuery scripts from VA behavior *}
		
		{if !empty($jquery_scripts)}
		{foreach from=$jquery_scripts item=jquery_script}
		try {
			{$jquery_script nofilter}
		} catch(e) { }
		{/foreach}
		{/if}
	});
});
</script>