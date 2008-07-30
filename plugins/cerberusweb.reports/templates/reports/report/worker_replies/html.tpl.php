<b>Range (days):</b> 
<a href="javascript:;" onclick="genericAjaxGet('reportWorkerReplies','c=reports&a=action&extid=report.tickets.worker_replies&extid_a=getWorkerRepliesReport&age=365d');">365 days</a>
| <a href="javascript:;" onclick="genericAjaxGet('reportWorkerReplies','c=reports&a=action&extid=report.tickets.worker_replies&extid_a=getWorkerRepliesReport&age=180d');">180 days</a>
| <a href="javascript:;" onclick="genericAjaxGet('reportWorkerReplies','c=reports&a=action&extid=report.tickets.worker_replies&extid_a=getWorkerRepliesReport&age=90d');">90 days</a>
| <a href="javascript:;" onclick="genericAjaxGet('reportWorkerReplies','c=reports&a=action&extid=report.tickets.worker_replies&extid_a=getWorkerRepliesReport&age=30d');">30 days</a>
| <a href="javascript:;" onclick="genericAjaxGet('reportWorkerReplies','c=reports&a=action&extid=report.tickets.worker_replies&extid_a=getWorkerRepliesReport&age=7d');">7 days</a>
| <a href="javascript:;" onclick="genericAjaxGet('reportWorkerReplies','c=reports&a=action&extid=report.tickets.worker_replies&extid_a=getWorkerRepliesReport&age=1d');">past 24 hrs</a>
<br>
<b>Range (months):</b> 
<a href="javascript:;" onclick="genericAjaxGet('reportWorkerReplies','c=reports&a=action&extid=report.tickets.worker_replies&extid_a=getWorkerRepliesReport&age=12mo');">12 months</a>
| <a href="javascript:;" onclick="genericAjaxGet('reportWorkerReplies','c=reports&a=action&extid=report.tickets.worker_replies&extid_a=getWorkerRepliesReport&age=6mo');">6 months</a>
| <a href="javascript:;" onclick="genericAjaxGet('reportWorkerReplies','c=reports&a=action&extid=report.tickets.worker_replies&extid_a=getWorkerRepliesReport&age=3mo');">3 months</a>
| <a href="javascript:;" onclick="genericAjaxGet('reportWorkerReplies','c=reports&a=action&extid=report.tickets.worker_replies&extid_a=getWorkerRepliesReport&age=1mo');">1 month</a>
<br>
<br>

<img src="{devblocks_url}ajax.php?c=reports&a=action&extid=report.tickets.worker_replies&extid_a=drawRepliesGraph&age={$age}{/devblocks_url}" style="border:1px solid rgb(200,200,200);margin:5px;padding:5px;">
<br>

{if !empty($worker_counts)}
	<table cellspacing="0" cellpadding="2" border="0">
	{foreach from=$workers item=worker key=worker_id}
		{if !empty($worker_counts.$worker_id)}
		{assign var=counts value=$worker_counts.$worker_id}
		
		<tr>
			<td colspan="3" style="border-bottom:1px solid rgb(200,200,200);"><h2>{$workers.$worker_id->getName()}</h2></td>
		</tr>
		
		{foreach from=$counts item=team_hits key=team_id}
			{if is_numeric($team_id)}
			<tr>
				<td style="padding-right:20px;">{$groups.$team_id->name}</td>
				<td align="right">{$team_hits}</td>
				<td></td>
			</tr>
			{/if}
		{/foreach}
		
		<tr>
			<td></td>
			<td style="border-top:1px solid rgb(200,200,200);" align="right"><b>{$counts.total}</b></td>
			<td style="padding-left:10px;"><b>(avg: {math equation="x/y" x=$counts.total y=$age_dur format="%0.2f"}/{if $age_term=='d'}day{else}mo{/if})</b></td>
		</tr>
		
		{/if}
	{/foreach}
	</table>
{/if}


