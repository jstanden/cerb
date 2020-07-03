{$element_id = uniqid()}
<div class="cerb-form-builder-prompt cerb-form-builder-prompt-reply" id="{$element_id}">
	<p>
		<input type="hidden" name="prompts[{$var}]" value="">
	</p>
</div>

<script type="text/javascript">
$(function() {
	var $element = $('#{$element_id}');

	var $popup = genericAjaxPopupFetch('replyInteraction');

	// If this popup isn't already open
	if(null == $popup) {
		var formData = new FormData();
		formData.set('c', 'profiles');
		formData.set('a', 'invoke');
		formData.set('module', 'ticket');
		formData.set('action', 'reply');
		formData.set('forward', '0');
		formData.set('draft_id', '{$draft_id}');
		formData.set('reply_mode', '0');
		formData.set('is_confirmed', '1');

		$popup = genericAjaxPopup('replyInteraction', formData, null, false, '80%');

		$popup.on('cerb-reply-sent cerb-reply-saved cerb-reply-draft', function(json) {
			console.log(json);

			if(json && json.record && json.record.id) {
				$element.find('input[name="prompts[{$var}]"]').val(json.record.id);

				// Automatically continue
				var $widget_form = $element.closest('form');
				$widget_form.find('.cerb-form-builder-continue').click();
			}
		});

	// If the reply window is already open, just focus it
	} else {
		$popup.show().find('textarea').focus();
	}
});
</script>