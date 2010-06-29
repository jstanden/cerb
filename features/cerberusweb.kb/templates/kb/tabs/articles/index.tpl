<table cellspacing="0" cellpadding="0" border="0" width="100%" style="padding-bottom:5px;">
<tr>
	<td width="1%" nowrap="nowrap" valign="top" style="padding-right:5px;">
		<form name="compose" enctype="multipart/form-data" method="post" action="{devblocks_url}{/devblocks_url}">
			<input type="hidden" name="c" value="kb.ajax">
			<input type="hidden" name="a" value="">

			{if $root_id}
				{assign var=parent_id value=$categories.$root_id->parent_id}
				{if $parent_id}
					{if $active_worker->hasPriv('core.kb.categories.modify')}<button type="button" onclick="genericAjaxPopup('peek','c=kb.ajax&a=showKbCategoryEditPanel&id={$root_id}&return={$request_path|escape:'url'}',null,false,'500');"><span class="cerb-sprite sprite-folder_gear"></span> Edit Category</button>{/if}
				{else}
					{if $active_worker->hasPriv('core.kb.topics.modify')}<button type="button" onclick="genericAjaxPopup('peek','c=kb.ajax&a=showTopicEditPanel&id={$root_id}&return={$request_path|escape:'url'}',null,false,'500');"><span class="cerb-sprite sprite-folder_gear"></span> Edit Topic</button>{/if}
				{/if}
				{if $active_worker->hasPriv('core.kb.categories.modify')}<button type="button" onclick="genericAjaxPopup('peek','c=kb.ajax&a=showKbCategoryEditPanel&id=0&root_id={$root_id}&return={$request_path|escape:'url'}',null,false,'500');"><span class="cerb-sprite sprite-folder_add"></span> Add Subcategory</button>{/if}
			{else}
				{if $active_worker->hasPriv('core.kb.topics.modify')}<button type="button" onclick="genericAjaxPopup('peek','c=kb.ajax&a=showTopicEditPanel&id=0&return={$request_path|escape:'url'}',null,false,'500');"><span class="cerb-sprite sprite-folder_add"></span> Add Topic</button>{/if}
			{/if}
			
			{if $active_worker->hasPriv('core.kb.articles.modify')}<button type="button" onclick="genericAjaxPopup('peek','c=kb.ajax&a=showArticleEditPanel&id=0&root_id={$root_id}&view_id={$view->id}',null,false,'700');"><span class="cerb-sprite sprite-add"></span> Add Article</button>{/if}
		</form>
	</td>
	<td width="98%" valign="middle">
	</td>
	<td width="1%" nowrap="nowrap" valign="middle" align="right">
		<form action="{devblocks_url}{/devblocks_url}" method="post">
		<input type="hidden" name="c" value="kb.ajax">
		<input type="hidden" name="a" value="doArticleQuickSearch">
		<span><b>{$translate->_('common.search')|capitalize}:</b></span> <select name="type">
			<option value="articles_all">Articles (all words)</option>
			<option value="articles_phrase">Articles (phrase)</option>
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

<div style="padding-bottom:5px;">
<a href="{devblocks_url}c=kb{/devblocks_url}">Top</a> ::
{if !empty($breadcrumb)}
	{foreach from=$breadcrumb item=bread_id}
		<a href="{devblocks_url}c=kb&a=category&id={$bread_id}{/devblocks_url}">{$categories.$bread_id->name}</a> :
	{/foreach} 
{/if}
</div>
<br>

{if !empty($tree.$root_id)}
<table cellspacing="0" cellpadding="0" border="0" width="100%">
	<tr>
	<td width="50%" valign="top">
	{foreach from=$tree.$root_id item=count key=cat_id name=kbcats}
		<span class="cerb-sprite sprite-folder"></span>
		<a href="{devblocks_url}c=kb&a=category&id={$cat_id}{/devblocks_url}" style="font-weight:bold;">{$categories.$cat_id->name}</a> ({$count|string_format:"%d"})<br>
	
		{if !empty($tree.$cat_id)}
			&nbsp; &nbsp; 
			{foreach from=$tree.$cat_id item=count key=child_id name=subcats}
				 <a href="{devblocks_url}c=kb&a=category&id={$child_id}{/devblocks_url}">{$categories.$child_id->name}</a>{if !$smarty.foreach.subcats.last}, {/if}
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
