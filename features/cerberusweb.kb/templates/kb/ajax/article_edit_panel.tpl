<form action="{devblocks_url}{/devblocks_url}" method="POST" id="frmKbEditPanel" onsubmit="return false;">
<input type="hidden" name="c" value="kb.ajax">
<input type="hidden" name="a" value="saveArticleEditPanel">
<input type="hidden" name="id" value="{$article->id}">
<input type="hidden" name="do_delete" value="0">

<div id="kbArticleTabs">
	<ul>
		<li><a href="#kbArticleEditor">Editor</a></li>
		<li><a href="#kbArticleProperties">Properties</a></li>
	</ul>
	
	<div id="kbArticleEditor">
		<b>Title:</b><br>
		<input type="text" name="title" value="{$article->title|escape}" style="width:99%;border:solid 1px rgb(180,180,180);"><br>
		
		<textarea id="content" name="content" style="width:99%;height:200px;border:solid 1px rgb(180,180,180);">{$article->content|escape}</textarea>
		<br>
		
		Format:
		<label><input type="radio" name="format" value="2" {if 2==$article->format || empty($article->format)}checked{/if}> <b>Markdown</b> (recommended)</label> [<a href="http://en.wikipedia.org/wiki/Markdown" target="_blank">?</a>] 
		<label><input type="radio" name="format" value="1" {if 1==$article->format}checked{/if}> <b>HTML</b></label> 
		<br>
		<br>
		
		<div id="kbEditPreview"></div>		
	</div>
	
	<div id="kbArticleProperties">
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
		
		{include file="file:$core_tpl/internal/custom_fields/bulk/form.tpl" bulk=false}
		<br>
	</div>
</div> 

{if $active_worker->hasPriv('core.kb.articles.modify')}<button type="button" id="btnKbArticleEditSave"><span class="cerb-sprite sprite-check"></span> {$translate->_('common.save_changes')|capitalize}</button>{/if} 
{if !empty($article) && $active_worker->hasPriv('core.kb.articles.modify')}<button type="button" onclick="if(confirm('Are you sure you want to permanently delete this article?')) { this.form.do_delete.value='1';$('#btnKbArticleEditSave').click(); } "><span class="cerb-sprite sprite-delete2"></span> {$translate->_('common.delete')|capitalize}</button>{/if}
</form>

<script type="text/javascript">
	$popup = genericAjaxPopupFetch('peek');
	$popup.one('popup_open',function(event,ui) {
		$(this).dialog('option','title','Knowledgebase Article');
		$("#kbArticleTabs").tabs();
		$('#frmKbEditPanel :input:text:first').focus().select();
		
		{if 1==$article->format}
		$("#content").markItUp(markitupHTMLSettings);
		{else}
		$("#content").markItUp(markitupMarkdownSettings);
		{/if}

		$('#frmKbEditPanel input[name=format]').bind('click', function(event) {
			$("#content").markItUpRemove();
			if(2==$(event.target).val()) {
				$("#content").markItUp(markitupMarkdownSettings);
			} else if(1==$(event.target).val()) {
				$("#content").markItUp(markitupHTMLSettings);
			} 
		} );
		
		$('#btnKbArticleEditSave').bind('click', function() {
			genericAjaxPopupClose('peek');
			genericAjaxPost('frmKbEditPanel', '', '', function(json) {
			{if !empty($view_id)}
			genericAjaxGet('view{$view_id}','c=internal&a=viewRefresh&id={$view_id|escape}');
			{elseif !empty($return_uri)}
			document.location = "{devblocks_url}{/devblocks_url}{$return_uri}";
			{/if}
			} );
		} );
	} );
</script>
