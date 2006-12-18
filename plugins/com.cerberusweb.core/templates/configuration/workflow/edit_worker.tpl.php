<input type="hidden" name="c" value="core.module.configuration">
<input type="hidden" name="a" value="saveWorker">
<input type="hidden" name="id" value="{if !empty($worker->id)}{$worker->id}{else}0{/if}">
<table cellpadding="2" cellspacing="0" border="0" width="100%" class="configTable">
	<tr>
		<td colspan="2" class="configTableTh">
			{if empty($worker->id)}
			Add Worker
			{else}
			Modify '{$worker->login}'
			{/if}
		</td>
	</tr>
	<tr>
		<td width="0%" nowrap="nowrap"><b>Full Name:</b></td>
		<td width="100%"><input type="text" name="" value=""></td>
	</tr>
	<tr>
		<td width="0%" nowrap="nowrap"><b>Primary Email:</b></td>
		<td width="100%"><input type="text" name="" value=""></td>
	</tr>
	<tr>
		<td width="0%" nowrap="nowrap"><b>Login:</b></td>
		<td width="100%"><input type="text" name="login" value="{$worker->login|escape:"html"}"></td>
	</tr>
	<tr>
		<td width="0%" nowrap="nowrap"><b>Password:</b></td>
		<td width="100%"><input type="password" name="password" value=""></td>
	</tr>
	<tr>
		<td width="0%" nowrap="nowrap">Password (again):</td>
		<td width="100%"><input type="password" name="" value=""></td>
	</tr>
	<tr>
		<td width="0%" nowrap="nowrap" valign="top">
			<b>Teams:</b><br>
			<a href="javascript:;" onclick="checkAll('configWorkerTeams',true);">check all</a><br>
			<a href="javascript:;" onclick="checkAll('configWorkerTeams',false);">check none</a>
		</td>
		<td width="100%" id="configWorkerTeams" valign="top">
			{if $worker->id}{assign var=workerTeams value=$worker->getTeams()}{/if}
			{foreach from=$teams item=team key=team_id}
			<label><input type="checkbox" name="team_id[]" value="{$team->id}" {if $workerTeams.$team_id}checked{/if}>{$team->name}</label><br>
			{/foreach}
		</td>
	</tr>
	{if !empty($worker->id)}
	<tr>
		<td width="0%" nowrap="nowrap"><b>Delete:</b></td>
		<td width="100%"><label style="background-color:rgb(255,220,220);"><input type="checkbox" name="delete" value="1"> Delete this worker</label></td>
	</tr>
	{/if}
	<tr>
		<td colspan="2">
			<input type="submit" value="{$translate->say('common.save_changes')}">
		</td>
	</tr>
</table>
