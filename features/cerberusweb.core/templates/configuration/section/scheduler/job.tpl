{$jobdiv = $job->id|replace:'.':'_'}
{assign var=enabled value=$job->getParam('enabled',0)}
{assign var=locked value=$job->getParam('locked',0)}
{assign var=lastrun value=$job->getParam('lastrun',0)}

{if $locked}
	<span class="glyphicons glyphicons-lock" title="Locked" style="font-size:16px;color:rgb(246,203,13);"></span>
{else}
	{if $enabled}
		<span class="glyphicons glyphicons-circle-ok" style="font-size:16px;color:rgb(0,180,0);"></span>
	{else}
		<span class="glyphicons glyphicons-circle-remove" style="font-size:16px;color:rgb(185,185,185);"></span>
	{/if}
{/if}

<a href="javascript:;" onclick="toggleDiv('jobedit_{$jobdiv}');">{$job->manifest->name}</a>

<div style="display:block;border:1px solid rgb(200,200,200);background-color:rgb(255,255,255);padding:5px;margin:5px;">
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
	
	Last run: {if $lastrun}{$lastrun|devblocks_date}{else}Never{/if}
	{if $enabled && !$locked}
	- <a href="{devblocks_url}c=cron&id={$job->id}{/devblocks_url}?ignore_wait=1&loglevel=6" target="_blank" rel="noopener">run now</a>
	{/if}
	<br>
	
	{if $locked}Locked: {$locked|devblocks_date}<br>{/if}
</div>

<div id="jobedit_{$jobdiv}" style="display:none;margin-left:20px;margin-right:20px;">
	{include file="devblocks:cerberusweb.core::configuration/section/scheduler/job_edit.tpl"}
</div>
