<div id="announcements">
{if !empty($feeds)}
	{foreach from=$feeds item=feed name=feeds}
	<div class="feed">
		<div class="header">
			{if !empty($feed.url)}
			<a href="{$feed.url|escape}" target="_blank"><img src="{devblocks_url}c=resource&p=cerberusweb.support_center&f=images/feed-icon-16x16.gif{/devblocks_url}" alt="RSS" align="top" border="0"></a>
			{/if}
			<h1 style="display:inline;">{$feed.title}</h1>
			<br>
		</div>
	
		<div style="margin:10px;">
		{foreach from=$feed.items item=item name=items}
			{if $smarty.foreach.items.iteration > 5}
			{else}
				{assign var=item_guid value=''|cat:$item.title|cat:'_'|cat:$item.link|md5}
				
				<img src="{devblocks_url}c=resource&p=cerberusweb.support_center&f=images/document.gif{/devblocks_url}" align="absmiddle"> 
				<a href="{$item.link|escape}" target="_blank">{$item.title|escape}</a> 
				<br>
				<div style="margin-left:20px;margin-bottom:10px;">
				{if !empty($item.content)}
					{$item.content|strip_tags|truncate:255:'...':true}
					<br>
				{/if}
				<b>{$translate->_('portal.sc.public.common.source')}</b> <a href="{$item.link|escape}" style="color:rgb(50,50,50);" target="_blank">{$item.link|truncate:65:'...':true:true|escape}</a><br>
				</div>
			{/if}
		{/foreach}
		</div>
	</div>
	{/foreach}
{/if} {*feeds*}
</div>