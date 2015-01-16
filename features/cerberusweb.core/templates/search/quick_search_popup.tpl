{include file="devblocks:cerberusweb.core::search/quick_search.tpl" view=$view is_popup=true}

<script type="text/javascript">
$(function() {
	var $popup = genericAjaxPopupFetch('search');
	
	$popup.one('popup_open', function(event,ui) {
		$popup.dialog('option','title',"{'common.search'|devblocks_translate|capitalize|escape:'javascript' nofilter}: {$view->name|escape:'javascript' nofilter}");
		$popup.dialog('option', 'resizable', false);
		$popup.dialog('option', 'minHeight', 50);
		$popup.find('input:text:first').select().focus();
		$popup.closest('.ui-dialog').css('overflow', 'visible');
		$popup.css('overflow', 'inherit');
	});
});
</script>