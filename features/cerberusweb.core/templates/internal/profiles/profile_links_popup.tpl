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
			$worklist.css('background-color','rgb(100,100,100)');
			
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
					
					var $data = [ 
						'c=internal',
						'a=contextDeleteLinksJson',
						'from_context={$from_context_extension->id}',
						'from_context_id={$from_context_id}', 
						'context={$to_context_extension->id}'
					];
					
					for(idx in ids) {
						if(null != ids[idx] && ids[idx] > 0) {
							$data.push('context_id[]='+ids[idx]);
						}
					}
					
					var options = { };
					options.type = 'POST';
					options.async = false;
					options.data = $data.join('&');
					options.url = DevblocksAppPath+'ajax.php',
					options.cache = false;
					options.success = function(json) {
						// Refresh the popup's worklist
						$view.find('table.worklist span.glyphicons-refresh').closest('a').click();
						
						// Tell the parent
						$popup.trigger('links_save');
					};
					
					if(null == options.headers)
						options.headers = {};
				
					options.headers['X-CSRF-Token'] = $('meta[name="_csrf_token"]').attr('content');
					
					$.ajax(options);
					
				})
				.prependTo($worklist_actions)
				;
		}
		
		on_refresh();

		$popup.delegate('DIV[id^=view]','view_refresh', on_refresh);
	});
});
</script>