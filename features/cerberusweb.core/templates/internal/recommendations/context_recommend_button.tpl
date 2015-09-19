{if empty($recommend_btn_domid)}{$recommend_btn_domid = uniqid()}{/if}
{$num_recommendations = $object_recommendations.{$context_id}|count}
{$is_current_worker = isset($object_recommendations.$context_id) && in_array($active_worker->id, $object_recommendations.$context_id)}
<button type="button" id="{$recommend_btn_domid}" class="{if $is_current_worker}green{/if}" title="{'common.recommendations'|devblocks_translate|capitalize}" group_id="{$recommend_group_id}" bucket_id="{$recommend_bucket_id}">
	{if $full}
		<div class="badge-count">{$num_recommendations}</div>
		{'common.recommended'|devblocks_translate|capitalize}
	{else}
		<div class="badge-count">{$num_recommendations}</div>
	{/if}
</button>

<script type="text/javascript">
$(function() {
	var $btn = $('#{$recommend_btn_domid}');
	
	$btn.click(function(e) {
		var group_id = $btn.attr('group_id');
		var bucket_id = $btn.attr('bucket_id');
		
		// Left-click shortcut for toggling current worker
		if(e.shiftKey) {
			genericAjaxGet('', 'c=internal&a=handleSectionAction&section=recommendations&action=toggleCurrentWorkerAsRecommendation&context={$context}&context_id={$context_id}&full={if empty($full)}0{else}1{/if}', function(json) {
				if(undefined != json.count && undefined != json.has_active_worker) {
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
			var $popup = genericAjaxPopup('recommend','c=internal&a=handleSectionAction&section=recommendations&action=showContextRecommendationsPopup&context={$context}&context_id={$context_id}&full={if empty($full)}0{else}1{/if}&group_id=' + group_id + '&bucket_id=' + bucket_id);
			
			$popup.one('recommendations_save', function(e) {
				if(undefined != e.recommendations_count && undefined != e.recommendations_include_worker) {
					$btn.fadeTo('fast', 0.5);
					$btn.find('div.badge-count').text(e.recommendations_count);
					
					if(e.recommendations_include_worker) {
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