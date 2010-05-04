<form action="{devblocks_url}{/devblocks_url}" method="POST" id="frmKbEditPanel" onsubmit="return false;">
<input type="hidden" name="c" value="kb.ajax">
<input type="hidden" name="a" value="saveArticleEditPanel">
<input type="hidden" name="id" value="{$article->id}">
<input type="hidden" name="do_delete" value="0">

<b>Title:</b><br>
<input type="text" name="title" value="{$article->title|escape}" style="width:99%;border:solid 1px rgb(180,180,180);"><br>
<br>

<b>Add to Categories:</b><br>
<div style="overflow:auto;height:150px;border:solid 1px rgb(180,180,180);background-color:rgb(255,255,255);">
	{foreach from=$levels item=depth key=node_id}
		<label>
			<input type="checkbox" name="category_ids[]" value="{$node_id}" onchange="div=document.getElementById('kbTreeCat{$node_id}');div.style.color=(this.checked)?'green':'';div.style.background=(this.checked)?'rgb(230,230,230)':'';" {if (empty($article) && $root_id==$node_id) || isset($article_categories.$node_id)}checked{/if}>
			<span style="padding-left:{math equation="(x-1)*10" x=$depth}px;{if !$depth}font-weight:bold;{/if}">{if $depth}<span class="cerb-sprite sprite-tree_cap"></span>{else}<span class="cerb-sprite sprite-folder"></span>{/if} <span id="kbTreeCat{$node_id}" {if (empty($article) && $root_id==$node_id) || isset($article_categories.$node_id)}style="color:green;background-color:rgb(230,230,230);"{/if}>{$categories.$node_id->name}</span></span>
		</label>
		<br>
	{/foreach}
</div>
<br>

<b>Content:</b><br>
<textarea id="content" name="content" style="width:99%;height:200px;border:solid 1px rgb(180,180,180);">{$article->content|escape}</textarea>
<br>

Format:
<label><input type="radio" name="format" value="1" {if 1==$article->format}checked{/if}> HTML</label> 
<label><input type="radio" name="format" value="0" {if 0==$article->format}checked{/if}> Plaintext</label> 
<br>
<br>

{if $active_worker->hasPriv('core.kb.articles.modify')}<button type="button" id="btnKbArticleEditSave" onclick="genericAjaxPanelPostCloseReloadView('frmKbEditPanel','{$view_id}');"><span class="cerb-sprite sprite-check"></span> {$translate->_('common.save_changes')|capitalize}</button>{/if} 
{if !empty($article) && $active_worker->hasPriv('core.kb.articles.modify')}<button type="button" onclick="if(confirm('Are you sure you want to permanently delete this article?')) { this.form.do_delete.value='1';$('#btnKbArticleEditSave').click(); } "><span class="cerb-sprite sprite-delete2"></span> {$translate->_('common.delete')|capitalize}</button>{/if}
</form>

<script language="JavaScript1.2" type="text/javascript">
	genericPanel.one('dialogopen',function(event,ui) {
		genericPanel.dialog('option','title','Knowledgebase Article');
		$('#frmKbEditPanel :input:text:first').focus().select();
	} );
</script>
