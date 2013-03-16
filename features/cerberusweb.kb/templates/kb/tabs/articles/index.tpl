<form id="frmKbBrowseTab{$tab->id}" enctype="multipart/form-data" method="post" action="{devblocks_url}{/devblocks_url}">
	<input type="hidden" name="c" value="kb.ajax">
	<input type="hidden" name="a" value="">

	{if $active_worker->hasPriv('core.kb.categories.modify')}
		{$parent_id = 0}
		{if $root_id}
			{assign var=parent_id value=$categories.$root_id->parent_id}
		{/if}
			
		{if !empty($root_id)}
			<button type="button" class="category_edit">
				<span class="cerb-sprite2 sprite-folder-gear"></span>
				{$translate->_('display.manage.kb.edit_t_sc.prefix')}
				{if $parent_id}
					{$translate->_('display.manage.kb.category')}
				{else}
					{$translate->_('display.manage.kb.topic')}
				{/if}
				{$translate->_('display.manage.kb.edit_t_sc.suffix')}
			</button>
		{/if}
		<button type="button" class="category_add">
			<span class="cerb-sprite sprite-folder_add"></span>
			{$translate->_('display.manage.kb.add_t_sc.prefix')}
			{if empty($root_id)}
				{$translate->_('display.manage.kb.topic')}
			{else}
				{$translate->_('display.manage.kb.subcategory')}
			{/if}
			{$translate->_('display.manage.kb.add_t_sc.suffix')}
		</button>
			
		<button type="button" onclick="genericAjaxPopup('peek','c=kb.ajax&a=showArticleEditPanel&id=0&root_id={$root_id}&view_id={$view->id}',null,false,'700');"><span class="cerb-sprite2 sprite-plus-circle"></span>{$translate->_('display.manage.kb.add_article')}</button>
	{/if}
</form>

<fieldset style="margin-top:5px;">
	<legend>
		{if empty($root_id)}
		Topics
		{else}
		{$categories.$root_id->name}
		{/if}
	</legend>
	
	<div style="padding-bottom:5px;">
	<a href="javascript:;" onclick="genericAjaxGet('divWorkspaceTab{$tab->id}','c=pages&a=handleTabAction&tab={$tab_extension->id}&tab_id={$tab->id}&action=changeCategory&category_id=0');">Top</a> ::
	{if !empty($breadcrumb)}
		{foreach from=$breadcrumb item=bread_id}
			<a href="javascript:;" onclick="genericAjaxGet('divWorkspaceTab{$tab->id}','c=pages&a=handleTabAction&tab={$tab_extension->id}&tab_id={$tab->id}&action=changeCategory&category_id={$bread_id}');">{$categories.$bread_id->name}</a> :
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
			<a href="javascript:;" onclick="genericAjaxGet('divWorkspaceTab{$tab->id}','c=pages&a=handleTabAction&tab={$tab_extension->id}&tab_id={$tab->id}&action=changeCategory&category_id={$cat_id}');" style="font-weight:bold;">{$categories.$cat_id->name}</a> ({$count|string_format:"%d"})<br>
		
			{if !empty($tree.$cat_id)}
				&nbsp; &nbsp; 
				{foreach from=$tree.$cat_id item=count key=child_id name=subcats}
					 <a href="javascript:;" onclick="genericAjaxGet('divWorkspaceTab{$tab->id}','c=pages&a=handleTabAction&tab={$tab_extension->id}&tab_id={$tab->id}&action=changeCategory&category_id={$child_id}');">{$categories.$child_id->name}</a>{if !$smarty.foreach.subcats.last}, {/if}
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
</fieldset>

{include file="devblocks:cerberusweb.core::internal/views/search_and_view.tpl" view=$view}

<script type="text/javascript">
$frm = $('#frmKbBrowseTab{$tab->id}');

$frm.find('button.category_add').click(function(e) {
	$popup = genericAjaxPopup('peek','c=kb.ajax&a=showKbCategoryEditPanel&root_id={$root_id}',null,false,'500');
	
	$popup.one('kb_category_save', function(e) {
		category_id = 0;
		
		if(null != e.id) {
			category_id = e.id;
		}
		
		genericAjaxGet('divWorkspaceTab{$tab->id}','c=pages&a=handleTabAction&tab={$tab_extension->id}&tab_id={$tab->id}&action=changeCategory&category_id=' + category_id);
	});
});

$frm.find('button.category_edit').click(function(e) {
	$popup = genericAjaxPopup('peek','c=kb.ajax&a=showKbCategoryEditPanel&id={$root_id}',null,false,'500');
	
	$popup.one('kb_category_save', function(e) {
		category_id = 0;
		
		if(null != e.id) {
			category_id = e.id;
		}
		
		genericAjaxGet('divWorkspaceTab{$tab->id}','c=pages&a=handleTabAction&tab={$tab_extension->id}&tab_id={$tab->id}&action=changeCategory&category_id=' + category_id);
	});
});
</script>