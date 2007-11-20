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
		<td width="100%"><input type="text" id="workerForm_firstName" name="first_name" value="{$worker->first_name}"{if (empty($license) || empty($license.key)) && count($workers) >= 3} disabled{/if}></td>
	</tr>
	<tr>
		<td width="0%" nowrap="nowrap">Last Name:</td>
		<td width="100%"><input type="text" name="last_name" value="{$worker->last_name}"{if (empty($license) || empty($license.key)) && count($workers) >= 3} disabled{/if}></td>
	</tr>
	<tr>
		<td width="0%" nowrap="nowrap">Title:</td>
		<td width="100%"><input type="text" name="title" value="{$worker->title}"{if (empty($license) || empty($license.key)) && count($workers) >= 3} disabled{/if}></td>
	</tr>
	<tr>
		<td width="0%" nowrap="nowrap"><b>E-mail:</b></td>
		<td width="100%"><input type="text" id="workerForm_email" name="email" value="{$worker->email}" size="45"{if (empty($license) || empty($license.key)) && count($workers) >= 3} disabled{/if}></td>
	</tr>
	<tr>
		<td width="0%" nowrap="nowrap">{if empty($worker->id)}<b>Password:</b>{else}Password:{/if}</td>
		<td width="100%"><input type="password" id="workerForm_password" name="password" value=""{if (empty($license) || empty($license.key)) && count($workers) >= 3} disabled{/if}>
		{if empty($worker->id)}&nbsp(Leave blank to automatically e-mail a randomly-generated password.){/if}</td>
	</tr>
	<tr>
		<td width="0%" nowrap="nowrap">Password (again):</td>
		<td width="100%"><input type="password" id="workerForm_password2" name="password2" value=""{if (empty($license) || empty($license.key)) && count($workers) >= 3} disabled{/if}></td>
	</tr>
	
	<tr><td colspan="2">&nbsp;</td></tr>
	
	<tr>
		<td width="0%" nowrap="nowrap" valign="top">
			<b>Groups:</b><br>
			{if (empty($license) || empty($license.key)) && count($workers) >= 3}
			{else}
			<a href="javascript:;" onclick="checkAll('configWorkerTeams',true);">check all</a><br>
			<a href="javascript:;" onclick="checkAll('configWorkerTeams',false);">check none</a>
			{/if}
		</td>
		<td width="100%" id="configWorkerTeams" valign="top">
			{if $worker->id}{assign var=workerTeams value=$worker->getMemberships()}{/if}
			{foreach from=$teams item=team key=team_id}
			<label><input type="checkbox" name="team_id[]" value="{$team->id}" {if $workerTeams.$team_id}checked{/if}{if (empty($license) || empty($license.key)) && count($workers) >= 3} disabled{/if}>{$team->name}</label><br>
			{/foreach}
		</td>
	</tr>
	
	<tr><td colspan="2">&nbsp;</td></tr>
	
	<tr>
		<td width="0%" nowrap="nowrap" valign="top"><b>Permissions:</b></td>
		<td width="100%" valign="top">
			{* Superuser -- Can't remove self *}
			{if $active_worker->id == $worker->id}
				<input type="hidden" name="is_superuser" value="{$worker->is_superuser}">
			{else}
				<label><input type="checkbox" name="is_superuser" value="1" {if $worker->is_superuser}checked{/if}{if (empty($license) || empty($license.key)) && count($workers) >= 3} disabled{/if}> Administrator</label><br>
			{/if}
			
			<label style="padding-left:10px;"><input type="checkbox" name="can_delete" value="1" {if $worker->can_delete}checked{/if}{if (empty($license) || empty($license.key)) && count($workers) >= 3} disabled{/if}> Can Permanently Delete Tickets</label><br>
		</td>
	</tr>
	<tr>
	</tr>
	
	<tr><td colspan="2">&nbsp;</td></tr>
	
	{if !empty($worker->id)}
	<tr>
		<td width="0%" nowrap="nowrap"><b>Delete:</b></td>
		<td width="100%"><label style="background-color:rgb(255,220,220);"><input type="checkbox" name="do_delete" value="1"> Delete this worker</label></td>
	</tr>
	{/if}
	<tr>
		<td colspan="2">
			{if (empty($license) || empty($license.key)) && count($workers) >= 3}{else}<button type="submit"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/check.gif{/devblocks_url}" align="top"> {$translate->_('common.save_changes')|capitalize}</button>{/if}
		</td>
	</tr>
</table>
</div>