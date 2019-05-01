{$element_id = uniqid()}
<div class="cerb-form-builder-prompt cerb-form-builder-prompt-text" id="{$element_id}">
	<h6>{$label}</h6>
	
	{$value = $dict->get($var)}
	
	{if $mode == 'multiple'}
	<textarea name="prompts[{$var}]" placeholder="{$placeholder}" autocomplete="off">{$value|default:$default}</textarea>
	{else}
	<input name="prompts[{$var}]" type="text" placeholder="{$placeholder}" value="{$value|default:$default}" autocomplete="off">
	{/if}
</div>