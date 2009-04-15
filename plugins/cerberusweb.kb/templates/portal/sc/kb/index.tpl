<div id="kb">

<div class="header"><h1>{$translate->_('common.knowledgebase')|capitalize}</h1></div>

<div class="search">
	<form action="{devblocks_url}c=kb&a=search{/devblocks_url}" method="POST">
		<input class="query" type="text" name="q" value=""><input type="submit" value="search">
	</form>
</div>

<div class="browse">

{* Heading *}
{if empty($root_id)}
<h2>{$translate->_('Topics')|capitalize}</h2>
{else}
<h2>{$categories.$root_id->name}</h2>
{/if}

{* Breadcrumb category navigation *}
<div style="padding-bottom:10px;">
{if !empty($root_id)}
	<a href="{devblocks_url}c=kb&a=browse{/devblocks_url}" style="font-style:italic;">{$translate->_('portal.kb.public.top')}</a> ::
	{if !empty($breadcrumb)}
		{foreach from=$breadcrumb item=bread_id}
			<a href="{devblocks_url}c=kb&a=browse&id={$bread_id|string_format:"%06d"}{/devblocks_url}" style="font-style:italic;">{$categories.$bread_id->name}</a> :
		{/foreach}
	{/if}
{/if}
</div>

{* Browse Categories *}
{if !empty($tree.$root_id)}
<table cellspacing="0" cellpadding="0" border="0" width="100%">
	<tr>
	<td width="50%" valign="top">
	{foreach from=$tree.$root_id item=count key=cat_id name=kbcats}
		<img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/folder.gif{/devblocks_url}" align="top">
		<a href="{devblocks_url}c=kb&a=browse&id={$cat_id|string_format:"%06d"}{/devblocks_url}" style="font-weight:bold;">{$categories.$cat_id->name}</a> ({$count|string_format:"%d"})<br>
	
		{if !empty($tree.$cat_id)}
			&nbsp; &nbsp; 
			{foreach from=$tree.$cat_id item=count key=child_id name=subcats}
				 <a href="{devblocks_url}c=kb&a=browse&id={$child_id|string_format:"%06d"}{/devblocks_url}">{$categories.$child_id->name}</a>{if !$smarty.foreach.subcats.last}, {/if}
			{/foreach}
			<br>
		{/if}
		<br>
		
		{if $smarty.foreach.kbcats.iteration==$mid}
			</td>
			<td width="50%" valign="top">
		{/if}
	{/foreach}
	</td>
	</tr>
</table>
{else}
There are no subcategories.
{/if}

</div><!--#kb.browse-->
<br>

{if !empty($root_id)}
	<div class="header"><h1>Articles in {$categories.$root_id->name}</h1></div>
{elseif empty($root) && !empty($view)}
	<div class="header"><h1>All Articles</h1></div>
{/if}

{if !empty($view)}
<div id="view{$view->id}">
{$view->render()}
</div>
{/if}

</div><!--#kb-->