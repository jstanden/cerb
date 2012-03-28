{include file="devblocks:cerberusweb.core::internal/views/search_and_view.tpl" view=$view}

<form action="#" method="POST" id="chooser{$view->id}">
<button type="button" class="submit"><span class="cerb-sprite2 sprite-tick-circle-frame"></span> Save Worklist</button>
</form>

<script type="text/javascript">
	$popup = genericAjaxPopupFind('#chooser{$view->id}');
	
	$popup.one('popup_open',function(event,ui) {
		event.stopPropagation();
		$(this).dialog('option','title','{$context->manifest->name} Worklist');
		
		$('#{$view->id}_actions').find('tr:first td').html('');

		$(this).delegate('DIV[id^=view]','view_refresh',function() {
			id = $(this).attr('id').replace('view','');
			$('#' + id + '_actions')
				.find(' > TBODY > TR:first > TD:first')
				.html('')
				;
		});
		
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