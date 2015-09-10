{if $view instanceof IAbstractView_QuickSearch}
<div style="float:right;">
	{include file="devblocks:cerberusweb.core::search/quick_search.tpl" view=$view return_url=null reset=false}
</div>
{/if}

{$search_popup_uniqid = uniqid()}
<div id="{$search_popup_uniqid}">
{include file="devblocks:cerberusweb.core::internal/views/search_and_view.tpl"}
</div>

<script type="text/javascript">
$(function() {
	var $popup = genericAjaxPopupFind('#{$search_popup_uniqid}');
	
	$popup.one('popup_open',function(event,ui) {
		$popup.dialog('option','title', '{$context_ext->manifest->name} Search');
		
		var on_refresh = function() {
			var $worklist = $popup.find('TABLE.worklist');

			$worklist.css('background','none');
			$worklist.css('background-color','rgb(100,100,100)');
		}
		
		on_refresh();

		$popup.delegate('DIV[id^=view]','view_refresh', on_refresh);
		
		$popup.find('input.input_search').first().focus();
		
		$popup.css('overflow', 'inherit');
	});
});
</script>