{include file="devblocks:cerberusweb.core::search/quick_search.tpl" view=$view is_popup=true}

<script type="text/javascript">
$(function() {
	var $popup = genericAjaxPopupFetch('search');
	
	$popup.one('popup_open', function(event,ui) {
		var $input = $popup.find('input:text:first');
		
		$popup.dialog('option','title',"{'common.search'|devblocks_translate|capitalize|escape:'javascript' nofilter}: {$view->name|escape:'javascript' nofilter}");
		$popup.dialog('option', 'resizable', false);
		$popup.dialog('option', 'minHeight', 50);
		$popup.dialog('option', 'closeOnEscape', false);
		$input.select().focus();
	});
});
</script>