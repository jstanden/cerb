<input type="hidden" name="c" value="config">
<input type="hidden" name="a" value="saveWorker">
<input type="hidden" name="id" value="{if !empty($worker->id)}{$worker->id}{else}0{/if}">
<div class="block">
<table cellpadding="2" cellspacing="0" border="0">
	<tr>
		<td colspan="2">
			{if empty($worker->id)}
			<h2>Add Worker</h2>
			{else}
			<h2>Modify '{$worker->getName()}'</h2>
			{/if}
		</td>
	</tr>
	<tr>
		<td width="0%" nowrap="nowrap"><b>First Name:</b></td>
		<td width="100%"><input type="text" name="first_name" value="{$worker->first_name}"></td>
	</tr>
	<tr>
		<td width="0%" nowrap="nowrap">Last Name:</td>
		<td width="100%"><input type="text" name="last_name" value="{$worker->last_name}"></td>
	</tr>
	<tr>
		<td width="0%" nowrap="nowrap">Title:</td>
		<td width="100%"><input type="text" name="title" value="{$worker->title}"></td>
	</tr>
	<tr>
		<td width="0%" nowrap="nowrap">Administrator:</td>
		<td width="100%"><input type="checkbox" name="is_superuser" value="1" {if $worker->is_superuser}checked{/if}></td>
	</tr>
	<tr>
		<td width="0%" nowrap="nowrap"><b>E-mail:</b></td>
		<td width="100%"><input type="text" name="email" value="{$worker->email}" size="45"></td>
	</tr>
	<tr>
		<td width="0%" nowrap="nowrap">Password:</td>
		<td width="100%"><input type="password" name="password" value=""></td>
	</tr>
	<tr>
		<td width="0%" nowrap="nowrap">Password (again):</td>
		<td width="100%"><input type="password" name="" value=""></td>
	</tr>
	
	<tr><td colspan="2">&nbsp;</td></tr>
	
	<tr>
		<td width="0%" nowrap="nowrap" valign="top">
			<b>Groups:</b><br>
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
	
	<tr><td colspan="2">&nbsp;</td></tr>
	
	{if !empty($worker->id)}
	<tr>
		<td width="0%" nowrap="nowrap"><b>Delete:</b></td>
		<td width="100%"><label style="background-color:rgb(255,220,220);"><input type="checkbox" name="delete" value="1"> Delete this worker</label></td>
	</tr>
	{/if}
	<tr>
		<td colspan="2">
			<button type="submit"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/check.gif{/devblocks_url}" align="top"> {$translate->_('common.save_changes')|capitalize}</button>
		</td>
	</tr>
</table>
</div>