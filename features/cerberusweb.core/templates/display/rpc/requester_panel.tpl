<table cellpadding="0" cellspacing="0" border="0" width="98%">
	<tr>
		<td align="left" width="0%" nowrap="nowrap" style="padding-right:5px;"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/businessmen.gif{/devblocks_url}" align="absmiddle"></td>
		<td align="left" width="100%" nowrap="nowrap"><h1>Ticket Requesters</h1></td>
	</tr>
</table>

<form action="{devblocks_url}{/devblocks_url}" method="POST" id="formDisplayReq" name="formDisplayReq">
<input type="hidden" name="c" value="display">
<input type="hidden" name="a" value="saveRequestersPanel">
<input type="hidden" name="ticket_id" value="{$ticket_id}">
<input type="hidden" name="msg_id" value="{$msg_id}">

{if !empty($requesters)}
<b>Remove checked:</b><br>
{foreach from=$requesters item=requester}
	<label><input type="checkbox" name="req_deletes[]" value="{$requester->id}"> {$requester->email}</label>
	<br>
{/foreach}
<br>
{/if}

<b>Add new requesters:</b> (one e-mail per line)<br>
<textarea name="req_adds" rows="4" cols="35" style="width:98%;"></textarea><br>
<br>

<button type="button" onclick="displayAjax.saveRequesterPanel('formDisplayReq','displayRequesters{$msg_id}');"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/check.gif{/devblocks_url}" align="top"> {$translate->_('common.save_changes')|capitalize}</button>
<button type="button" onclick="genericPanel.dialog('close');"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/delete.gif{/devblocks_url}" align="top"> {$translate->_('common.cancel')|capitalize}</button>
<br>
</form>