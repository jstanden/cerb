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
			<span style="padding-left:{math equation="(x-1)*10" x=$depth}px;{if !$depth}font-weight:bold;{/if}">{if $depth}<img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/tree_cap.gif{/devblocks_url}" align="absmiddle">{else}<img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/folder.gif{/devblocks_url}" align="absmiddle">{/if} <span id="kbTreeCat{$node_id}" {if (empty($article) && $root_id==$node_id) || isset($article_categories.$node_id)}style="color:green;background-color:rgb(230,230,230);"{/if}>{$categories.$node_id->name}</span></span>
		</label>
		<br>
	{/foreach}
</div>
<br>

<b>Insert/Paste Content:</b> (from your external editor, if applicable)<br>
<textarea id="content_raw" name="content_raw" style="width:99%;height:150px;border:solid 1px rgb(180,180,180);">{$article->content_raw|escape}</textarea>
<br>

Format:
<label><input type="radio" name="format" value="1" {if 1==$article->format}checked{/if}> HTML</label> 
<label><input type="radio" name="format" value="0" {if 0==$article->format}checked{/if}> Plaintext</label> 
<br>
<br>

{if $active_worker->hasPriv('core.kb.articles.modify')}<button type="button" id="btnKbArticleEditSave" onclick="arr['content_raw'].disable_design_mode(true);genericAjaxPanelPostCloseReloadView('frmKbEditPanel','{$view_id}');"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/check.gif{/devblocks_url}" align="top"> {$translate->_('common.save_changes')|capitalize}</button>{/if} 
{if $active_worker->hasPriv('core.kb.articles.modify')}<button type="button" onclick="if(confirm('Are you sure you want to permanently delete this article?')) { this.form.do_delete.value='1';$('#btnKbArticleEditSave').click(); } "><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/delete2.gif{/devblocks_url}" align="top"> {$translate->_('common.delete')|capitalize}</button>{/if}
</form>

<script language="JavaScript1.2" type="text/javascript">
	genericPanel.one('dialogopen',function(event,ui) {
		genericPanel.dialog('option','title','Knowledgebase Article');
		$('#frmKbEditPanel :input:text:first').focus().select();
	} );
	
	/* WYSIWYG Editor */
    var arr = $('#content_raw').rte( {
		controls_rte: {
			sep1: { separator: true }, 
			h1: { command: 'heading', args:'<h1>' }, 
			h2: { command: 'heading', args:'<h2>' }, 
			h3: { command: 'heading', args:'<h3>' }, 
			bold: { command: 'bold' }, 
			italic: { command: 'italic' }, 
			underline: { command: 'underline' }, 
			superscript: { command: 'superscript' }, 
			subscript: { command: 'subscript' },
			removeFormat: { command: 'removeFormat' },
			sep2: { separator: true }, 
			link: { 
				exec: function() {
					var url = prompt("Link URL:","http://");
					this.editor_cmd('createLink', url);
				} 
			}, 
			unlink: { command: 'unlink' },
			image: { 
				exec: function() {
					var url = prompt("Image URL:","http://");
					this.editor_cmd('insertimage', url);
				} 
			}, 
			sep3: { separator: true }, 
			indent: { command: 'indent' },
			outdent: { command: 'outdent' },
			justifyLeft: { command: 'justifyLeft' },
			justifyCenter: { command: 'justifyCenter' },
			justifyRight: { command: 'justifyRight' },
			justifyFull: { command: 'justifyFull' },
			sep4: { separator: true }, 
			unorderedList: { command: 'insertunorderedlist' },
			orderedList: { command: 'insertorderedlist' },
			sep5: { separator: true }, 
		} ,
		controls_html: { }
	} );
	
	arr['content_raw'].disable_design_mode(false);
</script>
