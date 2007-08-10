<table cellpadding="0" cellspacing="0" border="0" width="98%">
	<tr>
		<td align="left" width="0%" nowrap="nowrap" style="padding-right:5px;"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/link_add.gif{/devblocks_url}" align="absmiddle"></td>
		<td align="left" width="100%" nowrap="nowrap"><h1>Fetch &amp; Retrieve</h1></td>
		<td align="right" width="0%" nowrap="nowrap"><form><input type="button" value=" X " onclick="genericPanel.hide();"></form></td>
	</tr>
</table>

<form action="{devblocks_url}{/devblocks_url}" method="POST" id="formBatchUpdate" name="formBatchUpdate">
<!-- <input type="hidden" name="action_id" value="{$id}"> -->
<input type="hidden" name="c" value="tickets">
<input type="hidden" name="a" value="doBatchUpdate">
<input type="hidden" name="view_id" value="{$view_id}">
<input type="hidden" name="ticket_ids" value="">

<div style="height:400px;overflow:auto;border:1px solid rgb(180,180,180);margin:2px;padding:3px;background-color:rgb(255,255,255);">
</div>

<input type="button" value="{$translate->_('common.save_changes')}" onclick="ajax.s('{$view_id}');">
<br>
</form>