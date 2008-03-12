<H2>Scheduler</H2>
<ul style="margin-top:5px;">
<li>Simple: <a href="{devblocks_url}c=cron{/devblocks_url}" target="_blank">automatically run jobs in a browser window</a></li>
<li>Advanced: ping <a href="{devblocks_url full=true}c=cron{/devblocks_url}" target="_blank"><b>{devblocks_url full=true}c=cron{/devblocks_url}</b></a> with wget/lynx in an external cron/scheduled task</li>
</ul>

<div id="tourConfigTasks"></div>
<div class="block">
<h2>Scheduled Jobs</h2>

<blockquote style="margin:10px;">
{foreach from=$jobs item=job key=job_id name=jobs}
	{assign var=enabled value=$job->getParam('enabled',0)}
	{assign var=locked value=$job->getParam('locked',0)}
	{assign var=lastrun value=$job->getParam('lastrun',0)}
	
	{if $locked}
		<img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/lock.gif{/devblocks_url}" align="top" title="Locked">
	{else}
		{if $enabled}
			<img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/check.gif{/devblocks_url}" align="top" title="Enabled">
		{else}
			<img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/delete.gif{/devblocks_url}" align="top" title="Disabled">
		{/if}
	{/if}
	{*<a href="{devblocks_url}c=config&a=jobs&b=manage&id={$job_id}{/devblocks_url}">{$job->manifest->name}</a>*}
	<a href="javascript:;" onclick="toggleDiv('job_{$job_id}');">{$job->manifest->name}</a>
	
	<div id="" style="display:block;border:1px solid rgb(200,200,200);background-color:rgb(255,255,255);padding:5px;margin:5px;">
		{assign var=duration value=$job->getParam('duration',5)}
		{assign var=term value=$job->getParam('term','m')}
		Runs every: {$duration}
		{if $term=='d'}
			days
		{elseif $term=='m'}
			minutes
		{elseif $term=='h'}
			hours
		{/if}<br>
		
		Last run: {if $lastrun}{$lastrun|date_format:"%a, %b %d %Y %I:%M %p"}{else}Never{/if}
		{if $enabled && !$locked}
		 - <a href="{devblocks_url}c=cron&id={$job_id}{/devblocks_url}" target="_blank">run now</a>
		{/if}
		<br>
		
		{if $locked}Locked: {$locked|date_format:"%a, %b %d %Y %I:%M %p"}<br>{/if}
	</div>
	
	<div id="job_{$job_id}" style="display:none;margin-left:20px;margin-right:20px;">
		{include file="file:$path/configuration/tabs/scheduler/job.tpl.php"}
	</div>
	
	{if !$smarty.foreach.jobs.last}
		<br>
	{/if}
{/foreach}
</blockquote>

</div>

<br>
