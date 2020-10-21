{$script_uid = uniqid('script')}
<script type="text/javascript" id="{$script_uid}">
$(function() {
    var $script = $('#{$script_uid}');

	var $popup = genericAjaxPopupFind($script);

	if($popup) {
        $popup.dialog('option', 'title', "{$form_title|escape:'javascript' nofilter}");
    }
});
</script>
