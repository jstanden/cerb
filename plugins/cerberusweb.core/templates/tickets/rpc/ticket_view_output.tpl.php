{if !empty($last_action)}
<div id="{$view->id}_output" style="margin:10px;padding:5px;border:1px solid rgb(200,200,200);background-color:rgb(250,250,150);">
		<img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/information.gif{/devblocks_url}" align="absmiddle"> 
	
		{$last_action_count} ticket{if $last_action_count!=1}s{/if} 
	
		{if $last_action->action == 'spam'}
			marked spam.
		{elseif $last_action->action == 'not_spam'}
			marked not spam.
		{elseif $last_action->action == 'delete'}
			deleted.
		{elseif $last_action->action == 'surrender'}
			surrendered.
		{elseif $last_action->action == 'close'}
			closed.
		{elseif $last_action->action == 'move'}
			{assign var=moved_to_team_id value=$last_action->action_params.team_id}
			{assign var=moved_to_category_id value=$last_action->action_params.category_id}
	
			moved to 
			{if empty($moved_to_category_id)}
				'{$teams.$moved_to_team_id->name}'.
			{else}
				{assign var=moved_team_category value=$team_categories.$moved_to_team_id}
				'{$teams.$moved_to_team_id->name}: {$moved_team_category.$moved_to_category_id->name}'.
			{/if}
		{/if}
		
		 ( 
		 <a href="javascript:;" onclick="ajax.viewUndo('{$view->id}');" style="font-weight:bold;">Undo</a> 
		  | 
		 <a href="javascript:;" onclick="toggleDiv('{$view->id}_output','none');genericAjaxGet('','c=tickets&a=viewUndo&view_id={$view->id}&clear=1');" style="">Dismiss</a>
		  )  
</div>
{/if}
