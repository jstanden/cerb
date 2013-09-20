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
			
			<fieldset class="delete" style="display:none;">
				<legend>Where should this deleted group's tickets be moved to?</legend>
				
				<p>
					<select name="delete_move_id">
						{foreach from=$groups item=move_group key=move_group_id}
							{if $move_group_id != $group->id}<option value="{$move_group_id}">{$move_group->name}</option>{/if}
						{/foreach}
					</select>
				</p>
				
				<button type="button" class="red" onclick="this.form.delete_box.value='1';this.form.submit();">Delete</button>
				<button type="button" class="" onclick="this.form.delete_box.value='0';$(this).closest('fieldset.delete').fadeOut().siblings('div.toolbar').fadeIn();">Cancel</button>
			</fieldset>
			
			<div class="toolbar">
				<button type="submit"><span class="cerb-sprite2 sprite-tick-circle"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
				{if !empty($group->id)}<button type="button" onclick="$(this).closest('div.toolbar').fadeOut().siblings('fieldset.delete').fadeIn();"><span class="cerb-sprite2 sprite-cross-circle"></span> {'common.remove'|devblocks_translate|capitalize}</button>{/if}
			</div>
		</td>
	</tr>
</table>
</fieldset>
