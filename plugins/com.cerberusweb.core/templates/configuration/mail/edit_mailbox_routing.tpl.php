<table cellpadding="0" cellspacing="0" border="0" width="98%">
	<tr>
		<td align="left" width="100%" nowrap="nowrap"><h1>Mailbox Routing</h1></td>
		<td align="right" width="0%" nowrap="nowrap"><form><input type="button" value=" X " onclick="configAjax.mailboxRoutingDialog.hide();"></form></td>
	</tr>
</table>
<form action="javascript:;" method="post" id="routingDialog">
<input type="hidden" name="c" value="{$c}">
<input type="hidden" name="a" value="saveMailboxRoutingDialog">
<input type="hidden" name="id" value="{$id}">
<b>Incoming Address:</b><br>
{if $id}
{$address->email}<br>
{else}
	<div class="automod">
	<div class="autocomplete" style="width:98%;margin:2px;">
	<input type="text" id="routingEntry" name="address" value="" size="45" style="width:98%;" class="autoinput">
	<div id="routingEntryContainer" class="autocontainer"></div>
	</div>
	</div>
{/if}

<br>

<b>Send to Mailbox:</b> 
<select name="mailbox_id">
{if !empty($mailboxes)}
{foreach from=$mailboxes item=mailbox key=mailbox_id}
	<option value="{$mailbox_id}" {if $selected_id==$mailbox_id}selected{/if}>{$mailbox->name}
{/foreach}
{/if}
</select><br>

<br>

<input type="button" value="{$translate->say('common.save_changes')|capitalize}" onclick="configAjax.postShowMailboxRouting('{$id}');">
{if $id}<input type="button" value="{$translate->say('common.remove')|capitalize}" onclick="if(confirm('Are you sure?')) configAjax.deleteMailboxRouting('{$id}');">{/if}
</form>