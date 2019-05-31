{include file="devblocks:cerberusweb.core::search/quick_search.tpl" view=$view}

<script type="text/javascript">
$(function() {
	var $popup = genericAjaxPopupFetch('search');
	
	$popup.one('popup_open', function(event,ui) {
		var $input = $popup.find('.cerb-input-quicksearch');
		
		$popup.dialog('option','title',"{'common.search'|devblocks_translate|capitalize|escape:'javascript' nofilter}: {$view->name|escape:'javascript' nofilter}");
		$popup.dialog('option', 'resizable', false);
		$popup.dialog('option', 'minHeight', 50);
		$popup.dialog('option', 'width', '50%');
		$popup.dialog('option', 'closeOnEscape', false);
		
		ace.edit($popup.find('.cerb-input-quicksearch:first').nextAll('pre.ace_editor').attr('id')).focus();
		
		$popup.css('overflow', 'inherit');
	});
});
</script>