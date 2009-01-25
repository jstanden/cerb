{include file="file:$core_tpl/groups/submenu.tpl.php"}

<h1>Group Setup</h1>

<table cellpadding="0" cellspacing="5" border="0" width="100%">
	<tr>
		<td width="0%" nowrap="nowrap" valign="top">
		
			<div class="block">
			<h2>Groups</h2>
			<table cellpadding="2" cellspacing="2" border="0">
			{foreach from=$groups item=group name=groups key=group_id}
				{assign var=group_member value=$active_worker_memberships.$group_id}
				{if $group_member || $active_worker->is_superuser}
				<tr>
					<td style="padding-right:20px;">
						{if $group_member->is_manager || $active_worker->is_superuser}
							<a href="{devblocks_url}c=groups&gid={$group_id}{/devblocks_url}"><b>{$group->name}</b></a>
						{else}
							{$group->name}
						{/if}
					</td>
					<td style="padding-right:20px;">
						{if $group_member->is_manager}
							Manager
						{elseif !empty($group_member)}
							Member
						{/if}
					</td>
				</tr>
				{/if}
			{/foreach}
			</table>
			</div>
			
		</td>
		
		<td width="100%" valign="top">
			{if !empty($team)}
				{include file="$core_tpl/groups/edit_group.tpl.php" group=$team}
			{/if}
		</td>
		
	</tr>
</table>
