{$element_id = uniqid('el')}
<div id="{$element_id}" class="cerb-interaction-popup--form-elements--prompt cerb-interaction-popup--form-elements-text">
	<h6>{$label}</h6>
	<input name="prompts[{$var}]" type="text" placeholder="{$placeholder}" value="{$value|default:$default}" autocomplete="off">
</div>

<script type="text/javascript" nonce="{$session->nonce}">
{
	let $prompt = document.querySelector('#{$element_id}');
	let $input = $prompt.querySelector('input[type=text]');

	$input.addEventListener('keydown', function(e) {
		if (e.keyIdentifier==='U+000A' || e.keyIdentifier==='Enter' || e.keyCode===13) {
			e.preventDefault();

			let $popup = $prompt.closest('.cerb-interaction-popup');

			let $submits = $popup.querySelectorAll('.cerb-interaction-popup--form-elements-continue');

			// If we have multiple submits, click the first one when pressing enter
			if($submits.length) {
				$submits[0].dispatchEvent(
					new MouseEvent("click", { "view": window, "bubbles": true, "cancelable": false })
				);
			}
		}
	});
}
</script>