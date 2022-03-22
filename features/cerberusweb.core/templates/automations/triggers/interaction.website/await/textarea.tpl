{$element_id = uniqid('el')}
<div id="{$element_id}" class="cerb-interaction-popup--form-elements--prompt cerb-interaction-popup--form-elements-textarea">
	<h6>{$label}</h6>
	<textarea name="prompts[{$var}]" placeholder="{$placeholder}">{$value|default:$default}</textarea>
</div>

<script type="text/javascript" nonce="{$session->nonce}">
{
	let $prompt = document.querySelector('#{$element_id}');
	let $input = $prompt.querySelector('textarea');

	// Move the cursor to the end of the text
	$input.focus();
	$input.setSelectionRange($input.value.length, $input.value.length);
}
</script>