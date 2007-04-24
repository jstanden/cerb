<h1>{$translated.whos_heading}</h1>
<div class="line"><img src="{devblocks_url}images/spacer.gif{/devblocks_url}" width="1" height="1"></div>
{foreach from=$whos_online item=who name=whos}
	<b>{$who->getName()}</b> {if $who->title}({$who->title}) {/if}{if $who->last_activity}{$who->last_activity->toString()}{/if} (ip: x.x.x.x idle: xx secs)<br>
{/foreach}