<b>{'common.send.from'|devblocks_translate|capitalize}:</b>
<div style="margin-left:10px;margin-bottom:0.5em;">
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

<b><abbr title="A sender personal name; e.g. Support Team">{'common.send.as'|devblocks_translate|capitalize}</abbr>:</b>
<div style="margin-left:10px;margin-bottom:0.5em;">
	<textarea name="{$namePrefix}[send_as]" size="45" style="width:100%;" class="placeholders">{$params.send_as}</textarea>
</div>

<b>{'message.header.to'|devblocks_translate|capitalize}:</b>
<div style="margin-left:10px;margin-bottom:0.5em;">
	<textarea name="{$namePrefix}[to]" size="45" style="width:100%;" class="placeholders">{$params.to}</textarea>
	<ul class="bubbles">
	{foreach from=$trigger->variables item=var_data key=var_key}
		{if $var_data.type == "ctx_{CerberusContexts::CONTEXT_ADDRESS}"}
			<li><label><input type="checkbox" name="{$namePrefix}[to_var][]" value="{$var_key}" {if is_array($params.to_var) && in_array($var_key, $params.to_var)}checked="checked"{/if}> (variable) {$var_data.label}</label></li>
		{/if}
	{/foreach}
	</ul>
</div>

<b>{'message.header.cc'|devblocks_translate|capitalize}:</b>
<div style="margin-left:10px;margin-bottom:0.5em;">
	<textarea name="{$namePrefix}[cc]" size="45" style="width:100%;" class="placeholders">{$params.cc}</textarea>
	<ul class="bubbles">
	{foreach from=$trigger->variables item=var_data key=var_key}
		{if $var_data.type == "ctx_{CerberusContexts::CONTEXT_ADDRESS}"}
			<li><label><input type="checkbox" name="{$namePrefix}[cc_var][]" value="{$var_key}" {if is_array($params.cc_var) && in_array($var_key, $params.cc_var)}checked="checked"{/if}> (variable) {$var_data.label}</label></li>
		{/if}
	{/foreach}
	</ul>
</div>

<b>{'message.header.bcc'|devblocks_translate|capitalize}:</b>
<div style="margin-left:10px;margin-bottom:0.5em;">
	<textarea name="{$namePrefix}[bcc]" size="45" style="width:100%;" class="placeholders">{$params.bcc}</textarea>
	<ul class="bubbles">
	{foreach from=$trigger->variables item=var_data key=var_key}
		{if $var_data.type == "ctx_{CerberusContexts::CONTEXT_ADDRESS}"}
			<li><label><input type="checkbox" name="{$namePrefix}[bcc_var][]" value="{$var_key}" {if is_array($params.bcc_var) && in_array($var_key, $params.bcc_var)}checked="checked"{/if}> (variable) {$var_data.label}</label></li>
		{/if}
	{/foreach}
	</ul>
</div>

<b>{'message.header.subject'|devblocks_translate|capitalize}:</b>
<div style="margin-left:10px;margin-bottom:0.5em;">
	<textarea name="{$namePrefix}[subject]" size="45" style="width:100%;" class="placeholders">{$params.subject}</textarea>
</div>

<b>{'message.headers.custom'|devblocks_translate|capitalize}:</b> (one per line, e.g. "X-Precedence: Bulk")
<div style="margin-left:10px;margin-bottom:0.5em;">
	<textarea name="{$namePrefix}[headers]" rows="3" cols="45" style="width:100%;" class="placeholders">{$params.headers}</textarea>
</div>

<b>{'common.format'|devblocks_translate|capitalize}:</b>
<div style="margin-left:10px;margin-bottom:0.5em;">
	<label><input type="radio" name="{$namePrefix}[format]" value="" {if !$params.format}checked="checked"{/if}> Plaintext</label>
	<label><input type="radio" name="{$namePrefix}[format]" value="parsedown" {if 'parsedown' == $params.format}checked="checked"{/if}> Markdown</label>
</div>

<div style="{if $params.format=='parsedown'}{else}display:none;{/if}" class="options-parsedown">
	<b>HTML Template:</b><br>
	<div style="margin-left:10px;margin-bottom:0.5em;">
		<button type="button" class="chooser-abstract" data-field-name="{$namePrefix}[html_template_id]" data-context="{CerberusContexts::CONTEXT_MAIL_HTML_TEMPLATE}" data-single="true" data-autocomplete="" data-autocomplete-placeholders="{$smarty.capture.addy_placeholders}"><span class="glyphicons glyphicons-search"></span></button>
		<ul class="bubbles chooser-container">
			{if $params.html_template_id}
				{$html_template = $html_templates.{$params.html_template_id}}
				{if $html_template}
				<li><input type="hidden" name="{$namePrefix}[html_template_id]" value="{$html_template->id}"><a href="javascript:;" class="cerb-peek-trigger no-underline" data-context="{CerberusContexts::CONTEXT_MAIL_HTML_TEMPLATE}" data-context-id="{$html_template->id}">{$html_template->name}</a></li>
				{/if}
			{/if}
		</ul>
	</div>
</div>

<b>{'common.content'|devblocks_translate|capitalize}:</b><br>
<div style="margin-left:10px;margin-bottom:0.5em;">
	<div style="padding:5px 0px;" class="options-parsedown">
		<button type="button" class="editor-upload-image" title="Upload image"><span class="glyphicons glyphicons-picture"></span></button>
		<button type="button" class="editor-preview" title="Preview"><span class="glyphicons glyphicons-new-window-alt"></span></button>
	</div>
	<textarea name="{$namePrefix}[content]" rows="3" cols="45" style="width:100%;height:150px;" class="placeholders editor">{$params.content}</textarea>
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
<b>Attach the files from these variables:</b>
<div style="margin-left:10px;margin-bottom:0.5em;">
{$smarty.capture.attachment_vars nofilter}
</div>
{/if}

<b>Attach these file bundles:</b>
<div style="margin-left:10px;margin-bottom:0.5em;">
	<button type="button" class="chooser-file-bundle"><span class="glyphicons glyphicons-paperclip"></span></button>
	<ul class="bubbles chooser-container">
	{foreach from=$params.bundle_ids item=bundle_id}
		{$bundle = DAO_FileBundle::get($bundle_id)}
		{if !empty($bundle)}
		<li><input type="hidden" name="{$namePrefix}[bundle_ids][]" value="{$bundle_id}">{$bundle->name} <a href="javascript:;" onclick="$(this).parent().remove();"><span class="glyphicons glyphicons-circle-remove"></span></a></li>
		{/if} 
	{/foreach}
	</ul>
</div>

<b>Also send email in simulator mode:</b>
<div style="margin-left:10px;margin-bottom:10px;">
	<label><input type="radio" name="{$namePrefix}[run_in_simulator]" value="1" {if $params.run_in_simulator}checked="checked"{/if}> {'common.yes'|devblocks_translate|capitalize}</label>
	<label><input type="radio" name="{$namePrefix}[run_in_simulator]" value="0" {if !$params.run_in_simulator}checked="checked"{/if}> {'common.no'|devblocks_translate|capitalize}</label>
</div>

<script type="text/javascript">
$(function() {
	var $action = $('#{$namePrefix}_{$nonce}');
	var $content = $action.find('textarea.editor');
	
	$action.find('.cerb-peek-trigger').cerbPeekTrigger();
	$action.find('.chooser-abstract').cerbChooserTrigger();
	
	// Attachments
	
	$action.find('button.chooser-file-bundle').each(function() {
		ajax.chooser(this,'{CerberusContexts::CONTEXT_FILE_BUNDLE}','{$namePrefix}[bundle_ids]', { autocomplete:false });
	});
	
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
				var $editor = $content.nextAll('pre.ace_editor');
				
				if(!event.response || 0 == event.response)
					return;
				
				var evt = new jQuery.Event('cerb.insertAtCursor');
				{literal}evt.content = "![inline-image]({{cerb_file_url(" + event.response[0].id + ",'" + event.response[0].name + "')}})";{/literal}
				$editor.trigger(evt);
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