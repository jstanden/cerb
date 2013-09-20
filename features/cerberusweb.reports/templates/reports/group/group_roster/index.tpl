<fieldset class="peek">
	<legend>{'reports.ui.group.roster'|devblocks_translate}</legend>

	{if !empty($groups)}
	{foreach from=$groups item=group key=group_id}
		<h3 style="margin:0;">{$group->name}</h3>
		{if isset($rosters.$group_id)}
			<ul style="margin:5px;">
			{foreach from=$rosters.$group_id item=member key=member_id}
				<li style="list-style-type:square;line-height:110%;">{$workers.$member_id->getName()} ({if $member->is_manager}{'common.manager'|devblocks_translate|capitalize}{else}{'common.member'|devblocks_translate|capitalize}{/if})</li>
			{/foreach}
			</ul>
		{/if}
	{/foreach}
	{/if}
</fieldset>