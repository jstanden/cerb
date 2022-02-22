<div style="margin-top:5px;" id="divKbWidget{$widget->id}">

<form id="frmKbBrowseWidget{$widget->id}" action="#">
	{$parent_id = 0}
	{if $root_id}
		{$parent_id = $categories.$root_id->parent_id}
	{/if}
		
	{if !empty($root_id) && $active_worker->hasPriv("contexts.cerberusweb.contexts.kb_category.update")}<button type="button" class="category-edit" data-context="{CerberusContexts::CONTEXT_KB_CATEGORY}" data-context-id="{$root_id}" data-edit="true"><span class="glyphicons glyphicons-folder-closed"></span></a> Edit {if $parent_id}Category{else}Topic{/if}</button>{/if}
	{if $active_worker->hasPriv("contexts.cerberusweb.contexts.kb_category.create")}<button type="button" class="category-add" data-context="{CerberusContexts::CONTEXT_KB_CATEGORY}" data-context-id="0" data-edit="parent.id:{$root_id}"><span class="glyphicons glyphicons-folder-plus"></span> Add {if empty($root_id)}Topic{else}Subcategory{/if}</button>{/if}
		
	{if $active_worker->hasPriv('contexts.cerberusweb.contexts.kb_article.create')}
	<button type="button" class="article-add" data-context="{CerberusContexts::CONTEXT_KB_ARTICLE}" data-context-id="0" data-edit="category.id:{$root_id}"><span class="glyphicons glyphicons-circle-plus"></span> Add Article</button>
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
	<a href="javascript:;" onclick="genericAjaxGet('divKbWidget{$widget->id}','c=pages&a=invokeWidget&widget_id={$widget->id}&action=changeCategory&category_id=0');">Top</a> ::
	{if !empty($breadcrumb)}
		{foreach from=$breadcrumb item=bread_id}
			<a href="javascript:;" onclick="genericAjaxGet('divKbWidget{$widget->id}','c=pages&a=invokeWidget&widget_id={$widget->id}&action=changeCategory&category_id={$bread_id}');">{$categories.$bread_id->name}</a> :
		{/foreach} 
	{/if}
	</div>
	<br>
	
	{if !empty($tree.$root_id)}
	<table cellspacing="0" cellpadding="0" border="0" width="100%">
		<tr>
		<td width="50%" valign="top">
		{foreach from=$tree.$root_id item=count key=cat_id name=kbcats}
			<span class="glyphicons glyphicons-folder-closed" style="color:rgb(80,80,80);"></span> 
			<a href="javascript:;" onclick="genericAjaxGet('divKbWidget{$widget->id}','c=pages&a=invokeWidget&widget_id={$widget->id}&action=changeCategory&category_id={$cat_id}');" style="font-weight:bold;">{$categories.$cat_id->name}</a> ({$count|string_format:"%d"})<br>
		
			{if !empty($tree.$cat_id)}
				&nbsp; &nbsp; 
				{foreach from=$tree.$cat_id item=count key=child_id name=subcats}
					<a href="javascript:;" onclick="genericAjaxGet('divKbWidget{$widget->id}','c=pages&a=invokeWidget&widget_id={$widget->id}&action=changeCategory&category_id={$child_id}');">{$categories.$child_id->name}</a>{if !$smarty.foreach.subcats.last}, {/if}
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

</div>

<script type="text/javascript">
$(function() {
	var $frm = $('#frmKbBrowseWidget{$widget->id}');
	
	$frm.find('button.article-add')
		.cerbPeekTrigger()
		.on('cerb-peek-saved', function(e) {
			category_id = e.id;
			genericAjaxGet('divKbWidget{$widget->id}','c=pages&a=invokeWidget&widget_id={$widget->id}&action=changeCategory&category_id={$root_id}');
		})
	;
	
	$frm.find('button.category-add')
		.cerbPeekTrigger()
		.on('cerb-peek-saved', function(e) {
			category_id = e.id;
			genericAjaxGet('divKbWidget{$widget->id}','c=pages&a=invokeWidget&widget_id={$widget->id}&action=changeCategory&category_id=' + category_id);
		})
	;
	
	$frm.find('button.category-edit')
		.cerbPeekTrigger()
		.on('cerb-peek-saved', function(e) {
			category_id = e.id;
			genericAjaxGet('divKbWidget{$widget->id}','c=pages&a=invokeWidget&widget_id={$widget->id}&action=changeCategory&category_id=' + category_id);
		})
		.on('cerb-peek-deleted', function(e) {
			{if $root_id}
			genericAjaxGet('divKbWidget{$widget->id}','c=pages&a=invokeWidget&widget_id={$widget->id}&action=changeCategory&category_id={$categories.$root_id->parent_id}');
			{/if}
		})
	;
});
</script>