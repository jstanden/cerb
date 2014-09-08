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
	<textarea name="{$namePrefix}[content]" rows="3" cols="45" style="width:100%;height:200px;" class="placeholders editor">{$params.content}</textarea>
</div>

<b>{'message.headers.custom'|devblocks_translate|capitalize}:</b> (one per line, e.g. "X-Precedence: Bulk")
<div style="margin-left:10px;margin-bottom:0.5em;">
	<textarea name="{$namePrefix}[headers]" rows="3" cols="45" style="width:100%;" class="placeholders">{$params.headers}</textarea>
</div>

<label><input type="checkbox" name="{$namePrefix}[is_autoreply]" value="1" {if $params.is_autoreply}checked="checked"{/if}> Don't save a copy of this message in the conversation history.</label>
<br>

<script type="text/javascript">
$(function() {
	$action = $('fieldset#{$namePrefix}');
	
	var $content = $action.find('textarea.editor');
	var $format = $action.find('input:hidden[name="{$namePrefix}[format]"]');
	var $html_template = $action.find('select[name="{$namePrefix}[html_template_id]"]');
	
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
