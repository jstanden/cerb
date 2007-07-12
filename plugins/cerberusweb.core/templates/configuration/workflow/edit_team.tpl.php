<input type="hidden" name="c" value="config">
<input type="hidden" name="a" value="saveTeam">
<input type="hidden" name="id" value="{$team->id}">
<div class="block">
<table cellpadding="2" cellspacing="0" border="0">
	<tr>
		<td colspan="2">
			{if empty($team->id)}
			<h2>Add Group</h2>
			{else}
			<h2>Modify '{$team->name}'</h2>
			{/if}
		</td>
	</tr>
	<tr>
		<td width="0%" nowrap="nowrap" valign="top"><b>Name:</b></td>
		<td width="100%"><input type="text" name="name" value="{$team->name|escape:"html"}" size="45"></td>
	</tr>
	
	<tr><td colspan="2">&nbsp;</td></tr>
	
	<tr>
		<td width="0%" nowrap="nowrap" valign="top">
			<b>Workers:</b><br>
			<a href="javascript:;" onclick="checkAll('configTeamWorkers',true);">check all</a><br>
			<a href="javascript:;" onclick="checkAll('configTeamWorkers',false);">check none</a>
		</td>
		<td width="100%" id="configTeamWorkers" valign="top">
			{if $team->id}{assign var=teamWorkers value=$team->getWorkers()}{/if}
			{foreach from=$workers item=worker key=worker_id}
			<label><input type="checkbox" name="agent_id[]" value="{$worker_id}" {if $teamWorkers.$worker_id}checked{/if}>{$worker->getName()}</label><br>
			{/foreach}
		</td>
	</tr>
	
	<tr><td colspan="2">&nbsp;</td></tr>
	
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
			<button type="submit"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/check.gif{/devblocks_url}" align="top"> {$translate->_('common.save_changes')|capitalize}</button>
		</td>
	</tr>
</table>
</div>
