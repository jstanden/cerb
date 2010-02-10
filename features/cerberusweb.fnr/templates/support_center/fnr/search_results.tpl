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
	{foreach from=$feeds item=feed name=feeds}
		<br>
		<div class="block">
			<h1 style="margin:0px;">{$feed.name}</h1>
		
			{foreach from=$feed.feed.items item=item name=items}
				{assign var=item_guid value=''|cat:$item.title|cat:'_'|cat:$item.link|md5}
				
				<img src="{devblocks_url}c=resource&p=usermeet.core&f=images/document.gif{/devblocks_url}" align="absmiddle"> 
				<a href="javascript:;" class="peek" style="font-weight:normal;">{$item.title|escape}</a>
				<div class="peek" style="margin-left:20px;padding:5px;display:none;">
					{$item.content|escape:"script"}
					<div style="margin-top:5px;">		
						<b>URL:</b> <a href="{$item.link}" style="font-style:italic;" target="_blank">{$item.link}</a>
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