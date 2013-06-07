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
				if(!$(this).is('a.minimal'))
					$(this).remove();
			});
			$header_links.find('a').css('font-size','11px');

			$worklist_body = $('#view{$view->id}').find('TABLE.worklistBody');
			
			$worklist_body.find('TBODY')
				.unbind('click')
				.click(function(e) {
					var $preview = $(this).find('TR.preview');
					$preview.toggle();
					
					var $icon = $(this).closest('tbody').find('tr:first b.subject').prev('span.ui-icon');
					
					if($preview.is(':visible')) {
						$icon.removeClass('ui-icon-triangle-1-e').addClass('ui-icon-triangle-1-s');
					} else {
						$icon.removeClass('ui-icon-triangle-1-s').addClass('ui-icon-triangle-1-e');
					}
				})
				;
			
			$worklist_body.find('TBODY TR.preview')
				.hide()
				.css('max-height','')
				;
			
			$worklist_body.find('a.subject').each(function() {
				var $tr = $(this).closest('tr');
				var $td = $(this).closest('td');
				var $td_first = $tr.find('> td:first');
				var attr_id = $td.attr('id');
				var attr_context = $td.attr('context');
				
				var $btn = $('<button type="button" id="'+attr_id+'" context="'+attr_context+'"><span class="cerb-sprite2 sprite-plus-circle"></span></button>');
				
				$btn.click(function(e) {
					e.stopPropagation();
					
					var $this = $(this);

					$this.find('span.cerb-sprite2')
						.removeClass('sprite-plus-circle')
						.addClass('sprite-tick-circle-gray')
						;
					
					$popup=genericAjaxPopupFind('#chooser{$view->id}');

					event=jQuery.Event('snippet_select');
					event.snippet_id=$this.attr('id');
					event.context=$this.attr('context');
					
					$popup.trigger(event);
				});
				
				$td_first.prepend($btn);
				
				var $txt = $('<span class="ui-icon ui-icon-triangle-1-e" style="display:inline-block;vertical-align:middle;"></span> <b class="subject">' + $(this).text() + '</b>');
				$txt.insertBefore($(this));
				
				$(this).remove();
			});
			
			$actions = $('#{$view->id}_actions');
			$actions.html('');
		}
		
		on_refresh();

		$(this).delegate('DIV[id^=view]','view_refresh', on_refresh);
		
	});
	
	$popup.one('dialogclose', function(event) {
		event.stopPropagation();
		genericAjaxPopupDestroy('{$layer}');
	});
</script>