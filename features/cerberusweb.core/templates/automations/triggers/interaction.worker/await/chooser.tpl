{$element_id = uniqid('prompt')}
<div class="cerb-form-builder-prompt cerb-form-builder-prompt-chooser" id="{$element_id}">
	<h6>{$label}</h6>
	{$selected_values = $value|default:$default}
	<div class="cerb-ui-record-chooser">
		{if $selected_values}
			{if !is_array($selected_values)}{$selected_values = [$selected_values]}{/if}
			{$selected_models = CerberusContexts::getModels($record_type, $selected_values)}
			{$selected_dicts = DevblocksDictionaryDelegate::getDictionariesFromModels($selected_models, $record_type)}

			{if $selected_dicts}
				{foreach from=$selected_values item=selected_value}
					{if array_key_exists($selected_value, $selected_dicts)}
					<li data-context="{$record_type}" data-context-id="{$selected_value}" data-label="{$selected_dicts[$selected_value]->get('_label')}" data-image="{$selected_dicts[$selected_value]->get('_image_url')}"></li>
					{/if}
				{/foreach}
			{/if}
		{/if}
	</div>
</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	let $prompt = $('#{$element_id}');
	if(window.CerbUI && CerbUI.RecordChooser)
		$prompt.find('.cerb-ui-record-chooser').each(function() {
			new CerbUI.RecordChooser(this, {
				context: '{$record_type}',
				name: 'prompts[{$var}]',
				multiple: {if $multiple}true{else}false{/if},
				query: '{$query|escape:'javascript' nofilter}'
			});
		});
});
</script>