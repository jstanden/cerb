<input type="hidden" name="c" value="config">
<input type="hidden" name="a" value="saveTeam">
<input type="hidden" name="id" value="{$team->id}">
<table cellpadding="2" cellspacing="0" border="0" width="100%" class="configTable">
	<tr>
		<td colspan="2" class="configTableTh">
			{if empty($team->id)}
			Add Team
			{else}
			Modify '{$team->name}'
			{/if}
		</td>
	</tr>
	<tr>
		<td width="0%" nowrap="nowrap" valign="top"><b>Name:</b></td>
		<td width="100%"><input type="text" name="name" value="{$team->name|escape:"html"}"></td>
	</tr>
	<tr>
		<td width="0%" nowrap="nowrap" valign="top">
			<b>Workers:</b><br>
			<a href="javascript:;" onclick="checkAll('configTeamWorkers',true);">check all</a><br>
			<a href="javascript:;" onclick="checkAll('configTeamWorkers',false);">check none</a>
		</td>
		<td width="100%" id="configTeamWorkers" valign="top">
			{if $team->id}{assign var=teamWorkers value=$team->getWorkers()}{/if}
			{foreach from=$workers item=worker key=worker_id}
			<label><input type="checkbox" name="agent_id[]" value="{$worker_id}" {if $teamWorkers.$worker_id}checked{/if}>{$worker->login}</label><br>
			{/foreach}
		</td>
	</tr>
	<tr>
		<td width="0%" nowrap="nowrap" valign="top">
			<b>Mailboxes:</b><br>
			<a href="javascript:;" onclick="checkAll('configTeamMailboxes',true);">check all</a><br>
			<a href="javascript:;" onclick="checkAll('configTeamMailboxes',false);">check none</a>
		</td>
		<td width="100%" id="configTeamMailboxes" valign="top">
			{if $team->id}{assign var=teamMailboxes value=$team->getMailboxes()}{/if}
			{foreach from=$mailboxes item=mailbox key=mailbox_id}
			<label><input type="checkbox" name="mailbox_id[]" value="{$mailbox_id}" {if $teamMailboxes.$mailbox_id}checked{/if}>{$mailbox->name}</label><br>
			{/foreach}
		</td>
	</tr>
	<tr>
		<td width="0%" nowrap="nowrap" valign="top">
			<b>Permissions:</b><br>
			<a href="javascript:;" onclick="checkAll('configTeamAcl',true);">check all</a><br>
			<a href="javascript:;" onclick="checkAll('configTeamAcl',false);">check none</a>
		</td>
		<td width="100%" id="configTeamAcl" valign="top">
			<label><input type="checkbox" name="acl[]" value="">Can ...</label><br>
		</td>
	</tr>
	{if !empty($team->id)}
	<tr>
		<td width="0%" nowrap="nowrap"><b>Delete:</b></td>
		<td width="100%"><label style="background-color:rgb(255,220,220);"><input type="checkbox" name="delete" value="1"> Delete this team</label></td>
	</tr>
	{/if}
	<tr>
		<td colspan="2">
			<input type="submit" value="{$translate->say('common.save_changes')}">
		</td>
	</tr>
</table>
