<table cellpadding="0" cellspacing="0" border="0" width="98%">
	<tr>
		<td align="left" width="100%" nowrap="nowrap"><h1>Mailbox Routing</h1></td>
		<td align="right" width="0%" nowrap="nowrap"><form><input type="button" value=" X " onclick="configAjax.mailboxRoutingDialog.hide();"></form></td>
	</tr>
</table>
<form action="javascript:;" method="post" id="routingDialog">
<input type="hidden" name="c" value="config">
<input type="hidden" name="a" value="saveMailboxRoutingDialog">
<input type="hidden" name="id" value="{$id}">

<b>Destination Matches:</b> (<a href="javascript:;">Explain</a>)<br>
<input type="text" name="pattern" value="{$route->pattern}" size="45" style="width:98%;"><br>
(use * for wildcards, for example: support@*)<br>
<br>

<b>Send to Mailbox:</b><br>
<select name="mailbox_id">
{if !empty($mailboxes)}
{foreach from=$mailboxes item=mailbox key=mailbox_id}
	<option value="{$mailbox_id}" {if $route->mailbox_id==$mailbox_id}selected{/if}>{$mailbox->name}
{/foreach}
{/if}
</select><br>
<br>

<!-- 
<b>Reorder:</b><br>
<select name="pos">
	<option value="">-- leave unchanged --
	<option value="0">Move to First
	<option value="-1">Move to Last
</select><br>
<br>
-->

<input type="button" value="{$translate->say('common.save_changes')|capitalize}" onclick="configAjax.postShowMailboxRouting('{$id}');">
{if $id}<input type="button" value="{$translate->say('common.remove')|capitalize}" onclick="if(confirm('Are you sure?')) configAjax.deleteMailboxRouting('{$id}');">{/if}
</form>