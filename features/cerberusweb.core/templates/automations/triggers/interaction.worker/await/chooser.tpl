{$element_id = uniqid('prompt')}
<div class="cerb-form-builder-prompt cerb-form-builder-prompt-chooser" id="{$element_id}">
	<h6>{$label}</h6>
	<button type="button" data-field-name="prompts[{$var}]{if $multiple}[]{/if}" data-context="{$record_type}" {if !$multiple}data-single="true"{/if} data-query="{$query}" {if $autocomplete}data-autocomplete="" data-autocomplete-if-empty="true"{/if}><span class="cerb-icons cerb-icon-search"></span></button>
	{$selected_values = $value|default:$default}
	<ul class="bubbles chooser-container">
		{if $selected_values}
			{if !is_array($selected_values)}{$selected_values = [$selected_values]}{/if}
			{$selected_models = CerberusContexts::getModels($record_type, $selected_values)}
			{$selected_dicts = DevblocksDictionaryDelegate::getDictionariesFromModels($selected_models, $record_type)}

			{if $selected_dicts}
				{foreach from=$selected_values item=selected_value}
					{if array_key_exists($selected_value, $selected_dicts)}
					<li>
						<input type="hidden" name="prompts[{$var}]{if $multiple}[]{/if}" value="{$selected_value}">
						<a data-context="{$record_type}" data-context-id="{$selected_value}">{$selected_dicts[$selected_value]->get('_label')}</a>
					</li>
					{/if}
				{/foreach}
			{/if}
		{/if}
	</ul>
</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	let $prompt = $('#{$element_id}');
	$prompt.find('button').cerbChooserTrigger();
	$prompt.find('ul li a[data-context]').cerbPeekTrigger();
});
</script>