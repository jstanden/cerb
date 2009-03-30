<form action="{devblocks_url}{/devblocks_url}" method="POST">
<input type="hidden" name="c" value="kb.ajax">
<input type="hidden" name="a" value="saveArticlePeekPanel">
<input type="hidden" name="id" value="{$article->id}">

<table cellspacing="0" cellpadding="0" border="0" width="100%">
<tr>
	<td>
		<h1>{$article->title|escape}</h1>
	</td>
</tr>
</table>

<div style="overflow:auto;height:300px;border:solid 1px rgb(180,180,180);background-color:rgb(255,255,255);" ondblclick="if(null != genericPanel) genericPanel.hide();">
{if !$article->format}{$article->content|escape|nl2br}{else}{$article->content}{/if}
</div>
<br>

<br>

{if $active_worker->hasPriv('core.kb.articles.modify')}<button type="button" onclick="genericAjaxPanel('c=kb.ajax&a=showArticleEditPanel&id={$article->id}',null,false,'700px');"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/document_edit.gif{/devblocks_url}" align="top"> {$translate->_('common.edit')|capitalize}</button>{/if}
<button type="button" onclick="genericPanel.hide();"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/delete.gif{/devblocks_url}" align="top"> {$translate->_('common.cancel')|capitalize}</button>
</form>