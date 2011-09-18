{if !empty($last_action)}
<div id="{$view->id}_output" class="ui-widget">
	<div class="ui-state-highlight ui-corner-all" style="margin: 0 0 .5em 0; padding: 0 .7em;"> 
		<p><span class="ui-icon ui-icon-info" style="float: left; margin-right: .3em;"></span>
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
		
		 ( 
		 <a href="javascript:;" onclick="ajax.viewUndo('{$view->id}');" style="font-weight:bold;">Undo</a> 
		  | 
		 <a href="javascript:;" onclick="$('#{$view->id}_output').html('');genericAjaxGet('','c=tickets&a=viewUndo&view_id={$view->id}&clear=1');" style="">Dismiss</a>
		  )
	</div>
</div>
{/if}
