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
	
	{if empty($team->id)}
	<tr>
		<td width="0%" nowrap="nowrap" valign="top">
			<b>Initial Manager:</b><br>
		</td>
		<td width="100%" id="configTeamWorkers" valign="top">
			<select name="leader_id">
			<option value="0">-- none --</option>
			{foreach from=$workers item=worker key=worker_id}
				<option value="{$worker->id}" {if $team->leader_id==$worker->id}selected{/if}>{$worker->getName()}{if !empty($worker->title)} ({$worker->title}){/if}</option>
			{/foreach}
			</select>
		</td>
	</tr>
	{/if}

	<!-- 	
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
	 -->

	<tr>
		<td colspan="2">
			<input type="hidden" name="delete_box" value="0">
			<div id="deleteGroup" style="display:none;">
				<div style="background-color:rgb(255,220,220);border:1px solid rgb(200,50,50);margin:10px;padding:5px;">
					<h3>Delete Group</h3>
					<b>Move tickets to:</b><br>
					<select name="delete_move_id">
						{foreach from=$teams item=move_team key=move_team_id}
							{if $move_team_id != $team->id}<option value="{$move_team_id}">{$move_team->name}</option>{/if}
						{/foreach}
					</select>
					<button type="button" onclick="this.form.delete_box.value='1';this.form.submit();">Delete</button>
					<button type="button" onclick="toggleDiv('deleteGroup','none');">Cancel</button>
				</div>
				<br>
			</div>
			<button type="submit"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/check.gif{/devblocks_url}" align="top"> {$translate->_('common.save_changes')|capitalize}</button>
			{if !empty($team->id)}<button type="button" onclick="toggleDiv('deleteGroup','block');"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/delete.gif{/devblocks_url}" align="top"> {$translate->_('common.remove')|capitalize}</button>{/if}
		</td>
	</tr>
</table>
</div>
