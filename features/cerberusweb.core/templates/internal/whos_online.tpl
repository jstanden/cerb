{$whos_online = DAO_Worker::getAllOnline()}
{if !empty($whos_online)}
{$whos_online_count = count($whos_online)}
<h1 style="border-bottom:1px solid rgb(220,220,220);">{'whos_online.heading'|devblocks_translate:$whos_online_count}</h1>
{foreach from=$whos_online item=who name=whos}
	{if $who->last_activity->translation_code}
		{$who->last_activity->toString($who) nofilter} 
		({$who->last_activity_date|devblocks_prettytime}{if !empty($who->last_activity_ip)}, {$who->last_activity_ip}{/if})
		<br>
	{/if}
{/foreach}
{/if}