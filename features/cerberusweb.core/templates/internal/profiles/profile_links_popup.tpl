{$links_popup_uniqid = uniqid()}
<div id="{$links_popup_uniqid}">
{include file="devblocks:cerberusweb.core::internal/views/search_and_view.tpl"}
</div>

<script type="text/javascript">
$(function() {
	var $popup = genericAjaxPopupFind('#{$links_popup_uniqid}');
	
	$popup.one('popup_open',function(event,ui) {
		$popup.dialog('option','title', 'Links from {$from_context_extension->manifest->name} to {$to_context_extension->manifest->name}');

		var on_refresh = function() {
			var $worklist = $popup.find('TABLE.worklist');

			$worklist.css('background','none');
			$worklist.css('background-color','rgb(100,100,100)');
		}
		
		on_refresh();

		$popup.delegate('DIV[id^=view]','view_refresh', on_refresh);
	});
});
</script>