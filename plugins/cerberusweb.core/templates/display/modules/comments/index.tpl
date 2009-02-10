<h2>Add Comment</h2>
<form action="{devblocks_url}{/devblocks_url}" method="post" id="displayAddCommentForm">
<input type="hidden" name="c" value="display">
<input type="hidden" name="a" value="saveComment">
<input type="hidden" name="ticket_id" value="{$ticket_id}">

<b>Author:</b> {$active_worker->getName()}<br>
<textarea name="comment" rows="5" cols="60" style="width:98%;"></textarea><br>
<br>

{if !empty($workers)}
{assign var=owner_id value=$ticket->next_worker_id}
<label><input type="checkbox" onclick="toggleDiv('addCommentNotifyWorkers');"> <b>Notify workers</b></label>
<div id="addCommentNotifyWorkers" style="display:none;">
<select name="notify_worker_ids[]" multiple="multiple" size="8" id="notify_worker_ids">
	{foreach from=$active_workers item=worker name=notify_workers}
	{if $owner_id && $worker->id == $owner_id}{math assign=notify_owner_id equation="x-1" x=$smarty.foreach.notify_workers.iteration}{/if}
	{if $worker->id == $active_worker->id}{math assign=notify_me_id equation="x-1" x=$smarty.foreach.notify_workers.iteration}{/if}
	<option value="{$worker->id}">{$worker->getName()}</option>
	{/foreach}
</select><br>
(hold CTRL or CMD to select multiple)<br>
{if !empty($notify_me_id)}<button type="button" onclick="document.getElementById('notify_worker_ids').options[{$notify_me_id}].selected=true;">{$translate->_('common.me')}</button>{/if} 
{if !empty($owner_id) || isset($notify_owner_id)}<button type="button" onclick="document.getElementById('notify_worker_ids').options[{$notify_owner_id}].selected=true;">{$workers.$owner_id->getName()} (owner)</button>{/if}
</div>
{/if}
<br>
<br>

<button type="submit"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/check.gif{/devblocks_url}" align="top"> {$translate->_('common.save_changes')|capitalize}</button>
</form>
<br>

{if !empty($comments)}
	{foreach from=$comments item=comment key=comment_id}
		{include file="$core_tpl/display/modules/conversation/comment.tpl"}
	{/foreach}
{/if}

<br>