<div class="cerb-ui-form--field">
	<label class="cerb-ui-form--label">{'common.send.from'|devblocks_translate|capitalize}</label>
	<select name="{$namePrefix}[from_address_id]">
		<option value="0">(default)</option>
		<optgroup label="Sender Addresses">
			{foreach from=$replyto_addresses key=address_id item=replyto}
			<option value="{$address_id}" {if $params.from_address_id==$address_id}selected="selected"{/if}>{$replyto->email}</option>
			{/foreach}
		</optgroup>
		{if !empty($placeholders)}
		<optgroup label="Placeholders">
		{foreach from=$placeholders item=label key=placeholder}
		<option value="{$placeholder}" {if $params.from_address_id==$placeholder}selected="selected"{/if}>{$label}</option>
		{/foreach}
		</optgroup>
		{/if}
	</select>
</div>

{*
<b><abbr title="A valid sender email address; e.g. support@cerb.example. Uses default if blank.">{'common.send.from'|devblocks_translate|capitalize}</abbr>:</b>
<div style="margin-left:10px;margin-bottom:0.5em;">
	<textarea name="{$namePrefix}[send_from]" size="45" style="width:100%;" class="placeholders">{$params.send_from}</textarea>
</div>
*}

<div class="cerb-ui-form--field">
	<label class="cerb-ui-form--label"><abbr title="A sender personal name; e.g. Support Team">{'common.send.as'|devblocks_translate|capitalize}</abbr></label>
	<textarea name="{$namePrefix}[send_as]" class="placeholders">{$params.send_as}</textarea>
</div>

<div class="cerb-ui-form--field">
	<label class="cerb-ui-form--label">{'message.header.to'|devblocks_translate|capitalize}</label>
	<textarea name="{$namePrefix}[to]" class="placeholders">{$params.to}</textarea>
	<ul class="bubbles">
	{foreach from=$trigger->variables item=var_data key=var_key}
		{if $var_data.type == "ctx_{CerberusContexts::CONTEXT_ADDRESS}"}
			<li><label><input type="checkbox" name="{$namePrefix}[to_var][]" value="{$var_key}" {if is_array($params.to_var) && in_array($var_key, $params.to_var)}checked="checked"{/if}> (variable) {$var_data.label}</label></li>
		{/if}
	{/foreach}
	</ul>
</div>

<div class="cerb-ui-form--field">
	<label class="cerb-ui-form--label">{'message.header.cc'|devblocks_translate|capitalize}</label>
	<textarea name="{$namePrefix}[cc]" class="placeholders">{$params.cc}</textarea>
	<ul class="bubbles">
	{foreach from=$trigger->variables item=var_data key=var_key}
		{if $var_data.type == "ctx_{CerberusContexts::CONTEXT_ADDRESS}"}
			<li><label><input type="checkbox" name="{$namePrefix}[cc_var][]" value="{$var_key}" {if is_array($params.cc_var) && in_array($var_key, $params.cc_var)}checked="checked"{/if}> (variable) {$var_data.label}</label></li>
		{/if}
	{/foreach}
	</ul>
</div>

<div class="cerb-ui-form--field">
	<label class="cerb-ui-form--label">{'message.header.bcc'|devblocks_translate|capitalize}</label>
	<textarea name="{$namePrefix}[bcc]" class="placeholders">{$params.bcc}</textarea>
	<ul class="bubbles">
	{foreach from=$trigger->variables item=var_data key=var_key}
		{if $var_data.type == "ctx_{CerberusContexts::CONTEXT_ADDRESS}"}
			<li><label><input type="checkbox" name="{$namePrefix}[bcc_var][]" value="{$var_key}" {if is_array($params.bcc_var) && in_array($var_key, $params.bcc_var)}checked="checked"{/if}> (variable) {$var_data.label}</label></li>
		{/if}
	{/foreach}
	</ul>
</div>

<div class="cerb-ui-form--field">
	<label class="cerb-ui-form--label">{'message.header.subject'|devblocks_translate|capitalize}</label>
	<textarea name="{$namePrefix}[subject]" class="placeholders">{$params.subject}</textarea>
</div>

<div class="cerb-ui-form--field">
	<label class="cerb-ui-form--label">{'message.headers.custom'|devblocks_translate|capitalize}<span class="cerb-ui-form--hint">one per line, e.g. "X-Precedence: Bulk"</span></label>
	<textarea name="{$namePrefix}[headers]" rows="3" class="placeholders">{$params.headers}</textarea>
</div>

<div class="cerb-ui-form--field">
	<label class="cerb-ui-form--label">{'common.format'|devblocks_translate|capitalize}</label>
	<div>
		<label><input type="radio" name="{$namePrefix}[format]" value="" {if !$params.format}checked="checked"{/if}> Plaintext</label>
		<label><input type="radio" name="{$namePrefix}[format]" value="parsedown" {if 'parsedown' == $params.format}checked="checked"{/if}> Markdown</label>
	</div>
</div>

<div style="{if $params.format=='parsedown'}{else}display:none;{/if}" class="options-parsedown">
	<div class="cerb-ui-form--field">
		<label class="cerb-ui-form--label">HTML Template</label>
		<div>
			<a class="cerb-chooser cerb-html-template-chooser" data-context="{CerberusContexts::CONTEXT_MAIL_HTML_TEMPLATE}" data-single="true">ID</a>:
			<input type="text" name="{$namePrefix}[html_template_id]" value="{$params.html_template_id}" class="placeholders" size="40" autocomplete="off" spellcheck="false">
		</div>
	</div>
</div>

<div class="cerb-ui-form--field">
	<label class="cerb-ui-form--label">{'common.content'|devblocks_translate|capitalize}</label>
	<div class="options-parsedown">
		<button type="button" class="editor-upload-image" title="Upload image"><span class="cerb-icons cerb-icon-picture"></span></button>
		<button type="button" class="editor-preview" title="Preview"><span class="cerb-icons cerb-icon-new-window"></span></button>
	</div>
	<textarea name="{$namePrefix}[content]" rows="3" style="height:150px;" class="placeholders editor">{$params.content}</textarea>
</div>

{* Check for attachment list variables *}
{capture name="attachment_vars"}
{foreach from=$trigger->variables item=var key=var_key}
{if $var.type == "ctx_{CerberusContexts::CONTEXT_ATTACHMENT}"}
<div>
	<label><input type="checkbox" name="{$namePrefix}[attachment_vars][]" value="{$var_key}" {if is_array($params.attachment_vars) && in_array($var_key, $params.attachment_vars)}checked="checked"{/if}>{$var.label}</label>
</div>
{/if}
{/foreach}{/capture}

{if $smarty.capture.attachment_vars}
<div class="cerb-ui-form--field">
	<label class="cerb-ui-form--label">Attach the files from these variables</label>
	<div>
	{$smarty.capture.attachment_vars nofilter}
	</div>
</div>
{/if}

{if DevblocksPlatform::isPluginEnabled('cerb.file_bundles')}
<div class="cerb-ui-form--field">
	<label class="cerb-ui-form--label">Attach these file bundles</label>
	<div class="cerb-ui-record-chooser cerb-file-bundle-chooser">
	{foreach from=$params.bundle_ids item=bundle_id}
		{$bundle = DAO_FileBundle::get($bundle_id)}
		{if !empty($bundle)}
		<li data-context="cerberusweb.contexts.file_bundle" data-context-id="{$bundle_id}" data-label="{$bundle->name}"></li>
		{/if}
	{/foreach}
	</div>
</div>
{/if}

<div class="cerb-ui-form--field">
	<label class="cerb-ui-form--label">Also send email in simulator mode</label>
	<div>
		<label><input type="radio" name="{$namePrefix}[run_in_simulator]" value="1" {if $params.run_in_simulator}checked="checked"{/if}> {'common.yes'|devblocks_translate|capitalize}</label>
		<label><input type="radio" name="{$namePrefix}[run_in_simulator]" value="0" {if !$params.run_in_simulator}checked="checked"{/if}> {'common.no'|devblocks_translate|capitalize}</label>
	</div>
</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	var $action = $('#{$namePrefix}_{$nonce}');
	var $content = $action.find('textarea.editor');

	$action.find('.cerb-peek-trigger').cerbPeekTrigger();

	if(window.CerbUI && CerbUI.RecordChooser) {
		// HTML template: placeholder-capable (type a placeholder, or pick a template via the ID link).
		$action.find('.cerb-html-template-chooser').each(function() {
			CerbUI.RecordChooser.pickerLink(this, { input: $action.find('input[name="{$namePrefix}[html_template_id]"]')[0] });
		});

		{if DevblocksPlatform::isPluginEnabled('cerb.file_bundles')}
		$action.find('.cerb-file-bundle-chooser').each(function() {
			new CerbUI.RecordChooser(this, { context: 'cerberusweb.contexts.file_bundle', name: '{$namePrefix}[bundle_ids]', multiple: true, emptyIcon: 'paperclip' });
		});
		{/if}
	}

	// Format toggle
	$format = $action.find('input:radio[name="{$namePrefix}[format]"]');

	$format.on('change', function(e) {
		var $this = $(this);
		if($this.val() == 'parsedown') {
			$action.find('.options-parsedown').hide().fadeIn();
		} else {
			$action.find('.options-parsedown').hide();
		}
	});

	// Text editor
	var $button_upload = $action.find('button.editor-upload-image')
		.on('click', function(e) {
			var $chooser = genericAjaxPopup('chooser','c=internal&a=invoke&module=records&action=chooserOpenFile&single=1',null,true,'75%');

			$chooser.one('chooser_save', function(event) {
				if(!event.response || 0 == event.response)
					return;

				{literal}var insert = "![inline-image]({{cerb_file_url(" + event.response[0].id + ",'" + event.response[0].name + "')}})";{/literal}

				// The content textarea is enhanced into a CerbUI.ScriptingEditor (instances key on the wrapper)
				var wrap = $content.length ? $content[0].closest('.cerb-ui-scriptingeditor') : null;
				var ed = (wrap && window.CerbUI && CerbUI.ScriptingEditor) ? CerbUI.ScriptingEditor.from(wrap) : null;

				if(ed) {
					ed.focus();
					ed.insertAtCursor(insert);
				} else if($content.length) {
					$content.focus().insertAtCursor(insert);
				}
			});
		})
	;

	var $button_preview = $action.find('button.editor-preview')
		.on('click', function(e) {
			var $frm = $action.closest('form');

			var formData = new FormData($frm[0]);
			formData.set('c', 'profiles');
			formData.set('a', 'invoke');
			formData.set('module', 'behavior');
			formData.set('action', 'testDecisionEventSnippets');
			formData.set('prefix', '{$namePrefix}');
			formData.set('field', 'content');
			formData.set('is_editor', 'format');
			formData.set('_replyto_field', 'from_address_id');

			genericAjaxPost(
				formData,
				null,
				null,
				function(html) {
					genericAjaxPopup(
						'preview',
						'',
						null,
						false,
						'90%',
						function() {
							$('#popuppreview').dialog('option','title','Preview');
							$('#popuppreview').html(html);
						}
					);
				}
			);
		})
	;
});
</script>
