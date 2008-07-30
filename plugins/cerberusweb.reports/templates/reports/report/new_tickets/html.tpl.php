<b>Range (days):</b> 
<a href="javascript:;" onclick="genericAjaxGet('reportNewTickets','c=reports&a=action&extid=report.tickets.new_tickets&extid_a=getNewTicketsReport&age=365d');">365 days</a>
| <a href="javascript:;" onclick="genericAjaxGet('reportNewTickets','c=reports&a=action&extid=report.tickets.new_tickets&extid_a=getNewTicketsReport&age=180d');">180 days</a>
| <a href="javascript:;" onclick="genericAjaxGet('reportNewTickets','c=reports&a=action&extid=report.tickets.new_tickets&extid_a=getNewTicketsReport&age=90d');">90 days</a>
| <a href="javascript:;" onclick="genericAjaxGet('reportNewTickets','c=reports&a=action&extid=report.tickets.new_tickets&extid_a=getNewTicketsReport&age=30d');">30 days</a>
| <a href="javascript:;" onclick="genericAjaxGet('reportNewTickets','c=reports&a=action&extid=report.tickets.new_tickets&extid_a=getNewTicketsReport&age=7d');">7 days</a>
| <a href="javascript:;" onclick="genericAjaxGet('reportNewTickets','c=reports&a=action&extid=report.tickets.new_tickets&extid_a=getNewTicketsReport&age=1d');">past 24 hours</a>
<br>
<b>Range (months):</b> 
<a href="javascript:;" onclick="genericAjaxGet('reportNewTickets','c=reports&a=action&extid=report.tickets.new_tickets&extid_a=getNewTicketsReport&age=12mo');">12 months</a>
| <a href="javascript:;" onclick="genericAjaxGet('reportNewTickets','c=reports&a=action&extid=report.tickets.new_tickets&extid_a=getNewTicketsReport&age=6mo');">6 months</a>
| <a href="javascript:;" onclick="genericAjaxGet('reportNewTickets','c=reports&a=action&extid=report.tickets.new_tickets&extid_a=getNewTicketsReport&age=3mo');">3 months</a>
| <a href="javascript:;" onclick="genericAjaxGet('reportNewTickets','c=reports&a=action&extid=report.tickets.new_tickets&extid_a=getNewTicketsReport&age=1mo');">1 month</a>
<br>
<br>

<img src="{devblocks_url}ajax.php?c=reports&a=action&extid=report.tickets.new_tickets&extid_a=drawTicketGraph&age={$age}{/devblocks_url}" style="border:1px solid rgb(200,200,200);margin:5px;padding:5px;">	<br>
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
				<td style="padding-left:10px;padding-right:20px;">Inbox</td>
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
				<td style="padding-left:10px;"><b>(avg: {math equation="x/y" x=$counts.total y=$age_dur format="%0.2f"}/{if $age_term=='d'}day{else}mo{/if})</b></td>
			</tr>
		{/if}
	{/foreach}
	</table>
{/if}

