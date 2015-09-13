<b>{'message.header.from'|devblocks_translate|capitalize}:</b>
<div style="margin-left:10px;margin-bottom:0.5em;">
	<select name="{$namePrefix}[from_address_id]">
		<option value="0">(default)</option>
		<optgroup label="Reply-to Addresses">
			{foreach from=$replyto_addresses key=address_id item=replyto}
			{if !empty($replyto->reply_personal)}
			<option value="{$address_id}" {if $params.from_address_id==$address_id}selected="selected"{/if}>{if !empty($replyto->reply_personal)}{$replyto->reply_personal} {/if}&lt;{$replyto->email}&gt;</option>
			{else}
			<option value="{$address_id}" {if $params.from_address_id==$address_id}selected="selected"{/if}>{$replyto->email}</option>
			{/if}
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

<b>{'message.header.to'|devblocks_translate|capitalize}:</b>
<div style="margin-left:10px;margin-bottom:0.5em;">
	<input type="text" name="{$namePrefix}[to]" value="{$params.to}" size="45" style="width:100%;" class="placeholders">
	<ul class="bubbles">
	{foreach from=$trigger->variables item=var_data key=var_key}
		{if $var_data.type == "ctx_{CerberusContexts::CONTEXT_ADDRESS}"}
			<li><label><input type="checkbox" name="{$namePrefix}[to_var][]" value="{$var_key}" {if in_array($var_key, $params.to_var)}checked="checked"{/if}> (variable) {$var_data.label}</label></li>
		{/if}
	{/foreach}
	</ul>
</div>

<b>{'message.header.subject'|devblocks_translate|capitalize}:</b>
<div style="margin-left:10px;margin-bottom:0.5em;">
	<input type="text" name="{$namePrefix}[subject]" value="{$params.subject}" size="45" style="width:100%;" class="placeholders">
</div>

<div style="{if $params.format=='parsedown'}{else}display:none;{/if}" class="div-showhide">
	<b>HTML Template:</b><br>
	<div style="margin-left:10px;margin-bottom:0.5em;">
		<select name="{$namePrefix}[html_template_id]">
			<option value=""> - {'common.default'|devblocks_translate|lower} -</option>
			{foreach from=$html_templates item=html_template}
			<option value="{$html_template->id}" {if $params.html_template_id==$html_template->id}selected="selected"{/if}>{$html_template->name}</option>
			{/foreach}
		</select>
	</div>
</div>

<b>{'common.content'|devblocks_translate|capitalize}:</b><br>
<div style="margin-left:10px;margin-bottom:0.5em;">
	<input type="hidden" name="{$namePrefix}[format]" value="{$params.format}">
	<textarea name="{$namePrefix}[content]" rows="3" cols="45" style="width:100%;height:150px;" class="placeholders editor">{$params.content}</textarea>
</div>

<b>{'message.headers.custom'|devblocks_translate|capitalize}:</b> (one per line, e.g. "X-Precedence: Bulk")
<div style="margin-left:10px;margin-bottom:0.5em;">
	<textarea name="{$namePrefix}[headers]" rows="3" cols="45" style="width:100%;" class="placeholders">{$params.headers}</textarea>
</div>

{* Check for attachment list variables *}
{capture name="attachment_vars"}
{foreach from=$trigger->variables item=var key=var_key}
{if $var.type == "ctx_{CerberusContexts::CONTEXT_ATTACHMENT}"}
<div>
	<label><input type="checkbox" name="{$namePrefix}[attachment_vars][]" value="{$var_key}" {if in_array($var_key, $params.attachment_vars)}checked="checked"{/if}>{$var.label}</label>
</div>
{/if}
{/foreach}
{/capture}

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
		<li><input type="hidden" name="{$namePrefix}[bundle_ids][]" value="{$bundle_id}">{$bundle->name} <a href="javascript:;" onclick="$(this).parent().remove();"><span class="ui-icon ui-icon-trash" style="display:inline-block;width:14px;height:14px;"></span></a></li>
		{/if} 
	{/foreach}
	</ul>
</div>

<script type="text/javascript">
$(function() {
	var $action = $('fieldset#{$namePrefix}');
	
	var $content = $action.find('textarea.editor');
	var $format = $action.find('input:hidden[name="{$namePrefix}[format]"]');
	var $html_template = $action.find('select[name="{$namePrefix}[html_template_id]"]');
	
	// Attachments
	
	$action.find('button.chooser-file-bundle').each(function() {
		ajax.chooser(this,'{CerberusContexts::CONTEXT_FILE_BUNDLE}','{$namePrefix}[bundle_ids]', { autocomplete:false });
	});
	
	// Text editor
	
	var markitupPlaintextSettings = $.extend(true, { }, markitupPlaintextDefaults);
	var markitupParsedownSettings = $.extend(true, { }, markitupParsedownDefaults);

	markitupPlaintextSettings.markupSet.unshift(
		{ name:'Switch to Markdown', openWith: 
			function(markItUp) { 
				var $editor = $(markItUp.textarea);
				$editor.markItUpRemove().markItUp(markitupParsedownSettings);
				$editor.closest('form').find('input:hidden[name=format]').val('parsedown');
				$format.val('parsedown');
				$html_template.closest('.div-showhide').fadeIn();
			},
			key: 'H',
			className:'parsedown'
		},
		{ separator:' ' },
		{ name:'Preview', key: 'P', call:'preview', className:"preview" }
	);
	
	var previewParser = function(content) {
		var $frm = $content.closest('form');
		
		genericAjaxPost(
			$frm,
			'',
			'c=internal&a=testDecisionEventSnippets&prefix={$namePrefix}&field=content&is_editor=format&_replyto_field=from_address_id',
			function(o) {
				content = o;
			},
			{
				async: false
			}
		);
		
		return content;
	};
	
	markitupPlaintextSettings.previewParser = previewParser;
	markitupPlaintextSettings.previewAutoRefresh = false;
	
	markitupParsedownSettings.previewParser = previewParser;
	markitupParsedownSettings.previewAutoRefresh = false;
	delete markitupParsedownSettings.previewInWindow;
	
	markitupParsedownSettings.markupSet.unshift(
		{ name:'Switch to Plaintext', openWith: 
			function(markItUp) { 
				var $editor = $(markItUp.textarea);
				$editor.markItUpRemove().markItUp(markitupPlaintextSettings);
				$editor.closest('form').find('input:hidden[name=format]').val('');
				$format.val('');
				$html_template.closest('.div-showhide').hide();
			},
			key: 'H',
			className:'plaintext'
		},
		{ separator:' ' }
	);
	
	markitupParsedownSettings.markupSet.splice(
		6,
		0,
		{ name:'Upload an Image', openWith: 
			function(markItUp) {
				$chooser=genericAjaxPopup('chooser','c=internal&a=chooserOpenFile&single=1',null,true,'750');
				
				$chooser.one('chooser_save', function(event) {
					if(!event.response || 0 == event.response)
						return;
					
					$content.insertAtCursor("![inline-image](" + event.response[0].url + ")");
				});
			},
			key: 'U',
			className:'image-inline'
		}
	);
	
	try {
		{if $params.format == 'parsedown'}
		$content.markItUp(markitupParsedownSettings);
		{else}
		$content.markItUp(markitupPlaintextSettings);
		{/if}
		
	} catch(e) {
		if(window.console)
			console.log(e);
	}
});
</script>