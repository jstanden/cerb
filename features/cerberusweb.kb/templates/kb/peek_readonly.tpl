<form action="{devblocks_url}{/devblocks_url}" method="POST">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<iframe src="{devblocks_url full=true}ajax.php?c=kb.ajax&a=getArticleContent&id={$article->id}&_csrf_token={$session.csrf_token}{/devblocks_url}" style="margin:5px 0px 5px 5px;height:400px;width:98%;border:1px solid rgb(200,200,200);" frameborder="0"></iframe>
<br>
{include file="devblocks:cerberusweb.core::internal/attachments/list.tpl" context={CerberusContexts::CONTEXT_KB_ARTICLE} context_id={$article->id}}

{if $active_worker->hasPriv('core.kb.articles.modify')}<button type="button" onclick="genericAjaxPopup('peek','c=kb.ajax&a=showArticleEditPanel&id={$article->id}&view_id={$view_id}',null,false,'725');"><span class="glyphicons glyphicons-edit"></span> {'common.edit'|devblocks_translate|capitalize}</button>{/if}
</form>

<script type="text/javascript">
	$popup = genericAjaxPopupFetch('peek');
	$popup.one('popup_open',function(event,ui) {
		$(this).dialog('option','title','{$article->title|escape:'javascript' nofilter}');
		$('#frmKbEditPanel :input:text:first').focus().select();
	} );
</script>
