<div class="block">

<div align="center">
<img src="{devblocks_url}c=reports&a=drawTicketGraph&age={$age}{/devblocks_url}">
<br>
<b>Range (days):</b> 
<a href="javascript:;" onclick="genericAjaxGet('reportNewTickets','c=reports&a=getNewTicketsReport&age=365d');">365 days</a>
| <a href="javascript:;" onclick="genericAjaxGet('reportNewTickets','c=reports&a=getNewTicketsReport&age=180d');">180 days</a>
| <a href="javascript:;" onclick="genericAjaxGet('reportNewTickets','c=reports&a=getNewTicketsReport&age=90d');">90 days</a>
| <a href="javascript:;" onclick="genericAjaxGet('reportNewTickets','c=reports&a=getNewTicketsReport&age=30d');">30 days</a>
| <a href="javascript:;" onclick="genericAjaxGet('reportNewTickets','c=reports&a=getNewTicketsReport&age=7d');">7 days</a>
| <a href="javascript:;" onclick="genericAjaxGet('reportNewTickets','c=reports&a=getNewTicketsReport&age=1d');">past 24 hours</a>
<br>
<b>Range (months):</b> 
<a href="javascript:;" onclick="genericAjaxGet('reportNewTickets','c=reports&a=getNewTicketsReport&age=12mo');">12 months</a>
| <a href="javascript:;" onclick="genericAjaxGet('reportNewTickets','c=reports&a=getNewTicketsReport&age=6mo');">6 months</a>
| <a href="javascript:;" onclick="genericAjaxGet('reportNewTickets','c=reports&a=getNewTicketsReport&age=3mo');">3 months</a>
| <a href="javascript:;" onclick="genericAjaxGet('reportNewTickets','c=reports&a=getNewTicketsReport&age=1mo');">1 month</a>
<br>
</div>
<br>

<h2>Created Tickets by Group</h2>
<table cellspacing="0" cellpadding="2" border="0">
{foreach from=$groups key=group_id item=group}
	{assign var=counts value=$group_counts.$group_id}
	{if !empty($counts.total)}
		<tr>
			<td style="padding-right:20px;">
				<a href="javascript:;" style="font-weight:bold;" onclick="toggleDiv('expandGroup{$group_id}');">{$groups.$group_id->name}</a>
				<div id="expandGroup{$group_id}" style="display:none;padding-left:15px;padding-bottom:2px;">
				{if !empty($counts.0)}Inbox: {$counts.0}{/if}
				{foreach from=$group_buckets.$group_id key=bucket_id item=b}
				{if !empty($counts.$bucket_id)}	{$b->name}: {$counts.$bucket_id}{/if}
				{/foreach}
				</div>
			</td>
			<td valign="top">{$counts.total} &nbsp; </td>
			<td valign="top">(avg: {math equation="x/y" x=$counts.total y=$age_dur format="%0.2f"}/{if $age_term=='d'}day{else}mo{/if})</td>
		</tr>
	{/if}
{/foreach}
</table>
</div>
