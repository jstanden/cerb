{if !empty($feeds)}
{foreach from=$feeds item=matches name=matches}
	<h3>{$matches->title()}</h3>
	   	
	<blockquote style="margin:0px;margin-left:20px;">
	{foreach from=$matches item=item name=items}
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
		
			<label>
				<input type="checkbox" name="items[]" value="{$item_guid}" onclick=""> 
				<span style="font-size:120%;font-weight:bold;color:rgb(50,180,50);">{$title}</span>
			</label>
			<div style="margin-bottom:0px;margin-left:20px;padding:5px;background-color:rgb(242,242,242);">
				{$description}
			</div>
			<div style="margin-bottom:10px;margin-left:20px;padding:0px;">
				<a href="{$link}" style="font-size:90%;" target="_blank">{$link}</a>
			</div>
			<!-- <span style="font-size:90%;color:rgb(160,160,160);">{$date}</span> -->
	{/foreach}
	</blockquote>
{/foreach}
{/if} {*feeds*}

<button type="button" onclick="">Use Selected Matches</button>