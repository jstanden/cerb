{$element_id = uniqid('el')}
<div id="{$element_id}" class="cerb-interaction-popup--form-elements--prompt cerb-interaction-popup--form-elements-text">
	<h6>{$label}</h6>
	<input name="prompts[{$var}]" type="text" placeholder="{$placeholder}" value="{$value|default:$default}" autocomplete="off">
</div>

<script type="text/javascript" nonce="{$session->nonce}">
{
	let $prompt = document.querySelector('#{$element_id}');
	let $input = $prompt.querySelector('input[type=text]');

	// Move the cursor to the end of the text
	$input.focus();
	$input.setSelectionRange($input.value.length, $input.value.length);
}
</script>