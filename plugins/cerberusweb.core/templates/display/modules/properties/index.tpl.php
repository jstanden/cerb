<form action="{devblocks_url}{/devblocks_url}" method="post" id="frmDisplayRecipients">
<input type="hidden" name="c" value="display">
<input type="hidden" name="a" value="saveProperties">
<input type="hidden" name="ticket_id" value="{$ticket->id}">

<h2>Properties</h2>
<blockquote style="margin:10px;">
	<b>Subject:</b><br>
	<input type="text" name="subject" size="45" maxlength="255" value="{$ticket->subject|escape}" style="width:90%;"><br>
	<br>
	
	<b>{$translate->_('ticket.status')|capitalize}:</b><br>
	<label><input type="radio" name="closed" value="0" onclick="toggleDiv('ticketClosed','none');" {if !$ticket->is_closed && !$ticket->is_waiting}checked{/if}>{$translate->_('status.open')|capitalize}</label>
	<label><input type="radio" name="closed" value="2" onclick="toggleDiv('ticketClosed','block');" {if !$ticket->is_closed && $ticket->is_waiting}checked{/if}>{$translate->_('status.waiting')|capitalize}</label>
	<label><input type="radio" name="closed" value="1" onclick="toggleDiv('ticketClosed','block');" {if $ticket->is_closed && !$ticket->is_deleted}checked{/if}>{$translate->_('status.closed')|capitalize}</label>
	<label><input type="radio" name="closed" value="3" onclick="toggleDiv('ticketClosed','none');" {if $ticket->is_deleted}checked{/if}>{$translate->_('status.deleted')|capitalize}</label>
	<br>
	<br>
	
	<div id="ticketClosed" style="display:{if $ticket->is_waiting || ($ticket->is_closed && !$ticket->is_deleted)}block{else}none{/if};margin-left:10px;">
	<b>{$translate->_('display.reply.next.resume')}</b> {$translate->_('display.reply.next.resume_eg')}<br> 
	<input type="text" name="ticket_reopen" size="55" value="{if !empty($ticket->due_date)}{$ticket->due_date|devblocks_date}{/if}"><br>
	{$translate->_('display.reply.next.resume_blank')}<br>
	<br>
	</div>
	
	<b>Who should handle the next reply?</b><br> 
	<select name="next_worker_id" onchange="toggleDiv('ticketPropsUnlockDate',this.selectedIndex?'block':'none');">
		<option value="0" {if 0==$ticket->next_worker_id}selected{/if}>Anybody
		{foreach from=$workers item=worker key=worker_id name=workers}
			{if $worker_id==$active_worker->id}{assign var=next_worker_id_sel value=$smarty.foreach.workers.iteration}{/if}
			<option value="{$worker_id}" {if $worker_id==$ticket->next_worker_id}selected{/if}>{$worker->getName()}
		{/foreach}
	</select>&nbsp;
   	{if !empty($next_worker_id_sel)}
   		<button type="button" onclick="this.form.next_worker_id.selectedIndex = {$next_worker_id_sel};toggleDiv('ticketPropsUnlockDate','block');">me</button>
   		<button type="button" onclick="this.form.next_worker_id.selectedIndex = 0;toggleDiv('ticketPropsUnlockDate','none');">anybody</button>
   	{/if}
	<br>
	<br>
	
	<div id="ticketPropsUnlockDate" style="display:{if $ticket->next_worker_id}block{else}none{/if};margin-left:10px;">	
		<b>Allow anybody to handle the next reply after:</b> (e.g. "2 hours", "5pm", {*"Tuesday", "June 30", *}or leave blank to keep assigned)<br>  
		<input type="text" name="unlock_date" size="32" maxlength="255" value="{if $ticket->unlock_date}{$ticket->unlock_date|devblocks_date}{/if}">
		<button type="button" onclick="this.form.unlock_date.value='+2 hours';">+2 hours</button>
		<br>
		<br>
	</div>
		
	<b>Next Action:</b><br>
	<input type="text" name="next_action" size="45" maxlength="255" value="{$ticket->next_action|escape}" style="width:90%;"><br>
	<br>
	
</blockquote>

<h2>Send responses to:</h2>
<blockquote style="margin:10px;">
	{if !empty($requesters)}
		<table cellpadding="2" cellspacing="0" border="0">
		<tr>
			<td><b>E-mail</b></td>
			<td align="center"><b>{$translate->_('common.delete')|capitalize}</b></td>
		</tr>
		{foreach from=$requesters item=requester}
			<tr>
				<td align="left">{$requester->email}</td>
				<td align="center"><input type="checkbox" name="remove[]" value="{$requester->id}"></td>
			</tr>
		{/foreach}
		</table>
		<br>
	{/if}
	
	<b>Add more recipients:</b> (one e-mail address per line)<br>
	<textarea rows="3" cols="50" name="add"></textarea><br>
	
	<br>
	<button type="submit"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/check.gif{/devblocks_url}" align="top"> {$translate->_('common.save_changes')|capitalize}</button>
</blockquote>

</form>