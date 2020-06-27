<div class="worklist-bulk-progress" style="font-size:18px;font-weight:bold;text-align:center;padding:10px;margin:10px;">
	<div>
		Bulk updated <span class="at">0</span> of <span class="total">{$total}</span>...
	</div>
	{include file="devblocks:cerberusweb.core::ui/spinner.tpl"}
</div>

<script type="text/javascript">
$(function() {
	var $view = $('#view{$view_id}');
	var $progress = $view.find('div.worklist-bulk-progress');
	var $at = $progress.find('span.at');
	
	var at = 0;
	
	$('#viewForm{$view_id}').hide().remove();
	
	var nextCursor = function() {
		var formData = new FormData();
		formData.set('c', 'internal');
		formData.set('a', 'invoke');
		formData.set('module', 'worklists');
		formData.set('action', 'viewBulkUpdateNextCursorJson');
		formData.set('cursor', '{$cursor}');
		formData.set('view_id', '{$view_id}');

		genericAjaxPost(formData, '', '', function(json) {
			if(json.completed) {
				genericAjaxGet('view{$view_id}', 'c=internal&a=invoke&module=worklists&action=refresh&id={$view_id}');
				
			} else if(json.count) {
				at += json.count;
				$at.text(at);
				
				setTimeout(nextCursor, 100);
			} else {
				// [TODO] Error
			}
			
		});
	};
	
	nextCursor();
});
</script>