{if $continue_options.reset || $continue_options.continue}
	{$response_uid = "response{uniqid()}"}
	<div id="{$response_uid}" style="display:flex;margin-top:30px;">
		{if $continue_options.reset}
			<button style="flex:1 1;max-width:5em;" type="button" class="cerb-button cerb-form-builder-reset" tabindex="-1"><span></span></button>
		{/if}
		<div style="flex:2 2;"></div>
		{if $continue_options.continue}
			<input type="hidden" name="prompts[__submit]" value="continue">
			<button style="flex:1 1;max-width:5em;" type="button" class="cerb-button cerb-form-builder-continue"><span></span></button>
		{/if}
	</div>

	<script type="text/javascript">
		$(function() {
			var $response = $('#{$response_uid}');
			var $form = $response.closest('.cerb-form-builder');

			var $button_continue = $response.find('.cerb-form-builder-continue');

			$button_continue.on('click', function(e) {
				e.stopPropagation();

				$button_continue.hide();

				var evt = $.Event('cerb-form-builder-submit');
				$form.triggerHandler(evt);
			});

			var $button_reset = $response.find('.cerb-form-builder-reset');

			$button_reset.on('click', function(e) {
				e.stopPropagation();

				var evt = $.Event('cerb-form-builder-reset');
				$form.triggerHandler(evt);
			});
		});
	</script>
{/if}