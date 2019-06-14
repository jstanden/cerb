<div>
{include file="devblocks:cerberusweb.core::search/quick_search.tpl" view=$view return_url=null focus=true}
</div>

<div style="clear:both;"></div>

{include file="devblocks:cerberusweb.core::internal/views/search_and_view.tpl" view=$view}

<form action="#" method="POST" id="chooser{$view->id}">
<button type="button" class="submit"><span class="glyphicons glyphicons-circle-ok" style="color:rgb(0,180,0);"></span> Use this worklist</button>
</form>

<script type="text/javascript">
$(function() {
	var $popup = genericAjaxPopupFetch('{$layer}');
	
	$popup.one('popup_open',function(event,ui) {
		event.stopPropagation();
		$popup.dialog('option','title','{$context->manifest->name|escape:'javascript' nofilter} Worklist');
		
		$popup.css('overflow', 'inherit');
		
		var on_refresh = function() {
			var $worklist = $('#view{$view->id}').find('TABLE.worklist');
			$worklist.css('background','none');
			$worklist.css('background-color','rgb(100,100,100)');
			
			var $header = $worklist.find('> tbody > tr:first > td:first > span.title');
			var $header_links = $worklist.find('> tbody > tr:first td:nth(1)');
			$header_links.children().each(function(e) {
				if(!$(this).is('a.minimal'))
					$(this).remove();
			});

			var $worklist_body = $('#view{$view->id}').find('TABLE.worklistBody');
			$worklist_body.find('a.subject').each(function() {
				var $txt = $('<b class="subject"/>').text($(this).text());
				$txt.insertBefore($(this));
				$(this).remove();
			});
			
			var $actions = $('#{$view->id}_actions');
			$actions.html('');
		}
		
		on_refresh();

		$popup.delegate('DIV[id^=view]','view_refresh', on_refresh);
		
		$("form#chooser{$view->id} button.submit").click(function(event) {
			event.stopPropagation();
			
			genericAjaxGet('', 'c=internal&a=serializeView&view_id={$view->id}&context={$context}', function(json) {
				// Trigger event
				var event = jQuery.Event('chooser_save');
				event.view_name = json.view_name;
				event.worklist_model = json.worklist_model;
				event.worklist_quicksearch = $popup.find('.cerb-input-quicksearch').val();
				$popup.trigger(event);
				
				genericAjaxPopupDestroy('{$layer}');
			});
		});
	});
	
	$popup.one('dialogclose', function(event) {
		event.stopPropagation();
		genericAjaxPopupDestroy('{$layer}');
	});
});
</script>