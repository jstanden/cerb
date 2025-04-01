{$element_uid = uniqid('el_')}
<div id="{$element_uid}"></div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	const $element = $('#{$element_uid}');
	const $form = $element.closest('.cerb-form-builder');

	$element.hide();

	$form.triggerHandler($.Event('cerb-form-builder-submit'));
});
</script>