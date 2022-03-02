{if empty($watchers_btn_domid)}{$watchers_btn_domid = uniqid()}{/if}
{$num_watchers = 0}
{if is_array($object_watchers) && array_key_exists($context_id, $object_watchers)}{$num_watchers = $object_watchers.$context_id|count}{/if}
{$is_current_worker = is_array($object_watchers) && array_key_exists($context_id, $object_watchers) && array_key_exists($active_worker->id, $object_watchers.$context_id)}
{if !isset($watchers_group_id)}{$watchers_group_id = null}{/if}
{if !isset($watchers_bucket_id)}{$watchers_bucket_id = null}{/if}

<button type="button" id="{$watchers_btn_domid}" class="{if $is_current_worker}green{/if}" data-group-id="{$watchers_group_id}" data-bucket-id="{$watchers_bucket_id}">
	{if isset($full_label) && $full_label}
		<div class="badge-count">{$num_watchers}</div>
		{'common.watching'|devblocks_translate|capitalize}
	{else}
		<div class="badge-count">{$num_watchers}</div>
	{/if}
</button>

<script type="text/javascript">
$(function() {
	var $btn = $('#{$watchers_btn_domid}');
	
	$btn.click(function(e) {
		var group_id = $btn.attr('data-group-id');
		var bucket_id = $btn.attr('data-bucket-id');
		
		// Left-click shortcut for toggling current worker
		if(e.shiftKey) {
			var formData = new FormData();
			formData.set('c', 'internal');
			formData.set('a', 'invoke');
			formData.set('module', 'watchers');
			formData.set('action', 'toggleCurrentWorkerAsWatcher');
			formData.set('context', '{$context}');
			formData.set('context_id', '{$context_id}');
			formData.set('full', '{if empty($full)}0{else}1{/if}');

			genericAjaxPost(formData, '', '', function(json) {
				if(undefined !== json.count && undefined !== json.has_active_worker) {
					$btn.fadeTo('fast', 0.5);
					$btn.find('div.badge-count').text(json.count);
					
					if(json.has_active_worker) {
						$btn.addClass('green');
					} else {
						$btn.removeClass('green');
					}
					
					$btn.fadeTo('fast', 1.0);
				}
			});
			
		} else {
			var $popup = genericAjaxPopup('watchers','c=internal&a=invoke&module=watchers&action=showContextWatchersPopup&context={$context}&context_id={$context_id}&full={if empty($full)}0{else}1{/if}&group_id=' + group_id + '&bucket_id=' + bucket_id);
			
			$popup.one('watchers_save', function(e) {
				if(undefined !== e.watchers_count && undefined !== e.watchers_include_worker) {
					$btn.fadeTo('fast', 0.5);
					$btn.find('div.badge-count').text(e.watchers_count);
					
					if(e.watchers_include_worker) {
						$btn.addClass('green');
					} else {
						$btn.removeClass('green');
					}
					
					$btn.fadeTo('fast', 1.0);
				}
			});
		}
		
	});
});
</script>