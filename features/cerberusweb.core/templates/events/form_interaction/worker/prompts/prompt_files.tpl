{$element_id = uniqid('prompt')}
<div class="cerb-form-builder-prompt cerb-form-builder-prompt-files" id="{$element_id}">
	<h6>{$label}</h6>

	<button type="button" class="cerb-form-builder-prompt-files-button"><span class="glyphicons glyphicons-paperclip"></span></button>
	<ul class="chooser-container bubbles cerb-attachments-container">
		{if $records && is_array($records)}
			{foreach from=$records item=record name=records}
				<li>
					<a href="javascript:;" class="cerb-peek-trigger" data-context="{CerberusContexts::CONTEXT_ATTACHMENT}" data-context-id="{$record->id}">
						<b>{$record->_label}</b>
						({$record->size|devblocks_prettybytes}	-
						{if !empty($record->mime_type)}{$record->mime_type}{else}{'display.convo.unknown_format'|devblocks_translate|capitalize}{/if})
					</a>
					<input type="hidden" name="prompts[{$var}]{if $selection=='single'}{else}[]{/if}" value="{$record->id}">
					<a href="javascript:;" onclick="$(this).parent().remove();"><span class="glyphicons glyphicons-circle-remove"></span></a>
				</li>
			{/foreach}
		{/if}
	</ul>
</div>

<script type="text/javascript">
$(function() {
	var $element = $('#{$element_id}');
	var $button = $element.find('.cerb-form-builder-prompt-files-button');

	$element.find('.cerb-peek-trigger')
		.cerbPeekTrigger()
		;

	$button.each(function(e) {
		var options = {
			single: {if $selection=='single'}true{else}false{/if}
		};
		ajax.chooserFile(this, 'prompts[{$var}]', options);
	});
});
</script>