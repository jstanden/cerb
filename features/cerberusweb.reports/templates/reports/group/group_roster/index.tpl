<fieldset class="peek">
	<legend>{$translate->_('reports.ui.group.roster')}</legend>

	{if !empty($groups)}
	{foreach from=$groups item=group key=group_id}
		<h3 style="margin:0;">{$group->name}</h3>
		{if isset($rosters.$group_id)}
			<ul style="margin:5px;">
			{foreach from=$rosters.$group_id item=member key=member_id}
				<li style="list-style-type:square;line-height:110%;">{$workers.$member_id->getName()} ({if $member->is_manager}{$translate->_('common.manager')|capitalize}{else}{$translate->_('common.member')|capitalize}{/if})</li>
			{/foreach}
			</ul>
		{/if}
	{/foreach}
	{/if}
</fieldset>