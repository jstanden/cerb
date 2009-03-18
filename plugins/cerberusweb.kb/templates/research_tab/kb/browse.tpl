<table cellspacing="0" cellpadding="0" border="0" width="100%" style="padding-bottom:5px;">
<tr>
	<td width="1%" nowrap="nowrap" valign="top" style="padding-right:5px;">
		<form name="compose" enctype="multipart/form-data" method="post" action="{devblocks_url}{/devblocks_url}">
			<input type="hidden" name="c" value="kb.ajax">
			<input type="hidden" name="a" value="">

			{if $root_id}
				{assign var=parent_id value=$categories.$root_id->parent_id}
				{if $parent_id}
					<button type="button" onclick="genericAjaxPanel('c=kb.ajax&a=showKbCategoryEditPanel&id={$root_id}&return={$request_path|escape:'url'}',null,false,'500px');"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/folder_gear.gif{/devblocks_url}" align="top"> Edit Category</button>
				{else}
					<button type="button" onclick="genericAjaxPanel('c=kb.ajax&a=showTopicEditPanel&id={$root_id}&return={$request_path|escape:'url'}',null,false,'500px');"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/folder_gear.gif{/devblocks_url}" align="top"> Edit Topic</button>
				{/if}
				<button type="button" onclick="genericAjaxPanel('c=kb.ajax&a=showKbCategoryEditPanel&id=0&root_id={$root_id}&return={$request_path|escape:'url'}',null,false,'500px');"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/folder_add.gif{/devblocks_url}" align="top"> Add Subcategory</button>
			{else}
				<button type="button" onclick="genericAjaxPanel('c=kb.ajax&a=showTopicEditPanel&id=0&return={$request_path|escape:'url'}',null,false,'500px');"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/folder_add.gif{/devblocks_url}" align="top"> Add Topic</button>
			{/if}
			
			<button type="button" onclick="genericAjaxPanel('c=kb.ajax&a=showArticleEditPanel&id=0&root_id={$root_id}&return={$request_path|escape:'url'}',null,false,'550px');"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/document_new.gif{/devblocks_url}" align="top"> Add Article</button>
			<button type="button" onclick="document.location.href='{devblocks_url}c=research&a=kb&s=search{/devblocks_url}';"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/data_find.gif{/devblocks_url}" align="top"> {$translate->_('common.search')|capitalize}</button>
		</form>
	</td>
	<td width="98%" valign="middle">
	</td>
	<td width="1%" nowrap="nowrap" valign="middle" align="right">
		<form action="{devblocks_url}{/devblocks_url}" method="post">
		<input type="hidden" name="c" value="kb.ajax">
		<input type="hidden" name="a" value="doArticleQuickSearch">
		<span><b>{$translate->_('common.search')|capitalize}:</b></span> <!--<select name="type">
			<option value="content">Content</option>
		</select>--><input type="hidden" name="type" value="content"><input type="text" name="query" size="24"><button type="submit">go!</button>
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

<div style="padding-bottom:5px;">
<a href="{devblocks_url}c=research&a=kb{/devblocks_url}">Top</a> ::
{if !empty($breadcrumb)}
	{foreach from=$breadcrumb item=bread_id}
		<a href="{devblocks_url}c=research&a=kb&id={$bread_id|string_format:"%06d"}{/devblocks_url}">{$categories.$bread_id->name}</a> :
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
		<a href="{devblocks_url}c=research&a=kb&id={$cat_id|string_format:"%06d"}{/devblocks_url}" style="font-weight:bold;">{$categories.$cat_id->name}</a> ({$count|string_format:"%d"})<br>
	
		{if !empty($tree.$cat_id)}
			&nbsp; &nbsp; 
			{foreach from=$tree.$cat_id item=count key=child_id name=subcats}
				 <a href="{devblocks_url}c=research&a=kb&id={$child_id|string_format:"%06d"}{/devblocks_url}">{$categories.$child_id->name}</a>{if !$smarty.foreach.subcats.last}, {/if}
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
