<ul class="submenu">
</ul>
<div style="clear:both;"></div>

<h2>{$translate->_('reports.ui.group.roster')}</h2>
<br>

{if !empty($groups)}
{foreach from=$groups item=group key=group_id}
	<div class="block">
		<h2>{$group->name}</h2>
		{if isset($rosters.$group_id)}
			<ul style="margin:5px;">
			{foreach from=$rosters.$group_id item=member key=member_id}
				<li style="list-style-type:square;">{$workers.$member_id->getName()} ({if $member->is_manager}{$translate->_('group.manager')|capitalize}{else}{$translate->_('group.member')|capitalize}{/if})</li>
			{/foreach}
			</ul>
		{/if}
	</div>
	<br>
{/foreach}
{/if}