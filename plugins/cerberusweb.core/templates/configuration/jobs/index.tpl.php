{include file="file:$path/configuration/menu.tpl.php"}
<br>

<div id="tourConfigTasks"></div>
<div class="block">
<h2>Scheduled Jobs</h2>

<blockquote>
{foreach from=$jobs item=job key=job_id}
	{assign var=enabled value=$job->getParam('enabled',0)}
	{assign var=lastrun value=$job->getParam('lastrun',0)}
	
	{if $enabled}
		<img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/check.gif{/devblocks_url}" align="top" title="Enabled">
	{else}
		<img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/delete.gif{/devblocks_url}" align="top" title="Disabled">
	{/if}
	<a href="{devblocks_url}c=config&a=jobs&b=manage&id={$job_id}{/devblocks_url}">{$job->manifest->name}</a>
	
	{assign var=duration value=$job->getParam('duration',5)}
	{assign var=term value=$job->getParam('term','m')}
	( Runs every {$duration}
	{if $term=='d'}
		days
	{elseif $term=='m'}
		minutes
	{elseif $term=='h'}
		hours
	{/if}
	 )
	
	{if $enabled}
	[ <a href="{devblocks_url}c=cron&id={$job_id}{/devblocks_url}" target="_blank">run</a> ]<br>
	{/if}
	
	<div id="job_{$job_id}" style="display:block;border:1px solid rgb(200,200,200);background-color:rgb(255,255,255);padding:5px;margin:5px;">
		Last run: {if $lastrun}{$lastrun|date_format:"%a, %b %d %Y %I:%M %p"}{else}Never{/if}
		<br>
	</div>
	
	<br>
{/foreach}
</blockquote>

</div>

<br>

<script>
	var configAjax = new cConfigAjax();
</script>