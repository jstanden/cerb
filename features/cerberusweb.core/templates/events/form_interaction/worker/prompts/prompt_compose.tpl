{$element_id = uniqid()}
<div class="cerb-form-builder-prompt cerb-form-builder-prompt-compose" id="{$element_id}">
	<p>
		<input type="hidden" name="prompts[{$var}]" value="">
	</p>
</div>

<script type="text/javascript">
$(function() {
	var $element = $('#{$element_id}');

	// Find the compose popup
	var $popup_compose = genericAjaxPopupFetch('compose');

	// If the compose popup isn't open already
	if(null == $popup_compose) {
		$popup_compose = genericAjaxPopup('compose','c=internal&a=invoke&module=records&action=showPeekPopup&context=ticket&context_id=0&draft_id={$draft_id}',null,false,'80%');

		$popup_compose.on('compose_save', function(json) {
			if(json && json.record && json.record.id) {
				$element.find('input[name="prompts[{$var}]"]').val(json.record.id);

				// Automatically continue
				var $widget_form = $element.closest('form');
				$widget_form.find('.cerb-form-builder-continue').click();
			}
		});
	}
});
</script>