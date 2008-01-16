<div class="block">

<div align="center">
<img src="{devblocks_url}c=reports&a=drawAverageResponseTimeGraph&age={$age}{/devblocks_url}">
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

<h2>Average Response Time (in minutes)</h2>
<table cellspacing="0" cellpadding="2" border="0">
	{foreach from=$worker_responses item=responses key=worker_id}
		<tr>
			<td style="padding-right:20px;">
				<b>{$responses.name}</b></a>
			</td>
			{if $responses.count != 0}<td valign="top">{math equation="x/y/60" x=$responses.total_time y=$responses.count format="%0.2f"} minutes {/if}&nbsp; </td>
			<td valign="top">({$responses.count} replies)</td>
		</tr>
	{/foreach}
</table>
</div>
