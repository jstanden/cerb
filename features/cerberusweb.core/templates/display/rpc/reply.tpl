{$headers = $message->getHeaders()}
{$mail_reply_html = DAO_WorkerPref::get($active_worker->id, 'mail_reply_html', 0)}
{$is_html = ($draft && $draft->params.format == 'parsedown') || $mail_reply_html}

<div class="reply_frame {if "inline" == $reply_format}block{/if}" style="margin:10px;">

<div id="replyAgentMount{$message->id}" style="--cerb-agent-pane-height:72vh;">
<form id="reply{$message->id}_form" method="post">
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
				<div class="cerb-ui-panel cerb-ui-panel--spaced cerb-ui-panel--warn">
					<div class="cerb-ui-header cerb-ui-header--center">
						<div class="cerb-ui-callout">
							<span class="cerb-icons cerb-icon-alert cerb-ui-callout--icon"></span>
							<div>
								<div class="cerb-ui-header--title-sm">Your message will not be delivered.</div>
								<div class="cerb-ui-header--subtitle">
									{$sender_address = DAO_Address::get($bucket->getReplyFrom())}
									{if $sender_address}
										<a class="cerb-peek-trigger" data-context="{CerberusContexts::CONTEXT_ADDRESS}" data-context-id="{$sender_address->id}">{$sender_address->email}</a>
										is not configured as a sender address. To send live mail, edit the email address and select <b>"We send email from this address"</b>.
									{else}
										The sender address for this bucket does not have a mail transport configured.
										To send live email, an administrator must assign a mail transport to the <a class="cerb-peek-trigger" data-context="{CerberusContexts::CONTEXT_BUCKET}" data-context-id="{$ticket->bucket_id}">bucket</a> or <a class="cerb-peek-trigger" data-context="{CerberusContexts::CONTEXT_GROUP}" data-context-id="{$ticket->group_id}">group</a>.
									{/if}
								</div>
							</div>
						</div>
					</div>
				</div>
			{elseif 'core.mail.transport.null' == $reply_transport->extension_id}
				<div class="cerb-ui-panel cerb-ui-panel--spaced cerb-ui-panel--warn">
					<div class="cerb-ui-header cerb-ui-header--center">
						<div class="cerb-ui-callout">
							<span class="cerb-icons cerb-icon-alert cerb-ui-callout--icon"></span>
							<div>
								<div class="cerb-ui-header--title-sm">Your message will not be delivered.</div>
								<div class="cerb-ui-header--subtitle">
									This bucket is configured to discard outgoing messages.
									To send live email, change the mail transport on the <a class="cerb-peek-trigger" data-context="{CerberusContexts::CONTEXT_BUCKET}" data-context-id="{$ticket->bucket_id}">bucket</a> or <a class="cerb-peek-trigger" data-context="{CerberusContexts::CONTEXT_GROUP}" data-context-id="{$ticket->group_id}">group</a>.
								</div>
							</div>
						</div>
					</div>
				</div>
			{/if}
			
			<table cellpadding="1" cellspacing="0" border="0" width="100%">
				{if $reply_from && $reply_transport}
				<tr>
					<td width="1%" nowrap="nowrap" align="right" valign="middle" class="cerb-u-text-uppercase cerb-u-text-muted cerb-u-fs-n1">{'message.header.from'|devblocks_translate|capitalize}:&nbsp;</td>
					<td width="99%" align="left">
						{$reply_as} &lt;<a class="cerb-peek-trigger" data-context="{CerberusContexts::CONTEXT_ADDRESS}" data-context-id="{$reply_from->id}">{$reply_from->email}</a>&gt; via
						<a class="cerb-peek-trigger" data-context="{CerberusContexts::CONTEXT_MAIL_TRANSPORT}" data-context-id="{$reply_transport->id}">{$reply_transport->name}</a>
					</td>
				</tr>
				{/if}
				
				<tr>
					<td width="1%" nowrap="nowrap" align="right" valign="middle" class="cerb-u-text-uppercase cerb-u-text-muted cerb-u-fs-n1"><a class="cerb-recipient-chooser" data-context="{CerberusContexts::CONTEXT_ADDRESS}" data-query="">{'message.header.to'|devblocks_translate|capitalize}</a>:&nbsp;</td>
					<td width="99%" align="left">
						<input type="text" size="45" name="to" value="{$draft->params.to}" placeholder="{if $is_forward}These recipients will receive this forwarded message{else}These recipients will automatically be included in all future correspondence as participants{/if}" class="required" style="width:98%;padding:2px;">
						{if !$is_forward}
							{if !empty($suggested_recipients)}
								<div id="reply{$message->id}_suggested">
									<a data-cerb-reply-suggested-remove><span class="cerb-icons cerb-icon-circle-remove"></span></a>
									<b>Consider adding these recipients:</b>
									<ul class="bubbles">
									{foreach from=$suggested_recipients item=sug name=sugs}
										<li><a class="suggested">{$sug.full_email}</a></li>
									{/foreach}
									</ul> 
								</div>
							{/if}
						{/if}
					</td>
				</tr>
				
				<tr>
					<td width="1%" nowrap="nowrap" align="right" valign="middle" class="cerb-u-text-uppercase cerb-u-text-muted cerb-u-fs-n1"><a class="cerb-recipient-chooser" data-context="{CerberusContexts::CONTEXT_ADDRESS}" data-query="">{'message.header.cc'|devblocks_translate|capitalize}</a>:&nbsp;</td>
					<td width="99%" align="left">
						<input type="text" size="45" name="cc" value="{$draft->params.cc}" placeholder="These recipients will publicly receive a one-time copy of this message" style="width:98%;padding:2px;">
					</td>
				</tr>
				
				<tr>
					<td width="1%" nowrap="nowrap" align="right" valign="middle" class="cerb-u-text-uppercase cerb-u-text-muted cerb-u-fs-n1"><a class="cerb-recipient-chooser" data-context="{CerberusContexts::CONTEXT_ADDRESS}" data-query="">{'message.header.bcc'|devblocks_translate|capitalize}</a>:&nbsp;</td>
					<td width="99%" align="left">
						<input type="text" size="45" name="bcc" value="{$draft->params.bcc}" placeholder="These recipients will secretly receive a one-time copy of this message" style="width:98%;padding:2px;">
					</td>
				</tr>
				
				<tr>
					<td width="1%" nowrap="nowrap" align="right" valign="middle" class="cerb-u-text-uppercase cerb-u-text-muted cerb-u-fs-n1">{'message.header.subject'|devblocks_translate|capitalize}:&nbsp;</td>
					<td width="99%" align="left">
						<input type="text" size="45" name="subject" value="{$draft->params.subject}" style="width:98%;padding:2px;" class="required" maxlength="255">
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

		{* Formatting buttons + markdown/plaintext toggle come from the editor (built-in). These host sections —
		   #command / snippet / save-draft / GPG, plus any worker-configured custom toolbar — merge into the strip. *}
		<ul class="cerb-ui-toolbar" data-cerb-reply-editor-toolbar hidden>
			<li data-value="commands" data-icon="placeholders" title="Insert #command"></li>
			<li data-value="snippets" data-icon="clipboard" title="Insert snippet (Ctrl+Shift+Period)"></li>
			<li data-value="save_draft" data-icon="save" title="Save draft (Ctrl+S)"></li>
			<li></li>
			<li data-value="encrypt" data-toggle data-key="gpg_encrypt" data-icon="lock"{if $draft->params.options_gpg_encrypt} data-pressed="1"{/if} title="{'common.encrypt'|devblocks_translate|capitalize}"></li>
			<li data-value="sign" data-toggle data-key="gpg_sign" data-icon="user-lock"{if $draft->params.options_gpg_sign} data-pressed="1"{/if} title="{'common.encrypt.sign'|devblocks_translate|capitalize}"></li>
		</ul>

		{if $toolbar_custom}
		<div data-cerb-toolbar class="cerb-reply-editor-subtoolbar-custom" hidden>
			{DevblocksPlatform::services()->ui()->toolbar()->render($toolbar_custom)}
		</div>
		{/if}

		<textarea name="content" id="reply_{$message->id}" spellcheck="true" {if !$is_forward}autofocus{/if}>{$draft->getParam('content')}</textarea>
	</div>

	<div id="reply{$message->id}EditorPreviewPanel" style="min-height:100px;max-height:400px;overflow:auto;border:1px dotted var(--cerb-color-background-contrast-150);padding:5px;"></div>
</div>

<fieldset class="peek reply-attachments" style="margin-top:10px;">
	<legend>{'common.attachments'|devblocks_translate|capitalize}</legend>

	<div class="cerb-ui-file-upload" data-name="file_ids" data-multiple="1">
	{if $draft->params.file_ids}
		{foreach from=$draft->params.file_ids item=file_id}
			{$file = DAO_Attachment::get($file_id)}
			{if !empty($file)}
			<li data-file-id="{$file->id}" data-file-name="{$file->name}" data-file-size="{$file->storage_size}"></li>
			{/if}
		{/foreach}
	{elseif $is_forward && !empty($forward_attachments)}
		{foreach from=$forward_attachments item=attach}
			<li data-file-id="{$attach->id}" data-file-name="{$attach->name}" data-file-size="{$attach->storage_size}"></li>
		{/foreach}
	{/if}
	</div>
</fieldset>

{$cur_group = $groups[$ticket->group_id]}
{$cur_bucket = $buckets[$ticket->bucket_id]}
{$reply_owner = $workers.{$ticket->owner_id}}
{$is_watching = isset($object_watchers[$ticket->id][$active_worker->id])}
{$reply_half = 'flex:1 1 calc(50% - 0.5em);min-width:13em;'}

<div class="cerb-ui-panel cerb-ui-panel--spaced" id="replyStatus{$message->id}">
	<div class="cerb-ui-header cerb-ui-header--tight">
		<div class="cerb-ui-header--title-sm">{'common.properties'|devblocks_translate|capitalize}</div>
	</div>

	<div class="cerb-ui-form">
		<div class="cerb-ui-form--row">
			{* Status *}
			<div class="cerb-ui-form--field" style="{$reply_half}">
				<label class="cerb-ui-form--label">{'common.status'|devblocks_translate|capitalize}</label>
				<div>
					<input type="hidden" name="status_id" id="replyStatusId{$message->id}" value="{$draft->params.status_id}">
					<div class="cerb-ui-switcher" data-cerb-input="replyStatusId{$message->id}" id="replyStatusSwitcher{$message->id}">
						<button type="button" data-value="{Model_Ticket::STATUS_OPEN}"{if $draft->params.status_id==Model_Ticket::STATUS_OPEN} class="cerb-ui-switcher--active"{/if}{if $pref_keyboard_shortcuts} title="(Ctrl+Shift+O)"{/if}><span class="cerb-icons cerb-icon-play-button"></span> {'status.open'|devblocks_translate|capitalize}</button>
						<button type="button" data-value="{Model_Ticket::STATUS_WAITING}"{if $draft->params.status_id==Model_Ticket::STATUS_WAITING} class="cerb-ui-switcher--active"{/if}{if $pref_keyboard_shortcuts} title="(Ctrl+Shift+W)"{/if}><span class="cerb-icons cerb-icon-clock"></span> {'status.waiting'|devblocks_translate|capitalize}</button>
						{if $active_worker->hasPriv('core.ticket.actions.close') || ($ticket->status_id == Model_Ticket::STATUS_CLOSED)}<button type="button" data-value="{Model_Ticket::STATUS_CLOSED}"{if $draft->params.status_id==Model_Ticket::STATUS_CLOSED} class="cerb-ui-switcher--active"{/if}{if $pref_keyboard_shortcuts} title="(Ctrl+Shift+C)"{/if}><span class="cerb-icons cerb-icon-circle-ok"></span> {'status.closed'|devblocks_translate|capitalize}</button>{/if}
					</div>

					<div id="replyClosed{$message->id}" class="cerb-u-mt-2" style="display:{if $draft->params.status_id==Model_Ticket::STATUS_WAITING}block{else}none{/if};">
						<label class="cerb-ui-form--label">{'display.reply.next.resume'|devblocks_translate}</label>
						<div class="cerb-u-flex cerb-u-items-center cerb-u-gap-1">
							<input type="text" name="ticket_reopen" style="flex:1 1 auto;min-width:0;" value="{$draft->params.ticket_reopen}">
						</div>
						<div class="cerb-ui-form--help">{'display.reply.next.resume_blank'|devblocks_translate}</div>
					</div>
				</div>
			</div>

			{* Move (Group → Bucket) — nested type-to-filter menu *}
			<div class="cerb-ui-form--field" style="{$reply_half}">
				<label class="cerb-ui-form--label">{'common.move'|devblocks_translate|capitalize}</label>
				<div>
					<input type="hidden" name="group_id" id="replyGroupId{$message->id}" value="{$ticket->group_id}">
					<input type="hidden" name="bucket_id" id="replyBucketId{$message->id}" value="{$ticket->bucket_id}">

					<button type="button" class="cerb-ui-button cerb-ui-button--subtle cerb-u-flex cerb-u-items-center cerb-u-gap-1" id="replyBucketTrigger{$message->id}" data-group-id="{$ticket->group_id}" data-group-label="{if $cur_group}{$cur_group->name}{/if}" data-avatar="{devblocks_url}c=avatars&context=group&context_id={$ticket->group_id}{/devblocks_url}">
						<span data-cerb-bucket-icon class="cerb-u-flex cerb-u-items-center"></span>
						<span data-cerb-bucket-label>{if $cur_group}{$cur_group->name}{/if}{if $cur_bucket} &rsaquo; {$cur_bucket->name}{/if}</span>
						<span class="cerb-icons cerb-icon-chevron-down"></span>
					</button>

					<ul id="replyBucketMenu{$message->id}" hidden>
						{foreach from=$groups item=group key=group_id}
							<li data-group-id="{$group_id}" data-group-label="{$group->name}" data-avatar="{devblocks_url}c=avatars&context=group&context_id={$group_id}{/devblocks_url}">{$group->name}
								<ul>
									{foreach from=$buckets item=bucket key=bucket_id}
										{if $bucket->group_id == $group_id}
											<li data-group-id="{$group_id}" data-bucket-id="{$bucket_id}" data-group-label="{$group->name}" data-group-avatar="{devblocks_url}c=avatars&context=group&context_id={$group_id}{/devblocks_url}">{$bucket->name}</li>
										{/if}
									{/foreach}
								</ul>
							</li>
						{/foreach}
					</ul>
				</div>
			</div>

			{* Owner *}
			<div class="cerb-ui-form--field" style="{$reply_half}">
				<label class="cerb-ui-form--label">{'common.owner'|devblocks_translate|capitalize}</label>
				<div class="cerb-ui-record-chooser" id="replyOwnerChooser{$message->id}">
					{if $reply_owner}
						<li data-context-id="{$reply_owner->id}" data-label="{$reply_owner->getName()}" data-image="{devblocks_url}c=avatars&context=worker&context_id={$reply_owner->id}{/devblocks_url}?v={$reply_owner->updated}"></li>
					{/if}
				</div>
			</div>

			{* Watchers — just the current worker's watch toggle (AJAX, not form-submitted) *}
			<div class="cerb-ui-form--field" style="{$reply_half}">
				<label class="cerb-ui-form--label">{'common.watchers'|devblocks_translate|capitalize}</label>
				<div class="cerb-u-flex cerb-u-items-center cerb-u-gap-2">
					<label class="cerb-ui-toggle">
						<input type="checkbox" id="replyWatch{$message->id}" {if $is_watching}checked="checked"{/if}>
						<span class="cerb-ui-toggle--slider"></span>
					</label>
					<label for="replyWatch{$message->id}">Watch this conversation</label>
				</div>
			</div>

			{* HTML mail template — only with formatting on (empty = default) *}
			{if $html_templates}
				<div class="cerb-ui-form--field" data-cerb-reply-html-template style="{$reply_half}{if !$is_html}display:none;{/if}">
					<label class="cerb-ui-form--label">{'common.html_mail_template'|devblocks_translate|capitalize}</label>
					<div class="cerb-ui-record-chooser" id="replyHtmlTemplateChooser{$message->id}">
						{$reply_html_template_id = $draft->params.html_template_id|default:0}
						{if $reply_html_template_id && isset($html_templates[$reply_html_template_id])}
							{$reply_cur_template = $html_templates[$reply_html_template_id]}
							<li data-context-id="{$reply_cur_template->id}" data-label="{$reply_cur_template->name}"></li>
						{/if}
					</div>
				</div>
			{/if}
		</div>
	</div>
</div>

{if $custom_fields || $custom_fieldsets_available}
	{if !empty($custom_fields)}
		{include file="devblocks:cerberusweb.core::internal/custom_fields/form.tpl" custom_fields=$custom_fields}
	{/if}

	{include file="devblocks:cerberusweb.core::internal/custom_fieldsets/peek_custom_fieldsets.tpl" context=CerberusContexts::CONTEXT_TICKET context_id=$ticket->id collapsible=true collapse_default=true custom_fieldsets_available=$custom_fieldsets_available custom_fieldsets_linked=$custom_fieldsets_linked}
{/if}

<div class="cerb-ui-panel cerb-ui-panel--spaced" data-cerb-deliver-later>
	<div class="cerb-ui-header cerb-ui-header--tight cerb-ui-header--center">
		<div class="cerb-ui-header--title-sm">
			<label class="cerb-ui-toggle">
				<input type="checkbox" class="cerb-reply-deliver-later-toggle" {if $draft->params.send_at}checked="checked"{/if}>
				<span class="cerb-ui-toggle--slider"></span>
			</label>
			Deliver later
		</div>
	</div>

	<div data-cerb-deliver-later-body style="{if $draft->params.send_at}{else}display:none;{/if}">
		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">When should the message be delivered? <span class="cerb-ui-form--hint">(leave blank to send immediately)</span></label>
			<div class="cerb-u-flex cerb-u-items-center cerb-u-gap-1">
				<input type="text" name="send_at" placeholder="now" style="flex:1 1 auto;min-width:0;" value="{$draft->params.send_at}">
			</div>
		</div>
	</div>
</div>

<div id="reply{$message->id}_buttons">
	<span class="cerb-ui-button--split">
		<button type="button" class="cerb-ui-button send" title="{if $pref_keyboard_shortcuts}(Ctrl+Shift+Enter){/if}"><span class="cerb-icons cerb-icon-send"></span> {if $is_forward}{'display.ui.forward'|devblocks_translate|capitalize}{else}{'display.ui.send_message'|devblocks_translate}{/if}</button><button type="button" class="cerb-ui-button" data-cerb-reply-send-menu><span class="cerb-icons cerb-icon-chevron-down"></span></button>
	</span>
	<ul hidden>
		<li><a class="send">{if $is_forward}{'display.ui.forward'|devblocks_translate}{else}{'display.ui.send_message'|devblocks_translate}{/if}</a></li>
		{if $active_worker->hasPriv('core.mail.save_without_sending')}<li><a class="save">{'display.ui.save_nosend'|devblocks_translate}</a></li>{/if}
	</ul>
	<button type="button" class="cerb-ui-button cerb-ui-button--subtle draft"><span class="cerb-icons cerb-icon-save"></span> {'display.ui.continue_later'|devblocks_translate}</button>
	<button type="button" class="cerb-ui-button cerb-ui-button--subtle discard"><span class="cerb-icons cerb-icon-trash"></span> {'display.ui.discard'|devblocks_translate|capitalize}</button>
</div>
</form>
</div>{* #replyAgentMount — AgentPane wraps this in popup mode *}

</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	let draftAutoSaveInterval = null;

	let $frm = $('#reply{$message->id}_form');
	let $reply = $frm.closest('div.reply_frame');
	let $reply_status = $('#replyStatus{$message->id}');

	Devblocks.formDisableSubmit($frm);

	// Save the draft via AJAX (used by the toolbar save button, Ctrl+S, and the autosave interval)
	let savingDraft = false;
	let saveDraftReplyNow = function() {
		if(savingDraft)
			return;
		savingDraft = true;

		let formData = new FormData($frm[0]);
		formData.set('c', 'profiles');
		formData.set('a', 'invoke');
		formData.set('module', 'draft');
		formData.set('action', 'saveDraftReply');
		formData.set('is_ajax', '1');

		genericAjaxPost(formData, null, '', function(obj) {
			savingDraft = false;

			if(!obj)
				return;

			if(obj.error) {
				$('#divDraftStatus{$message->id}').html(obj.error);
			} else if(obj.html && obj.draft_id) {
				$('#divDraftStatus{$message->id}').html(obj.html);
				$frm.find('input[name=draft_id]').val(obj.draft_id);
			}
		});
	};

	function enableAutoSaveDraft() {
		if(null == draftAutoSaveInterval) {
			draftAutoSaveInterval = setInterval(function() {
				saveDraftReplyNow();
			}, 30000);
		}
	}
	
	function disableAutoSaveDraft() {
		if(null != draftAutoSaveInterval) {
			clearInterval(draftAutoSaveInterval);
			draftAutoSaveInterval = null;
		}
	}

	$frm.find('.cerb-editor-tabs > ul').each(function() {
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
			formData.set('id', $frm.find('input[name=id]').val());
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

	$reply.on('cerb-reply--close', function(e) {
		e.stopPropagation();
		
		{if 'inline' == $reply_format}
		$reply.parent().empty();
		{else}
		genericAjaxPopupClose($popup);
		{/if}
	});
	
	var onReplyFormInit = function() {
		// Disable ENTER submission on the FORM text input
		$frm
			.find('input:text')
			.keydown(function(e) {
				if(13 === e.which)
					e.preventDefault();
			})
			;
		
		$frm.find('.cerb-peek-trigger').cerbPeekTrigger();
		
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
		}
		
		$frm.find('input:text[name=to], input:text[name=cc], input:text[name=bcc]').focus(function(event) {
			$('#reply{$message->id}_suggested').appendTo($(this).closest('td'));
		});
		
		// Attachments

		var fu_attachments = null;
		if(window.CerbUI && CerbUI.FileUpload)
			fu_attachments = new CerbUI.FileUpload($frm.find('.cerb-ui-file-upload')[0], { name: 'file_ids', multiple: true });

		// Properties: Status switcher → reveal the reopen-at date for 'waiting' only

		if(window.CerbUI && CerbUI.Switcher) {
			$reply_status.find('.cerb-ui-switcher[data-cerb-input]').each(function() {
				var input = document.getElementById(this.getAttribute('data-cerb-input'));
				var isStatus = (this.id === 'replyStatusSwitcher{$message->id}');

				new CerbUI.Switcher(this, {
					value: input ? input.value : null,
					onSelect: function(value) {
						if(input) input.value = value;

						if(isStatus) {
							if(value === '{Model_Ticket::STATUS_WAITING}') {
								$('#replyClosed{$message->id}').stop(true,true).fadeIn();
							} else {
								$('#replyClosed{$message->id}').stop(true,true).fadeOut();
							}
						}
					}
				});
			});
		}

		// Properties: Owner record chooser (active workers)

		if(window.CerbUI && CerbUI.RecordChooser) {
			var replyOwnerEl = $reply_status.find('#replyOwnerChooser{$message->id}')[0];
			if(replyOwnerEl) {
				new CerbUI.RecordChooser(replyOwnerEl, {
					context: 'worker',
					name: 'owner_id',
					query: 'isDisabled:n',
					emptyIcon: 'user',
					searchPlaceholder: "{'common.owner'|devblocks_translate|capitalize|escape:'javascript' nofilter}"
				});
			}

			// HTML mail template (empty = default)
			var replyHtmlTemplateEl = $reply_status.find('#replyHtmlTemplateChooser{$message->id}')[0];
			if(replyHtmlTemplateEl) {
				new CerbUI.RecordChooser(replyHtmlTemplateEl, {
					context: 'html_template',
					name: 'html_template_id',
					emptyIcon: 'template',
					searchPlaceholder: "{'common.html_mail_template'|devblocks_translate|capitalize|escape:'javascript' nofilter}"
				});
			}
		}

		// Properties: Move (Group → Bucket) nested type-to-filter menu

		var $replyBucketLabel = $reply_status.find('[data-cerb-bucket-label]');
		var $replyBucketIcon = $reply_status.find('[data-cerb-bucket-icon]');
		var $replyBucketTrigger = $reply_status.find('#replyBucketTrigger{$message->id}');
		var replyBucketMenuUl = $reply_status.find('#replyBucketMenu{$message->id}')[0];

		var replySetBucketIcon = function(label, gid, imageUrl) {
			if(!(window.CerbUI && CerbUI.Avatar)) return;
			var av = CerbUI.Avatar.create({ label: label || '?', seed: 'group:' + gid, imageUrl: imageUrl || '', size: 18 });
			av.style.marginRight = '0.4em';
			$replyBucketIcon.empty().append(av);
		};

		if($replyBucketTrigger.attr('data-group-id'))
			replySetBucketIcon($replyBucketTrigger.attr('data-group-label'), $replyBucketTrigger.attr('data-group-id'), $replyBucketTrigger.attr('data-avatar'));

		if(replyBucketMenuUl && window.CerbUI && CerbUI.Menu) {
			var replyBucketMenu = new CerbUI.Menu(replyBucketMenuUl, {
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
					$reply_status.find('#replyGroupId{$message->id}').val(gid);
					$reply_status.find('#replyBucketId{$message->id}').val(bid);
					$replyBucketLabel.text((src.getAttribute('data-group-label') || '') + ' › ' + (src.textContent || '').trim());
					replySetBucketIcon(src.getAttribute('data-group-label'), gid, src.getAttribute('data-group-avatar'));
				}
			});

			$replyBucketTrigger.on('click', function(e) {
				e.stopPropagation();
				replyBucketMenu.isOpen() ? replyBucketMenu.close() : replyBucketMenu.open(this);
			});
		}

		// Properties: Watchers — toggle the current worker as a watcher (AJAX, not form-submitted)

		if(window.CerbUI && CerbUI.Toggle) {
			var replyWatchEl = $reply_status.find('#replyWatch{$message->id}').closest('.cerb-ui-toggle')[0];
			if(replyWatchEl) {
				new CerbUI.Toggle(replyWatchEl, {
					onChange: function(checked) {
						var formData = new FormData();
						formData.set('c', 'internal');
						formData.set('a', 'invoke');
						formData.set('module', 'watchers');
						formData.set('action', 'toggleCurrentWorkerAsWatcher');
						formData.set('context', '{CerberusContexts::CONTEXT_TICKET}');
						formData.set('context_id', '{$ticket->id}');
						formData.set('_csrf_token', '{$session.csrf_token}');
						genericAjaxPost(formData, '', '', function(json) {
							// Keep the toggle in sync with the server's actual state
							if(json && 'object' === typeof json && json.hasOwnProperty('has_active_worker'))
								$reply_status.find('#replyWatch{$message->id}').prop('checked', !!json.has_active_worker);
						});
					}
				});
			}
		}

		// Deliver later — toggle reveals the send-at date

		if(window.CerbUI && CerbUI.Toggle) {
			var $deliverPanel = $frm.find('[data-cerb-deliver-later]');
			var deliverToggleEl = $deliverPanel.find('.cerb-ui-toggle')[0];
			if(deliverToggleEl) {
				new CerbUI.Toggle(deliverToggleEl, {
					onChange: function(checked) {
						var $body = $deliverPanel.find('[data-cerb-deliver-later-body]');
						if(checked) {
							$body.show().find('input:text').focus();
						} else {
							$body.hide().find('input:text').val('');
						}
					}
				});
			}
		}

		var $editor = $('#reply_{$message->id}'); // the textarea — kept for keyboard binds + value reads

		// Open the snippet chooser; the chosen snippet pastes through the shared snippet-inserted handler (below)
		let openSnippetChooser = function() {
			let chooser_url = 'c=internal&a=invoke&module=records&action=chooserOpen&qr=' + encodeURIComponent('type:[plaintext,ticket,worker]') + '&single=1&context=' + encodeURIComponent('cerberusweb.contexts.snippet');
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
		let editor_sections = [ $reply.find('[data-cerb-reply-editor-toolbar]')[0] ];
		{if $toolbar_custom}
		let reply_custom_toolbar_ul = $reply.find('.cerb-reply-editor-subtoolbar-custom ul.cerb-ui-toolbar')[0];
		if(reply_custom_toolbar_ul) editor_sections.push(reply_custom_toolbar_ul);
		{/if}

		let ed = new CerbUI.MarkdownEditor($frm.find('textarea[name=content]')[0], {
			mode: {if $is_html}'markdown'{else}'plaintext'{/if},
			onAutocomplete: CerbUI.mailReplyAutocompleteSource({ mode: 'reply' }),
			onImage: function(info) {
				if(fu_attachments) fu_attachments.add([{ id: info.file_id, name: info.file_name }]);
			},
			toolbar: {
				onMode: function(v) {
					// Markdown = HTML/parsedown mail (show the HTML template picker); plaintext = none
					let html = (v === 'markdown');
					$frm.find('input:hidden[name=format]').val(html ? 'parsedown' : '');
					$frm.find('[data-cerb-reply-html-template]').css('display', html ? '' : 'none');
				},
				sections: editor_sections,
				onAction: function(value, ed, item, sourceLi, e) {
					if(value === 'commands') { ed.insertText('#'); ed.openAutocomplete(); return true; }
					if(value === 'snippets') { openSnippetChooser(); return true; }
					if(value === 'save_draft') { saveDraftReplyNow(); return true; }
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
						name: 'cerb.toolbar.mail.reply',
						params: { selected_text: '' }
					},
					start: function(formData) {
						formData.set('caller[params][selected_text]', ed.getSelection());
						formData.set('caller[params][text]', ed.getValue());
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
			formData.set('context_ids[cerberusweb.contexts.ticket]', '{$ticket->id}');
			formData.set('context_ids[cerberusweb.contexts.worker]', '{$active_worker->id}');

			genericAjaxPost(formData, null, null, function(json) {
				if(json.has_prompts) {
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

		// Dates
		
		$frm.find('input[name=send_at]')
			.each(function() { if(window.CerbUI && CerbUI.DatePicker) new CerbUI.DatePicker.FormInput(this); })
			;
			
		$frm.find('input[name=ticket_reopen]')
			.each(function() { if(window.CerbUI && CerbUI.DatePicker) new CerbUI.DatePicker.FormInput(this, {
				submit: function(e) {
					$('#reply{$message->id}_buttons a.send').click();
				}
			}); })
			
		// Insert suggested on click

		let $suggested = $('#reply{$message->id}_suggested');

		$suggested.find('[data-cerb-reply-suggested-remove]').on('click', function(e) {
			e.stopPropagation();
			$(this).closest('div').remove();
		});

		$suggested.find('a.suggested').click(function(e) {
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

		// Deliver later — wired via CerbUI.Toggle above

		// Focus
		
		{if !$is_forward}
			$editor.focus();
		{else}
			$frm.find('input:text[name=to]').focus();
		{/if}
		
		// Reply action buttons
		
		var $buttons = $('#reply{$message->id}_buttons');

		// Send split-button caret → a CerbUI.Menu over the hidden source <ul>; selecting a row clicks its
		// existing <a> handler (a.send / a.save), so the send/save dispatch is untouched.
		(function() {
			let caret = $buttons.find('[data-cerb-reply-send-menu]')[0];
			let menuUl = $buttons.children('ul')[0];

			if(caret && menuUl && window.CerbUI && CerbUI.Menu) {
				let menu = new CerbUI.Menu(menuUl, {
					onSelect: function(renderedLi, sourceLi) {
						$(sourceLi).find('a').trigger('click');
					}
				});

				$(caret).on('click', function(e) {
					e.stopPropagation();
					menu.isOpen() ? menu.close() : menu.open(caret);
				});
			}
		})();

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
									'caller': 'mail.reply.send',
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
										reject();
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

		$buttons.find('button.send').on('click', $.throttle(500, function(e) {
			e.stopPropagation();
			e.preventDefault();

			$buttons.find('a.send').click();
		}));
		
		$buttons.find('button.discard').on('click', function(e) {
			e.stopPropagation();

			window.onbeforeunload = null;

			CerbUI.Confirm.open({
				title: 'Discard draft',
				body: 'Are you sure you want to permanently delete this reply draft?',
				confirmText: '{'display.ui.discard'|devblocks_translate|capitalize|escape:'javascript' nofilter}',
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

						$reply.trigger('cerb-reply-discard');

						$('#draft'+encodeURIComponent(draft_id)).remove();
						$reply.triggerHandler('cerb-reply--close');
					});
				}
			});
		});
		
		$buttons.find('a.send').on('click', $.throttle(500, function(e) {
			e.preventDefault();
			e.stopPropagation();
			
			var $button = $(this);

			Devblocks.clearAlerts();
			showLoadingPanel();
			$button.closest('div').hide();
			disableAutoSaveDraft();

			var hookError = function(message) {
				Devblocks.clearAlerts();

				if(typeof message === 'string' && message.length > 0)
					Devblocks.createAlertError(message);

				$button.closest('div').show();
				enableAutoSaveDraft();
			};

			var hookSuccess = function() {
				showLoadingPanel();
				
				$frm.find('input:hidden[name=reply_mode]').val('');

				let cb = function(json) {
					hideLoadingPanel();

					var event = new $.Event('cerb-reply-sent', {
						record: json
					});
					$reply.trigger(event);

					$reply.triggerHandler('cerb-reply--close');
				};

				genericAjaxPost($frm, '', null, cb, {
					error: function(err) {
						hideLoadingPanel();

						if(err.responseText)
							hookError(err.responseText);
					}
				});
			};
			
			var formData = new FormData($frm[0]);
			formData.set('c', 'profiles');
			formData.set('a', 'invoke');
			formData.set('module', 'ticket');
			formData.set('action', 'validateReplyJson');
			formData.set('reply_mode', 'send');

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
		
		$buttons.find('a.save').on('click', $.throttle(500, function(e) {
			e.preventDefault();
			e.stopPropagation();
			
			var $button = $(this);

			Devblocks.clearAlerts();
			showLoadingPanel();
			$button.closest('div').hide();
			disableAutoSaveDraft();
			
			var hookSuccess = function() {
				showLoadingPanel();
				
				$frm.find('input:hidden[name=reply_mode]').val('save');

				genericAjaxPost($frm, '', null, function(json) {
					hideLoadingPanel();
					
					var event = new $.Event('cerb-reply-saved', {
						record: json
					});
					$reply.trigger(event);

					$reply.triggerHandler('cerb-reply--close');
				});
			};
			
			var hookError = function(message) {
				Devblocks.createAlertError(message);
				$button.closest('div').show();
				enableAutoSaveDraft();
			};
			
			var formData = new FormData($frm[0]);
			formData.set('c', 'profiles');
			formData.set('a', 'invoke');
			formData.set('module', 'ticket');
			formData.set('action', 'validateReplyJson');
			formData.set('reply_mode', 'save');

			// Validate via Ajax before saving
			genericAjaxPost(formData, '', '', function(json) {
				hideLoadingPanel();

				if(typeof json == 'object' && json.statusText && json.status && 200 !== json.status)
					return hookError();

				if(null == json || 'object' != typeof json)
					return hookError('An unexpected error occurred. Try again.');
				
				if(json.hasOwnProperty('validation_interactions') && 'object' == typeof json.validation_interactions) {
					var validation_interactions = funcValidationInteractions(json);

					validation_interactions
						.then(function () {
							hookSuccess();
						})
						.catch(function () {
							// Aborted
							enableAutoSaveDraft();
						})
						.finally(function () {
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
		
		$buttons.find('.draft').on('click', $.throttle(500, function(e) {
			e.preventDefault();
			e.stopPropagation();

			var $button = $(this);

			Devblocks.clearAlerts();
			showLoadingPanel();
			$button.closest('div').hide();
			disableAutoSaveDraft();
			
			var hookSuccess = function() {
				showLoadingPanel();
				
				var formData = new FormData($frm[0]);
				formData.set('c', 'profiles');
				formData.set('a', 'invoke');
				formData.set('module', 'draft');
				formData.set('action', 'saveDraftReply');

				genericAjaxPost(formData, '', null, function(json) {
					hideLoadingPanel();
					
					$button.closest('div').show();

					var event = new $.Event('cerb-reply-draft', {
						record: json
					});
					$reply.trigger(event);

					$reply.triggerHandler('cerb-reply--close');
				});
			};
			
			var hookError = function(message) {
				Devblocks.createAlertError(message);
				$button.closest('div').show();
				enableAutoSaveDraft();
			};

			var formData = new FormData($frm[0]);
			formData.set('c', 'profiles');
			formData.set('a', 'invoke');
			formData.set('module', 'ticket');
			formData.set('action', 'validateReplyJson');
			formData.set('reply_mode', 'draft');

			// Validate via Ajax before saving
			genericAjaxPost(formData, '', '', function(json) {
				hideLoadingPanel();

				if(typeof json == 'object' && json.statusText && json.status && 200 !== json.status)
					return hookError();

				if(null == json || 'object' != typeof json)
					return hookError('An unexpected error occurred. Try again.');
				
				if(json.hasOwnProperty('validation_interactions') && 'object' == typeof json.validation_interactions) {
					var validation_interactions = funcValidationInteractions(json);

					validation_interactions
						.then(function () {
							showLoadingPanel();
							hookSuccess();
						})
						.catch(function () {
							// Aborted
							enableAutoSaveDraft();
						})
						.finally(function () {
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
		
		// Focus
		saveDraftReplyNow(); // save now
		enableAutoSaveDraft();
		
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
			var toolbarShortcutTrigger = function(e) {
				e.preventDefault();
				e.stopPropagation();
				$reply.find('[data-interaction-keyboard="' + this.keys + '"]').click();
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
					$reply_status.find('.cerb-ui-switcher button[data-value="{Model_Ticket::STATUS_CLOSED}"]').click();
				} catch(ex) { }
			});

			// Status Open
			$editor.bind('keydown', 'ctrl+shift+o', function(e) {
				e.preventDefault();
				try {
					$reply_status.find('.cerb-ui-switcher button[data-value="{Model_Ticket::STATUS_OPEN}"]').click();
					$reply_status.find('#replyBucketTrigger{$message->id}').focus();
				} catch(ex) { }
			});

			// Status Waiting
			$editor.bind('keydown', 'ctrl+shift+w', function(e) {
				e.preventDefault();
				try {
					$reply_status.find('.cerb-ui-switcher button[data-value="{Model_Ticket::STATUS_WAITING}"]').click();
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

			// Fix line endings
			$editor.bind('keydown', 'ctrl+shift+l', function(e) {
				e.preventDefault();
				try {
					ed.setValue(ed.getValue().replaceAll("\n\n\n","\n\n"));
				} catch(e) { }
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

					ed.setValue($.trim(out));

				} catch(ex) { }
			});
		{/if}

		{* @deprecated Run custom jQuery scripts from VA behavior *}
		{* [TODO] Remove this in 11.0 *}
		
		{if !empty($jquery_scripts)}
		$('#reply{$message->id}_form').closest('div.reply_frame').each(function(e) {
			{foreach from=$jquery_scripts item=jquery_script}
			try {
				{$jquery_script nofilter}
			} catch(e) { }
			{/foreach}
		});
		{/if}

		{* Agent pane — a collapsible chat astride the WHOLE reply form so an interaction can drive the UI, not
		   just the body. Popup mode only for now; built here (end of init) so the runCommand closure has `ed` +
		   the form widgets. Two commands: getFields (all fields in one round-trip) + setField (one field by key).
		   Empty toolbar → the pane hides its toggle. *}
		{if !$reply_format}
		if(window.CerbUI && CerbUI.AgentPane) {
			let $replyDialogPopup = genericAjaxPopupFind($reply);
			new CerbUI.AgentPane(document.getElementById('replyAgentMount{$message->id}'), {
				component: 'mail_reply',
				capabilities: 'getFields,setField',
				mutatingCommands: 'setField',   // writes the reply fields → guard against accidental navigation loss
				toolbarHtml: {$agent_toolbar_html_json|default:'""' nofilter},
				storageKey: 'cerb-mail-reply-agent-chat',
				fit: true, // popup: keep the form's natural height; just add a sidebar
				// Put the toggle on the right of the editor's toolbar strip (falls back to an auto strip if absent).
				toggleInto: (ed._editorToolbar && ed._editorToolbar.el) ? ed._editorToolbar.el : null,

				onToggle: function(collapsed) {
					// Widen the reply dialog to make room for the chat; restore on close.
					let dlg = (CerbUI.Dialog && $replyDialogPopup.length) ? CerbUI.Dialog.from($replyDialogPopup[0]) : null;
					if(!dlg) return;
					dlg._widthPct = collapsed ? 70 : 95;
					dlg.w = dlg._computeWidth();
					dlg.el.style.width = dlg.w + 'px';
					dlg._positionDefault();
					CerbUI.Dialog._syncPageHeight();
				},
				runCommand: function(name, params) {
					params = params || {};
					let fmt = function() { return $frm.find('input[name=format]').val() === 'parsedown' ? 'markdown' : 'plaintext'; };
					if(name === 'getFields') {
						return JSON.stringify({
							to:      $frm.find('input[name=to]').val(),
							cc:      $frm.find('input[name=cc]').val(),
							bcc:     $frm.find('input[name=bcc]').val(),
							subject: $frm.find('input[name=subject]').val(),
							format:  fmt(),
							content: ed.getValue()
						});
					}
					if(name === 'setField') {
						let key = params.key, value = (params.value == null) ? '' : String(params.value);
						switch(key) {
							case 'to': case 'cc': case 'bcc': case 'subject':
								$frm.find('input[name=' + key + ']').val(value);
								return 'ok';
							case 'content':
								ed.setValue(value);
								return 'ok';
							case 'format': {
								// setMode() alone doesn't run the toolbar onMode side-effects — replicate them.
								let toMd = (value === 'markdown');
								ed.setMode(toMd ? 'markdown' : 'plaintext');
								$frm.find('input:hidden[name=format]').val(toMd ? 'parsedown' : '');
								$frm.find('[data-cerb-reply-html-template]').css('display', toMd ? '' : 'none');
								return 'ok';
							}
						}
						return 'unknown field: ' + key;
					}
					return '';
				}
			});
		}
		{/if}
	}

	{if !$reply_format}
		var $popup = genericAjaxPopupFind($reply);
		
		$popup.one('popup_open',function() {
			$popup.dialog('option','title','{if $is_forward}{'display.ui.forward'|devblocks_translate|capitalize}{else}{'common.reply'|devblocks_translate|capitalize}{/if}');
			$popup.css('overflow', 'inherit');
		});
	{/if}
	
	onReplyFormInit();
});
</script>