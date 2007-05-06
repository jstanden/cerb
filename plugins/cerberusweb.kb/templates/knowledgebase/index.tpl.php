<h1>Knowledgebase</h1>
<img src="{devblocks_url}images/view.gif{/devblocks_url}"> <b>Search:</b> 
<input type="text" size="45"><input type="button" value="Go!">
<img src="{devblocks_url}images/folder_into.gif{/devblocks_url}" align="absmiddle"> <a href="javascript:;" onclick="kbAjax.showCategoryJump(this);">jump to category</a>
<br>

{if $node->id}
<br>
<table cellpadding="0" cellspacing="0" border="0" width="100%">
	<tr>
		<td width="0%" nowrap="nowrap" valign="top"><h2>{$node->name}</h2></td>
		<td width="100%" nowrap="nowrap" valign="middle">
			<img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/spacer.gif{/devblocks_url}" width="5" height="1">
			<img src="{devblocks_url}images/folder_edit.gif{/devblocks_url}" align="absmiddle"> <a href="javascript:;" onclick="kbAjax.showCategoryModify('{$node->id}','0',this);">modify</a>
		</td>
	</tr>
</table>
{/if}

{if $node->id}
{foreach from=$trail item=tn name=trails}
{if $smarty.foreach.trails.last && $tn->id}
	<b>{$tn->name}</b>
{else}
	<a href="{devblocks_url}c=kb&id={$tn->id}{/devblocks_url}">{$tn->name}</a> :
{/if}
{/foreach}
<br>
{/if}

<br>

<table cellpadding="0" cellspacing="0" border="0" width="100%">
	<tr>
		<td width="0%" nowrap="nowrap" valign="top"><h2>Subcategories:</h2></td>
		<td width="100%" nowrap="nowrap" valign="middle">
			<img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/spacer.gif{/devblocks_url}" width="5" height="1">
			<img src="{devblocks_url}images/folder_add.gif{/devblocks_url}" align="absmiddle"> <a href="javascript:;" onclick="kbAjax.showCategoryModify('0','{$node->id}',this);">add</a>
		</td>
	</tr>
</table>
<div style="border:1px solid rgb(224,224,224);padding:5px;">
{include file="file:$path/knowledgebase/category_table.tpl.php"}
</div>

<br>
<table cellpadding="0" cellspacing="0">
	<tr>
		<td><h2>Resources:</h2></td>
		<td> &nbsp;<img src="{devblocks_url}images/document_add.gif{/devblocks_url}" align="absmiddle"> <a href="#">add</a></td>
	</tr>
</table>
{include file="file:$path/knowledgebase/resource_list.tpl.php"}

<script>
	var kbAjax = new cKbAjax();
</script>