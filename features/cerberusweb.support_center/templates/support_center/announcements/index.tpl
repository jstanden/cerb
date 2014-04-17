{if !empty($feeds)}
{foreach from=$feeds item=feed name=feeds}
<fieldset>
	<legend>
		{if !empty($feed.url)}
			<img src="{devblocks_url}c=resource&p=cerberusweb.support_center&f=images/feed-icon-16x16.gif{/devblocks_url}" alt="RSS" align="top" border="0"> <a href="{$feed.url}" target="_blank">{$feed.title}</a>
		{else}
			{$feed.title}
		{/if}
	</legend>
	
	<div style="margin:10px 0px 0px 5px;">
	{foreach from=$feed.items item=item name=items}
		{if $smarty.foreach.items.iteration > 5}
		{else}
			{assign var=item_guid value=''|cat:$item.title|cat:'_'|cat:$item.link|md5}
			
			<img src="{devblocks_url}c=resource&p=cerberusweb.support_center&f=images/document.gif{/devblocks_url}" align="absmiddle"> 
			<a href="{$item.link}" target="_blank">{$item.title}</a> 
			<br>
			{if !empty($item.content)}
			<div style="margin:5px 0px 5px 25px;">
				{$item.content|strip_tags|truncate:255:'...':true nofilter}
			</div>
			{/if}
		{/if}
	{/foreach}
	</div>
</fieldset>
{/foreach}
{/if} {*feeds*}
