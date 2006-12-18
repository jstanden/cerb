<input type="hidden" name="c" value="core.module.configuration">
<input type="hidden" name="a" value="saveMailbox">
<input type="hidden" name="id" value="{if !empty($mailbox->id)}{$mailbox->id}{else}0{/if}">
<table cellpadding="2" cellspacing="0" border="0" width="100%" class="configTable">
	<tr>
		<td colspan="2" class="configTableTh">
			{if empty($mailbox->id)}
			Add Mailbox
			{else}
			Modify '{$mailbox->name}'
			{/if}
		</td>
	</tr>
	<tr>
		<td width="0%" nowrap="nowrap"><b>Name:</b></td>
		<td width="100%"><input type="text" name="name" value="{$mailbox->name|escape:"html"}"></td>
	</tr>
	<tr>
		<td width="0%" nowrap="nowrap"><b>Reply As:</b></td>
		<td width="100%"><input type="text" name="reply_as" value="{$reply_address->email|escape:"html"}"></td>
	</tr>
	<tr>
		<td width="0%" nowrap="nowrap" valign="top">
			<b>Teams:</b><br>
			<a href="javascript:;" onclick="checkAll('configMailboxTeams',true);">check all</a><br>
			<a href="javascript:;" onclick="checkAll('configMailboxTeams',false);">check none</a>
		</td>
		<td width="100%" id="configMailboxTeams">
			{if $mailbox->id}{assign var=mailboxTeams value=$mailbox->getTeams()}{/if}
			{foreach from=$teams item=team key=team_id}
			<label><input type="checkbox" name="team_id[]" value="{$team_id}" {if $mailboxTeams.$team_id}checked{/if}>{$team->name}</label><br>
			{/foreach}
		</td>
	</tr>
	{if !empty($mailbox->id)}
	<tr>
		<td width="0%" nowrap="nowrap"><b>Delete:</b></td>
		<td width="100%"><label style="background-color:rgb(255,220,220);"><input type="checkbox" name="delete" value="1"> Delete this mailbox</label></td>
	</tr>
	{/if}
	<tr>
		<td colspan="2">
			<input type="submit" value="{$translate->say('common.save_changes')}">
		</td>
	</tr>
</table>
