<h1>{$translated.whos_heading}</h1>
<div class="line"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/spacer.gif{/devblocks_url}" width="1" height="1"></div>
{foreach from=$whos_online item=who name=whos}
	{if $who->last_activity->translation_code}<b>{$who->getName()}</b> {if $who->title}({$who->title}) {/if}{$who->last_activity->toString()}<br>{/if}
{/foreach}