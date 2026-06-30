{$element_id = uniqid()}
<div class="cerb-form-builder-prompt cerb-form-builder-prompt-checkboxes" id="{$element_id}">
	<h6>{$label}</h6>

	<div class="cerb-portal-form-prompt-options">
		{if $dict->exists($var)}
			{$selected_options = $dict->get($var, [])}
		{else}
			{$selected_options = $default|default:[]}
		{/if}
	
		{foreach from=$options item=option}
		<label><input type="checkbox" name="prompts[{$var}][]" value="{$option}" {if is_array($selected_options) && in_array($option, $selected_options)}checked="checked"{/if}> {$option}</label>
		{/foreach}
	</div>
</div>