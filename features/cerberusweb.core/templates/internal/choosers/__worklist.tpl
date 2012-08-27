<div style="float:right;">
{include file="devblocks:cerberusweb.core::search/quick_search.tpl" view=$view return_url=null reset=false is_popup=true}
</div>

<div style="clear:both;"></div>

{include file="devblocks:cerberusweb.core::internal/views/search_and_view.tpl" view=$view}

<form action="#" method="POST" id="chooser{$view->id}">
<button type="button" class="submit"><span class="cerb-sprite2 sprite-tick-circle"></span> Save Worklist</button>
</form>

<script type="text/javascript">
	$popup = genericAjaxPopupFind('#chooser{$view->id}');
	
	$popup.one('popup_open',function(event,ui) {
		event.stopPropagation();
		$(this).dialog('option','title','{$context->manifest->name} Worklist');
		
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
			$worklist_body.find('a.subject').each(function() {
				$txt = $('<b class="subject">' + $(this).text() + '</b>');
				$txt.insertBefore($(this));
				$(this).remove();
			});
			
			$actions = $('#{$view->id}_actions');
			$actions.html('');
		}
		
		on_refresh();

		$(this).delegate('DIV[id^=view]','view_refresh', on_refresh);
		
		$("form#chooser{$view->id} button.submit").click(function(event) {
			event.stopPropagation();
			$popup = genericAjaxPopupFind('form#chooser{$view->id}');
			
			genericAjaxGet('', 'c=internal&a=serializeView&view_id={$view->id}', function(json) {
				// Trigger event
				event = jQuery.Event('chooser_save');
				event.view_name = json.view_name;
				event.view_model = json.view_model;
				$popup.trigger(event);
				
				genericAjaxPopupDestroy('{$layer}');
			});
		});		
	});
	$popup.one('diagogclose', function(event) {
		event.stopPropagation();
		genericAjaxPopupDestroy('{$layer}');
	});
</script>