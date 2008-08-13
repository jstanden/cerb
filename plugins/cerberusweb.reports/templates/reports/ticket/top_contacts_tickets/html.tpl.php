{if !empty($group_counts)}
	<table cellspacing="0" cellpadding="2" border="0">
	{foreach from=$group_counts key=org_id item=org}
			<tr>
				<td colspan="3" style="border-bottom:1px solid rgb(200,200,200);padding-right:20px;"><h2>{$group_counts.$org_id.name}</h2></td>
			</tr>
		{foreach from=$groups key=group_id item=group}
			{assign var=count_group_total value=$group_counts.$org_id.teams.$group_id.total}
			{assign var=count_group_buckets value=$group_counts.$org_id.teams.$group_id.buckets}
			
			{if !empty($count_group_total)}
				<tr>
					<td colspan="3" style="padding-left:10px;padding-right:20px;"><h3>{$groups.$group_id->name}</h3></td>
				</tr>
				
				{if !empty($count_group_buckets.0)}
				<tr>
					<td style="padding-left:20px;padding-right:20px;">Inbox</td>
					<td align="right">{$count_group_buckets.0}</td>
					<td></td>
				</tr>
				{/if}
				
				{foreach from=$group_buckets.$group_id key=bucket_id item=b}
					{if !empty($count_group_buckets.$bucket_id)}
					<tr>
						<td style="padding-left:20px;padding-right:20px;">{$b->name}</td>
						<td align="right">{$count_group_buckets.$bucket_id}</td>
						<td></td>
					</tr>
					{/if}
				{/foreach}

				<tr>
					<td></td>						
					<td align="right" style="border-top:1px solid rgb(200,200,200);"><b>{$count_group_total}</b></td>
					<td style="padding-left:10px;"></td>
				</tr>
			{/if}
		{/foreach}
	{/foreach}
	</table>
{/if}

