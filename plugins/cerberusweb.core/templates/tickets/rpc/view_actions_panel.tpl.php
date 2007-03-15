<table cellpadding="0" cellspacing="0" border="0" width="98%">
	<tr>
		<td align="left" width="0%" nowrap="nowrap" style="padding-right:5px;"><img src="{devblocks_url}images/gear.gif{/devblocks_url}" align="absmiddle"></td>
		<td align="left" width="100%" nowrap="nowrap"><h1>My Shortcut Actions</h1></td>
		<td align="right" width="0%" nowrap="nowrap"><form><input type="button" value=" X " onclick="ajax.manageViewActionPanel.hide();"></form></td>
	</tr>
</table>

<form action="{devblocks_url}{/devblocks_url}" method="POST" id="formViewActions" name="formViewActions">
<input type="hidden" name="action_id" value="{$id}">
<input type="hidden" name="view_id" value="{$view_id}">
<input type="hidden" name="c" value="tickets">
<input type="hidden" name="a" value="saveViewActionPanel">
<div style="height:300px;overflow:auto;border:1px solid rgb(180,180,180);margin:2px;padding:3px;">

<table cellspacing="0" cellpadding="2" width="100%">
	<tr>
		<td colspan="2">
			Action Title: <input type="text" size="35" name="title" value="{$action->name|escape:"htmlall"}"><br>
			<!--- 
			Show this action:
				<label><input type="radio" name="scope" value="0" checked> on all views</label> 
				<label><input type="radio" name="scope" value="{$view_id}"> only on this view</label>
			<br>
			--->
			<br>
			<b>Apply these changes to selected tickets:</b>
		</td>
	</tr>
	<tr>
		<td width="0%" nowrap="nowrap">Set status:</td>
		<td width="100%"><select name="status">
			<option value=""></option>
			{foreach from=$statuses item=k key=v}
			<option value="{$v}" {if $v==$action->params.status}selected{/if}>{$k}</option>
			{/foreach}
		</select></td>
	</tr>
	<tr>
		<td width="0%" nowrap="nowrap">Set priority:</td>
		<td width="100%"><select name="priority">
			<option value=""></option>
			{foreach from=$priorities item=k key=v}
			<option value="{$v}" {if $v==$action->params.priority}selected{/if}>{$k}</option>
			{/foreach}
		</select></td>
	</tr>
	<tr>
		<td width="0%" nowrap="nowrap">Set mailbox:</td>
		{* [TODO]: Needs Translations *}
		<td width="100%"><select name="mailbox">
			<option value=""></option>
			{foreach from=$mailboxes item=mailbox}
			<option value="{$mailbox->id}" {if $mailbox->id==$action->params.mailbox}selected{/if}>{$mailbox->name}</option>
			{/foreach}
		</select></td>
	</tr>
	<tr>
		<td width="0%" nowrap="nowrap">Set training:</td>
		<td width="100%"><select name="spam">
			<option value=""></option>
			{foreach from=$training item=k key=v}
			<option value="{$v}" {if $v==$action->params.spam}selected{/if}>{$k}</option>
			{/foreach}
		</select></td>
	</tr>
	<tr>
		<td width="0%" nowrap="nowrap">My tickets:</td>
		<td width="100%"><select name="flag">
			<option value=""></option>
			{foreach from=$flag item=k key=v}
			<option value="{$v}" {if $v==$action->params.flag}selected{/if}>{$k}</option>
			{/foreach}
		</select></td>
	</tr>
	<tr>
		<td width="0%" nowrap="nowrap" valign="top">Apply tags:</td>
		{* [TODO]: Needs Translations *}
		<td width="100%" valign="top">
			<input type="text" name="tags" size="30"> <input type="button" value="..." onclick="">
		</td>
	</tr>
	<tr>
		<td width="0%" nowrap="nowrap" valign="top">Suggest workers:</td>
		{* [TODO]: Needs Translations *}
		<td width="100%" valign="top">
			<input type="text" name="suggest" size="30"> <input type="button" value="..." onclick="">
		</td>
	</tr>
	<tr>
		<td width="0%" nowrap="nowrap" valign="top">Assign workers:</td>
		{* [TODO]: Needs Translations *}
		<td width="100%" valign="top">
			<input type="text" name="assign" size="30"> <input type="button" value="..." onclick="">
		</td>
	</tr>
</table>

</div>

<input type="button" value="{$translate->say('common.save_changes')}" onclick="ajax.saveViewActionPanel('{$id}','{$view_id}');">
{if !empty($id)}<input type="button" value="{$translate->say('common.remove')|capitalize}" onclick="">{/if}
<br>
</form>