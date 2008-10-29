{if $invalidDate}<font color="red"><b>{$translate->_('reports.ui.invalid_date')}</b></font>{/if}

<br>

{if !empty($group_counts)}
	<table cellspacing="0" cellpadding="2" border="0">
	{foreach from=$groups key=group_id item=group}
		{assign var=counts value=$group_counts.$group_id}
		{if !empty($counts.total)}
			<tr>
				<td colspan="3" style="border-bottom:1px solid rgb(200,200,200);padding-right:20px;"><h2>{$groups.$group_id->name}</h2></td>
			</tr>
			
			{if !empty($counts.0)}
			<tr>
				<td style="padding-left:10px;padding-right:20px;">{$translate->_('common.inbox')|capitalize}</td>
				<td align="right">{$counts.0}</td>
				<td></td>
			</tr>
			{/if}
			
			{foreach from=$group_buckets.$group_id key=bucket_id item=b}
				{if !empty($counts.$bucket_id)}
				<tr>
					<td style="padding-left:10px;padding-right:20px;">{$b->name}</td>
					<td align="right">{$counts.$bucket_id}</td>
					<td></td>
				</tr>
				{/if}
			{/foreach}

			<tr>
				<td></td>						
				<td align="right" style="border-top:1px solid rgb(200,200,200);"><b>{$counts.total}</b></td>
				{math assign="avg_new" equation="x/y" x=$counts.total y=$age_dur format="%0.2f"}
				<td style="padding-left:10px;"><b>{'reports.ui.average_per_day'|devblocks_translate:$avg_new}</b></td>
			</tr>
		{/if}
	{/foreach}
	</table>
{/if}

