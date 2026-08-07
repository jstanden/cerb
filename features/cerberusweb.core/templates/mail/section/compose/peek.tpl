{$mail_reply_html = DAO_WorkerPref::get($active_worker->id, 'mail_reply_html', 0)}
{$is_html = ($draft && $draft->params.format == 'parsedown') || $mail_reply_html}

<form action="{devblocks_url}{/devblocks_url}" method="POST" id="frmComposePeek{$popup_uniqid}">
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
		<td width="0%" nowrap="nowrap" align="right" class="cerb-u-text-uppercase cerb-u-text-muted cerb-u-fs-n1">{'message.header.from'|devblocks_translate|capitalize}:&nbsp;</td>
		<td width="100%">
			{$compose_cur_group = $groups[$draft->params.group_id]}
			{$compose_cur_bucket = $buckets[$draft->params.bucket_id]}

			<input type="hidden" name="group_id" id="composeGroupId{$popup_uniqid}" value="{$draft->params.group_id}">
			<input type="hidden" name="bucket_id" id="composeBucketId{$popup_uniqid}" value="{$draft->params.bucket_id}">

			<button type="button" class="cerb-ui-button cerb-ui-button--subtle cerb-u-flex cerb-u-items-center cerb-u-gap-1" id="composeBucketTrigger{$popup_uniqid}" data-group-id="{$draft->params.group_id}" data-group-label="{if $compose_cur_group}{$compose_cur_group->name}{/if}" data-avatar="{devblocks_url}c=avatars&context=group&context_id={$draft->params.group_id}{/devblocks_url}">
				<span data-cerb-bucket-icon class="cerb-u-flex cerb-u-items-center"></span>
				<span data-cerb-bucket-label>{if $compose_cur_group}{$compose_cur_group->name}{/if}{if $compose_cur_bucket} &rsaquo; {$compose_cur_bucket->name}{/if}</span>
				<span class="cerb-icons cerb-icon-chevron-down"></span>
			</button>

			<ul id="composeBucketMenu{$popup_uniqid}" hidden>
				{foreach from=$groups item=group key=group_id}
					{if $active_worker->isGroupMember($group_id)}
					<li data-group-id="{$group_id}" data-group-label="{$group->name}" data-avatar="{devblocks_url}c=avatars&context=group&context_id={$group_id}{/devblocks_url}">{$group->name}
						<ul>
							{foreach from=$buckets item=bucket key=bucket_id}
								{if $bucket->group_id == $group_id}
									<li data-group-id="{$group_id}" data-bucket-id="{$bucket_id}" data-group-label="{$group->name}" data-group-avatar="{devblocks_url}c=avatars&context=group&context_id={$group_id}{/devblocks_url}">{$bucket->name}</li>
								{/if}
							{/foreach}
						</ul>
					</li>
					{/if}
				{/foreach}
			</ul>
		</td>
	</tr>
	<tr>
		<td width="0%" nowrap="nowrap" valign="top" align="right" class="cerb-u-text-uppercase cerb-u-text-muted cerb-u-fs-n1">{'common.organization'|devblocks_translate|capitalize}:&nbsp;</td>
		<td width="100%">
			<input type="text" name="org_name" value="{$draft->params.org_name}" style="padding:2px 2px 2px 2.2em;width:98%;" placeholder="(optional) Link this ticket to an organization for suggested recipients">
		</td>
	</tr>
	<tr>
		<td width="0%" nowrap="nowrap" valign="top" align="right" class="cerb-u-text-uppercase cerb-u-text-muted cerb-u-fs-n1"><a class="cerb-recipient-chooser" data-context="{CerberusContexts::CONTEXT_ADDRESS}" data-query="">{'message.header.to'|devblocks_translate|capitalize}</a>:&nbsp;</td>
		<td width="100%">
			<input type="text" name="to" id="emailinput{$popup_uniqid}" value="{$draft->getParam('to')}" style="padding:2px;width:98%;" placeholder="These recipients will automatically be included in all future correspondence">

			<div id="compose_suggested{$popup_uniqid}" style="display:none;">
				<a data-cerb-link="remove_suggested"><span class="cerb-icons cerb-icon-circle-remove"></span></a>
				<b>Consider adding these recipients:</b>
				<ul class="bubbles"></ul>
			</div>
		</td>
	</tr>
	<tr>
		<td width="0%" nowrap="nowrap" valign="top" align="right" class="cerb-u-text-uppercase cerb-u-text-muted cerb-u-fs-n1"><a class="cerb-recipient-chooser" data-context="{CerberusContexts::CONTEXT_ADDRESS}" data-query="">{'message.header.cc'|devblocks_translate|capitalize}</a>:&nbsp;</td>
		<td width="100%">
			<input type="text" name="cc" style="width:98%;padding:2px;" value="{$draft->params.cc}" placeholder="These recipients will publicly receive a copy of this message" autocomplete="off">
		</td>
	</tr>
	<tr>
		<td width="0%" nowrap="nowrap" valign="top" align="right" class="cerb-u-text-uppercase cerb-u-text-muted cerb-u-fs-n1"><a class="cerb-recipient-chooser" data-context="{CerberusContexts::CONTEXT_ADDRESS}" data-query="">{'message.header.bcc'|devblocks_translate|capitalize}</a>:&nbsp;</td>
		<td width="100%">
			<input type="text" name="bcc" style="width:98%;padding:2px;" value="{$draft->params.bcc}" placeholder="These recipients will secretly receive a copy of this message" autocomplete="off">
		</td>
	</tr>
	<tr>
		<td width="0%" nowrap="nowrap" valign="top" align="right" class="cerb-u-text-uppercase cerb-u-text-muted cerb-u-fs-n1">{'message.header.subject'|devblocks_translate|capitalize}:&nbsp;</td>
		<td width="100%">
			<input type="text" name="subject" style="width:98%;padding:2px;" value="{if $draft}{$draft->getParam('subject')}{/if}" autocomplete="off" maxlength="255">
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
					{* Formatting buttons + markdown/plaintext toggle come from the editor (built-in). These host sections —
					   #command / snippet / save-draft / GPG, plus any worker-configured custom toolbar — merge into the strip. *}
					<ul class="cerb-ui-toolbar" data-cerb-compose-editor-toolbar hidden>
						<li data-value="commands" data-icon="placeholders" title="Insert #command"></li>
						<li data-value="snippets" data-icon="clipboard" title="Insert snippet (Ctrl+Shift+Period)"></li>
						<li data-value="save_draft" data-icon="save" title="Save draft (Ctrl+S)"></li>
						<li></li>
						<li data-value="encrypt" data-toggle data-key="gpg_encrypt" data-icon="lock"{if $draft->params.options_gpg_encrypt} data-pressed="1"{/if} title="{'common.encrypt'|devblocks_translate|capitalize}"></li>
						<li data-value="sign" data-toggle data-key="gpg_sign" data-icon="user-lock"{if $draft->params.options_gpg_sign} data-pressed="1"{/if} title="{'common.encrypt.sign'|devblocks_translate|capitalize}"></li>
					</ul>

					{if $toolbar_custom}
					<div data-cerb-toolbar class="cerb-compose-editor-subtoolbar-custom" hidden>
						{DevblocksPlatform::services()->ui()->toolbar()->render($toolbar_custom)}
					</div>
					{/if}

					<textarea id="divComposeContent{$popup_uniqid}" name="content" spellcheck="true">{$draft->getParam('content')}</textarea>
				</div>

				<div id="compose{$popup_uniqid}EditorPreviewPanel" style="min-height:100px;max-height:400px;overflow:auto;border:1px dotted rgb(150,150,150);padding:5px;"></div>
			</div>
		</td>
	</tr>
</table>

<fieldset class="peek compose-attachments">
	<legend>{'common.attachments'|devblocks_translate|capitalize}</legend>
	<div class="cerb-ui-file-upload" data-name="file_ids" data-multiple="1">
	{if $draft->params.file_ids}
	{foreach from=$draft->params.file_ids item=file_id}
		{$file = DAO_Attachment::get($file_id)}
		{if !empty($file)}
			<li data-file-id="{$file->id}" data-file-name="{$file->name}" data-file-size="{$file->storage_size}"></li>
		{/if}
	{/foreach}
	{/if}
	</div>
</fieldset>

{$compose_half = 'flex:1 1 calc(50% - 0.5em);min-width:13em;'}

<div class="cerb-ui-panel cerb-ui-panel--spaced">
	<div class="cerb-ui-header cerb-ui-header--tight">
		<div class="cerb-ui-header--title-sm">{'common.properties'|devblocks_translate|capitalize}</div>
	</div>

	<div class="cerb-ui-form">
		<div class="cerb-ui-form--row">
			{* Status *}
			<div class="cerb-ui-form--field" style="{$compose_half}">
				<label class="cerb-ui-form--label">{'common.status'|devblocks_translate|capitalize}</label>
				<div>
					<input type="hidden" name="status_id" id="composeStatusId{$popup_uniqid}" value="{$draft->params.status_id|default:Model_Ticket::STATUS_OPEN}">
					<div class="cerb-ui-switcher" data-cerb-input="composeStatusId{$popup_uniqid}" id="composeStatusSwitcher{$popup_uniqid}">
						<button type="button" data-value="{Model_Ticket::STATUS_OPEN}"{if $draft->params.status_id==Model_Ticket::STATUS_OPEN} class="cerb-ui-switcher--active"{/if}{if $pref_keyboard_shortcuts} title="(Ctrl+Shift+O)"{/if}><span class="cerb-icons cerb-icon-play-button"></span> {'status.open'|devblocks_translate|capitalize}</button>
						<button type="button" data-value="{Model_Ticket::STATUS_WAITING}"{if $draft->params.status_id==Model_Ticket::STATUS_WAITING} class="cerb-ui-switcher--active"{/if}{if $pref_keyboard_shortcuts} title="(Ctrl+Shift+W)"{/if}><span class="cerb-icons cerb-icon-clock"></span> {'status.waiting'|devblocks_translate|capitalize}</button>
						{if $active_worker->hasPriv('core.ticket.actions.close')}<button type="button" data-value="{Model_Ticket::STATUS_CLOSED}"{if $draft->params.status_id==Model_Ticket::STATUS_CLOSED} class="cerb-ui-switcher--active"{/if}{if $pref_keyboard_shortcuts} title="(Ctrl+Shift+C)"{/if}><span class="cerb-icons cerb-icon-circle-ok"></span> {'status.closed'|devblocks_translate|capitalize}</button>{/if}
					</div>

					<div id="divComposeClosed{$popup_uniqid}" class="cerb-u-mt-2" style="display:{if $draft->params.status_id==Model_Ticket::STATUS_OPEN}none{else}block{/if};">
						<label class="cerb-ui-form--label">{'display.reply.next.resume'|devblocks_translate}</label>
						<div class="cerb-u-flex cerb-u-items-center cerb-u-gap-1">
							<input type="text" name="ticket_reopen" style="flex:1 1 auto;min-width:0;" value="{$draft->params.ticket_reopen}">
						</div>
						<div class="cerb-ui-form--help">{'display.reply.next.resume_blank'|devblocks_translate}</div>
					</div>
				</div>
			</div>

			{* Owner *}
			<div class="cerb-ui-form--field" style="{$compose_half}">
				<label class="cerb-ui-form--label">{'common.owner'|devblocks_translate|capitalize}</label>
				<div class="cerb-ui-record-chooser" id="composeOwnerChooser{$popup_uniqid}">
					{foreach from=$workers item=v key=k}
						{if !$v->is_disabled && $draft->params.owner_id == $v->id}
							<li data-context-id="{$v->id}" data-label="{$v->getName()}" data-image="{devblocks_url}c=avatars&context=worker&context_id={$v->id}{/devblocks_url}?v={$v->updated}"></li>
						{/if}
					{/foreach}
				</div>
			</div>

			{* Watchers *}
			<div class="cerb-ui-form--field" style="{$compose_half}">
				<label class="cerb-ui-form--label">{'common.watchers'|devblocks_translate|capitalize}</label>
				<div class="cerb-ui-record-chooser" id="composeWatchersChooser{$popup_uniqid}">
					{if is_array($draft->params.watcher_ids)}
						{foreach from=$workers item=v key=k}
							{if !$v->is_disabled && in_array($v->id,$draft->params.watcher_ids)}
								<li data-context-id="{$v->id}" data-label="{$v->getName()}" data-image="{devblocks_url}c=avatars&context=worker&context_id={$v->id}{/devblocks_url}?v={$v->updated}"></li>
							{/if}
						{/foreach}
					{/if}
				</div>
			</div>

			{* HTML mail template — only with formatting on (empty = default) *}
			{if $html_templates}
				<div class="cerb-ui-form--field" data-cerb-compose-html-template style="{$compose_half}{if !$is_html}display:none;{/if}">
					<label class="cerb-ui-form--label">{'common.html_mail_template'|devblocks_translate|capitalize}</label>
					<div class="cerb-ui-record-chooser" id="composeHtmlTemplateChooser{$popup_uniqid}">
						{$compose_cur_template = $html_templates[$draft->params.html_template_id]}
						{if $draft->params.html_template_id && $compose_cur_template}
							<li data-context-id="{$compose_cur_template->id}" data-label="{$compose_cur_template->name}"></li>
						{/if}
					</div>
				</div>
			{/if}
		</div>

		{* Options *}
		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">{'common.options'|devblocks_translate|capitalize}</label>
			<div class="cerb-u-flex cerb-u-items-center cerb-u-gap-2">
				<label class="cerb-ui-toggle">
					<input type="checkbox" name="options_dont_send" id="composeDontSend{$popup_uniqid}" value="1" {if $draft->params.options_dont_send}checked="checked"{/if}>
					<span class="cerb-ui-toggle--slider"></span>
				</label>
				<label for="composeDontSend{$popup_uniqid}">Start a new conversation without sending a copy to the recipients</label>
			</div>
		</div>
	</div>
</div>

{if $custom_fields || $custom_fieldsets_available}
	{if !empty($custom_fields)}
		{include file="devblocks:cerberusweb.core::internal/custom_fields/form.tpl" custom_fields=$custom_fields}
	{/if}

	{include file="devblocks:cerberusweb.core::internal/custom_fieldsets/peek_custom_fieldsets.tpl" context=CerberusContexts::CONTEXT_TICKET context_id=0 collapsible=true collapse_default=true custom_fieldsets_available=$custom_fieldsets_available custom_fieldsets_linked=$custom_fieldsets_linked}
{/if}

<div class="cerb-ui-panel cerb-ui-panel--spaced" data-cerb-compose-deliver-later>
	<div class="cerb-ui-header cerb-ui-header--tight cerb-ui-header--center">
		<div class="cerb-ui-header--title-sm">
			<label class="cerb-ui-toggle">
				<input type="checkbox" class="cerb-compose-deliver-later-toggle" {if $draft->params.send_at}checked="checked"{/if}>
				<span class="cerb-ui-toggle--slider"></span>
			</label>
			Deliver later
		</div>
	</div>

	<div data-cerb-compose-deliver-later-body style="{if $draft->params.send_at}{else}display:none;{/if}">
		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">When should the message be delivered? <span class="cerb-ui-form--hint">(leave blank to send immediately)</span></label>
			<div class="cerb-u-flex cerb-u-items-center cerb-u-gap-1">
				<input type="text" name="send_at" placeholder="now" style="flex:1 1 auto;min-width:0;" value="{if !empty($draft)}{$draft->params.send_at}{/if}">
			</div>
		</div>
	</div>
</div>

<div class="submit-normal" style="margin-top:10px;">
	<button type="button" class="cerb-ui-button submit" title="{if $pref_keyboard_shortcuts}(Ctrl+Shift+Enter){/if}"><span class="cerb-icons cerb-icon-send"></span> {'display.ui.send_message'|devblocks_translate}</button>
	<button type="button" class="cerb-ui-button cerb-ui-button--subtle draft"><span class="cerb-icons cerb-icon-save"></span> {'display.ui.continue_later'|devblocks_translate}</button>
	<button type="button" class="cerb-ui-button cerb-ui-button--subtle discard"><span class="cerb-icons cerb-icon-trash"></span> {'common.discard'|devblocks_translate|capitalize}</button>
</div>
</form>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	let draftComposeAutoSaveInterval = null;

	let $frm = $('#frmComposePeek{$popup_uniqid}');
	let $popup = genericAjaxPopupFind($frm);

	Devblocks.formDisableSubmit($frm);

	// Save the draft via AJAX (used by the toolbar save button, Ctrl+S, and the autosave interval)
	let savingDraft = false;
	let saveDraftComposeNow = function() {
		if(savingDraft)
			return;
		savingDraft = true;

		let formData = new FormData($frm[0]);
		formData.set('c', 'profiles');
		formData.set('a', 'invoke');
		formData.set('module', 'draft');
		formData.set('action', 'saveDraftCompose');

		genericAjaxPost(formData, null, '', function(json) {
			savingDraft = false;

			if('object' != typeof json)
				return;

			if(json.error) {
				$('#divDraftStatus{$popup_uniqid}').html(json.error);
			} else if(json.html && json.draft_id) {
				$('#divDraftStatus{$popup_uniqid}').html(json.html);
				$frm.find('input[name=draft_id]').val(json.draft_id);
			}
		});
	};

	$frm.find('[data-cerb-link=remove_suggested]').on('click', function(e) {
		e.stopPropagation();
		$(this).closest('div').hide();
	});

	$frm.find('[data-cerb-link=remove_file]').on('click', Devblocks.onClickRemoveParent);

	function enableAutoSaveDraft() {
		if(null == draftComposeAutoSaveInterval) {
			draftComposeAutoSaveInterval = setInterval(function () {
				saveDraftComposeNow();
			}, 30000);
		}
	}

	function disableAutoSaveDraft() {
		if(null != draftComposeAutoSaveInterval) {
			clearInterval(draftComposeAutoSaveInterval);
			draftComposeAutoSaveInterval = null;
		}
	}
	
	$popup.one('popup_open',function() {
		var $frm = $('#frmComposePeek{$popup_uniqid}');
		$popup.dialog('option','title','{'mail.send_mail'|devblocks_translate|capitalize|escape:'javascript' nofilter}');
		
		$popup.find('.cerb-editor-tabs > ul').each(function() {
			if(!(window.CerbUI && CerbUI.Tabs)) return;
			new CerbUI.Tabs(this, { onTabSelected: function(index, tab) {
				if(tab.li.getAttribute('data-cerb-tab') !== 'preview')
					return;

				var $panel = $(tab.panel);
				Devblocks.getSpinner().appendTo($panel.html(''));

				var formData = new FormData();
				formData.set('c', 'profiles');
				formData.set('a', 'invoke');
				formData.set('module', 'ticket');
				formData.set('action', 'previewReplyMessage');
				formData.set('format', $frm.find('input[name=format]').val());
				formData.set('group_id', $frm.find('[name=group_id]').val());
				formData.set('bucket_id', $frm.find('[name=bucket_id]').val());
				formData.set('html_template_id', $frm.find('[name=html_template_id]').val() || '');
				formData.set('content', $frm.find('textarea[name=content]').val());

				genericAjaxPost(formData, null, null, function(html) {
					$panel.html(html);
				});
			} });
		});
		
		// Autocompletes

		if(window.CerbUI && CerbUI.TextChooser) {
			// Recipient fields: comma-tokenized (autocomplete the address after the last comma, keep the
			// posted value a comma-separated string). Mirrors the old emailAutoComplete multiple mode.
			const emailCommaToken = {
				minLength: 1,
				avatars: true,
				context: 'address',
				source: 'c=internal&a=invoke&module=records&action=autocomplete&context=address',
				getTerm: function(v) { const p = v.lastIndexOf(','); return (p !== -1 ? v.substring(p + 1) : v).trim(); },
				onSelect: function(item, input) {
					const v = input.value, p = v.lastIndexOf(',');
					input.value = (p !== -1 ? v.substring(0, p) + ', ' : '') + item.label + ', ';
				}
			};
			$frm.find('input[name=to], input[name=cc], input[name=bcc]').each(function() { new CerbUI.TextChooser(this, emailCommaToken); });
			$frm.find('input:text[name=org_name]').each(function() {
				new CerbUI.TextChooser(this, {
					icon: 'building-office',
					avatars: true,
					minLength: 1, // don't pop the menu on focus with an empty field
					context: 'org',
					source: 'c=internal&a=invoke&module=records&action=autocomplete&context=org',
					onSelect: function(item, input) { input.value = item.label; } // post the org name, not its id
				});
			});
		}
		
		// Date helpers

		$frm.find('input[name=send_at]')
			.each(function() { if(window.CerbUI && CerbUI.DatePicker) new CerbUI.DatePicker.FormInput(this); })
			;

		$frm.find('input[name=ticket_reopen]')
			.each(function() { if(window.CerbUI && CerbUI.DatePicker) new CerbUI.DatePicker.FormInput(this); })
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
						
						if(val.length > 0 && val.trim().substr(-1) !== ',') {
							$input.val(val + ', ' + event.labels.join(', '));
						} else {
							$input.val(val + (0 === val.length || val.substr(-1) === ' ' ? '' : ' ') + event.labels.join(', '));
						}
					}
				});
			})
		;
		
		if(window.CerbUI && CerbUI.RecordChooser) {
			new CerbUI.RecordChooser($frm.find('#composeOwnerChooser{$popup_uniqid}')[0], { context: '{CerberusContexts::CONTEXT_WORKER}', name: 'owner_id', emptyIcon: 'user', query: 'isDisabled:n' });
			new CerbUI.RecordChooser($frm.find('#composeWatchersChooser{$popup_uniqid}')[0], { context: '{CerberusContexts::CONTEXT_WORKER}', name: 'watcher_ids', multiple: true, emptyIcon: 'user', query: 'isDisabled:n' });

			// HTML mail template (empty = default)
			var composeHtmlTemplateEl = $frm.find('#composeHtmlTemplateChooser{$popup_uniqid}')[0];
			if(composeHtmlTemplateEl) {
				new CerbUI.RecordChooser(composeHtmlTemplateEl, {
					context: 'html_template',
					name: 'html_template_id',
					emptyIcon: 'template',
					searchPlaceholder: "{'common.html_mail_template'|devblocks_translate|capitalize|escape:'javascript' nofilter}"
				});
			}
		}

		// Properties: status switcher (reveals the reopen field when not 'open') + options toggle + template menu
		if(window.CerbUI && CerbUI.Switcher) {
			let statusEl = $frm.find('#composeStatusSwitcher{$popup_uniqid}')[0];
			if(statusEl) {
				let statusInput = document.getElementById('composeStatusId{$popup_uniqid}');
				new CerbUI.Switcher(statusEl, {
					value: statusInput ? statusInput.value : null,
					onSelect: function(value) {
						if(statusInput) statusInput.value = value;
						if(value === '{Model_Ticket::STATUS_OPEN}')
							$('#divComposeClosed{$popup_uniqid}').stop(true,true).fadeOut();
						else
							$('#divComposeClosed{$popup_uniqid}').stop(true,true).fadeIn();
					}
				});
			}
		}

		if(window.CerbUI && CerbUI.Toggle)
			$frm.find('input[name=options_dont_send]').closest('.cerb-ui-toggle').each(function() { new CerbUI.Toggle(this); });

		// Attachments

		var fu_attachments = null;
		if(window.CerbUI && CerbUI.FileUpload)
			fu_attachments = new CerbUI.FileUpload($frm.find('.cerb-ui-file-upload')[0], { name: 'file_ids', multiple: true });

		var $editor = $frm.find('textarea[name=content]'); // the textarea — kept for keyboard binds + value reads

		// Open the snippet chooser; the chosen snippet pastes through the shared snippet-inserted handler (below)
		let openSnippetChooser = function() {
			let chooser_url = 'c=internal&a=invoke&module=records&action=chooserOpen&qr=' + encodeURIComponent('type:[plaintext,worker]') + '&single=1&context=' + encodeURIComponent('cerberusweb.contexts.snippet');
			let $chooser = genericAjaxPopup(Devblocks.uniqueId(), chooser_url, null, true, '90%');
			$chooser.on('chooser_save', function(event) {
				if(!event.values || 0 === event.values.length)
					return;
				let snippet_id = event.values[0];
				if(null != snippet_id)
					$(ed.el).triggerHandler($.Event('cerb-editor-toolbar-snippet-inserted', { snippet_id: snippet_id }));
			});
		};

		// Text editor (CerbUI.MarkdownEditor) — built-in formatting + markdown/plaintext toggle; the host buttons
		// (#command / snippet / save-draft / GPG) and the worker-configured custom toolbar merge in as sections.
		let editor_sections = [ $frm.find('[data-cerb-compose-editor-toolbar]')[0] ];
		{if $toolbar_custom}
		let compose_custom_toolbar_ul = $frm.find('.cerb-compose-editor-subtoolbar-custom ul.cerb-ui-toolbar')[0];
		if(compose_custom_toolbar_ul) editor_sections.push(compose_custom_toolbar_ul);
		{/if}

		let ed = new CerbUI.MarkdownEditor($frm.find('textarea[name=content]')[0], {
			mode: {if $is_html}'markdown'{else}'plaintext'{/if},
			onAutocomplete: CerbUI.mailReplyAutocompleteSource({ mode: 'compose' }),
			onImage: function(info) {
				if(fu_attachments) fu_attachments.add([{ id: info.file_id, name: info.file_name }]);
			},
			toolbar: {
				onMode: function(v) {
					// Markdown = HTML/parsedown mail (show the HTML template picker); plaintext = none
					let html = (v === 'markdown');
					$frm.find('input:hidden[name=format]').val(html ? 'parsedown' : '');
					$frm.find('[data-cerb-compose-html-template]').css('display', html ? '' : 'none');
				},
				sections: editor_sections,
				onAction: function(value, ed, item, sourceLi, e) {
					if(value === 'commands') { ed.insertText('#'); ed.openAutocomplete(); return true; }
					if(value === 'snippets') { openSnippetChooser(); return true; }
					if(value === 'save_draft') { saveDraftComposeNow(); return true; }
					if(value === 'encrypt') {
						let on = !!(item && item.pressed);
						$frm.find('> input:hidden[name=options_gpg_encrypt]').val(on ? 1 : 0);
						// Encrypting implies signing
						let tb = ed._editorToolbar && ed._editorToolbar.toolbar;
						if(on && tb && !tb.isPressed('gpg_sign')) {
							tb.setPressed('gpg_sign', true);
							$frm.find('> input:hidden[name=options_gpg_sign]').val(1);
						}
						return true;
					}
					if(value === 'sign') {
						$frm.find('> input:hidden[name=options_gpg_sign]').val((item && item.pressed) ? 1 : 0);
						return true;
					}
					return false;
				},
				toolbarOpts: {
					caller: {
						name: 'cerb.toolbar.mail.compose',
						params: { selected_text: '' }
					},
					start: function(formData) {
						formData.set('caller[params][selected_text]', ed.getSelection());
					},
					done: function(e) {
						if(e.type !== 'cerb-interaction-done')
							return;
						if(e.eventData.exit === 'return') {
							Devblocks.interactionWorkerPostActions(e.eventData);
							if(e.eventData.return && e.eventData.return.snippet) {
								ed.replaceSelection(e.eventData.return.snippet);
								setTimeout(function() { ed.focus(); }, 25);
							}
						}
					}
				}
			}
		});

		// Snippet paste — shared by the toolbar snippet button (openSnippetChooser) AND the inline #snippet
		// autocomplete (CerbUI.mailReplyAutocompleteSource triggers this on ed.el with a snippet_id).
		$(ed.el).on('cerb-editor-toolbar-snippet-inserted', function(event) {
			if(!event.hasOwnProperty('snippet_id'))
				return;

			let formData = new FormData();
			formData.set('c', 'profiles');
			formData.set('a', 'invoke');
			formData.set('module', 'snippet');
			formData.set('action', 'paste');
			formData.set('id', event.snippet_id);
			formData.set('context_ids[cerberusweb.contexts.worker]', '{$active_worker->id}');

			genericAjaxPost(formData, null, null, function(json) {
				if(json.hasOwnProperty('has_prompts')) {
					let $popup_paste = genericAjaxPopup('snippet_paste', 'c=profiles&a=invoke&module=snippet&action=getPrompts&id=' + encodeURIComponent(json.id) + '&context_id=' + encodeURIComponent(json.context_id), null, false, '50%');
					$popup_paste.bind('snippet_paste', function(event) {
						if(null != event.text)
							ed.insertText(event.text);
					});
				} else {
					ed.insertText(json.text);
				}
			});
		});

		// From (Group → Bucket) — nested type-to-filter menu (mirrors reply's Move)
		var $composeBucketLabel = $frm.find('[data-cerb-bucket-label]');
		var $composeBucketIcon = $frm.find('[data-cerb-bucket-icon]');
		var $composeBucketTrigger = $frm.find('#composeBucketTrigger{$popup_uniqid}');
		var composeBucketMenuUl = $frm.find('#composeBucketMenu{$popup_uniqid}')[0];

		var composeSetBucketIcon = function(label, gid, imageUrl) {
			if(!(window.CerbUI && CerbUI.Avatar)) return;
			var av = CerbUI.Avatar.create({ label: label || '?', seed: 'group:' + gid, imageUrl: imageUrl || '', size: 18 });
			av.style.marginRight = '0.4em';
			$composeBucketIcon.empty().append(av);
		};

		if($composeBucketTrigger.attr('data-group-id'))
			composeSetBucketIcon($composeBucketTrigger.attr('data-group-label'), $composeBucketTrigger.attr('data-group-id'), $composeBucketTrigger.attr('data-avatar'));

		if(composeBucketMenuUl && window.CerbUI && CerbUI.Menu) {
			var composeBucketMenu = new CerbUI.Menu(composeBucketMenuUl, {
				filter: true,
				captureKeys: true, // keep type-to-filter from leaking to page shortcuts; refocus the trigger on select
				panelClass: 'cerb-bucket-menu',
				onRenderItem: function(li, src) {
					var gid = src.getAttribute('data-group-id');
					if(!gid || !(window.CerbUI && CerbUI.Avatar)) return;

					if(li.classList.contains('cerb-ui-menu--item-pathed')) {
						var groupLabel = src.getAttribute('data-group-label') || '';
						var bucketName = (src.textContent || '').trim();
						li.querySelectorAll('.cerb-ui-menu--label, .cerb-ui-menu--path').forEach(function(n) { n.remove(); });

						var av = CerbUI.Avatar.create({ label: groupLabel || bucketName, seed: 'group:' + gid, imageUrl: src.getAttribute('data-group-avatar') || '', size: 22 });
						av.classList.add('cerb-bucket-menu--avatar');
						li.insertBefore(av, li.firstChild);

						var stack = document.createElement('span');
						stack.className = 'cerb-bucket-menu--text';
						var eyebrow = document.createElement('span');
						eyebrow.className = 'cerb-bucket-menu--eyebrow';
						eyebrow.textContent = groupLabel;
						var main = document.createElement('span');
						main.className = 'cerb-bucket-menu--main';
						main.textContent = bucketName;
						stack.appendChild(eyebrow);
						stack.appendChild(main);
						li.appendChild(stack);
						return;
					}

					if(src.hasAttribute('data-bucket-id')) return;
					var av2 = CerbUI.Avatar.create({ label: src.getAttribute('data-group-label') || '?', seed: 'group:' + gid, imageUrl: src.getAttribute('data-avatar') || '', size: 18 });
					av2.style.marginRight = '0.5em';
					li.insertBefore(av2, li.firstChild);
				},
				onSelect: function(li, src) {
					var bid = src.getAttribute('data-bucket-id');
					if(bid === null) return;
					var gid = src.getAttribute('data-group-id');
					$frm.find('#composeGroupId{$popup_uniqid}').val(gid);
					$frm.find('#composeBucketId{$popup_uniqid}').val(bid);
					$composeBucketLabel.text((src.getAttribute('data-group-label') || '') + ' › ' + (src.textContent || '').trim());
					composeSetBucketIcon(src.getAttribute('data-group-label'), gid, src.getAttribute('data-group-avatar'));
				}
			});

			$composeBucketTrigger.on('click', function(e) {
				e.stopPropagation();
				composeBucketMenu.isOpen() ? composeBucketMenu.close() : composeBucketMenu.open(this);
			});
		}
		
		$frm.find('input:text[name=to], input:text[name=cc], input:text[name=bcc]').focus(function(event) {
			$('#compose_suggested{$popup_uniqid}').appendTo($(this).closest('td'));
		});
		
		$frm.find('input:text[name=org_name]').on('change',function() {
			genericAjaxGet('', 'c=profiles&a=invoke&module=org&action=getTopContactsByOrgJson&org_name=' + encodeURIComponent($(this).val()), function(json) {
				var $sug = $('#compose_suggested{$popup_uniqid}');
				
				$sug.find('ul.bubbles li').remove();
				
				if(0 === json.length) {
					$sug.hide();
					return;
				}
				
				for(let i in json) {
					let label = '';
					if(null != json[i].name && json[i].name.length > 0) {
						label += json[i].name + " ";
						label += '<' + json[i].email + '>';
					} else {
						label += json[i].email;
					}

					$('<li/>')
						.appendTo($sug.find('ul.bubbles'))
						.append(
							$('<a/>')
								.addClass('suggested')
								.text(label)
								.appendTo($sug)
						)
					;
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
					else if($last===',')
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
		
		if(null != draftComposeAutoSaveInterval) {
			clearTimeout(draftComposeAutoSaveInterval);
			draftComposeAutoSaveInterval = null;
		}

		// Deliver later — toggle reveals the send-at date (ticket_reopen + send_at date pickers wired above)

		if(window.CerbUI && CerbUI.Toggle) {
			var $deliverPanel = $frm.find('[data-cerb-compose-deliver-later]');
			var deliverToggleEl = $deliverPanel.find('.cerb-ui-toggle')[0];
			if(deliverToggleEl) {
				new CerbUI.Toggle(deliverToggleEl, {
					onChange: function(checked) {
						var $body = $deliverPanel.find('[data-cerb-compose-deliver-later-body]');
						if(checked) {
							$body.show().find('input:text').focus();
						} else {
							$body.hide().find('input:text').val('');
						}
					}
				});
			}
		}
		
		enableAutoSaveDraft();

		// Shortcuts

		{if $pref_keyboard_shortcuts}
			var toolbarShortcutTrigger = function(e) {
				e.preventDefault();
				e.stopPropagation();
				$frm.find('[data-interaction-keyboard="' + this.keys + '"]').click();
				return true;
			};
	
			{if $toolbar_keyboard_shortcuts}
			{foreach from=$toolbar_keyboard_shortcuts item=toolbar_keyboard_shortcut}
			$editor.bind(
				'keydown',
				{$toolbar_keyboard_shortcut.keys|json_encode nofilter},
				toolbarShortcutTrigger.bind({$toolbar_keyboard_shortcut|json_encode nofilter})
			);
			{/foreach}
			{/if}
	
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
					$frm.find('#composeStatusSwitcher{$popup_uniqid} button[data-value="{Model_Ticket::STATUS_CLOSED}"]').click();
					$frm.find('input:text[name=ticket_reopen]').select().focus();
				} catch(ex) { }
			});

			// Status open
			$editor.bind('keydown', 'ctrl+shift+o', function(e) {
				e.preventDefault();
				try {
					$frm.find('#composeStatusSwitcher{$popup_uniqid} button[data-value="{Model_Ticket::STATUS_OPEN}"]').click();
				} catch(ex) { }
			});

			// Status waiting
			$editor.bind('keydown', 'ctrl+shift+w', function(e) {
				e.preventDefault();
				try {
					$frm.find('#composeStatusSwitcher{$popup_uniqid} button[data-value="{Model_Ticket::STATUS_WAITING}"]').click();
					$frm.find('input:text[name=ticket_reopen]').select().focus();
				} catch(ex) { }
			});

			// Insert signature
			$editor.bind('keydown', 'ctrl+shift+g', function(e) {
				e.preventDefault();
				try {
					ed.insertText('#signature\n');
				} catch(ex) { }
			});

			// Insert snippet
			$editor.bind('keydown', 'ctrl+shift+i', function(e) {
				e.preventDefault();
				try {
					openSnippetChooser();
				} catch(ex) { }
			});

			// Reformat quotes
			$editor.bind('keydown', 'ctrl+shift+q', function(e) {
				e.preventDefault();
				try {
					var txt = ed.getValue();

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

					// Re-wrap quoted blocks
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

					ed.setValue($.trim(out));
				} catch(ex) { }
			});
		{/if}

		// Focus the first empty text input
		$frm
			.find(':input:text')
			.filter(function() {
				return !$(this).val();
			})
			.first()
			.focus()
			.select()
		;
		
		$popup.on('popup_saved', function() {
			hideLoadingPanel();
		});
		
		var funcValidationInteractions = function(json) {
			var validation_interactions = Promise.resolve();
			
			if('object' != typeof json || !json.hasOwnProperty('validation_interactions'))
				return validation_interactions;

			for(var validation_interaction_key in json.validation_interactions) {
				if(!json.validation_interactions.hasOwnProperty(validation_interaction_key))
					continue;

				var validation_interaction = json.validation_interactions[validation_interaction_key];

				if(!validation_interaction.hasOwnProperty('data'))
					continue;

				validation_interactions = validation_interactions.then(function() {
					return new Promise(function(resolve, reject) {
						var interaction_params = '';

						if(this.data.hasOwnProperty('inputs') && 'object' == typeof this.data.inputs)
							interaction_params = $.param(this.data.inputs);
						
						var $interaction =
							$('<div/>')
								.attr('data-interaction-uri', this.data.uri)
								.attr('data-interaction-params', interaction_params)
								.attr('data-interaction-done', '')
								.cerbBotTrigger({
									'modal': true,
									'caller': 'mail.compose.send',
									'start': function(formData) {
										var draft_id = $frm.find('input:hidden[name=draft_id]').val();
										formData.set('caller[params][draft_id]', draft_id);	
									},
									'done': function(e) {
										e.stopPropagation();
										$interaction.remove();
										
										// If the interaction rejected validation
										if(e.eventData.hasOwnProperty('exit') && 'return' === e.eventData.exit) {
											if(e.eventData.hasOwnProperty('return') && e.eventData.return.hasOwnProperty('reject')) {
												setTimeout(function() { $editor.focus(); }, 25);
												reject(e);
												return;
											}
										}
										
										resolve(e);
									},
									'error': function(e) {
										e.stopPropagation();
										$interaction.remove();
										resolve(e);
										setTimeout(function() { $editor.focus(); }, 25);
									},
									'abort': function(e) {
										e.stopPropagation();
										$interaction.remove();
										reject(e);
										setTimeout(function() { $editor.focus(); }, 25);
									}
								})
								.click()
						;
					}.bind(this));
				}.bind(validation_interaction));
			}
			
			return validation_interactions;
		};		
		
		$frm.find('button.submit').on('click', $.throttle(500, function(e) {
			e.preventDefault();
			e.stopPropagation();

			var $button = $(this);

			Devblocks.clearAlerts();
			showLoadingPanel();
			$button.closest('div').hide();
			disableAutoSaveDraft();

			var hookError = function(message) {
				Devblocks.clearAlerts();
				Devblocks.createAlertError(message);
				$button.closest('div').show();
				enableAutoSaveDraft();
			};

			var hookSuccess = function() {
				showLoadingPanel();

				$frm.find('input:hidden[name=compose_mode]').val('');

				let cb = function(json) {
					hideLoadingPanel();

					$popup.trigger('popup_saved');

					var post_event = $.Event('cerb-compose-sent', {
						record: json
					});
					genericAjaxPopupClose($popup, post_event);
				}

				genericAjaxPost($frm, '', null, cb, {
					error: function(err) {
						hideLoadingPanel();

						if(err.responseText) {
							hookError(err.responseText);
						}
					}
				});
			};

			var formData = new FormData($frm[0]);
			formData.set('c', 'profiles');
			formData.set('a', 'invoke');
			formData.set('module', 'draft');
			formData.set('action', 'validateComposeJson');
			formData.set('compose_mode', 'send');

			// Validate via Ajax before sending
			genericAjaxPost(formData, '', '', function(json) {
				hideLoadingPanel();

				if(typeof json == 'object' && json.statusText && json.status && 200 !== json.status)
					return hookError();

				if(null == json || 'object' != typeof json)
					return hookError('An unexpected error occurred. Try again.');

				if(json.hasOwnProperty('validation_interactions') && 'object' == typeof json.validation_interactions) {
					var validation_interactions = funcValidationInteractions(json);

					validation_interactions
						.then(function() {
							hookSuccess();
						})
						.catch(function() {
							// Aborted
							enableAutoSaveDraft();
						})
						.finally(function() {
							$button.closest('div').show();
						})
					;

				} else if(json.hasOwnProperty('status') && json.status) {
					hookSuccess();

				} else {
					hookError(json.message);
				}
			});
		}));

		$frm.find('.draft').on('click', $.throttle(500, function(e) {
			e.preventDefault();
			e.stopPropagation();

			var $button = $(this);

			Devblocks.clearAlerts();
			showLoadingPanel();
			$button.closest('div').hide();
			disableAutoSaveDraft();

			var hookSuccess = function() {
				disableAutoSaveDraft();

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
						genericAjaxGet('view{$view_id}','c=internal&a=invoke&module=worklists&action=refresh&id={$view_id}');
						genericAjaxPopupClose($popup, $.Event('cerb-compose-draft'));
					}
				);
			};

			var hookError = function(message) {
				Devblocks.createAlertError(message);
				$button.closest('div').show();
				enableAutoSaveDraft();
			};

			var formData = new FormData($frm[0]);
			formData.set('c', 'profiles');
			formData.set('a', 'invoke');
			formData.set('module', 'draft');
			formData.set('action', 'validateComposeJson');
			formData.set('compose_mode', 'draft');

			// Validate via Ajax before sending
			genericAjaxPost(formData, '', '', function(json) {
				hideLoadingPanel();

				if(typeof json == 'object' && json.statusText && json.status && 200 !== json.status)
					return hookError();

				if(null == json || 'object' != typeof json)
					return hookError('An unexpected error occurred. Try again.');

				if(json.hasOwnProperty('validation_interactions') && 'object' == typeof json.validation_interactions) {
					var validation_interactions = funcValidationInteractions(json);

					validation_interactions
						.then(function() {
							hookSuccess();
						})
						.catch(function() {
							// Aborted
							enableAutoSaveDraft();
						})
						.finally(function() {
							$button.closest('div').show();
						})
					;

				} else if(json.hasOwnProperty('status') && json.status) {
					hookSuccess();

				} else {
					hookError(json.message);
				}
			});
		}));

		$frm.find('button.discard').on('click', function(e) {
			e.stopPropagation();

			window.onbeforeunload = null;

			CerbUI.Confirm.open({
				title: 'Discard draft',
				body: 'Are you sure you want to permanently delete this message?',
				confirmText: '{'common.discard'|devblocks_translate|capitalize|escape:'javascript' nofilter}',
				cancelText: '{'common.cancel'|devblocks_translate|capitalize|escape:'javascript' nofilter}',
				onConfirm: function() {
					disableAutoSaveDraft();

					var draft_id = $frm.find('input:hidden[name=draft_id]').val();

					var formData = new FormData();
					formData.set('c', 'profiles');
					formData.set('a', 'invoke');
					formData.set('module', 'draft');
					formData.set('action', 'deleteDraft');
					formData.set('draft_id', draft_id);

					genericAjaxPost(formData, '', '', function(res) {
						if(typeof res == 'object' && res.status && 200 !== res.status)
							return;

						genericAjaxGet('view{$view_id}', 'c=internal&a=invoke&module=worklists&action=refresh&id={$view_id}');
						genericAjaxPopupClose($popup, $.Event('cerb-compose-discard'));
					});
				}
			});
		});

		{if $draft->params.org_name}
		$frm.find('input:text[name=org_name]').trigger('change');
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