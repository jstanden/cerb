<div id="resources">

<div class="header"><h1>{'fnr.portal.sc.search'|devblocks_translate|capitalize}</h1></div>

<div class="search">
	<form action="{devblocks_url}c=resources&a=search{/devblocks_url}" method="POST">
		<input class="query" type="text" name="q" value="{$q|escape}"><button type="submit">{'common.search'|devblocks_translate|lower}</button>
	</form>
</div>
<br>

{if !empty($q)}
	<div class="header"><h1>{'fnr.portal.sc.search.results'|devblocks_translate:$q|escape}</h1></div>

	{if !empty($feeds)}
	{foreach from=$feeds item=matches name=matches}
		<br>
		<div class="block">
			<h1 style="margin:0px;">{$matches.name}</h1>
		
			{foreach from=$matches.feed item=item name=items}
				{assign var=link value=''}
				{assign var=title value=''}
				{assign var=description value=''}
				
				{if is_a($item,'Zend_Feed_Entry_Rss')}
					{assign var=link value=$item->link()}
					{assign var=title value=$item->title()}
					{assign var=description value=$item->description()}
					{assign var=date value=$item->pubDate()}
				{elseif is_a($item,'Zend_Feed_Entry_Atom')}
					{assign var=link value=$item->link.href}
					{assign var=title value=$item->title()}
					{assign var=description value=$item->summary()}
					{assign var=date value=$item->published()}
				{/if}
		
				{assign var=item_guid value=''|cat:$title|cat:'_'|cat:$link|md5}
				
				<img src="{devblocks_url}c=resource&p=usermeet.core&f=images/document.gif{/devblocks_url}" align="absmiddle"> 
				<a href="javascript:;" class="peek" style="font-weight:normal;">{$title|escape}</a>
				<div class="peek" style="margin-left:20px;padding:5px;display:none;">
					{$description|escape:"script"}
					<div style="margin-top:5px;">		
						<b>URL:</b> <a href="{$link}" style="font-style:italic;" target="_blank">{$link}</a>
					</div>
				</div>
				<br> 
	
			{/foreach}
		</div>
	{/foreach}
	{else}
		{'fnr.portal.sc.search.results.empty'|devblocks_translate}
	{/if} {*feeds*}
<br>

<script type="text/javascript" language="JavaScript1.2">
{literal}
$(document).ready(function () {
  $('div.peek').hide();
  $('a.peek').click(function() {
    $(this).next('div.peek').slideToggle('fast');
  });
});
{/literal}
</script>

{/if}

</div>