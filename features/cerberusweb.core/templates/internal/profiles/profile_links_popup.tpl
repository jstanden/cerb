{$links_popup_uniqid = uniqid()}
<div id="{$links_popup_uniqid}">
	<div>
	{include file="devblocks:cerberusweb.core::search/quick_search.tpl" view=$view return_url=null focus=true}
	</div>
	
	{include file="devblocks:cerberusweb.core::internal/views/search_and_view.tpl"}
</div>

<script type="text/javascript">
$(function() {
	var $popup = genericAjaxPopupFind('#{$links_popup_uniqid}');
	
	$popup.one('popup_open',function(event,ui) {
		$popup.dialog('option','title', 'Links from {$from_context_extension->manifest->name} to {$to_context_extension->manifest->name}');
		$popup.css('overflow', 'inherit');
		
		var on_refresh = function() {
			var $worklist = $popup.find('TABLE.worklist');
			var $worklist_actions = $popup.find('#{$view->id}_actions');
			
			$worklist.css('background','none');
			$worklist.css('background-color','var(--cerb-color-background-contrast-100)');
			
			var $button = $('<button type="button" />')
				.text('Unlink')
				.hide()
				.click(function(e) {
					e.stopPropagation();
					var $view = $('#view{$view->id}');
					
					// Get checked row IDs
					var $rows = $view.find('input:checkbox:checked');
					var ids = $rows.map(function() {
						return $(this).val();
					}).get();
					
					// Ajax unlink

					var formData = new FormData();
					formData.set('c', 'internal');
					formData.set('a', 'invoke');
					formData.set('module', 'records');
					formData.set('action', 'contextDeleteLinksJson');
					formData.set('from_context', '{$from_context_extension->id}');
					formData.set('from_context_id', '{$from_context_id}');
					formData.set('context', '{$to_context_extension->id}');

					for(var idx in ids) {
						if(ids.hasOwnProperty(idx)) {
							if (null != ids[idx] && ids[idx] > 0) {
								formData.append('context_id[]', ids[idx]);
							}
						}
					}
					
					genericAjaxPost(formData, null, null, function() {
						// Refresh the popup's worklist
						$view.find('table.worklist span.glyphicons-refresh').closest('a').click();
						
						// Tell the parent
						$popup.trigger('links_save');
					});
				})
				.prependTo($worklist_actions)
				;
		}
		
		on_refresh();

		$popup.delegate('DIV[id^=view]','view_refresh', on_refresh);
	});
});
</script>