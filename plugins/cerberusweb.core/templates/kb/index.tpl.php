{include file="file:$core_tpl/kb/submenu.tpl.php"}

<table cellspacing="0" cellpadding="0" border="0" width="100%">
<tr>
	<td width="1%" nowrap="nowrap" valign="top" style="padding-right:5px;">
		<h1>Knowledgebase</h1>
	</td>
	<td width="98%" valign="middle">
	</td>
	<td width="1%" nowrap="nowrap" valign="middle" align="right">
		<form action="{devblocks_url}{/devblocks_url}" method="post">
		<input type="hidden" name="c" value="kb">
		<input type="hidden" name="a" value="doArticleQuickSearch">
		<span><b>Quick Search:</b></span> <select name="type">
			<option value="content">Content</option>
		</select><input type="text" name="query" size="24"><button type="submit">go!</button>
		</form>
	</td>
</tr>
</table>

<div class="block">

{if empty($root_id)}
<h2>Topics</h2>
{else}
<h2>{$categories.$root_id->name}</h2>
{/if}

{if $root_id}
	{assign var=parent_id value=$categories.$root_id->parent_id}
	<form name="compose" enctype="multipart/form-data" method="post" action="{devblocks_url}{/devblocks_url}">
		<input type="hidden" name="c" value="kb">
		<input type="hidden" name="a" value="createArticle">
			<button type="button" onclick="genericAjaxPanel('c=kb&a=showArticleEditPanel&id=0&root_id={$root_id}&return={$response_uri|escape:'url'}',null,false,'550px');"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/document_new.gif{/devblocks_url}" align="top"> Add Article</button>
			{if $parent_id}<button type="button" onclick="genericAjaxPanel('c=kb&a=showKbCategoryEditPanel&id={$root_id}&return={$response_uri|escape:'url'}',null,false,'500px');"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/folder_gear.gif{/devblocks_url}" align="top"> Edit Category</button>{/if}
			<button type="button" onclick="genericAjaxPanel('c=kb&a=showKbCategoryEditPanel&id=0&root_id={$root_id}&return={$response_uri|escape:'url'}',null,false,'500px');"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/folder_add.gif{/devblocks_url}" align="top"> Add Subcategory</button>
	</form>
{/if}
<br>

<div style="padding-bottom:5px;">
<a href="{devblocks_url}c=kb&a=overview{/devblocks_url}">Top</a> ::
{if !empty($breadcrumb)}
	{foreach from=$breadcrumb item=bread_id}
		<a href="{devblocks_url}c=kb&a=overview&id={$bread_id|string_format:"%06d"}{/devblocks_url}">{$categories.$bread_id->name}</a> :
	{/foreach} 
{/if}
</div>
<br>

{if !empty($tree.$root_id)}
<table cellspacing="0" cellpadding="0" border="0" width="100%">
	<tr>
	<td width="50%" valign="top">
	{foreach from=$tree.$root_id item=count key=cat_id name=kbcats}
		<img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/folder.gif{/devblocks_url}" align="top">
		<a href="{devblocks_url}c=kb&a=overview&id={$cat_id|string_format:"%06d"}{/devblocks_url}" style="font-weight:bold;">{$categories.$cat_id->name}</a> ({$count|string_format:"%d"})<br>
	
		{if !empty($tree.$cat_id)}
			&nbsp; &nbsp; 
			{foreach from=$tree.$cat_id item=count key=child_id name=subcats}
				 <a href="{devblocks_url}c=kb&a=overview&id={$child_id|string_format:"%06d"}{/devblocks_url}">{$categories.$child_id->name}</a>{if !$smarty.foreach.subcats.last}, {/if}
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
{/if}

</div>
<br>

<div id="view{$view->id}">
{$view->render()}
</div>

