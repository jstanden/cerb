{$script_uid = uniqid('script')}
<script type="text/javascript" id="{$script_uid}">
$(function() {
	var $script = $('#{$script_uid}');
	var $form = $script.closest('form');

	var evt = $.Event('cerb-form-builder-end');
	$form.triggerHandler(evt);
});
</script>