<form id="reply{$message->id}_form" action="{devblocks_url}{/devblocks_url}" method="POST" enctype="multipart/form-data">
<input type="hidden" name="c" value="display">
<input type="hidden" name="a" value="doAddNote">
<input type="hidden" name="id" value="{$message->id}">
<input type="hidden" name="ticket_id" value="{$message->ticket_id}">
<div class="block" style="width:98%;margin:10px;">
<table cellpadding="2" cellspacing="0" border="0" width="100%">
	<tr>
		<td><h2>Add Sticky Note</h2></td>
	</tr>
	<tr>
		<td><b>Author:</b> {$worker->getName()}</td>
	</tr>
	<tr>
		<td>
			<textarea name="content" rows="8" cols="80" id="note_content" class="reply" style="width:98%;border:1px solid rgb(180,180,180);padding:5px;"></textarea>
		</td>
	</tr>
	{if !empty($workers)}
	{assign var=owner_id value=$ticket->next_worker_id}
	<tr>
		<td>
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
			<br>
			</div>
			<br>
			<br>
		</td>
	</tr>
	{/if}
	<tr>
		<td nowrap="nowrap" valign="top">
			<button type="button" onclick="genericAjaxPost('reply{$message->id}_form','{$message->id}notes','c=display&a=doAddNote');clearDiv('reply{$message->id}');"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/check.gif{/devblocks_url}" align="top"> Add Note</button>
			<button type="button" onclick="clearDiv('reply{$message->id}');"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/delete.gif{/devblocks_url}" align="top"> Cancel</button>
		</td>
	</tr>
</table>
</div>
</form>