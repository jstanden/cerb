{$element_uid = uniqid('el_')}
<div id="{$element_uid}" class="cerb-interaction-popup--form-elements-submit">
	{if $var}
		<input type="hidden" data-cerb-submit-var name="prompts[{$var}]" value="">
	{/if}

	{foreach from=$buttons item=button}
		<button type="button" value="{$button.value}" class="cerb-interaction-popup--form-elements-button cerb-interaction-popup--form-elements-{$button._type} {if $button.style}cerb-button-style-{$button.style}{/if}">
			{if 'end' == $button.icon_at}
				{$button.label}
			{/if}
			{*if $button.icon}
				<span class="glyphicons glyphicons-{$button.icon}" style="color:inherit;margin-right:3px;"></span>
			{/if*}
			{if 'end' != $button.icon_at}
				{$button.label}
			{/if}
		</button>
	{/foreach}
</div>

<script type="text/javascript" nonce="{$session->nonce}">
{
	let $element = document.querySelector('#{$element_uid}');
	let $popup = $element.closest('.cerb-interaction-popup');
	let $hidden = $element.querySelector('input[data-cerb-submit-var]');
	let $buttons_continue = $element.querySelectorAll('.cerb-interaction-popup--form-elements-continue');
	let $buttons_reset = $element.querySelectorAll('.cerb-interaction-popup--form-elements-reset');

	if($buttons_continue) {
		$$.forEach($buttons_continue, function(index, $button) {
			$button.addEventListener('click', function (e) {
				e.stopPropagation();

				if($hidden)
					$hidden.value = $button.value;

				$element.style.display = 'none';

				$popup.dispatchEvent($$.createEvent('cerb-interaction-event--submit'));
			});
		});
	}

	if($buttons_reset) {
		$$.forEach($buttons_reset, function(index, $button) {
			$button.addEventListener('click', function (e) {
				e.stopPropagation();

				$element.style.display = 'none';

				$popup.dispatchEvent($$.createEvent('cerb-interaction-event--reset'));
			});
		});
	}
}
</script>