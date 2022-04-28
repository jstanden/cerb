{$element_uid = uniqid('el_')}
<div id="{$element_uid}" class="cerb-interaction-panel--form-elements-submit">
	{if $continue_options.reset}
	<button type="button" class="cerb-interaction-panel--form-elements-button cerb-interaction-panel--form-elements-reset" tabindex="-1"><span></span></button>
	{/if}
	
	<div style="flex:2 2;"></div>
	
	{if $continue_options.continue}
	<button type="button" class="cerb-interaction-panel--form-elements-button cerb-interaction-panel--form-elements-continue"><span></span></button>
	{/if}
</div>

<script type="text/javascript">
$$.ready(function() {
	var $element = document.querySelector('#{$element_uid}');
	var $panel = $element.closest('.cerb-interaction-panel');
	var $button_continue = $element.querySelector('.cerb-interaction-panel--form-elements-continue');
	var $button_reset = $element.querySelector('.cerb-interaction-panel--form-elements-reset');

	if($button_continue) {
		$button_continue.addEventListener('click', function (e) {
			e.stopPropagation();

			$button_continue.style.display = 'none';
			
			$panel.dispatchEvent($$.createEvent('cerb-interaction-event--submit'));
		});
	}
	
	if($button_reset) {
		$button_reset.addEventListener('click', function (e) {
			e.stopPropagation();

			$button_reset.style.display = 'none';

			$panel.dispatchEvent($$.createEvent('cerb-interaction-event--reset'));
		});
	}
});
</script>