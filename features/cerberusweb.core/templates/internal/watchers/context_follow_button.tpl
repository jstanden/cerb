{$num_watchers = $object_watchers.{$context_id}|count}
{$is_current_worker = isset($object_watchers.{$context_id}.{$active_worker->id})}
<button type="button" class="{if $is_current_worker}{else}green{/if}" onclick="genericAjaxGet($(this).parent(),'c=internal&a=toggleContextWatcher&context={$context}&context_id={$context_id}&follow={if $is_current_worker}0{else}1{/if}');" title="{if $num_watchers}{foreach from=$object_watchers.{$context_id} key=worker_id item=worker name=workers}{if isset($workers.{$worker_id})}{$workers.{$worker_id}->getName()}{if !$smarty.foreach.workers.last}, {/if}{/if}{/foreach}{/if}">
	{if $is_current_worker}
	<span class="cerb-sprite2 sprite-minus-circle-frame"></span>
	{else}
	<span class="cerb-sprite2 sprite-plus-circle-frame"></span>
	{/if}
	{$num_watchers}{* &#x25be;*} 
</button>
