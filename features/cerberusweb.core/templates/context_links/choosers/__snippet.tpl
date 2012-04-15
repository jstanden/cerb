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
		
		// Quick search
		
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
		
		// Progressive de-enhancement
		
		var on_refresh = function() {
			$worklist = $('#view{$view->id}').find('TABLE.worklist');
			$worklist.css('background','none');
			$worklist.css('background-color','rgb(100,100,100)');
			
			$header = $worklist.find('> tbody > tr:first > td:first > span.title');
			$header.css('font-size', '14px');
			$header_links = $worklist.find('> tbody > tr:first td:nth(1)');
			$header_links.children().each(function(e) {
				if(!$(this).is('a.minimal, input:checkbox'))
					$(this).remove();
			});
			$header_links.find('a').css('font-size','11px');

			$worklist_body = $('#view{$view->id}').find('TABLE.worklistBody');
			$worklist_body.find('a.subject').each(function() {
				$txt = $('<a href="javascript:;" class="subject">' + $(this).text() + '</a>');
				$txt.click(function(e) {
					$td = $(this).closest('td');
					$popup=genericAjaxPopupFind('#chooser{$view->id}');
					event=jQuery.Event('snippet_select');
					event.snippet_id=$td.attr('id');
					event.context=$td.attr('context');
					$popup.trigger(event);
				});
				$txt.insertBefore($(this));
				$(this).remove();
			});
			
			$actions = $('#{$view->id}_actions').find('> tbody > tr:first td');
			$actions.html('');
		}
		
		on_refresh();

		$(this).delegate('DIV[id^=view]','view_refresh', on_refresh);		
		
	});
	
	$popup.one('diagogclose', function(event) {
		event.stopPropagation();
		genericAjaxPopupDestroy('{$layer}');
	});
</script>