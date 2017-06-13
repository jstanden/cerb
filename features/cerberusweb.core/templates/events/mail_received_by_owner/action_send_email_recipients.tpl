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

<b>{'common.content'|devblocks_translate|capitalize}:</b>
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
	<label><input type="checkbox" name="{$namePrefix}[attachment_vars][]" value="{$var_key}" {if is_array($params.attachment_vars) && in_array($var_key, $params.attachment_vars)}checked="checked"{/if}>{$var.label}</label>
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
		<li><input type="hidden" name="{$namePrefix}[bundle_ids][]" value="{$bundle_id}">{$bundle->name} <a href="javascript:;" onclick="$(this).parent().remove();"><span class="glyphicons glyphicons-circle-remove"></span></a></li>
		{/if} 
	{/foreach}
	</ul>
</div>

<b>{'common.options'|devblocks_translate|capitalize}:</b>
<div style="margin-left:10px;margin-bottom:0.5em;">
	<label><input type="checkbox" name="{$namePrefix}[is_autoreply]" value="1" {if $params.is_autoreply}checked="checked"{/if}> Don't save a copy of this message in the conversation history.</label>
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
			'c=internal&a=testDecisionEventSnippets&prefix={$namePrefix}&field=content&is_editor=format&_group_key=group_id&_bucket_key=ticket_bucket_id',
			function(o) {
				content = o;
			},
			{
				async:false
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
