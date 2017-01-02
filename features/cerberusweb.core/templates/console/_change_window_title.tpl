{$uniqid = uniqid()}
<div id="{$uniqid}"></div>
<script type="text/javascript">
$(function() {
	var $popup = genericAjaxPopupFind('#{$uniqid}');
	$popup.dialog('option','title', "{$title|escape:'javascript' nofilter}");
});
</script>