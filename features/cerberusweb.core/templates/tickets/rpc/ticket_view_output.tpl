{if !empty($last_action)}
<div id="{$view->id}_output">
	<div class="cerb-alert cerb-alert-rounded">
		<div class="cerb-alert-close">
			<span data-cerb-link="worklist_dismiss" class="cerb-icons cerb-icon-circle-remove"></span>
		</div>
	
		<span class="cerb-icons cerb-icon-circle-info" style="vertical-align:baseline;"></span>
		
		{$last_action_count} ticket{if $last_action_count!=1}s{/if} 
	
		{if $last_action->action == 'spam'}
			marked spam.
		{elseif $last_action->action == 'not_spam'}
			marked not spam.
		{elseif $last_action->action == 'delete'}
			deleted.
		{elseif $last_action->action == 'close'}
			closed.
		{elseif $last_action->action == 'waiting'}
			marked waiting for reply.
		{elseif $last_action->action == 'not_waiting'}
			marked not waiting for reply.
		{elseif $last_action->action == 'move'}
			{assign var=moved_to_group_id value=$last_action->action_params.group_id}
			{assign var=moved_to_bucket_id value=$last_action->action_params.bucket_id}
	
			moved to 
			{if empty($moved_to_bucket_id)}
				'{$groups.$moved_to_group_id->name}'.
			{else}
				{assign var=moved_group_bucket value=$group_buckets.$moved_to_group_id}
				'{$groups.$moved_to_group_id->name}: {$moved_group_bucket.$moved_to_bucket_id->name}'.
			{/if}
		{/if}

		(<a data-cerb-link="worklist_undo"><b>undo</b></a>)
	</div>
</div>
<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	let $output = $('#{$view->id}_output');

	$output.find('[data-cerb-link=worklist_dismiss]').on('click', function(e) {
		e.stopPropagation();
		$('#{$view->id}_output').html('');
		ajax.viewUndo('{$view->id}', true);
	});

	$output.find('[data-cerb-link=worklist_undo]').on('click', function(e) {
		e.stopPropagation();
		ajax.viewUndo('{$view->id}', false);
	});
});
</script>
{/if}
