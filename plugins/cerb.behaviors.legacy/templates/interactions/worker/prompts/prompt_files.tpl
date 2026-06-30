{$element_id = uniqid('prompt')}
<div class="cerb-form-builder-prompt cerb-form-builder-prompt-files" id="{$element_id}">
	<h6>{$label}</h6>

	<div class="cerb-ui-file-upload" data-name="prompts[{$var}]"{if $selection!='single'} data-multiple="1"{/if}>
		{if $records && is_array($records)}
			{foreach from=$records item=record name=records}
				<li data-file-id="{$record->id}" data-file-name="{$record->_label}" data-file-size="{$record->size}"></li>
			{/foreach}
		{/if}
	</div>
</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	var $element = $('#{$element_id}');

	if(window.CerbUI && CerbUI.FileUpload)
		$element.find('.cerb-ui-file-upload').each(function() {
			new CerbUI.FileUpload(this, { name: 'prompts[{$var}]', multiple: {if $selection=='single'}false{else}true{/if} });
		});
});
</script>
