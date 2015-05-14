{$num_watchers = $object_watchers.{$context_id}|count}
{$is_current_worker = isset($object_watchers.{$context_id}.{$active_worker->id})}
<button type="button" class="{if $is_current_worker}green{/if}" onclick="genericAjaxGet($(this).parent(),'c=internal&a=toggleContextWatcher&context={$context}&context_id={$context_id}&follow={if $is_current_worker}0{else}1{/if}&full={if empty($full)}0{else}1{/if}');" title="{if $is_current_worker}Stop watching{else}Start watching{/if}">
	{if $full}
		<div class="badge-count">{$num_watchers}</div>
		Watching
	{else}
		<div class="badge-count">{$num_watchers}</div>
	{/if}
</button>
