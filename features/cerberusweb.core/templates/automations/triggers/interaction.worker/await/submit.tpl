{$response_uid = "response{uniqid()}"}
{if $label}<h6>{$label}</h6>{/if}
<div id="{$response_uid}" class="cerb-form-builder-prompt-submit" style="display:flex;{if $buttons}margin:40px 0 10px 0;{/if}">
	<input type="hidden" name="prompts[__submit]" value="continue">

	{if $var}
	<input type="hidden" data-cerb-submit-var name="prompts[{$var}]" value="">
	{/if}

	{foreach from=$buttons item=button}
		<button style="flex:1 1;" type="button" value="{$button.value}" class="cerb-ui-button{if $button.style == 'secondary'} cerb-ui-button--subtle{elseif $button.style == 'outline'} cerb-ui-button--outline{/if} cerb-form-builder-{$button._type}">
			{if 'end' == $button.icon_at}
				{$button.label}
			{/if}
			{if $button.icon}
				<span class="cerb-icons cerb-icon-{$button.icon}" style="color:inherit;margin-right:3px;"></span>
			{/if}
			{if 'end' != $button.icon_at}
				{$button.label}
			{/if}
		</button>
	{/foreach}
</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	var $response = $('#{$response_uid}');
	var $hidden = $response.find('input[data-cerb-submit-var]');
	var $form = $response.closest('.cerb-form-builder');

	{if ($is_automation_simulated|default:false) && !($is_automation_form_fill|default:false)}
	// Design-time builder preview: the buttons are inert (clicking must not hide the form or fire a submit).
	// (The simulator form-fill is also "simulated" but needs FUNCTIONAL buttons, so it takes the real branch below.)
	$response.find('button').on('click', function(e) { e.stopPropagation(); e.preventDefault(); });
	{else}
	var $button_continue = $response.find('.cerb-form-builder-continue');

	$button_continue.on('click', function(e) {
		e.stopPropagation();

		$response.hide();

		$hidden.val($(this).val());

		var evt = $.Event('cerb-form-builder-submit');
		$form.triggerHandler(evt);
	});

	var $button_reset = $response.find('.cerb-form-builder-reset');

	$button_reset.on('click', function(e) {
		e.stopPropagation();

		$response.hide();

		var evt = $.Event('cerb-form-builder-reset');
		$form.triggerHandler(evt);
	});
	{/if}
});
</script>