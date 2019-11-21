{$mail_reply_html = DAO_WorkerPref::get($active_worker->id, 'mail_reply_html', 0)}
{$is_html = ($draft && $draft->params.format == 'parsedown') || $mail_reply_html}

<form action="{devblocks_url}{/devblocks_url}" method="POST" id="frmComposePeek{$popup_uniqid}" onsubmit="return false;">
<input type="hidden" name="c" value="tickets">
<input type="hidden" name="a" value="saveComposePeek">
<input type="hidden" name="view_id" value="{$view_id}">
<input type="hidden" name="draft_id" value="{$draft->id}">
<input type="hidden" name="format" value="{if $is_html}parsedown{/if}">
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
			<input type="text" name="subject" style="width:98%;border:1px solid rgb(180,180,180);padding:2px;" value="{if $draft}{$draft->getParam('subject')}{/if}" autocomplete="off">
		</td>
	</tr>
	<tr>
		<td width="100%" colspan="2">
			<div id="divDraftStatus{$popup_uniqid}"></div>

			<div class="cerb-code-editor-toolbar">
				{if $interactions_menu}
					<div id="divComposeInteractions{$popup_uniqid}" style="display:inline-block;">
						{include file="devblocks:cerberusweb.core::events/interaction/interactions_menu.tpl" button_classes="cerb-code-editor-toolbar-button cerb-reply-editor-toolbar-button--interactions"}
					</div>
					<div class="cerb-code-editor-toolbar-divider"></div>
				{/if}

				<button type="button" title="Toggle formatting" class="cerb-code-editor-toolbar-button cerb-reply-editor-toolbar-button--formatting" data-format="{if $is_html}html{else}plaintext{/if}">{if $is_html}Formatting on{else}Formatting off{/if}</button>
				<div class="cerb-code-editor-toolbar-divider"></div>

				<div class="cerb-code-editor-subtoolbar-format-html" style="display:inline-block;{if !$is_html}display:none;{/if}">
					<button type="button" title="Bold" class="cerb-code-editor-toolbar-button cerb-markdown-editor-toolbar-button--bold"><span class="glyphicons glyphicons-bold"></span></button>
					<button type="button" title="Italics" class="cerb-code-editor-toolbar-button cerb-markdown-editor-toolbar-button--italic"><span class="glyphicons glyphicons-italic"></span></button>
					<button type="button" title="Link" class="cerb-code-editor-toolbar-button cerb-markdown-editor-toolbar-button--link"><span class="glyphicons glyphicons-link"></span></button>
					<button type="button" title="Image" class="cerb-code-editor-toolbar-button cerb-markdown-editor-toolbar-button--image"><span class="glyphicons glyphicons-picture"></span></button>
					<button type="button" title="List" class="cerb-code-editor-toolbar-button cerb-markdown-editor-toolbar-button--list"><span class="glyphicons glyphicons-list"></span></button>
					<button type="button" title="Quote" class="cerb-code-editor-toolbar-button cerb-markdown-editor-toolbar-button--quote"><span class="glyphicons glyphicons-quote"></span></button>
					<button type="button" title="Code" class="cerb-code-editor-toolbar-button cerb-markdown-editor-toolbar-button--code"><span class="glyphicons glyphicons-embed"></span></button>
					<button type="button" title="Table" class="cerb-code-editor-toolbar-button cerb-markdown-editor-toolbar-button--table"><span class="glyphicons glyphicons-table"></span></button>
					<div class="cerb-code-editor-toolbar-divider"></div>
				</div>

				<button type="button" title="Insert #command" class="cerb-code-editor-toolbar-button cerb-markdown-editor-toolbar-button--commands"><span class="glyphicons glyphicons-sampler"></span></button>
				<button type="button" title="Insert snippet" class="cerb-code-editor-toolbar-button cerb-markdown-editor-toolbar-button--snippets"><span class="glyphicons glyphicons-notes-2"></span></button>
				{*<button type="button" title="Track time" class="cerb-code-editor-toolbar-button cerb-reply-editor-toolbar-button--save"><span class="glyphicons glyphicons-stopwatch"></span></button>*}
				<button type="button" title="Save draft" class="cerb-code-editor-toolbar-button cerb-reply-editor-toolbar-button--save"><span class="glyphicons glyphicons-floppy-save"></span></button>
				<div class="cerb-code-editor-toolbar-divider"></div>

				<button type="button" title="Preview message" class="cerb-code-editor-toolbar-button cerb-markdown-editor-toolbar-button--preview"><span class="glyphicons glyphicons-eye-open"></span></button>
			</div>

			<textarea id="divComposeContent{$popup_uniqid}" name="content" data-editor-mode="ace/mode/text" data-editor-line-numbers="false" data-editor-lines="20">{if $draft}{$draft->getParam('content')}{else}{if $defaults.signature_pos}



#signature
#cut{/if}{/if}</textarea>
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

{if $gpg && $gpg->isEnabled()}
<fieldset class="peek">
	<legend>{'common.encryption'|devblocks_translate|capitalize}</legend>
	
	<div>
		<label style="margin-right:10px;">
		<input type="checkbox" name="options_gpg_encrypt" value="1" {if $draft->params.options_gpg_encrypt}checked="checked"{/if}> 
		Encrypt message using recipient public keys
		</label>
	</div>
</fieldset>
{/if}

<fieldset class="peek">
	<legend>{'common.properties'|devblocks_translate|capitalize}</legend>
	
	<div>
		<label>
		<input type="checkbox" name="options_dont_send" value="1" {if $draft->params.options_dont_send}checked="checked"{/if}> 
		Start a new conversation without sending a copy of this message to the recipients
		</label>
	</div>
	
	<div style="margin-top:10px;">
		<label {if $pref_keyboard_shortcuts}title="(Ctrl+Shift+O)"{/if}><input type="radio" name="status_id" value="{Model_Ticket::STATUS_OPEN}" class="status_open" {if (empty($draft) && 'open'==$defaults.status) || (!empty($draft) && $draft->params.status_id==Model_Ticket::STATUS_OPEN)}checked="checked"{/if} onclick="toggleDiv('divComposeClosed{$popup_uniqid}','none');"> {'status.open'|devblocks_translate}</label>
		<label {if $pref_keyboard_shortcuts}title="(Ctrl+Shift+W)"{/if}><input type="radio" name="status_id" value="{Model_Ticket::STATUS_WAITING}" class="status_waiting" {if (empty($draft) && 'waiting'==$defaults.status) || (!empty($draft) && $draft->params.status_id==Model_Ticket::STATUS_WAITING)}checked="checked"{/if} onclick="toggleDiv('divComposeClosed{$popup_uniqid}','block');"> {'status.waiting'|devblocks_translate}</label>
		{if $active_worker->hasPriv('core.ticket.actions.close')}<label {if $pref_keyboard_shortcuts}title="(Ctrl+Shift+C)"{/if}><input type="radio" name="status_id" value="{Model_Ticket::STATUS_CLOSED}" class="status_closed" {if (empty($draft) && 'closed'==$defaults.status) || (!empty($draft) && $draft->params.status_id==Model_Ticket::STATUS_CLOSED)}checked="checked"{/if} onclick="toggleDiv('divComposeClosed{$popup_uniqid}','block');"> {'status.closed'|devblocks_translate}</label>{/if}
		
		<div id="divComposeClosed{$popup_uniqid}" style="display:{if (empty($draft) && 'open'==$defaults.status) || (!empty($draft) && $draft->params.status_id==Model_Ticket::STATUS_OPEN)}none{else}block{/if};margin:5px 0px 0px 20px;">
			<b>{'display.reply.next.resume'|devblocks_translate}</b><br>
			{'display.reply.next.resume_eg'|devblocks_translate}<br> 
			<input type="text" name="ticket_reopen" size="64" class="input_date" value="{$draft->params.ticket_reopen}"><br>
			{'display.reply.next.resume_blank'|devblocks_translate}<br>
		</div>
	</div>
</fieldset>

<fieldset class="peek">
	<legend>Assignments</legend>
	
	<table cellpadding="0" cellspacing="0" width="100%" border="0">
		<tr>
			<td width="1%" nowrap="nowrap" style="padding-right:10px;" valign="top">
				{'common.owner'|devblocks_translate|capitalize}:
			</td>
			<td width="99%">
				<button type="button" class="chooser-abstract" data-context="{CerberusContexts::CONTEXT_WORKER}" data-query="isDisabled:n" data-field-name="owner_id" data-autocomplete="" data-autocomplete-if-empty="true" data-single="true"><span class="glyphicons glyphicons-search"></span></button>
				<ul class="bubbles chooser-container">
					{foreach from=$workers item=v key=k}
					{if !$v->is_disabled && $draft->params.owner_id == $v->id}
					<li><img class="cerb-avatar" src="{devblocks_url}c=avatars&context=worker&context_id={$v->id}{/devblocks_url}?v={$v->updated}"><input type="hidden" name="owner_id" value="{$v->id}"><a href="javascript:;" class="cerb-peek-trigger no-underline" data-context="{CerberusContexts::CONTEXT_WORKER}" data-context-id="{$v->id}">{$v->getName()}</a></li>
					{/if}
					{/foreach}
				</ul>
			</td>
		</tr>
		<tr>
			<td width="1%" nowrap="nowrap" style="padding-right:10px;" valign="top">
				{'common.watchers'|devblocks_translate|capitalize}:
			</td>
			<td width="99%">
				<button type="button" class="chooser-abstract" data-context="{CerberusContexts::CONTEXT_WORKER}" data-query="isDisabled:n" data-field-name="add_watcher_ids[]" data-autocomplete=""><span class="glyphicons glyphicons-search"></span></button>
				<ul class="bubbles chooser-container" style="display:block;">
					{if $draft->params.add_watcher_ids && is_array($draft->params.add_watcher_ids)}
					{foreach from=$draft->params.add_watcher_ids item=watcher_id}
						{$watcher = DAO_Worker::get($watcher_id)}
						{if $watcher}
						<li>
							<input type="hidden" name="add_watcher_ids[]" value="{$watcher_id}">
							{$watcher->getName()}
							<a href="javascript:;" onclick="$(this).parent().remove();"><span class="glyphicons glyphicons-circle-remove"></span></a>
						</li>
						{/if}
					{/foreach}
					{/if}
				</ul>
			</td>
		</tr>
	</table>
</fieldset>

<fieldset class="peek" style="{if empty($custom_fields) && empty($group_fields)}display:none;{/if}" id="compose_cfields{$popup_uniqid}">
	<legend>{'common.custom_fields'|devblocks_translate|capitalize}</legend>
	
	{$custom_field_values = $draft->params.custom_fields}
	
	{if !empty($custom_fields)}
	{include file="devblocks:cerberusweb.core::internal/custom_fields/bulk/form.tpl" bulk=false}
	{/if}
</fieldset>

{include file="devblocks:cerberusweb.core::internal/custom_fieldsets/peek_custom_fieldsets.tpl" context=CerberusContexts::CONTEXT_TICKET bulk=false}

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
	<div>
		<button type="button" class="submit" title="{if $pref_keyboard_shortcuts}(Ctrl+Shift+Enter){/if}"><span class="glyphicons glyphicons-circle-ok" style="color:rgb(0,180,0);"></span> Create a ticket without recipients</button>
	</div>
</div>

<div class="submit-normal" style="display:none;">
	<button type="button" class="submit" title="{if $pref_keyboard_shortcuts}(Ctrl+Shift+Enter){/if}"><span class="glyphicons glyphicons-circle-ok" style="color:rgb(0,180,0);"></span> {'display.ui.send_message'|devblocks_translate}</button>
</div>
</form>

<script type="text/javascript">
	if(undefined === draftComposeAutoSaveInterval)
		var draftComposeAutoSaveInterval = null;

	var $popup = genericAjaxPopupFind('#frmComposePeek{$popup_uniqid}');

	$popup.one('popup_open',function(event,ui) {
		$(this).dialog('option','title','{'mail.send_mail'|devblocks_translate|capitalize|escape:'javascript' nofilter}');
		
		var $frm = $('#frmComposePeek{$popup_uniqid}');
		
		// Close confirmation
		
		$popup.on('dialogbeforeclose', function(e, ui) {
			var keycode = e.keyCode || e.which;
			if(27 === keycode)
				return confirm('{'warning.core.editor.close'|devblocks_translate}');
		});
		
		// Autocompletes

		ajax.emailAutoComplete('#frmComposePeek{$popup_uniqid} input[name=to]', { multiple: true } );
		ajax.emailAutoComplete('#frmComposePeek{$popup_uniqid} input[name=cc]', { multiple: true } );
		ajax.emailAutoComplete('#frmComposePeek{$popup_uniqid} input[name=bcc]', { multiple: true } );

		ajax.orgAutoComplete('#frmComposePeek{$popup_uniqid} input:text[name=org_name]');
		
		// Date helpers
		
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
				var chooser_url = 'c=internal&a=chooserOpen&context=' + encodeURIComponent(context);
				
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
			.cerbCodeEditor()
			.cerbCodeEditorAutocompleteReplies()
			;

		var $editor_pre = $editor.nextAll('.ace_editor');

		var editor = ace.edit($editor_pre.attr('id'));

        $editor_pre.find('.ace_text-input')
            .cerbCodeEditorInlineImagePaster({
                editor: editor,
                attachmentsContainer: $attachments
            })
        ;

		var $editor_toolbar = $frm.find('.cerb-code-editor-toolbar')
			.cerbCodeEditorToolbarMarkdown()
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

			genericAjaxPost(
				'frmComposePeek{$popup_uniqid}',
				null,
				'c=profiles&a=handleSectionAction&section=draft&action=saveDraft&type=compose',
				function(json) {
					var obj = $.parseJSON(json);

					if(!obj || !obj.html || !obj.draft_id)
						return;

					$('#divDraftStatus{$popup_uniqid}').html(obj.html);

					$('#frmComposePeek{$popup_uniqid} input[name=draft_id]').val(obj.draft_id);

					$this.removeAttr('disabled');
				}
			);
		});

		// Formatting
		$editor_toolbar.find('.cerb-reply-editor-toolbar-button--formatting').on('click', function() {
			var $button = $(this);

			if('html' === $button.attr('data-format')) {
				$frm.find('input:hidden[name=format]').val('');
				$button.attr('data-format', 'plaintext');
				$button.text('Formatting off');
				$editor_toolbar.find('.cerb-code-editor-subtoolbar-format-html').css('display','none');
			} else {
				$frm.find('input:hidden[name=format]').val('parsedown');
				$button.attr('data-format', 'html');
				$button.text('Formatting on');
				$editor_toolbar.find('.cerb-code-editor-subtoolbar-format-html').css('display','inline-block');
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

			editor.insertSnippet('![Image](' + event.url + ')');
			editor.focus();
		});

		// Commands
		$editor_toolbar.find('.cerb-markdown-editor-toolbar-button--commands').on('click', function(e) {
			editor.insertSnippet("#");
			editor.commands.byName.startAutocomplete.exec(editor);
			editor.focus();
		});

		// Snippets
		var $editor_toolbar_button_snippets = $editor_toolbar.find('.cerb-markdown-editor-toolbar-button--snippets').on('click', function () {
			var context = 'cerberusweb.contexts.snippet';
			var chooser_url = 'c=internal&a=chooserOpen&q=' + encodeURIComponent('type:[plaintext,ticket,worker]') + '&single=1&context=' + encodeURIComponent(context);

			var $chooser = genericAjaxPopup(Devblocks.uniqueId(), chooser_url, null, true, '90%');

			$chooser.on('chooser_save', function (event) {
				if (!event.values || 0 == event.values.length)
					return;

				var snippet_id = event.values[0];

				if (null == snippet_id)
					return;

				// Now we need to read in each snippet as either 'raw' or 'parsed' via Ajax
				var url = 'c=internal&a=snippetPaste&id='
					+ encodeURIComponent(snippet_id)
					+ "&context_ids[cerberusweb.contexts.worker]={$active_worker->id}"
				;

				genericAjaxGet('', url, function (json) {
					// If the content has placeholders, use that popup instead
					if (json.has_custom_placeholders) {
						var $popup_paste = genericAjaxPopup('snippet_paste', 'c=internal&a=snippetPlaceholders&id=' + encodeURIComponent(json.id) + '&context_id=' + encodeURIComponent(json.context_id), null, false, '50%');

						$popup_paste.bind('snippet_paste', function (event) {
							if (null == event.text)
								return;

							editor.insert(event.text);
							editor.scrollToLine(editor.getCursorPosition().row);
							editor.focus();
						});

					} else {
						editor.insert(json.text);
						editor.scrollToLine(editor.getCursorPosition().row);
						editor.focus();
					}
				});
			});
		});

		// Preview
		$editor_toolbar.find('.cerb-markdown-editor-toolbar-button--preview').on('click', function () {
			var formData = new FormData();
			formData.append('c', 'profiles');
			formData.append('a', 'handleSectionAction');
			formData.append('section', 'ticket');
			formData.append('action', 'previewReplyMessage');
			formData.append('format', $frm.find('input[name=format]').val());
			formData.append('group_id', $frm.find('select[name=group_id]').val());
			formData.append('bucket_id', $frm.find('select[name=bucket_id]').val());
			formData.append('content', editor.getValue());

			genericAjaxPopup(
				'preview_reply',
				formData,
				'reuse',
				false
			);
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
				if(parent_id == '*' || parent_id == group_id)
					$(this).clone().appendTo($bucket);
			});
			
			$bucket.focus();
		});
		
		$frm.find('input:text[name=to]').on('change keyup', function(event) {
			var $input = $(this);
			
			if($input.val().length > 0) {
				$frm.find('div.submit-normal').show();
				$frm.find('div.submit-no-recipients').hide();
				
			} else {
				$frm.find('div.submit-normal').hide();
				$frm.find('div.submit-no-recipients').show();
				
			}
		}).trigger('change');
		
		$frm.find('input:text[name=to], input:text[name=cc], input:text[name=bcc]').focus(function(event) {
			$('#compose_suggested{$popup_uniqid}').appendTo($(this).closest('td'));
		});
		
		$frm.find('input:text[name=org_name]').bind('autocompletechange',function(event, ui) {
			genericAjaxGet('', 'c=contacts&a=getTopContactsByOrgJson&org_name=' + encodeURIComponent($(this).val()), function(json) {
				var $sug = $('#compose_suggested{$popup_uniqid}');
				
				$sug.find('ul.bubbles li').remove();
				
				if(0 == json.length) {
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
					
					if(0==$len || $last==' ')
						$to.val($val+$sug);
					else if($last==',')
						$to.val($val + ' '+$sug);
					else $to.val($val + ', '+$sug);
						$to.focus();
					
					var $ul = $this.closest('ul');
					$this.closest('li').remove();
					if(0==$ul.find('li').length)
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
		editor.commands.addCommand({
			name: 'Send',
			bindKey: { win: 'Ctrl-Shift-Enter', mac: 'Ctrl-Shift-Enter' },
			exec: function(editor) {
				try {
					$frm.find('button.submit').focus();
				} catch(ex) { }
			}
		});

		editor.commands.addCommand({
			name: 'Status closed',
			bindKey: { win: 'Ctrl-Shift-C', mac: 'Ctrl-Shift-C' },
			exec: function(editor) {
				try {
					var $radio = $frm.find('input:radio[name=status_id]');
					$radio.filter('.status_closed').click();
					$frm
						.find('input:text[name=ticket_reopen]')
						.select()
						.focus()
					;
				} catch(ex) { }
			}
		});

		editor.commands.addCommand({
			name: 'Status open',
			bindKey: { win: 'Ctrl-Shift-O', mac: 'Ctrl-Shift-O' },
			exec: function(editor) {
				try {
					var $radio = $frm.find('input:radio[name=status_id]');
					$radio.filter('.status_open').click().focus();
				} catch(ex) { }
			}
		});

		editor.commands.addCommand({
			name: 'Status waiting',
			bindKey: { win: 'Ctrl-Shift-W', mac: 'Ctrl-Shift-W' },
			exec: function(editor) {
				try {
					var $radio = $frm.find('input:radio[name=status_id]');
					$radio.filter('.status_waiting').click();
					$frm
						.find('input:text[name=ticket_reopen]')
						.select()
						.focus()
					;
				} catch(ex) { }
			}
		});

		editor.commands.addCommand({
			name: 'Insert signature',
			bindKey: { win: 'Ctrl-Shift-G', mac: 'Ctrl-Shift-G' },
			exec: function(editor) {
				try {
					editor.insertSnippet("#signature\n");
					editor.focus();
				} catch(ex) { }
			}
		});

		editor.commands.addCommand({
			name: 'Insert snippet',
			bindKey: { win: 'Ctrl-Shift-I', mac: 'Ctrl-Shift-I' },
			exec: function(editor) {
				try {
					$editor_toolbar_button_snippets.click();
				} catch(ex) { }
			}
		});

		editor.commands.addCommand({
			name: 'Bot interaction',
			bindKey: { win: 'Ctrl-Shift-B', mac: 'Ctrl-Shift-B' },
			exec: function(editor) {
				try {
					$editor_toolbar.find('.cerb-reply-editor-toolbar-button--interactions').click();
				} catch(ex) { }
			}
		});

		editor.commands.addCommand({
			name: 'Reformat',
			bindKey: { win: 'Ctrl-Shift-Q', mac: 'Ctrl-Shift-Q' },
			exec: function(editor) {
				try {
				    var txt = editor.getValue();

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

						while(undefined != bins[i].lines[l] && bail > 0) {
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
									if(undefined != bins[i].lines[l+1]) {
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

					editor.setValue($.trim(out));
					editor.clearSelection();
				} catch(ex) { }
			}
		});
		{/if}
		
		$frm.find(':input:text:first').focus().select();
		
		$popup.on('popup_saved', function() {
			hideLoadingPanel();
		});
		
		$frm.find('button.submit').click(function() {
			var $status = $frm.find('div.status').html('').hide();
			$status.text('').hide();
			
			showLoadingPanel();
			
			// Validate via Ajax before sending
			genericAjaxPost($frm, '', 'c=tickets&a=validateComposeJson', function(json) {
				if(json && json.status) {
					if(null != draftComposeAutoSaveInterval) { 
						clearTimeout(draftComposeAutoSaveInterval);
						draftComposeAutoSaveInterval = null;
					}
					
					genericAjaxPopupPostCloseReloadView(null,'frmComposePeek{$popup_uniqid}','{$view_id}',false,'compose_save');
					
				} else {
					hideLoadingPanel();
					$status.text(json.message).addClass('error').fadeIn();
				}
			});
		});
		
		{if $org}
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
</script>
