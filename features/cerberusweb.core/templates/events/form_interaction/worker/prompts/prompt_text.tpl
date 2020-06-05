{$element_id = uniqid()}
<div class="cerb-form-builder-prompt cerb-form-builder-prompt-text" id="{$element_id}">
	<h6>{$label}</h6>
	
	{$value = $dict->get($var)}
	
	{if $mode == 'multiple'}
	<textarea name="prompts[{$var}]" placeholder="{$placeholder}" autocomplete="off" style="height:4.5em;">{$value|default:$default}</textarea>
	{else}
	<input name="prompts[{$var}]" type="text" placeholder="{$placeholder}" value="{$value|default:$default}" autocomplete="off">
	{/if}
</div>

<script type="text/javascript">
$(function() {
	var $prompt = $('#{$element_id}');

	var $input = $prompt.find('input[type=text],textarea');
	var input = $input.get(0);

	// Move the cursor to the end of the text
	input.focus();
	input.setSelectionRange(input.value.length, input.value.length);
});
</script>