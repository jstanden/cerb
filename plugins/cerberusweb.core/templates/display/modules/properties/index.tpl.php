<form action="{devblocks_url}{/devblocks_url}" method="post" id="frmDisplayRecipients">
<input type="hidden" name="c" value="display">
<input type="hidden" name="a" value="saveProperties">
<input type="hidden" name="ticket_id" value="{$ticket->id}">

<div class="block">
<h2>Properties</h2>
<blockquote style="margin:10px;">
	<b>Next Action:</b><br>
	<input type="text" name="next_action" size="45" maxlength="255" value="{$ticket->next_action|escape:"htmlall"}" style="width:90%;"><br>
	<br>
	
	<b>Next Worker:</b><br> 
	<select name="next_worker_id">
		<option value="0" {if 0==$ticket->next_worker_id}selected{/if}>Anybody
		{foreach from=$workers item=worker key=worker_id}
			<option value="{$worker_id}" {if $worker_id==$ticket->next_worker_id}selected{/if}>{$worker->getName()}
		{/foreach}
	</select><br>
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
</div>

</form>