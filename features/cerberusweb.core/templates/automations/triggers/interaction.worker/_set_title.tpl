{$script_uid = uniqid('script')}
<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript" id="{$script_uid}">
$(function() {
    var $script = $('#{$script_uid}');
    var $form = $script.closest('form.cerb-form-builder');
	var $popup = genericAjaxPopupFind($form);
    
	// Only if our interaction is in a standalone popup (not inline)
    if($form.parent().is('[data-cerb-interaction-popup]') && $popup) {
        $popup.dialog('option', 'title', "{$form_title|escape:'javascript' nofilter}");
    }
});
</script>
