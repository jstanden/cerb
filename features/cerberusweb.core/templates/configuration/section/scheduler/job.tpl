{$jobdiv = $job->id|replace:'.':'_'}
{$enabled = $job->getParam('enabled',0)}
{$locked = $job->getParam('locked',0)}
{$lastrun = $job->getParam('lastrun',0)}
{$concurrency = $job->getParam('concurrency', $max_parallel)}
{$is_concurrent = array_key_exists('parallel', $job->manifest->params)}

<h3>
	{if $locked && !$is_concurrent}
		<span class="cerb-icons cerb-icon-lock" title="Locked" style="font-size:16px;color:rgb(246,203,13);"></span>
	{else}
		{if $enabled}
			{if $is_concurrent}
			<span class="cerb-icons cerb-icon-branch" style="font-size:16px;color:rgb(0,180,0);"></span>
			{else}
			<span class="cerb-icons cerb-icon-clock" style="font-size:16px;color:rgb(0,180,0);"></span>
			{/if}
		{else}
			<span class="cerb-icons cerb-icon-ban" style="font-size:16px;color:rgb(185,185,185);"></span>
		{/if}
	{/if}
	<a data-cerb-link-jobedit="jobedit_{$jobdiv}">{$job->manifest->name}</a>
</h3>

<div style="display:block;border:1px solid var(--cerb-color-background-contrast-200);background-color:var(--cerb-color-background);padding:5px;margin:5px;">
	<div>
	{if $is_concurrent}
		Runs up to <b>{$concurrency} parallel</b> instance{if $concurrency != 1}s{/if}
	{else}
		{$duration = $job->getParam('duration',5)}
		{$term = $job->getParam('term','m')}
		Runs once every <b>{$duration}
		{if $term=='d'}
			day{if $duration != 1}s{/if}
		{elseif $term=='m'}
			minute{if $duration != 1}s{/if}
		{elseif $term=='h'}
			hour{if $duration != 1}s{/if}
		{/if}</b>
	{/if}
	</div>

	Last run: {if $lastrun}{$lastrun|devblocks_date}{else}Never{/if}
	{if $enabled && !$locked}
	- <a href="{devblocks_url}c=cron&id={$job->id}{/devblocks_url}?ignore_wait=1&loglevel=6" target="_blank" rel="noopener">run now</a>
	{/if}
	<br>

	{if $locked && !$is_concurrent}Locked: {$locked|devblocks_date}<br>{/if}
</div>

<div id="jobedit_{$jobdiv}" style="display:none;margin-left:20px;margin-right:20px;">
	{include file="devblocks:cerberusweb.core::configuration/section/scheduler/job_edit.tpl"}
</div>
