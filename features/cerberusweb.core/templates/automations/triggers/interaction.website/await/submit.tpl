{$element_uid = uniqid('el_')}
<div id="{$element_uid}" class="cerb-interaction-popup--form-elements-submit">
	{if $continue_options.reset}
	<button type="button" class="cerb-interaction-popup--form-elements-button cerb-interaction-popup--form-elements-reset" tabindex="-1"><span></span></button>
	{/if}
	
	<div class="cerb-interaction-popup--form-elements-spacer"></div>
	
	{if $continue_options.continue}
	<button type="button" class="cerb-interaction-popup--form-elements-button cerb-interaction-popup--form-elements-continue"><span></span></button>
	{/if}
</div>

<script type="text/javascript" nonce="{$session->nonce}">
{
	var $element = document.querySelector('#{$element_uid}');
	var $popup = $element.closest('.cerb-interaction-popup');
	var $button_continue = $element.querySelector('.cerb-interaction-popup--form-elements-continue');
	var $button_reset = $element.querySelector('.cerb-interaction-popup--form-elements-reset');

	if($button_continue) {
		$button_continue.addEventListener('click', function (e) {
			e.stopPropagation();

			$button_continue.style.display = 'none';
			
			$popup.dispatchEvent($$.createEvent('cerb-interaction-event--submit'));
		});
	}
	
	if($button_reset) {
		$button_reset.addEventListener('click', function (e) {
			e.stopPropagation();

			$button_reset.style.display = 'none';

			$popup.dispatchEvent($$.createEvent('cerb-interaction-event--reset'));
		});
	}
}
</script>