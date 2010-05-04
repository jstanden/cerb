<form action="{devblocks_url}{/devblocks_url}" method="POST">

<iframe src="{$smarty.const.DEVBLOCKS_WEBPATH}ajax.php?c=kb.ajax&a=getArticleContent&id={$article->id|escape}" style="margin:5px 0px 5px 5px;height:400px;width:98%;border:1px solid rgb(200,200,200);" frameborder="0"></iframe>
<br>

{if $active_worker->hasPriv('core.kb.articles.modify')}<button type="button" onclick="genericAjaxPanel('c=kb.ajax&a=showArticleEditPanel&id={$article->id}&view_id={$view_id}',null,false,'725');"><span class="cerb-sprite sprite-document_edit"></span> {$translate->_('common.edit')|capitalize}</button>{/if}
</form>

<script language="JavaScript1.2" type="text/javascript">
	genericPanel.one('dialogopen',function(event,ui) {
		genericPanel.dialog('option','title','{$article->title|escape}');
		$('#frmKbEditPanel :input:text:first').focus().select();
	} );
</script>
