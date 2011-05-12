{$expand_btnid = microtime()|md5}
{$num_watchers = $object_watchers.{$context_id}|count}
{$is_current_worker = isset($object_watchers.{$context_id}.{$active_worker->id})}
<button type="button" class="{if $is_current_worker}{else}green{/if} {if $full}split-left{/if}" onclick="genericAjaxGet($(this).parent(),'c=internal&a=toggleContextWatcher&context={$context}&context_id={$context_id}&follow={if $is_current_worker}0{else}1{/if}&full={if empty($full)}0{else}1{/if}');">
	{if $is_current_worker}
	<span class="cerb-sprite2 sprite-minus-circle-frame"></span>
	{else}
	<span class="cerb-sprite2 sprite-plus-circle-frame"></span>
	{/if}
	{if $full}
		{if $is_current_worker}Stop watching{else}Start watching{/if} 
		({$num_watchers})
	{else}
		{$num_watchers}
	{/if}
</button><!--
--><button type="button" class="{if $full}split-right{/if}" id="{$expand_btnid}" {if !$full}style="display:none;"{/if}><span class="cerb-label" style="display:inline-block;height:16px;">&#x25be;</span></button>
{if empty($workers)}{$workers = DAO_Worker::getAllActive()}{/if}

<script type="text/javascript">
{if !$full}
$('#{$expand_btnid}').parent().hover(
		function(e) {
			$('#{$expand_btnid}').prev('button').addClass('split-left');
			$('#{$expand_btnid}').css('display','inline').addClass('split-right');
		},
		function(e) {
			$('#{$expand_btnid}').prev('button').removeClass('split-left');
			$('#{$expand_btnid}').css('display','none').removeClass('split-right');
		}
	)
	;
{/if}
$('#{$expand_btnid}').click(function(e) {
	$popup=genericAjaxPopup('watchers','c=internal&a=showContextWatchers&context={$context}&context_id={$context_id}',this,false,'500');
	$popup.one('watchers_save', function(event) {
		if(0 == event.add_worker_ids.length && 0 == event.delete_worker_ids.length)
			return;
		add_worker_ids = event.add_worker_ids.join(',');
		delete_worker_ids = event.delete_worker_ids.join(',');
		genericAjaxGet($('#{$expand_btnid}').parent(),'c=internal&a=addContextWatchers&context={$context}&context_id={$context_id}&add_worker_ids=' + add_worker_ids + '&delete_worker_ids=' + delete_worker_ids + '&full={if $full}1{else}0{/if}');
	});
});
</script>

