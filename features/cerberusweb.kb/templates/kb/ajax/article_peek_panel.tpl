<form action="{devblocks_url}{/devblocks_url}" method="POST">

<div style="overflow:auto;height:300px;border:solid 1px rgb(180,180,180);background-color:rgb(255,255,255);" ondblclick="if(null != genericPanel) genericPanel.dialog('close');">
{if !$article->format}{$article->content|escape|nl2br}{else}{$article->content}{/if}
</div>
<br>

{if $active_worker->hasPriv('core.kb.articles.modify')}<button type="button" onclick="genericAjaxPanel('c=kb.ajax&a=showArticleEditPanel&id={$article->id}&view_id={$view_id}',null,false,'700');"><span class="cerb-sprite sprite-document_edit"></span> {$translate->_('common.edit')|capitalize}</button>{/if}
</form>

<script language="JavaScript1.2" type="text/javascript">
	genericPanel.one('dialogopen',function(event,ui) {
		genericPanel.dialog('option','title','{$article->title|escape}');
		$('#frmKbEditPanel :input:text:first').focus().select();
	} );
</script>
