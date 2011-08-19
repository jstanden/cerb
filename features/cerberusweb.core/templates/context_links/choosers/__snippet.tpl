<div>
	<b>{'common.search'|devblocks_translate|capitalize}:</b>
	<input type="text" class="search" size="45">
</div>

{include file="devblocks:cerberusweb.core::internal/views/search_and_view.tpl" view=$view}

<form action="#" method="POST" id="chooser{$view->id}">
</form>

<script type="text/javascript">
	$popup = genericAjaxPopupFind('#chooser{$view->id}');
	
	$popup.one('popup_open',function(event,ui) {
		event.stopPropagation();
		$(this).dialog('option','title','{$context->manifest->name} Chooser');
		
		$popup.find('input:text:first').focus().select();
		
		$popup.find('input.search').keypress(function(e) {
			switch(e.which) {
				case 13:
					val = $(this).val();
					
					if(0 == val.length) {
						// Remove search filter
						ajax.viewRemoveFilter('{$view->id}', ['s_title']);
					} else {
						// Add search filter
						ajax.viewAddFilter('{$view->id}', 's_title', 'like', { 'value':$(this).val() } );
					}
					$(this).focus().select();
					break;
			}
		});
	});
	
	$popup.one('diagogclose', function(event) {
		event.stopPropagation();
		genericAjaxPopupDestroy('{$layer}');
	});
</script>