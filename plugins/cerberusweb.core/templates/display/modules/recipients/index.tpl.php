<br>
<h3>These people will receive copies of all ticket correspondence:</h3>
(select contacts to remove them from ticket updates)<br>

<form action="{devblocks_url}{/devblocks_url}" method="post" id="frmDisplayRecipients">
<input type="hidden" name="c" value="display">
<input type="hidden" name="a" value="saveManageRecipients">
<input type="hidden" name="ticket_id" value="{$ticket_id}">

<blockquote style="margin:10px;">
	{foreach from=$requesters item=requester}
		<label><input type="checkbox" name="remove[]" value="{$requester->id}"> {$requester->email}</label><br>
	{/foreach}
	
	<br>
	<b>Add more recipients:</b> (one e-mail address per line)<br>
	<textarea rows="3" cols="50" name="add"></textarea><br>
	
	<br>
	<button type="button" onclick="genericAjaxPost('frmDisplayRecipients','displayAdvancedOptions','c=display&a=saveManageRecipients');"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/check.gif{/devblocks_url}" align="top"> {$translate->_('common.save_changes')|capitalize}</button>
	<button type="button" onclick="clearDiv('displayAdvancedOptions');"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/delete.gif{/devblocks_url}" align="top"> {$translate->_('common.cancel')|capitalize}</button>
</blockquote>

</form>