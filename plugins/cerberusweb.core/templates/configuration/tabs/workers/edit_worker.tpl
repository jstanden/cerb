<input type="hidden" name="c" value="config">
<input type="hidden" name="a" value="saveWorker">
<input type="hidden" name="id" value="{if !empty($worker->id)}{$worker->id}{else}0{/if}">
<input type="hidden" name="do_delete" value="0">
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
		{if empty($worker->id)}&nbsp;(Leave blank to automatically e-mail a randomly-generated password.){/if}</td>
	</tr>
	<tr>
		<td width="0%" nowrap="nowrap">Password (again):</td>
		<td width="100%"><input type="password" id="workerForm_password2" name="password2" value=""{if (empty($license) || empty($license.key)) && count($workers) >= 3} disabled{/if}></td>
	</tr>
	
	<tr><td colspan="2">&nbsp;</td></tr>
	
	{if !empty($worker->id)}
		{* Superuser -- Can't delete self *}
		{if $active_worker->id != $worker->id}
		<tr>
			<td width="0%" nowrap="nowrap" valign="top"><b>Status:</b></td>
			<td width="100%" valign="top">
				<label><input type="radio" name="do_disable" value="0" {if !$worker->is_disabled}checked="checked"{/if}> Active</label><br>
				<label><input type="radio" name="do_disable" value="1" {if $worker->is_disabled}checked="checked"{/if}> Disabled</label><br>
			</td>
		</tr>
		
		<tr><td colspan="2">&nbsp;</td></tr>
		{/if}
	{/if}
	
	<tr>
		<td width="0%" nowrap="nowrap" valign="top">
			<b>Groups:</b>
		</td>
		<td width="100%" id="configWorkerTeams" valign="top">
			{if $worker->id}{assign var=workerTeams value=$worker->getMemberships()}{/if}
			{foreach from=$teams item=team key=team_id}
			{assign var=member value=$workerTeams.$team_id}
			<input type="hidden" name="group_ids[]" value="{$team->id}">
			<select name="group_roles[]" {if (empty($license) || empty($license.key)) && count($workers) >= 3} disabled="disabled"{/if}>
				<option value="">&nbsp;</option>
				<option value="1" {if $member && !$member->is_manager}selected{/if}>Member</option>
				<option value="2" {if $member && $member->is_manager}selected{/if}>Manager</option>
			</select>
			{$team->name}<br>
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
				Administrator
			{else}
				<label><input type="checkbox" name="is_superuser" onclick="toggleDiv('workerPrivCheckboxes',((this.checked)?'none':'block'));" value="1" {if $worker->is_superuser}checked{/if}{if (empty($license) || empty($license.key)) && count($workers) >= 3} disabled{/if}> Administrator</label><br>
			{/if}
			
			<div id="workerPrivCheckboxes" style="display:{if $worker->is_superuser}none{else}block{/if};">
				<label style="padding-left:10px;"><input type="checkbox" name="can_export" value="1" {if $worker->can_export}checked{/if}{if (empty($license) || empty($license.key)) && count($workers) >= 3} disabled{/if}> Can export helpdesk data from lists</label><br>
				<label style="padding-left:10px;"><input type="checkbox" name="can_delete" value="1" {if $worker->can_delete}checked{/if}{if (empty($license) || empty($license.key)) && count($workers) >= 3} disabled{/if}> Can permanently delete tickets</label><br>
			</div>
		</td>
	</tr>
	
	<tr>
		<td colspan="2">
			{if (empty($license) || empty($license.key)) && count($workers) >= 3}
			{else}
				<button type="submit"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/check.gif{/devblocks_url}" align="top"> {$translate->_('common.save_changes')|capitalize}</button>
				{if $active_worker->is_superuser && $active_worker->id != $worker->id}<button type="button" onclick="if(confirm('Are you sure you want to delete this worker and their history?')){literal}{{/literal}this.form.do_delete.value='1';this.form.submit();{literal}}{/literal}"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/delete2.gif{/devblocks_url}" align="top"> {$translate->_('common.delete')|capitalize}</button>{/if}
			{/if}
		</td>
	</tr>
</table>
</div>