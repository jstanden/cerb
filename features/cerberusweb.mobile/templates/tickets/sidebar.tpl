{if empty($sla_counts) && empty($group_counts) && empty($waiting_counts) && empty($worker_groups)}
<div class="block">
<h2>All Done!</h2>
<table cellspacing="0" cellpadding="2" border="0" width="220">
	<tr>
		<td>There are currently no open tickets.</td>
	</tr>
</table>
</div>
<br>
{/if}

{if !empty($group_counts)}
<div class="block">
<h2>Available</h2>
<table cellspacing="0" cellpadding="2" border="0" width="220">
<tr>
	<td><a href="{devblocks_url}c=mobile&a=tickets&a2=overview&all=all{/devblocks_url}">All</a></td>
	<td></td>
</tr>
{foreach from=$groups key=group_id item=group}
	{assign var=counts value=$group_counts.$group_id}
	{if !empty($counts.total)}
		<tr>
			<td style="padding-right:20px;" nowrap="nowrap" valign="top">
				<a href="{devblocks_url}c=mobile&a=tickets&a2=overview&s=group&gid={$group_id}{/devblocks_url}" style="font-weight:bold;">{$groups.$group_id->name}</a> <span style="color:rgb(150,150,150);">({$counts.total})</span>
				
				<div id="expandGroup{$group_id}" style="display:block;padding-left:10px;padding-bottom:0px;">
				<a href="{devblocks_url}c=mobile&a=tickets&a2=overview&s=group&gid={$group_id}{/devblocks_url}">- All -</a><br>
				{if !empty($counts.0)}<a href="{devblocks_url}c=mobile&a=tickets&a2=overview&s=group&gid={$group_id}&bid=0{/devblocks_url}">Inbox</a> <span style="color:rgb(150,150,150);">({$counts.0})</span><br>{/if}
				{foreach from=$group_buckets.$group_id key=bucket_id item=b}
					{if !empty($counts.$bucket_id)}	<a href="{devblocks_url}c=mobile&a=tickets&a2=overview&s=group&gid={$group_id}&bid={$bucket_id}{/devblocks_url}">{$b->name}</a> <span style="color:rgb(150,150,150);"> ({$counts.$bucket_id})</span><br>{/if}
				{/foreach}
				</div>
				
			</td>
			<td valign="top"> &nbsp; </td>
		</tr>
	{/if}
{/foreach}
</table>
</div>
<br>
{/if}

{if !empty($waiting_counts)}
<div class="block">
<h2>Waiting</h2>
<table cellspacing="0" cellpadding="2" border="0" width="220">
 
<tr>
	<td><a href="{devblocks_url}c=mobile&a=tickets&a2=overview&all=all{/devblocks_url}">All</a></td>
	<td></td>
</tr>
 
{foreach from=$groups key=group_id item=group}
	{assign var=counts value=$waiting_counts.$group_id}
	{if !empty($counts.total)}
		<tr>
			<td style="padding-right:20px;" nowrap="nowrap" valign="top">
				<a href="{devblocks_url}c=mobile&a=tickets&a2=overview&s=waiting&gid={$group_id}{/devblocks_url}" style="font-weight:bold;">{$groups.$group_id->name}</a> <span style="color:rgb(150,150,150);">({$counts.total})</span>
				<div style="display:block;padding-left:10px;padding-bottom:0px;">
				<a href="{devblocks_url}c=mobile&a=tickets&a2=overview&s=waiting&gid={$group_id}{/devblocks_url}">- All -</a><br>
				{if !empty($counts.0)}<a href="{devblocks_url}c=mobile&a=tickets&a2=overview&s=waiting&gid={$group_id}&bid=0{/devblocks_url}">Inbox</a> <span style="color:rgb(150,150,150);">({$counts.0})</span><br>{/if}
				{foreach from=$group_buckets.$group_id key=bucket_id item=b}
					{if !empty($counts.$bucket_id)}	<a href="{devblocks_url}c=mobile&a=tickets&a2=overview&s=waiting&gid={$group_id}&bid={$bucket_id}{/devblocks_url}">{$b->name}</a> <span style="color:rgb(150,150,150);"> ({$counts.$bucket_id})</span><br>{/if}
				{/foreach}
				</div>
			</td>
			<td valign="top"> &nbsp; </td>
		</tr>
	{/if}
{/foreach}
</table>
</div>
<br>
{/if}

{if !empty($worker_counts)}
<div class="block">
<h2>Assigned</h2>
<table cellspacing="0" cellpadding="2" border="0" width="220">
	{foreach from=$workers item=worker key=worker_id}
		{if !empty($worker_counts.$worker_id)}
		{assign var=counts value=$worker_counts.$worker_id}
		<tr>
			<td style="padding-right:20px;" nowrap="nowrap" valign="top">
				<!-- [<a href="javascript:;" onclick="toggleDiv('expandWorker{$worker_id}');">+</a>] --> 
				<a href="{devblocks_url}c=mobile&a=tickets&a2=overview&s=worker&wid={$worker_id}{/devblocks_url}" style="font-weight:bold;">{$workers.$worker_id->getName()}</a> <span style="color:rgb(150,150,150);">({$counts.total})</span>
			</td>
			<td valign="top"></td>
		</tr>
		{/if}
	{/foreach}
</table>
</div>
<br>
{/if}
