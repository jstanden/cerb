<div class="block">

<div align="center">
<img src="{devblocks_url}c=reports&a=drawRepliesGraph&age={$age}{/devblocks_url}">
<br>
<b>Range:</b> 
<a href="javascript:;" onclick="genericAjaxGet('reportWorkerReplies','c=reports&a=getWorkerRepliesReport&age=365');">365 days</a>
| <a href="javascript:;" onclick="genericAjaxGet('reportWorkerReplies','c=reports&a=getWorkerRepliesReport&age=180');">180 days</a>
| <a href="javascript:;" onclick="genericAjaxGet('reportWorkerReplies','c=reports&a=getWorkerRepliesReport&age=90');">90 days</a>
| <a href="javascript:;" onclick="genericAjaxGet('reportWorkerReplies','c=reports&a=getWorkerRepliesReport&age=30');">30 days</a>
| <a href="javascript:;" onclick="genericAjaxGet('reportWorkerReplies','c=reports&a=getWorkerRepliesReport&age=7');">7 days</a>
<br>
</div>
<br>

<h2>Worker Replies (Past {$age} Days)</h2>
<table cellspacing="0" cellpadding="2" border="0">
	{foreach from=$workers item=worker key=worker_id}
		{if !empty($worker_counts.$worker_id)}
		<tr>
			<td style="padding-right:20px;"><b>{$workers.$worker_id->getName()}</b></td>
			<td>{$worker_counts.$worker_id} &nbsp; (avg: {math equation="x/y" x=$worker_counts.$worker_id y=$age format="%0.2f"}/day)</td>
		</tr>
		{/if}
	{/foreach}
</table>
</div>

