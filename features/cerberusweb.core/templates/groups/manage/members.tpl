<form action="{devblocks_url}{/devblocks_url}" method="post">
<input type="hidden" name="c" value="groups">
<input type="hidden" name="a" value="saveTabMembers">
<input type="hidden" name="group_id" value="{$group->id}">

<fieldset>
	<legend>{'common.workers'|devblocks_translate|capitalize}</legend>
	
	{foreach from=$workers item=worker key=worker_id name=workers}
		{assign var=member value=$members.$worker_id}
		<div style="padding:2px;">
			<input type="hidden" name="worker_ids[]" value="{$worker_id}">
			<select name="worker_levels[]">
				<option value="">&nbsp;</option>
				<option value="1" {if $member && !$member->is_manager}selected{/if}>Member</option>
				<option value="2" {if $member && $member->is_manager}selected{/if}>Manager</option>
			</select>
			<span style="{if $member}font-weight:bold;{/if}">{$worker->getName()}</span>
			{if !empty($worker->title)} (<span style="color:rgb(0,120,0);">{$worker->title}</span>){/if}
		</div>
	{/foreach}
</fieldset>

<button type="submit"><span class="cerb-sprite2 sprite-tick-circle"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>	

</form>

