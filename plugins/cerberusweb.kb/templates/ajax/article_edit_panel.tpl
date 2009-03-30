<form action="{devblocks_url}{/devblocks_url}" method="POST" id="frmKbEditPanel">
<input type="hidden" name="c" value="kb.ajax">
<input type="hidden" name="a" value="saveArticleEditPanel">
<input type="hidden" name="id" value="{$article->id}">
<input type="hidden" name="do_delete" value="0">

<table cellspacing="0" cellpadding="0" border="0" width="100%">
<tr>
	<td>
		{if !empty($article)}
		<h1>Modify Knowledgebase Article</h1>
		{else}
		<h1>Add Knowledgebase Article</h1>
		{/if}
	</td>
</tr>
</table>

<b>Title:</b><br>
<input type="text" name="title" value="{$article->title|escape}" style="width:99%;border:solid 1px rgb(180,180,180);"><br>
<br>

<b>Add to Categories:</b><br>
<div style="overflow:auto;height:150px;border:solid 1px rgb(180,180,180);background-color:rgb(255,255,255);">
	{foreach from=$levels item=depth key=node_id}
		<label>
			<input type="checkbox" name="category_ids[]" value="{$node_id}" onchange="div=document.getElementById('kbTreeCat{$node_id}');div.style.color=(this.checked)?'green':'';div.style.background=(this.checked)?'rgb(230,230,230)':'';" {if (empty($article) && $root_id==$node_id) || isset($article_categories.$node_id)}checked{/if}>
			<span style="padding-left:{math equation="(x-1)*10" x=$depth}px;{if !$depth}font-weight:bold;{/if}">{if $depth}<img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/tree_cap.gif{/devblocks_url}" align="absmiddle">{else}<img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/folder.gif{/devblocks_url}" align="absmiddle">{/if} <span id="kbTreeCat{$node_id}" {if (empty($article) && $root_id==$node_id) || isset($article_categories.$node_id)}style="color:green;background-color:rgb(230,230,230);"{/if}>{$categories.$node_id->name}</span></span>
		</label>
		<br>
	{/foreach}
</div>
<br>

<b>Insert/Paste Content:</b> (from your external editor, if applicable)<br>
<textarea name="content_raw" style="width:99%;height:150px;border:solid 1px rgb(180,180,180);">{$article->content_raw|escape}</textarea>
<br>

Format:
<label><input type="radio" name="format" value="1" {if 1==$article->format}checked{/if}> HTML</label> 
<label><input type="radio" name="format" value="0" {if 0==$article->format}checked{/if}> Plaintext</label> 
<br>
<br>

{if $active_worker->hasPriv('core.kb.articles.modify')}<button type="button" onclick="genericAjaxPost('frmKbEditPanel','','c=kb.ajax&a=saveArticleEditPanel',{literal}function(o){genericPanel.hide();}{/literal});"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/check.gif{/devblocks_url}" align="top"> {$translate->_('common.save_changes')|capitalize}</button>{/if} 
{if $active_worker->hasPriv('core.kb.articles.modify')}<button type="button" onclick="{literal}if(confirm('Are you sure you want to permanently delete this article?')){this.form.do_delete.value='1';this.form.submit();}{/literal}"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/delete2.gif{/devblocks_url}" align="top"> {$translate->_('common.delete')|capitalize}</button>{/if}
<button type="button" onclick="genericPanel.hide();"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/delete.gif{/devblocks_url}" align="top"> {$translate->_('common.cancel')|capitalize}</button>
</form>