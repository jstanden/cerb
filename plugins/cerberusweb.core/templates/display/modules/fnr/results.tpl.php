{if !empty($feeds)}
{foreach from=$feeds item=matches name=matches}
	<br>
	<div class="block">
	<table cellspacing="0" cellpadding="0" border="0" width="98%">
		<tr>
			<td width="100%"><h2 style="margin:0px;">{$matches.name}</h2></td>
			<td width="0%" nowrap="nowrap" align="right" style="color:rgb(0,150,0);">&nbsp;<b>{$matches.topic_name}</b></td>
		</tr>
	</table>
	
	{foreach from=$matches.feed item=item name=items}
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
		
			<img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/document.gif{/devblocks_url}" align="absmiddle"> <a href="javascript:;" onclick="toggleDiv('{$item_guid}_preview');" style="font-weight:bold;">{$title}</a> 
			<br>

			<div class="subtle" style="margin-bottom:5px;margin-left:10px;padding:5px;display:none;" id="{$item_guid}_preview">
				{$description}
				<br>
				<b>Link:</b> <a href="{$link}" style="color:rgb(50,180,50);" target="_blank">{$link}</a>
			</div>
	{/foreach}
	</div>
{/foreach}
{/if} {*feeds*}
<br>
