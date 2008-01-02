<H1>My Groups</H1>
<br>

<div class="block">
<h2>Memberships</h2>

<table cellpadding="2" cellspacing="2" border="0">
{foreach from=$groups item=group name=groups key=group_id}
	{assign var=group_member value=$active_worker_memberships.$group_id}
	{if $group_member}
	<tr>
		<td style="padding-right:20px;"><b>{$group->name}</b></td>
		<td style="padding-right:20px;">
			{if $group_member->is_manager}
				Manager
			{else}
				Member
			{/if}
		</td>
		<td>
			{if $group_member->is_manager || $active_worker->is_superuser}
			<a href="{devblocks_url}c=groups&a=config&gid={$group_id}{/devblocks_url}">configure</a>
			{/if}
		</td>
	</tr>
	{/if}
{/foreach}
</table>
</div>