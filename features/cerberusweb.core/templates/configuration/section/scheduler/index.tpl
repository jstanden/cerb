<H2>Scheduler</H2>
<ul style="margin-top:5px;">
<li>Simple: <a href="{devblocks_url}c=cron{/devblocks_url}?reload=30&loglevel=6" target="_blank">automatically run jobs in a browser window</a></li>
<li>Advanced: ping <a href="{devblocks_url full=true}c=cron{/devblocks_url}?loglevel=3" target="_blank"><b>{devblocks_url full=true}c=cron{/devblocks_url}?loglevel=3</b></a> with wget/lynx in an external cron/scheduled task</li>
</ul>

<div style="margin:10px;">
{foreach from=$jobs item=job key=job_id name=jobs}
	
<div id="job_{$job_id|replace:'.':'_'}" style="margin-bottom:10px;">
	{include file="devblocks:cerberusweb.core::configuration/section/scheduler/job.tpl"}
</div>

{/foreach}
</div>
