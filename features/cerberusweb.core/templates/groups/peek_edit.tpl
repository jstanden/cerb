{$peek_context = CerberusContexts::CONTEXT_GROUP}
{$peek_context_id = $group->id}
{$form_id = "formGroupsPeek{uniqid()}"}
<form action="{devblocks_url}{/devblocks_url}" method="POST" id="{$form_id}">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="group">
<input type="hidden" name="action" value="savePeekJson">
<input type="hidden" name="view_id" value="{$view_id}">
{if !empty($group) && !empty($group->id)}<input type="hidden" name="id" value="{$group->id}">{/if}
<input type="hidden" name="do_delete" value="0">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<div id="{$form_id}Tabs">
	<ul>
		<li><a href="#{$form_id}Profile">{'common.profile'|devblocks_translate|capitalize}</a></li>
		<li><a href="#{$form_id}Inbox">{'common.mail.incoming'|devblocks_translate|capitalize}</a></li>
		<li><a href="#{$form_id}Mail">{'common.mail.outgoing'|devblocks_translate|capitalize}</a></li>
		<li><a href="#{$form_id}Members">{'common.members'|devblocks_translate|capitalize}</a></li>
	</ul>

	<div id="{$form_id}Profile">
		<table cellpadding="2" cellspacing="0" border="0" width="100%" style="margin-bottom:10px;">
			<tr>
				<td width="0%" nowrap="nowrap" valign="middle">{'common.name'|devblocks_translate|capitalize}: </td>
				<td width="100%">
					<input type="text" name="name" style="width:98%;border:1px solid rgb(180,180,180);padding:2px;" value="{$group->name}" autocomplete="off" autofocus="autofocus">
				</td>
			</tr>

			<tr>
				<td width="0%" nowrap="nowrap" valign="top">{'common.type'|devblocks_translate|capitalize}: </td>
				<td width="100%">
					<div>
						<label><input type="radio" name="is_private" value="0" {if !$group->is_private}checked="checked"{/if}> <b>{'common.public'|devblocks_translate|capitalize}</b> - group content is visible to non-members</label>
					</div>
					<div>
						<label><input type="radio" name="is_private" value="1" {if $group->is_private}checked="checked"{/if}> <b>{'common.private'|devblocks_translate|capitalize}</b> - group content is hidden from non-members</label>
					</div>
				</td>
			</tr>

			<tr>
				<td width="1%" nowrap="nowrap" valign="top">{'common.image'|devblocks_translate|capitalize}:</td>
				<td width="99%" valign="top">
					<div style="float:left;margin-right:5px;">
						<img class="cerb-avatar" src="{devblocks_url}c=avatars&context=group&context_id={$group->id}{/devblocks_url}?v={$group->updated}" style="height:50px;width:50px;">
					</div>
					<div style="float:left;">
						<button type="button" class="cerb-avatar-chooser" data-context="{CerberusContexts::CONTEXT_GROUP}" data-context-id="{$group->id}">{'common.edit'|devblocks_translate|capitalize}</button>
						<input type="hidden" name="avatar_image">
					</div>
				</td>
			</tr>

			{include file="devblocks:cerberusweb.core::internal/custom_fields/bulk/form.tpl" tbody=true bulk=false}
		</table>

		{include file="devblocks:cerberusweb.core::internal/custom_fieldsets/peek_custom_fieldsets.tpl" context=CerberusContexts::CONTEXT_GROUP context_id=$group->id}
	</div>

	<div id="{$form_id}Mail">
		{$option_id = "divGroupCfgSubject{uniqid()}"}
		<fieldset class="peek">
			<legend>Default for all group buckets:</legend>

			<table cellpadding="2" cellspacing="0" border="0" width="100%">
				<tr>
					<td valign="middle" width="0%" nowrap="nowrap">
						{'common.send.from'|devblocks_translate}:
					</td>
					<td valign="middle" width="100%">
						<button type="button" class="chooser-abstract" data-field-name="reply_address_id" data-context="{CerberusContexts::CONTEXT_ADDRESS}" data-single="true" data-query="mailTransport.id:>0 isBanned:n isDefunct:n" data-query-required="" data-autocomplete="mailTransport.id:>0 isBanned:n isDefunct:n" data-autocomplete-if-empty="true"><span class="cerb-icons cerb-icon-search"></span></button>

						{$replyto = DAO_Address::get($group->reply_address_id)}

						<ul class="bubbles chooser-container">
							{if $replyto}
								<li><img class="cerb-avatar" src="{devblocks_url}c=avatars&context=address&context_id={$replyto->id}{/devblocks_url}?v={$replyto->updated_at}"><input type="hidden" name="reply_address_id" value="{$replyto->id}"><a class="cerb-peek-trigger no-underline" data-context="{CerberusContexts::CONTEXT_ADDRESS}" data-context-id="{$replyto->id}">{$replyto->email}</a></li>
							{/if}
						</ul>
					</td>
				</tr>

				<tr>
					<td valign="top" width="0%" nowrap="nowrap">
						{'common.send.as'|devblocks_translate}:
					</td>
					<td valign="top">
						<textarea name="reply_personal" placeholder="e.g. Customer Support" class="cerb-template-trigger" data-context="{CerberusContexts::CONTEXT_WORKER}" style="width:100%;height:50px;">{$group->reply_personal}</textarea>
					</td>
				</tr>

				<tr>
					<td valign="middle" width="0%" nowrap="nowrap">
						{'common.signature'|devblocks_translate|capitalize}:
					</td>
					<td valign="middle">
						<button type="button" class="chooser-abstract" data-field-name="reply_signature_id" data-context="{CerberusContexts::CONTEXT_EMAIL_SIGNATURE}" data-single="true" data-query="" data-autocomplete="" data-autocomplete-if-empty="true"><span class="cerb-icons cerb-icon-search"></span></button>

						{$signature = DAO_EmailSignature::get($group->reply_signature_id)}

						<ul class="bubbles chooser-container">
							{if $signature}
								<li><input type="hidden" name="reply_signature_id" value="{$signature->id}"><a class="cerb-peek-trigger no-underline" data-context="{CerberusContexts::CONTEXT_EMAIL_SIGNATURE}" data-context-id="{$signature->id}">{$signature->name}</a></li>
							{/if}
						</ul>
					</td>
				</tr>

				<tr>
					<td valign="middle" width="0%" nowrap="nowrap">
						{'common.encrypt.signing.key'|devblocks_translate|capitalize}:
					</td>
					<td valign="middle">
						<button type="button" class="chooser-abstract" data-field-name="reply_signing_key_id" data-context="{CerberusContexts::CONTEXT_GPG_PRIVATE_KEY}" data-single="true" data-query=""><span class="cerb-icons cerb-icon-search"></span></button>

						{$signing_key = DAO_GpgPrivateKey::get($group->reply_signing_key_id)}

						<ul class="bubbles chooser-container">
							{if $signing_key}
								<li><input type="hidden" name="reply_signing_key_id" value="{$signing_key->id}"><a class="cerb-peek-trigger no-underline" data-context="{CerberusContexts::CONTEXT_GPG_PRIVATE_KEY}" data-context-id="{$signing_key->id}">{$signing_key->name}</a></li>
							{/if}
						</ul>
					</td>
				</tr>

				<tr>
					<td valign="middle" width="0%" nowrap="nowrap">
						HTML template:
					</td>
					<td valign="middle">
						<button type="button" class="chooser-abstract" data-field-name="reply_html_template_id" data-context="{CerberusContexts::CONTEXT_MAIL_HTML_TEMPLATE}" data-single="true" data-query="" data-autocomplete="" data-autocomplete-if-empty="true"><span class="cerb-icons cerb-icon-search"></span></button>

						{$html_template = DAO_MailHtmlTemplate::get($group->reply_html_template_id)}

						<ul class="bubbles chooser-container">
							{if $html_template}
								<li><input type="hidden" name="reply_html_template_id" value="{$html_template->id}"><a class="cerb-peek-trigger no-underline" data-context="{CerberusContexts::CONTEXT_MAIL_HTML_TEMPLATE}" data-context-id="{$html_template->id}">{$html_template->name}</a></li>
							{/if}
						</ul>
					</td>
				</tr>

				<tr>
					<td valign="top" width="0%" nowrap="nowrap">
						Masks:
					</td>
					<td valign="middle">
						<label><input type="checkbox" name="subject_has_mask" value="1" {if $group->subject_has_mask}checked{/if}> Include ticket masks in message subjects:</label><br>
						<div id="{$option_id}" style="margin:5px 0;display:{if $group->subject_has_mask}block{else}none{/if}">
							<b>Subject prefix:</b> (optional, e.g. "billing", "tech-support")<br>
							Re: [ <input type="text" name="subject_prefix" placeholder="prefix" value="{$group->subject_prefix}" size="24"> #MASK-12345-678]: Subject<br>
						</div>
					</td>
				</tr>
			</table>
		</fieldset>
	</div>

	<div id="{$form_id}Inbox">
		<fieldset data-cerb-bucket-routing-toolbar class="peek">
			<legend>When a new ticket arrives in the {if $group && $group->id}{$group->name}{else}group{/if} inbox: (KATA)</legend>
			<div class="cerb-code-editor-toolbar">
				<button type="button" class="cerb-code-editor-toolbar-button" data-cerb-editor-button-magic title="{'common.autocomplete'|devblocks_translate|capitalize} (Ctrl+Space)"><span class="cerb-icons cerb-icon-sparkles"></span></button>

				{if $group->id}
					<button type="button" class="cerb-code-editor-toolbar-button" data-cerb-editor-button-changesets title="{'common.change_history'|devblocks_translate|capitalize}"><span class="cerb-icons cerb-icon-history"></span></button>
				{/if}

				<div class="cerb-code-editor-toolbar-divider"></div>

				<button type="button" class="cerb-code-editor-toolbar-button" data-cerb-editor-button-help title="{'common.help'|devblocks_translate|capitalize}"><span class="cerb-icons cerb-icon-circle-question-mark"></span></button>
				<button type="button" class="cerb-code-editor-toolbar-button" data-cerb-editor-button-tester title="{'common.test'|devblocks_translate|capitalize}"><span class="cerb-icons cerb-icon-lab"></span></button>
			</div>

			<textarea name="routing_kata" data-editor-mode="ace/mode/cerb_kata">{$group->routing_kata}</textarea>
		</fieldset>

		<fieldset data-cerb-fieldset-help class="peek black cerb-hidden">
			<legend style="font-size:140%;">{'common.help'|devblocks_translate|capitalize}</legend>

			{if $routing_placeholders}
				<h3 style="padding:0;margin:0 0 5px 0;">{'common.placeholders'|devblocks_translate|capitalize}</h3>
				<div>
					<div class="cerb-markdown-content">
						<table cellpadding="2" cellspacing="2" width="100%">
							<colgroup>
								<col style="width:1%;white-space:nowrap;">
								<col style="padding-left:10px;">
							</colgroup>
							<tbody>
							{foreach from=$routing_placeholders item=placeholder_notes key=placeholder_key}
								<tr>
									<td valign="top">
										<strong><code>{$placeholder_key}</code></strong>
									</td>
									<td>
										{$placeholder_notes|devblocks_markdown_to_html nofilter}
									</td>
								</tr>
							{/foreach}
							</tbody>
						</table>
					</div>
				</div>
			{/if}
		</fieldset>

		<fieldset data-cerb-routing-tester class="peek black cerb-hidden" style="margin:10px 0 0 0;">
			<legend style="font-size:140%;">{'common.test'|devblocks_translate|capitalize}</legend>

			<div>
				<div data-cerb-routing-tester-editor-placeholders>
					<div class="cerb-code-editor-toolbar">
						<b>{'common.placeholders'|devblocks_translate|capitalize} (KATA)</b>
						<div class="cerb-code-editor-toolbar-divider"></div>
						<button type="button" class="cerb-code-editor-toolbar-button cerb-code-editor-toolbar-button--chooser" title="{'common.choose'|devblocks_translate|capitalize}"><span class="cerb-icons cerb-icon-search"></span></button>
						<button type="button" class="cerb-code-editor-toolbar-button cerb-code-editor-toolbar-button--run" title="{'common.run'|devblocks_translate|capitalize}"><span class="cerb-icons cerb-icon-play"></span></button>
					</div>
					<textarea name="tester[placeholders]" data-editor-mode="ace/mode/cerb_kata" rows="5" cols="45"></textarea>
				</div>

				<div data-cerb-routing-tester-results style="margin-top:10px;position:relative;"></div>
			</div>
		</fieldset>
	</div>

	<div id="{$form_id}Members" data-cerb-worker-group-memberships>
		<div class="cerb-code-editor-toolbar" style="margin-bottom:5px;">
			<select data-cerb-member-bulk-update>
				<option value="">- {{'common.bulk_update'|devblocks_translate|lower}} -</option>
				<option value="1">{'common.member'|devblocks_translate|capitalize}</option>
				<option value="2">{'common.manager'|devblocks_translate|capitalize}</option>
				<option value="0">{'common.neither'|devblocks_translate|capitalize}</option>
				<option value="reset">{'common.reset'|devblocks_translate|capitalize}</option>
			</select>
		</div>

		<table style="display:inline-block;margin-right:5em;column-count:3;column-width:250px;">
			{foreach from=$workers item=worker key=worker_id name=workers}
				{$member = $members.$worker_id}
				<tbody>
					<tr>
						<td style="padding-right:2em;">
							<a class="cerb-peek-trigger no-underline" data-context="{CerberusContexts::CONTEXT_WORKER}" data-context-id="{$worker->id}" title="{$worker->getName()}"><b>{$worker->getName()|truncate:32}</b></a>
						</td>
						<td>
							<select name="group_memberships[{$worker->id}]" data-cerb-default="{if !$member}0{elseif $member->is_manager}2{else}1{/if}">
								<option value="0" {if !$member}selected{/if}></option>
								<option value="1" {if $member && !$member->is_manager}selected{/if}>{{'common.member'|devblocks_translate}}</option>
								<option value="2" {if $member && $member->is_manager}selected{/if}>{{'common.manager'|devblocks_translate}}</option>
							</select>
						</td>
					</tr>
				</tbody>
			{/foreach}
		</table>
	</div>
</div>

{if !empty($group->id)}
<fieldset style="display:none;" class="delete">
	<legend>{'common.delete'|devblocks_translate|capitalize}</legend>
	
	<div>
		Are you sure you want to delete this group?
		
		{if !empty($destination_buckets)}
		<div style="color:var(--cerb-color-background-contrast-50);margin:10px;">
		
		<b>Move records from this group's buckets to:</b>
		
		<table cellpadding="2" cellspacing="0" border="0">
		
		{$buckets = $group->getBuckets()}
		{foreach from=$buckets item=bucket}
		<tr>
			<td>
				{$bucket->name}
			</td>
			<td>
				<span class="cerb-icons cerb-icon-right-arrow"></span>
			</td>
			<td>
				<select name="move_deleted_buckets[{$bucket->id}]">
					{foreach from=$destination_buckets item=dest_buckets key=dest_group_id}
					{$dest_group = $groups.$dest_group_id}
						{foreach from=$dest_buckets item=dest_bucket}
						<option value="{$dest_bucket->id}">{$dest_group->name}: {$dest_bucket->name}</option>
						{/foreach}
					{/foreach}
				</select>
			</td> 
		</tr>
		{/foreach}
		
		</table>
		
		</div>
		{/if}
	</div>

	<button type="button" class="delete red">{'common.yes'|devblocks_translate|capitalize}</button>
	<button type="button" class="delete-cancel">{'common.no'|devblocks_translate|capitalize}</button>
</fieldset>
{/if}

<div class="status"></div>

<div class="buttons" style="margin-top:10px;">
	<button type="button" class="submit"><span class="cerb-icons cerb-icon-circle-ok"></span> {'common.save_changes'|devblocks_translate}</button>
	{if !empty($group->id) && $active_worker->hasPriv("contexts.{$peek_context}.delete")}<button type="button" class="delete-prompt"><span class="cerb-icons cerb-icon-circle-remove"></span> {'common.delete'|devblocks_translate|capitalize}</button>{/if}
</div>

</form>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	let $frm = $('#{$form_id}');
	let $popup = genericAjaxPopupFind($frm);

	Devblocks.formDisableSubmit($frm);

	$popup.one('popup_open', function() {
		$popup.dialog('option','title',"{'common.edit'|devblocks_translate|capitalize}: {'common.group'|devblocks_translate|capitalize}");

		// Tabs

		$('#{$form_id}Tabs').tabs();

		// Buttons
		$popup.find('button.submit').click(Devblocks.callbackPeekEditSave);
		$popup.find('button.delete').click({ mode: 'delete' }, Devblocks.callbackPeekEditSave);
		$popup.find('button.delete-prompt').click(Devblocks.callbackPeekEditDeletePrompt);
		$popup.find('button.delete-cancel').click(Devblocks.callbackPeekEditDeleteCancel);

		// Inputs
		$popup.find('input[name=subject_has_mask]').on('click', function(e) {
			e.stopPropagation();
			toggleDiv('{$option_id}',(this.checked)?'block':'none');
		});

		// Avatar
		let $avatar_chooser = $popup.find('button.cerb-avatar-chooser');
		let $avatar_image = $avatar_chooser.closest('td').find('img.cerb-avatar');
		ajax.chooserAvatar($avatar_chooser, $avatar_image);
		
		// Template builders
		
		$popup.find('textarea.cerb-template-trigger')
			.cerbTemplateTrigger()
		;
		
		$popup.find('button.chooser-abstract')
			.cerbChooserTrigger()
			;
		
		$popup.find('.cerb-peek-trigger')
			.cerbPeekTrigger()
			;
		
		// Group matrix
		
		let $group_tab = $popup.find('[data-cerb-worker-group-memberships]');
		let $group_table = $group_tab.find('table');
		
		$group_tab.find('select[data-cerb-member-bulk-update]').on('change', function(e) {
			e.stopPropagation();

			let $option = $(this);
			let value = $option.val();
			$option.val('');

			if('reset' ===  value) {
				$group_table.find('select[data-cerb-default]').each(function() {
					let $this = $(this);
					$this.val($this.attr('data-cerb-default'));
				});

			} else {
				$group_table.find('select[data-cerb-default]').val(value);
			}
		});

		// Editor
		let autocomplete_suggestions = {if $autocomplete_json}{$autocomplete_json nofilter}{else}[]{/if};

		let $editor = $popup.find('[name=routing_kata]')
			.cerbCodeEditor()
			.cerbCodeEditorAutocompleteKata({
				autocomplete_suggestions: autocomplete_suggestions
			})
			.next('pre.ace_editor')
		;

		let editor = ace.edit($editor.attr('id'));

		$popup.find('[data-cerb-editor-button-magic]').on('click', function(e) {
			editor.commands.byName.startAutocomplete.exec(editor);
		});

		$popup.find('[data-cerb-editor-button-help]').on('click', function(e) {
			e.stopPropagation();
			let $button = $(this);
			let $fieldset = $popup.find('[data-cerb-fieldset-help]').toggle();

			if($fieldset.is(':visible')) {
				$button.addClass('cerb-code-editor-toolbar-button--enabled');
			} else {
				$button.removeClass('cerb-code-editor-toolbar-button--enabled');
			}
		});

		$popup.find('[data-cerb-editor-button-changesets]').on('click', function(e) {
			e.stopPropagation();

			let formData = new FormData();
			formData.set('c', 'internal');
			formData.set('a', 'invoke');
			formData.set('module', 'records');
			formData.set('action', 'showChangesetsPopup');
			formData.set('record_type', 'group_routing');
			formData.set('record_id', '{$group->id}');
			formData.set('record_key', 'routing_kata');

			let $editor_kata_differ_popup = genericAjaxPopup('editorDiff{$form_id}', formData, null, null, '80%');

			$editor_kata_differ_popup.one('cerb-diff-editor-ready', function(e) {
				e.stopPropagation();

				if(!e.hasOwnProperty('differ'))
					return;

				e.differ.editors.right.ace.setValue(editor.getValue());
				e.differ.editors.right.ace.clearSelection();

				e.differ.editors.right.ace.on('change', function() {
					editor.setValue(e.differ.editors.right.ace.getValue());
					editor.clearSelection();
				});
			});
		});

		// Toolbar

		let $toolbar = $popup.find('[data-cerb-bucket-routing-toolbar]').find('.cerb-code-editor-toolbar');

		$toolbar.cerbToolbar({
			caller: {
				name: 'cerb.toolbar.editor',
				params: {
					toolbar: 'cerb.toolbar.recordEditor.bucketRouting',
					selected_text: ''
				}
			},
			start: function(formData) {
				let pos = editor.getCursorPosition();
				let token_path = Devblocks.cerbCodeEditor.getKataTokenPath(pos, editor).join('');

				formData.set('caller[params][selected_text]', editor.getSelectedText());
				formData.set('caller[params][token_path]', token_path);
				formData.set('caller[params][cursor_row]', pos.row);
				formData.set('caller[params][cursor_column]', pos.column);
				formData.set('caller[params][value]', editor.getValue());
			},
			done: function(e) {
				e.stopPropagation();

				let $target = e.trigger;

				if(!$target.is('.cerb-bot-trigger'))
					return;

				if (e.eventData.exit === 'error') {

				} else if(e.eventData.exit === 'return') {
					Devblocks.interactionWorkerPostActions(e.eventData, editor);
				}
			},
			reset: function(e) {
				e.stopPropagation();
			}
		});

		$toolbar.cerbCodeEditorToolbarEventHandler({
			editor: editor
		});

		let $tab_routing = $('#{$form_id}Inbox');
		let $fieldset_tester = $tab_routing.find('[data-cerb-routing-tester]');
		let highlight_marker = null;
		let $fieldset_tester_results = $fieldset_tester.find('[data-cerb-routing-tester-results]');

		let $editor_tester = $fieldset_tester.find('textarea[name="tester[placeholders]"]')
			.cerbCodeEditor()
			.next('pre.ace_editor')
		;

		let editor_tester = ace.edit($editor_tester.attr('id'));

		$toolbar.find('[data-cerb-editor-button-tester]').on('click', function(e) {
			e.stopPropagation();
			let $button = $(this);

			if($fieldset_tester.toggle().is(':visible')) {
				$button.addClass('cerb-code-editor-toolbar-button--enabled');
			} else {
				$button.removeClass('cerb-code-editor-toolbar-button--enabled');
			}
		});

		$fieldset_tester.find('.cerb-code-editor-toolbar-button--chooser')
			.attr('data-interaction-uri', 'cerb:automation:ai.cerb.routingRuleBuilder.inputChooser')
			.attr('data-interaction-params', '')
			.cerbBotTrigger({
				'width': '80%',
				'done': function(e) {
					Devblocks.interactionWorkerPostActions(e.eventData, editor_tester);
				},
			})
		;

		$fieldset_tester.find('.cerb-code-editor-toolbar-button--run').on('click', function(e) {
			e.stopPropagation();

			if(null != highlight_marker) {
				editor.session.removeMarker(highlight_marker.id);
				highlight_marker = null;
			}

			$fieldset_tester_results.html('').hide();

			let formData = new FormData($frm.get(0));
			formData.set('c', 'profiles');
			formData.set('a', 'invoke');
			formData.set('module', 'mail_routing_rule');
			formData.set('action', 'testRoutingKataJson');

			genericAjaxPost(formData, null, null, function(json) {
				if('object' !== typeof json)
					return;

				if(!json.hasOwnProperty('key')) {
					let $h1 = $('<h1/>').text('(no matching rules)');
					$fieldset_tester_results.append($h1).fadeIn();

				} else if(json.hasOwnProperty('line')) {
					let row = json['line'];
					highlight_marker = editor.session.highlightLines(row, row);
					editor.scrollToLine(row);

					let $h1 = $('<h1/>').text('Matched ' + json['key']);
					$fieldset_tester_results.append($h1).fadeIn();
				}
			});
		});
	});
});
</script>