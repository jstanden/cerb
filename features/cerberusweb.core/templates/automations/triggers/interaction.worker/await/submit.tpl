{$response_uid = "response{uniqid()}"}
<div id="{$response_uid}" style="display:flex;margin:40px 0 10px 0;">
	<input type="hidden" name="prompts[__submit]" value="continue">
	
	{if $var}
	<input type="hidden" data-cerb-submit-var name="prompts[{$var}]" value="">
	{/if}
	
	{foreach from=$buttons item=button}
		<button style="flex:1 1;" type="button" value="{$button.value}" class="cerb-button {if $button.style}cerb-button-style-{$button.style}{/if} cerb-form-builder-{$button._type}">
			{if 'end' == $button.icon_at}
				{$button.label}
			{/if}
			{if $button.icon}
				<span class="glyphicons glyphicons-{$button.icon}" style="color:inherit;margin-right:3px;"></span>
			{/if}
			{if 'end' != $button.icon_at}
				{$button.label}
			{/if}
		</button>
	{/foreach}
</div>

<script type="text/javascript">
$(function() {
	var $response = $('#{$response_uid}');
	var $hidden = $response.find('input[data-cerb-submit-var]');
	var $form = $response.closest('.cerb-form-builder');

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
});
</script>