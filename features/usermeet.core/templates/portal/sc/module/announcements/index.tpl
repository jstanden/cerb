<div id="announcements">
{if !empty($feeds)}
	{foreach from=$feeds item=matches name=matches}
	<div class="feed">
		<div class="header">
			{if !empty($matches.url)}
			<a href="{$matches.url}" target="_blank"><img src="{devblocks_url}c=resource&p=usermeet.core&f=images/feed-icon-16x16.gif{/devblocks_url}" alt="RSS" align="top" border="0"></a>
			{/if}
			<h1 style="display:inline;">{$matches.name}</h1>
			<br>
		</div>
	
		<div style="margin:10px;">
		{foreach from=$matches.feed item=item name=items}
			{if $smarty.foreach.items.iteration > 5}
			{else}
				{assign var=link value=''}
				{assign var=title value=''}
				{assign var=description value=''}
				
				{if $item instanceof Zend_Feed_Entry_Rss}
					{assign var=link value=$item->link()}
					{assign var=title value=$item->title()}
					{assign var=description value=$item->description()}
					{assign var=date value=$item->pubDate()}
				{elseif $item instanceof Zend_Feed_Entry_Atom}
					{assign var=link value=$item->link.href}
					{assign var=title value=$item->title()}
					{assign var=description value=$item->summary()}
					{assign var=date value=$item->published()}
				{/if}
		
				{assign var=item_guid value=''|cat:$title|cat:'_'|cat:$link|md5}
				
				<img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/document.gif{/devblocks_url}" align="absmiddle"> 
				<a href="{$link}" target="_blank">{$title}</a> 
				<br>
				<div style="margin-left:20px;margin-bottom:10px;">
				{if !empty($description)}
					{$description|strip_tags|truncate:255:'...':true}
					<br>
				{/if}
				<b>{$translate->_('portal.sc.public.common.source')}</b> <a href="{$link}" style="color:rgb(50,50,50);" target="_blank">{$link|truncate:65:'...':true:true}</a><br>
				</div>
			{/if}
		{/foreach}
		</div>
	</div>
	{/foreach}
{/if} {*feeds*}
</div>