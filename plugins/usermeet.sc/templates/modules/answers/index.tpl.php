Search results for '<b>{$query}</b>':<br>
<br> 

{if !empty($feeds)}
{foreach from=$feeds item=matches name=matches}
	<div style="border-bottom:1px solid rgb(180,180,180);margin-bottom:10px;">
	<h2 style="margin:0px;">{$matches.name}</h2>
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
			<a href="{$link}" style="font-weight:normal;">{$title}</a> 
			<br>
			<div style="margin-left:20px;">
			{if !empty($description)}
				{$description|strip_tags}
				<br>
			{/if}
			<b>Source:</b> <a href="{$link}" style="color:rgb(50,50,50);" target="_blank">{$link|truncate:65:'...':true:true}</a><br>
			</div>
			<br>
		{/if}
	{/foreach}
	
	{if !empty($matches.feed->link)}
	<div style="font-size:85%;margin-left:20px;">
		&raquo; more from <a href="{$matches.feed->link}">{$matches.name}</a><br>
		<br>
	</div>
	{/if}
	</div>
	
{/foreach}
{/if} {*feeds*}
<br>
