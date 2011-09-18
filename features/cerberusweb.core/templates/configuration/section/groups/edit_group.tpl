<input type="hidden" name="c" value="config">
<input type="hidden" name="a" value="handleSectionAction">
<input type="hidden" name="section" value="groups">
<input type="hidden" name="action" value="saveGroup">
<input type="hidden" name="id" value="{$group->id}">

<fieldset>
	<legend>
		{if empty($group->id)}
		Add Group
		{else}
		Modify '{$group->name}'
		{/if}
	</legend>
	
<table cellpadding="2" cellspacing="0" border="0">
	<tr>
		<td width="0%" nowrap="nowrap" valign="top"><b>Name:</b></td>
		<td width="100%"><input type="text" name="name" value="{$group->name}" size="45"></td>
	</tr>
	
	<tr>
		<td width="0%" nowrap="nowrap" valign="top"><b>Members:</b></td>
		<td width="100%">
			<blockquote style="margin:5px;">
				<table cellspacing="0" cellpadding="0" border="0">
				{foreach from=$workers item=worker key=worker_id name=workers}
					{assign var=member value=$members.$worker_id}
					<tr>
						<td>
							<input type="hidden" name="worker_ids[]" value="{$worker_id}">
							<select name="worker_levels[]">
								<option value="">&nbsp;</option>
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
	
	<tr>
		<td colspan="2">
			{include file="devblocks:cerberusweb.core::internal/custom_fields/bulk/form.tpl" bulk=false}
		</td>
	</tr>
	
	<tr>
		<td colspan="2">
			<input type="hidden" name="delete_box" value="0">
			<div id="deleteGroup" style="display:none;">
				<div style="background-color:rgb(255,220,220);border:1px solid rgb(200,50,50);margin:10px;padding:5px;">
					<h3>Delete Group</h3>
					<b>Move tickets to:</b><br>
					<select name="delete_move_id">
						{foreach from=$groups item=move_group key=move_group_id}
							{if $move_group_id != $group->id}<option value="{$move_group_id}">{$move_group->name}</option>{/if}
						{/foreach}
					</select>
					<button type="button" onclick="this.form.delete_box.value='1';this.form.submit();">Delete</button>
					<button type="button" onclick="this.form.delete_box.value='0';toggleDiv('deleteGroup','none');">Cancel</button>
				</div>
				<br>
			</div>
			<button type="submit"><span class="cerb-sprite2 sprite-tick-circle-frame"></span> {$translate->_('common.save_changes')|capitalize}</button>
			{if !empty($group->id)}<button type="button" onclick="toggleDiv('deleteGroup','block');"><span class="cerb-sprite2 sprite-cross-circle-frame"></span> {$translate->_('common.remove')|capitalize}</button>{/if}
		</td>
	</tr>
</table>
</fieldset>
