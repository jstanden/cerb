<h1>{'whos_online.heading'|devblocks_translate:$whos_online_count}</h1>
<div class="line"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/spacer.gif{/devblocks_url}" width="1" height="1"></div>
{foreach from=$whos_online item=who name=whos}
	{if $who->last_activity->translation_code}{$who->last_activity->toString($who)}<br>{/if}
{/foreach}