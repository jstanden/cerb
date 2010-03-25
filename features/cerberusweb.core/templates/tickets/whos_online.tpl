{if !empty($whos_online)}
<h1>{'whos_online.heading'|devblocks_translate:$whos_online_count}</h1>
<div style="margin:0px;padding:0px;height:1px;background-color: rgb(220, 220, 220);"></div>
{foreach from=$whos_online item=who name=whos}
	{if $who->last_activity->translation_code}{$who->last_activity->toString($who)} 
	{if !empty($who->last_activity_ip)}({$who->last_activity_ip}){/if}<br>{/if}
{/foreach}
{/if}