{if empty($watcher_btn_domid)}{$watcher_btn_domid = uniqid()}{/if}
{$num_watchers = $object_watchers.{$context_id}|count}
{$is_current_worker = isset($object_watchers.{$context_id}.{$active_worker->id})}
<button type="button" id="{$watcher_btn_domid}" class="{if $is_current_worker}green{/if}" title="{'common.watchers'|devblocks_translate|capitalize}" group_id="{$watcher_group_id}" bucket_id="{$watcher_bucket_id}">
	{if $full}
		<div class="badge-count">{$num_watchers}</div>
		Watching
	{else}
		<div class="badge-count">{$num_watchers}</div>
	{/if}
</button>

<script type="text/javascript">
$(function() {
	var $btn = $('#{$watcher_btn_domid}');
	
	$btn.click(function() {
		var group_id = $btn.attr('group_id');
		var bucket_id = $btn.attr('bucket_id');
		var $popup = genericAjaxPopup('watchers','c=internal&a=handleSectionAction&section=watchers&action=showContextWatchersPopup&context={$context}&context_id={$context_id}&full={if empty($full)}0{else}1{/if}&group_id=' + group_id + '&bucket_id=' + bucket_id);
		
		$popup.one('watchers_save', function(e) {
			if(undefined != e.watchers_count && undefined != e.watchers_include_worker) {
				$btn.fadeTo('fast', 0.5);
				$btn.find('div.badge-count').html(e.watchers_count);
				
				if(e.watchers_include_worker) {
					$btn.addClass('green');
				} else {
					$btn.removeClass('green');
				}
				
				$btn.fadeTo('fast', 1.0);
			}
		});
		
	});
});
</script>