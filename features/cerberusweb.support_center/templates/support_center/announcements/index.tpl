{if !empty($feeds)}
{foreach from=$feeds item=feed name=feeds}
<fieldset>
	<legend>{$feed.title}</legend>
	
	{if !empty($feed.url)}
	<div>
		<img src="{devblocks_url}c=resource&p=cerberusweb.support_center&f=images/feed-icon-16x16.gif{/devblocks_url}" alt="RSS" align="top" border="0"> <a href="{$feed.url}" target="_blank">Subscribe</a>
		<br>
		<br>
	</div>
	{/if}
	
	{foreach from=$feed.items item=item name=items}
		{if $smarty.foreach.items.iteration > 5}
		{else}
			{assign var=item_guid value=''|cat:$item.title|cat:'_'|cat:$item.link|md5}
			
			<img src="{devblocks_url}c=resource&p=cerberusweb.support_center&f=images/document.gif{/devblocks_url}" align="absmiddle"> 
			<a href="{$item.link}" target="_blank">{$item.title}</a> 
			<br>
			<div style="margin-left:20px;margin-bottom:10px;">
			{if !empty($item.content)}
				{$item.content|strip_tags|truncate:255:'...':true nofilter}
				<br>
			{/if}
			<b>{'portal.sc.public.common.source'|devblocks_translate}</b> <a href="{$item.link}" style="color:rgb(50,50,50);" target="_blank">{$item.link|truncate:65:'...':true:true}</a><br>
			</div>
		{/if}
	{/foreach}
</fieldset>
{/foreach}
{/if} {*feeds*}
