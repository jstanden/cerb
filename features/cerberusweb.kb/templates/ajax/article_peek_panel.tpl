<form action="{devblocks_url}{/devblocks_url}" method="POST">

<table cellspacing="0" cellpadding="0" border="0" width="100%">
<tr>
	<td>
		<h1>{$article->title|escape}</h1>
	</td>
</tr>
</table>

<div style="overflow:auto;height:300px;border:solid 1px rgb(180,180,180);background-color:rgb(255,255,255);" ondblclick="if(null != genericPanel) genericPanel.dialog('close');">
{if !$article->format}{$article->content|escape|nl2br}{else}{$article->content}{/if}
</div>
<br>

<br>

{if $active_worker->hasPriv('core.kb.articles.modify')}<button type="button" onclick="genericAjaxPanel('c=kb.ajax&a=showArticleEditPanel&id={$article->id}&view_id={$view_id}',null,false,'700');"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/document_edit.gif{/devblocks_url}" align="top"> {$translate->_('common.edit')|capitalize}</button>{/if}
</form>