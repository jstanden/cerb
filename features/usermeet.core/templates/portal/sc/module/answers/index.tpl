{assign var=bolded_query value="<b>"|cat:$query|cat:"</b>"}
{'portal.sc.public.search.search_results'|devblocks_translate:$bolded_query}<br>
<br> 

{if !empty($articles)}
	<div class="header"><h1>{$translate->_('common.knowledgebase')}</h1></div>
	
	<div style="margin:10px;">
      	{foreach from=$articles item=article name=articles key=article_id}
			<img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/document.gif{/devblocks_url}" align="absmiddle"> 
			<a href="{devblocks_url}c=kb&a=article&id={$article.kb_id|string_format:"%06d"}{/devblocks_url}" style="font-weight:normal;" target="_blank">{$article.kb_title}</a> 
			<br>
			<div style="margin-left:20px;">
			{if !empty($article.kb_content)}
				{$article.kb_content|strip_tags|truncate:500:'...'}
				<br>
			{/if}
			<b>{$translate->_('portal.sc.public.common.source')}</b> <a href="{devblocks_url full=true}c=kb&a=article&id={$article.kb_id|string_format:"%06d"}{/devblocks_url}" style="color:rgb(50,50,50);" target="_blank">{devblocks_url full=true}c=kb&a=article&id={$article.kb_id|string_format:"%06d"}{/devblocks_url}</a><br>
			</div>
			<br>
        {/foreach}
        
		<div style="font-size:85%;margin-left:20px;">
			{devblocks_url assign="kb_url"}c=kb{/devblocks_url}
			
			{assign var=linked_kb value="<a href=\""|cat:$kb_url|cat:"\">"|cat:$translate->_('common.knowledgebase')|cat:"</a>"}
			&raquo; {'portal.sc.public.search.more_from'|devblocks_translate:$linked_kb}<br>
			<br>
		</div>
	</div>
{/if}

{if !empty($feeds)}
{foreach from=$feeds item=matches name=matches}
	<div class="header"><h1>{$matches.name}</h1></div>

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
				{assign var=link value=$item->link('alternate')}
				{assign var=title value=$item->title()}
				{assign var=description value=$item->summary()}
				{assign var=date value=$item->published()}
			{/if}
	
			{assign var=item_guid value=''|cat:$title|cat:'_'|cat:$link|md5}
			
			<img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/document.gif{/devblocks_url}" align="absmiddle"> 
			<a href="{$link}" style="font-weight:normal;" target="_blank">{$title}</a> 
			<br>
			<div style="margin-left:20px;">
			{if !empty($description)}
				{$description|strip_tags|truncate:500:'...'}
				<br>
			{/if}
			<b>{$translate->_('portal.sc.public.common.source')}</b> <a href="{$link}" style="color:rgb(50,50,50);" target="_blank">{$link|truncate:65:'...':true:true}</a><br>
			</div>
			<br>
		{/if}
	{/foreach}
	
	{if !empty($matches.feed->link)}
	<div style="font-size:85%;margin-left:20px;">
		{if $matches.feed instanceof Zend_Feed_Rss}
			{assign var=feed_link value=$matches.feed->link}
		{elseif $matches.feed instanceof Zend_Feed_Atom}
			{assign var=feed_link value=$matches.feed->link('alternate')}
		{/if}
		{assign var=linked_feed_name value="<a href=\""|cat:$feed_link|cat:"\">"|cat:$matches.name|cat:"</a>"}
		&raquo; {'portal.sc.public.search.more_from'|devblocks_translate:$linked_feed_name}<br>
		
		<br>
	</div>
	{/if}
	</div>
	
{/foreach}
{/if} {*feeds*}

{if empty($feeds) && empty($articles)}
	{$translate->_('portal.public.no_results')}
{/if}

<br>
