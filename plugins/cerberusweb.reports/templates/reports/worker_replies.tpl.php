<div class="block">

<div align="center">
<img src="{devblocks_url}c=reports&a=drawRepliesGraph&age={$age}{/devblocks_url}">
<br>
<b>Range (days):</b> 
<a href="javascript:;" onclick="genericAjaxGet('reportWorkerReplies','c=reports&a=getWorkerRepliesReport&age=365d');">365 days</a>
| <a href="javascript:;" onclick="genericAjaxGet('reportWorkerReplies','c=reports&a=getWorkerRepliesReport&age=180d');">180 days</a>
| <a href="javascript:;" onclick="genericAjaxGet('reportWorkerReplies','c=reports&a=getWorkerRepliesReport&age=90d');">90 days</a>
| <a href="javascript:;" onclick="genericAjaxGet('reportWorkerReplies','c=reports&a=getWorkerRepliesReport&age=30d');">30 days</a>
| <a href="javascript:;" onclick="genericAjaxGet('reportWorkerReplies','c=reports&a=getWorkerRepliesReport&age=7d');">7 days</a>
| <a href="javascript:;" onclick="genericAjaxGet('reportWorkerReplies','c=reports&a=getWorkerRepliesReport&age=1d');">past 24 hrs</a>
<br>
<b>Range (months):</b> 
<a href="javascript:;" onclick="genericAjaxGet('reportWorkerReplies','c=reports&a=getWorkerRepliesReport&age=12mo');">12 months</a>
| <a href="javascript:;" onclick="genericAjaxGet('reportWorkerReplies','c=reports&a=getWorkerRepliesReport&age=6mo');">6 months</a>
| <a href="javascript:;" onclick="genericAjaxGet('reportWorkerReplies','c=reports&a=getWorkerRepliesReport&age=3mo');">3 months</a>
| <a href="javascript:;" onclick="genericAjaxGet('reportWorkerReplies','c=reports&a=getWorkerRepliesReport&age=1mo');">1 month</a>
<br>
</div>
<br>

<h2>Worker Replies</h2>
<table cellspacing="0" cellpadding="2" border="0">
	{foreach from=$workers item=worker key=worker_id}
		{if !empty($worker_counts.$worker_id)}
		{assign var=counts value=$worker_counts.$worker_id}
		<tr>
			<td style="padding-right:20px;">
				<a href="javascript:;" style="font-weight:bold;" onclick="toggleDiv('expandWorker{$worker_id}');"><b>{$workers.$worker_id->getName()}</b></a>
				<div id="expandWorker{$worker_id}" style="display:none;padding-left:15px;padding-bottom:2px;">
					{foreach from=$counts item=team_hits key=team_id}
						{if is_numeric($team_id)}
							{$groups.$team_id->name}: {$team_hits}<br>
						{/if}
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

