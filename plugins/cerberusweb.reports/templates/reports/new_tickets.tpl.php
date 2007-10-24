<div class="block">

<div align="center">
<img src="{devblocks_url}c=reports&a=drawTicketGraph&age={$age}{/devblocks_url}">
<br>
<b>Range:</b> 
<a href="javascript:;" onclick="genericAjaxGet('reportNewTickets','c=reports&a=getNewTicketsReport&age=365');">365 days</a>
| <a href="javascript:;" onclick="genericAjaxGet('reportNewTickets','c=reports&a=getNewTicketsReport&age=180');">180 days</a>
| <a href="javascript:;" onclick="genericAjaxGet('reportNewTickets','c=reports&a=getNewTicketsReport&age=90');">90 days</a>
| <a href="javascript:;" onclick="genericAjaxGet('reportNewTickets','c=reports&a=getNewTicketsReport&age=30');">30 days</a>
| <a href="javascript:;" onclick="genericAjaxGet('reportNewTickets','c=reports&a=getNewTicketsReport&age=7');">7 days</a>
<br>
</div>
<br>

<h2>New Tickets by Group (Past {$age} Days)</h2>
<table cellspacing="0" cellpadding="2" border="0">
{foreach from=$groups key=group_id item=group}
	{assign var=counts value=$group_counts.$group_id}
	{if !empty($counts.total)}
		<tr>
			<td style="padding-right:20px;"><b>{$groups.$group_id->name}</b></td>
			<td>{$counts.total} &nbsp; (avg: {math equation="x/y" x=$counts.total y=$age format="%0.2f"}/day)</td>
		</tr>
		{*
		{if !empty($counts.0)}
		<tr>
			<td>Inbox</td>
			<td>{$counts.0}</td>
		</tr>
		{/if}
		{foreach from=$group_buckets.$group_id key=bucket_id item=b}
		{if !empty($counts.$bucket_id)}
		<tr>
			<td>{$b->name}</td>
			<td>{$counts.$bucket_id}</td>
		</tr>
		{/if}
		{/foreach}
		*}
	{/if}
{/foreach}
</table>
</div>
