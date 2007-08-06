{include file="$path/portal/sc/header.tpl.php"}

<form action="{devblocks_url}{/devblocks_url}" method="post">
<input type="hidden" name="a" value="doSearch">

<b>Search:</b><br>
<input type="text" name="q" value="{$terms}" autocomplete="off">
<button type="submit">Search</button><br>
<label><input type="checkbox" name="sources[]" value="jira" {if isset($sources.jira)}checked{/if}> Roadmap</label>
<label><input type="checkbox" name="sources[]" value="forums" {if isset($sources.forums)}checked{/if}> Forums</label>
<label><input type="checkbox" name="sources[]" value="wiki" {if isset($sources.wiki)}checked{/if}> Documentation</label>
<br>
</form>

{if !empty($feeds)}
{foreach from=$feeds item=matches}
	<h2>{$matches->title()}</h2>
	   	
	<ul style="margin-top:0px;">
	{foreach from=$matches item=item}
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
		
		<li>
			<a href="{$link}" style="font-size:120%;font-weight:bold;color:rgb(50,180,50);">{$title}</a>
			<span style="font-size:90%;color:rgb(160,160,160);">{$date}</span>
			<br>
			<div style="margin-bottom:10px;margin-left:10px;padding:5px;background-color:rgb(242,242,242);">{$description}</div>
		</li>
	{/foreach}
	</ul>
{/foreach}
{/if} {*feeds*}

{include file="$path/portal/sc/footer.tpl.php"}
