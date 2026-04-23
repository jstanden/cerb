{$element_id = uniqid()}
<div class="cerb-form-builder-prompt cerb-form-builder-prompt-picklist" id="{$element_id}">
	<h6>{$label}</h6>

	<div class="cerb-form-builder-prompt-options">
		{$value = $dict->get($var)}

		<select name="prompts[{$var}]">
		{foreach from=$options item=option}
			<option value="{$option}" {if $value|default:$default==$option}selected="selected"{/if}>{$option}</option>
		{/foreach}
		</select>
	</div>
</div>