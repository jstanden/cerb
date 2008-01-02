<form action="{devblocks_url}{/devblocks_url}" method="post" id="frmDisplayRecipients">
<input type="hidden" name="c" value="display">
<input type="hidden" name="a" value="saveProperties">
<input type="hidden" name="ticket_id" value="{$ticket->id}">

<h2>Properties</h2>
<blockquote style="margin:10px;">
	<b>Next Action:</b><br>
	<input type="text" name="next_action" size="45" maxlength="255" value="{$ticket->next_action|escape:"htmlall"}" style="width:90%;"><br>
	<br>
	
	<b>Next Worker:</b><br> 
	<select name="next_worker_id">
		<option value="0" {if 0==$ticket->next_worker_id}selected{/if}>Anybody
		{foreach from=$workers item=worker key=worker_id name=workers}
			{if $worker_id==$active_worker->id}{assign var=next_worker_id_sel value=$smarty.foreach.workers.iteration}{/if}
			<option value="{$worker_id}" {if $worker_id==$ticket->next_worker_id}selected{/if}>{$worker->getName()}
		{/foreach}
	</select>&nbsp;
   	{if !empty($next_worker_id_sel)}
   		<button type="button" onclick="this.form.next_worker_id.selectedIndex = {$next_worker_id_sel};">me</button>
   		<button type="button" onclick="this.form.next_worker_id.selectedIndex = 0;">anybody</button>
   	{/if}
	<br>
	<br>
	
	<b>Subject:</b><br>
	<input type="text" name="subject" size="45" maxlength="255" value="{$ticket->subject|escape:"htmlall"}" style="width:90%;"><br>
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