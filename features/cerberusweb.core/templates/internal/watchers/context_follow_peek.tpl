{$div_uniqid = uniqid()}

<div style="margin-bottom:5px;">
	{$context_ext->manifest->name}: <a href="{devblocks_url}c=profiles&what={$context_ext->manifest->params.alias}&id={$context_values.id}{/devblocks_url}-{$context_values._label|devblocks_permalink}" style="font-weight:bold;">{$context_values._label}</a>
</div>

<form id="{$div_uniqid}" style="margin-bottom:10px;" action="{devblocks_url}{/devblocks_url}" method="post">
	<input type="hidden" name="c" value="internal">
	<input type="hidden" name="a" value="invoke">
	<input type="hidden" name="module" value="watchers">
	<input type="hidden" name="action" value="saveContextWatchersPopupJson">
	<input type="hidden" name="context" value="{$context}">
	<input type="hidden" name="context_id" value="{$context_id}">
	<input type="hidden" name="group_id" value="{$group_id}">
	<input type="hidden" name="bucket_id" value="{$bucket_id}">
	<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">
	
	<div style="margin-bottom:10px;">
	{include file="devblocks:cerberusweb.core::internal/workers/worker_picker_container.tpl" context=$context context_id=$context_id}
	</div>
	
	<button type="button" class="submit"><span class="glyphicons glyphicons-circle-ok" style="color:rgb(0,180,0);"></span> {'common.save_changes'|devblocks_translate}</button>
</form>

<script type="text/javascript">
$(function() {
	var $frm = $('#{$div_uniqid}');
	var $popup = genericAjaxPopupFind('#{$div_uniqid}');
	
	$popup.one('popup_open', function(event,ui) {
		$popup.dialog('option','title',"{'common.watchers'|devblocks_translate|capitalize|escape:'javascript' nofilter}");
		
		var $btn = $popup.find('button.submit');
		
		$btn.click(function() {
			
			genericAjaxPost($frm, '', null, function(json) {
				
				// Trigger event
				if(undefined != json.count && undefined != json.has_active_worker) {
					var event = jQuery.Event('watchers_save');
					event.watchers_count = json.count;
					event.watchers_include_worker = json.has_active_worker;
					$popup.trigger(event);
				}
				
				genericAjaxPopupClose($popup);
			});
		});
		
	});
});
</script>
