{$peek_context = CerberusContexts::CONTEXT_BUCKET}
{$peek_context_id = $bucket->id}
{$form_id = "frmBucketPeek{uniqid()}"}

<form action="{devblocks_url}{/devblocks_url}" method="post" id="{$form_id}" onsubmit="return false;">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="bucket">
<input type="hidden" name="action" value="savePeekJson">
<input type="hidden" name="view_id" value="{$view_id}">
{if !empty($bucket) && !empty($bucket->id)}<input type="hidden" name="id" value="{$bucket->id}">{/if}
<input type="hidden" name="do_delete" value="0">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<table cellpadding="2" cellspacing="0" border="0" width="98%" style="margin-bottom:10px;">
	<tr>
		<td width="0%" nowrap="nowrap" align="left">
			<b>{'common.name'|devblocks_translate|capitalize}:</b> 
		</td>
		<td width="100%" valign="middle">
			<input type="text" name="name" value="{$bucket->name}" maxlength="64" autofocus="true" style="width:100%;">
		</td>
	</tr>
	
	<tr>
		<td align="right" valign="middle" width="0%" nowrap="nowrap">
			<b>{'common.group'|devblocks_translate|capitalize}:</b> 
		</td>
		<td valign="middle">
			{if !$bucket->id}
			<button type="button" class="chooser-abstract" data-field-name="group_id" data-context="{CerberusContexts::CONTEXT_GROUP}" data-single="true" data-query="" data-autocomplete="" data-autocomplete-if-empty="true"><span class="glyphicons glyphicons-search"></span></button>
			{/if}
			
			{$group = $groups.{$bucket->group_id}}
			
			<ul class="bubbles chooser-container">
				{if $group}
					<li><img class="cerb-avatar" src="{devblocks_url}c=avatars&context=group&context_id={$group->id}{/devblocks_url}?v={$group->updated}"><input type="hidden" name="group_id" value="{$group->id}"><a href="javascript:;" class="cerb-peek-trigger no-underline" data-context="{CerberusContexts::CONTEXT_GROUP}" data-context-id="{$group->id}">{$group->name}</a></li>
				{/if}
			</ul>
		</td>
	</tr>
</table>

{if $bucket && $bucket->is_default}
<fieldset data-cerb-bucket-routing-toolbar class="peek">
	<legend>Routing Rules: (KATA)</legend>
	<div class="cerb-code-editor-toolbar">
		<button type="button" class="cerb-code-editor-toolbar-button" data-cerb-editor-button-magic title="{'common.autocomplete'|devblocks_translate|capitalize} (Ctrl+Space)"><span class="glyphicons glyphicons-magic"></span></button>

		{if $bucket->id}
			<button type="button" class="cerb-code-editor-toolbar-button" data-cerb-editor-button-changesets title="{'common.change_history'|devblocks_translate|capitalize}"><span class="glyphicons glyphicons-history"></span></button>
		{/if}
	</div>

	<textarea name="routing_kata" data-editor-mode="ace/mode/cerb_kata">{$bucket->routing_kata}</textarea>
</fieldset>
{/if}

<fieldset class="peek">
	{$is_mail_configured = $bucket->reply_address_id || $bucket->reply_personal || $bucket->reply_signature_id || $bucket->reply_signing_key_id || $bucket->reply_html_template_id}
	<legend><label><input type="checkbox" name="enable_mail" value="1" {if $is_mail_configured}checked="checked"{/if} onclick="$(this).closest('fieldset').find('table:first').toggle();"> Bucket-level mail settings: <small>({'common.optional'|devblocks_translate|lower})</small></label></legend>
	<table cellpadding="2" cellspacing="0" border="0" width="98%" style="{if !$is_mail_configured}display:none;{/if}">
		<tr>
			<td align="right" valign="middle" width="0%" nowrap="nowrap">
				{'common.send.from'|devblocks_translate}:
			</td>
			<td valign="middle" width="100%">
				<button type="button" class="chooser-abstract" data-field-name="reply_address_id" data-context="{CerberusContexts::CONTEXT_ADDRESS}" data-single="true" data-query="mailTransport.id:>0 isBanned:n isDefunct:n" data-query-required="" data-autocomplete="mailTransport.id:>0 isBanned:n isDefunct:n" data-autocomplete-if-empty="true"><span class="glyphicons glyphicons-search"></span></button>
				
				{$replyto = DAO_Address::get($bucket->reply_address_id)}
				
				<ul class="bubbles chooser-container">
					{if $replyto}
						<li><img class="cerb-avatar" src="{devblocks_url}c=avatars&context=address&context_id={$replyto->id}{/devblocks_url}?v={$replyto->updated_at}"><input type="hidden" name="reply_address_id" value="{$replyto->id}"><a href="javascript:;" class="cerb-peek-trigger no-underline" data-context="{CerberusContexts::CONTEXT_ADDRESS}" data-context-id="{$replyto->id}">{$replyto->email}</a></li>
					{/if}
				</ul>
			</td>
		</tr>
		
		<tr>
			<td align="right" valign="top" width="0%" nowrap="nowrap">
				{'common.send.as'|devblocks_translate}:
			</td>
			<td valign="top">
				<textarea name="reply_personal" placeholder="e.g. Customer Support" class="cerb-template-trigger" data-context="{CerberusContexts::CONTEXT_WORKER}" style="width:100%;height:50px;">{$bucket->reply_personal}</textarea>
			</td>
		</tr>
		
		<tr>
			<td align="right" valign="middle" width="0%" nowrap="nowrap">
				{'common.signature'|devblocks_translate|capitalize}: 
			</td>
			<td valign="middle">
				<button type="button" class="chooser-abstract" data-field-name="reply_signature_id" data-context="{CerberusContexts::CONTEXT_EMAIL_SIGNATURE}" data-single="true" data-query="" data-autocomplete="" data-autocomplete-if-empty="true"><span class="glyphicons glyphicons-search"></span></button>
				
				{$signature = DAO_EmailSignature::get($bucket->reply_signature_id)}
				
				<ul class="bubbles chooser-container">
					{if $signature}
						<li><input type="hidden" name="reply_signature_id" value="{$signature->id}"><a href="javascript:;" class="cerb-peek-trigger no-underline" data-context="{CerberusContexts::CONTEXT_EMAIL_SIGNATURE}" data-context-id="{$signature->id}">{$signature->name}</a></li>
					{/if}
				</ul>
			</td>
		</tr>

		<tr>
			<td align="right" valign="middle" width="0%" nowrap="nowrap">
				{'common.encrypt.signing.key'|devblocks_translate|capitalize}
			</td>
			<td valign="middle">
				<button type="button" class="chooser-abstract" data-field-name="reply_signing_key_id" data-context="{Context_GpgPrivateKey::ID}" data-single="true" data-query=""><span class="glyphicons glyphicons-search"></span></button>

				{$signing_key = DAO_GpgPrivateKey::get($bucket->reply_signing_key_id)}

				<ul class="bubbles chooser-container">
					{if $signing_key}
						<li><input type="hidden" name="reply_signing_key_id" value="{$signing_key->id}"><a href="javascript:;" class="cerb-peek-trigger no-underline" data-context="{Context_GpgPrivateKey::ID}" data-context-id="{$signing_key->id}">{$signing_key->name}</a></li>
					{/if}
				</ul>
			</td>
		</tr>

		<tr>
			<td align="right" valign="middle" width="0%" nowrap="nowrap">
				HTML template: 
			</td>
			<td valign="middle">
				<button type="button" class="chooser-abstract" data-field-name="reply_html_template_id" data-context="{CerberusContexts::CONTEXT_MAIL_HTML_TEMPLATE}" data-single="true" data-query="" data-autocomplete="mailTransport.id:>0" data-autocomplete-if-empty="true"><span class="glyphicons glyphicons-search"></span></button>
				
				{$html_template = DAO_MailHtmlTemplate::get($bucket->reply_html_template_id)}
				
				<ul class="bubbles chooser-container">
					{if $html_template}
						<li><input type="hidden" name="reply_html_template_id" value="{$html_template->id}"><a href="javascript:;" class="cerb-peek-trigger no-underline" data-context="{CerberusContexts::CONTEXT_MAIL_HTML_TEMPLATE}" data-context-id="{$html_template->id}">{$html_template->name}</a></li>
					{/if}
				</ul>
			</td>
		</tr>
	</table>
</fieldset>

{if !empty($custom_fields)}
<fieldset class="peek">
	<legend>{'common.custom_fields'|devblocks_translate|capitalize}</legend>
	{include file="devblocks:cerberusweb.core::internal/custom_fields/bulk/form.tpl" bulk=false}
</fieldset>
{/if}

{include file="devblocks:cerberusweb.core::internal/custom_fieldsets/peek_custom_fieldsets.tpl" context=$peek_context context_id=$bucket->id}

{if !empty($bucket->id)}
<fieldset style="display:none;" class="delete">
	<legend>{'common.delete'|devblocks_translate|capitalize}</legend>
	
	<div style="margin:5px 10px;">
	Permanently delete this bucket and move the tickets?<br>
	<select name="delete_moveto">
		{foreach from=$buckets item=move_bucket key=move_bucket_id}
		{if $move_bucket_id == $bucket->id}
		{elseif $bucket->group_id == $move_bucket->group_id}
		<option value="{$move_bucket_id}">{$move_bucket->name}</option>
		{/if}
		{/foreach}
	</select>
	</div>

	{if !empty($bucket->id) && !$bucket->is_default}<button type="button" class="red delete">{'common.yes'|devblocks_translate|capitalize}</button>{/if}
	<button type="button" onclick="$(this).closest('form').find('div.buttons').fadeIn();$(this).closest('fieldset.delete').fadeOut();">{'common.no'|devblocks_translate|capitalize}</button>
</fieldset>
{/if}

<div class="status"></div>

<div class="buttons">
	<button type="button" class="submit"><span class="glyphicons glyphicons-circle-ok"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
	{if $bucket->id}<button type="button" class="save-continue"><span class="glyphicons glyphicons-circle-arrow-right"></span> {'common.save_and_continue'|devblocks_translate|capitalize}</button>{/if}
	{if !empty($bucket->id) && !$bucket->is_default && $active_worker->hasPriv("contexts.{$peek_context}.delete")}<button type="button" onclick="$(this).parent().siblings('fieldset.delete').fadeIn();$(this).closest('div').fadeOut();"><span class="glyphicons glyphicons-circle-remove"></span> {'common.delete'|devblocks_translate|capitalize}</button>{/if}
</div>

</form>

<script type="text/javascript">
$(function() {
	var $frm = $('#{$form_id}');
	var $popup = genericAjaxPopupFind($frm);
	
	$popup.one('popup_open',function() {
		$popup.dialog('option','title', '{'common.edit'|devblocks_translate|capitalize}: {'common.bucket'|devblocks_translate|capitalize|escape:'javascript' nofilter}');
		$popup.css('overflow', 'inherit');
		
		// Buttons
		
		$popup.find('button.submit').click(Devblocks.callbackPeekEditSave);
		$popup.find('button.save-continue').click({ mode: 'continue' }, Devblocks.callbackPeekEditSave);
		$popup.find('button.delete').click({ mode: 'delete' }, Devblocks.callbackPeekEditSave);

		// Template builders
		
		$popup.find('textarea.cerb-template-trigger')
			.cerbTemplateTrigger()
			.on('cerb-template-saved', function(e) {
				//var $trigger = $(this);
				//$trigger.val(e.worklist_quicksearch);
			})
		;
		
		$popup.find('button.chooser-abstract')
			.cerbChooserTrigger()
			;
		
		$popup.find('.cerb-peek-trigger')
			.cerbPeekTrigger()
			;

		{if $bucket && $bucket->is_default}
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

		$popup.find('[data-cerb-editor-button-changesets]').on('click', function(e) {
			e.stopPropagation();

			let formData = new FormData();
			formData.set('c', 'internal');
			formData.set('a', 'invoke');
			formData.set('module', 'records');
			formData.set('action', 'showChangesetsPopup');
			formData.set('record_type', 'bucket_routing');
			formData.set('record_id', '{$bucket->id}');
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
		{/if}{* if bucket->is_default *}
	});
});
</script>