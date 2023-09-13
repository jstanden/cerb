<form action="{devblocks_url}{/devblocks_url}" method="POST" class="cerb-form-builder">
	<input type="hidden" name="continuation_token" value="{$continuation_token}">
	<div class="cerb-form-data"></div>
</form>

{$script_uid = uniqid('script_')}
<script id="{$script_uid}" type="text/javascript">
$(function() {
	var $script = $('#{$script_uid}');
	var $spinner = Devblocks.getSpinner();
	
	var $form = $script.siblings('form.cerb-form-builder');
	var $data = $form.find('.cerb-form-data');

	$form.on('submit', function(e) {
		e.preventDefault();
		e.stopPropagation();

		$form.triggerHandler('cerb-form-builder-submit');
		return false;
	});

	$form.on('cerb-form-builder-submit', function(e) {
		e.stopPropagation();

		$spinner.insertAfter($data.hide());

		var formData = new FormData($form[0]);
		formData.set('c', 'profiles');
		formData.set('a', 'invoke');
		formData.set('module', 'bot');
		formData.set('action', 'sendMessage');

		genericAjaxPost(formData, null, null, function(html) {
			$spinner.detach();
			$data.html(html).fadeIn();

			$data.find(':focusable').not('[tabindex=-1],input[type=checkbox],.cerb-paging').first().focus();
		});
	});

	$form.on('cerb-form-builder-reset', function(e) {
		e.stopPropagation();

		$spinner.insertAfter($data.hide());

		var formData = new FormData($form[0]);
		formData.set('c', 'profiles');
		formData.set('a', 'invoke');
		formData.set('module', 'bot');
		formData.set('action', 'sendMessage');
		formData.set('reset', '1');

		genericAjaxPost(formData, null, null, function(html) {
			$spinner.detach();
			$data.html(html).fadeIn();

			$data.find(':focusable').not('[tabindex=-1],input[type=checkbox],.cerb-paging').first().focus();
			$form.trigger($.Event('cerb-interaction-reset'));
		});
	});

	$form.on('cerb-form-builder-end', function(e) {
		e.stopPropagation();

		var event_data = {
			eventData: e.eventData
		};

		$form.trigger($.Event('cerb-interaction-done', event_data));
	});

	$form.triggerHandler('cerb-form-builder-submit');
});
</script>
