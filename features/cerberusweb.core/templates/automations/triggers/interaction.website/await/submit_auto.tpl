{$element_uid = uniqid('el_')}
<div id="{$element_uid}" class="cerb-interaction-popup--form-elements-submit-auto">
</div>

<script type="text/javascript" nonce="{$session->nonce}">
{
	let $element = document.querySelector('#{$element_uid}');
	let $popup = $element.closest('.cerb-interaction-popup');

	$element.style.display = 'none';
	$popup.dispatchEvent($$.createEvent('cerb-interaction-event--submit'));

	// Scroll down
	const $form = $element.closest('.cerb-interaction-popup--form');
	const lastChild = $form.lastElementChild;
	lastChild.scrollIntoView({ behavior: 'smooth', block: 'end' });
}
</script>