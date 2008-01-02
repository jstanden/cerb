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
	
	<tr>
		<td width="0%" nowrap="nowrap" valign="top"><b>Members:</b></td>
		<td width="100%">
			<blockquote style="margin:5px;">
				<table cellspacing="0" cellpadding="3" border="0">
				{foreach from=$workers item=worker key=worker_id name=workers}
					{assign var=member value=$members.$worker_id}
					<tr>
						<td>
							<input type="hidden" name="worker_ids[]" value="{$worker_id}">
							<select name="worker_levels[]">
								<option value=""></option>
								<option value="1" {if $member && !$member->is_manager}selected{/if}>Member</option>
								<option value="2" {if $member && $member->is_manager}selected{/if}>Manager</option>
							</select>
							<span style="{if $member}font-weight:bold;{/if}">{$worker->getName()}</span>
							{if !empty($worker->title)} (<span style="color:rgb(0,120,0);">{$worker->title}</span>){/if}
						</td>
					</tr>
				{/foreach}
				</table>
			</blockquote>
		</td>
	</tr>
	
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
