{include file="devblocks:cerberusweb.core::search/quick_search.tpl" view=$view is_popup=true}

<script type="text/javascript">
	$popup = genericAjaxPopupFetch('search');
	$popup.one('popup_open', function(event,ui) {
		$(this).dialog('option','title',"{'common.search'|devblocks_translate|capitalize}");
		$(this).dialog('option', 'resizable', false);
		$(this).dialog('option', 'minHeight', 50);
		$(this).find('input:text:first').select().focus();
	});
</script>