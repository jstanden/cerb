{$element_uid = uniqid('el_')}
<div id="{$element_uid}" class="cerb-interaction-popup--form-elements-submit">
	{if $var}
		<input type="hidden" data-cerb-submit-var name="prompts[{$var}]" value="">
	{/if}

	{foreach from=$buttons item=button}
		<button type="button" value="{$button.value}" class="cerb-interaction-popup--form-elements-button cerb-interaction-popup--form-elements-{$button._type} {if $button.style}cerb-button-style-{$button.style}{/if} {if $button.size}cerb-button-size-{$button.size}{/if}">
			{if 'end' == $button.icon_at}
				{$button.label}
			{/if}
			{*if $button.icon}
				<span class="cerb-icons cerb-icon-{$button.icon}" style="color:inherit;margin-right:3px;"></span>
			{/if*}
			{if 'end' != $button.icon_at}
				{$button.label}
			{/if}
		</button>
	{/foreach}
</div>

{if ($is_automation_simulated|default:false) && !($is_automation_form_fill|default:false)}
{* Design-time builder preview: the buttons are inert (clicking must not hide the form or fire a submit). *}
<script type="text/javascript" nonce="{$session->nonce}">
$(function() {
	$('#{$element_uid}').find('button').on('click', function(e) { e.stopPropagation(); e.preventDefault(); });
});
</script>
{elseif $is_automation_simulated|default:false}
{* Simulator form-fill: functional, but drive the form-builder popup bridge (jQuery + .cerb-form-builder), not the
   public portal's $$ helper / cerb-interaction-popup event bus (neither exists in the automation editor). *}
<script type="text/javascript" nonce="{$session->nonce}">
$(function() {
	var $element = $('#{$element_uid}');
	var $form = $element.closest('.cerb-form-builder');
	var $hidden = $element.find('input[data-cerb-submit-var]');

	$element.find('.cerb-interaction-popup--form-elements-continue').on('click', function(e) {
		e.stopPropagation();
		if($hidden.length)
			$hidden.val($(this).val());
		$element.hide();
		$form.triggerHandler($.Event('cerb-form-builder-submit'));
	});

	$element.find('.cerb-interaction-popup--form-elements-reset').on('click', function(e) {
		e.stopPropagation();
		$element.hide();
		$form.triggerHandler($.Event('cerb-form-builder-reset'));
	});
});
</script>
{else}
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
{/if}