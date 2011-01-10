<ul class="submenu">
</ul>
<div style="clear:both;"></div>

<div style="float:right;">
	<form action="{devblocks_url}{/devblocks_url}" method="post">
	<input type="hidden" name="c" value="kb.ajax">
	<input type="hidden" name="a" value="doArticleQuickSearch">
	<span><b>{$translate->_('common.search')|capitalize}:</b></span> <select name="type">
		<option value="articles_all">Articles (all words)</option>
		<option value="articles_phrase">Articles (phrase)</option>
	</select><input type="text" name="query" class="input_search" size="24"><button type="submit">go!</button>
	</form>
</div>

<fieldset style="float:left;min-width:400px;">
	<legend>Knowledgebase Article</legend>
	<h1><b>{$article->title}</b></h1>
		
	<b>{$translate->_('kb_article.updated')|capitalize}:</b> <abbr title="{$article->updated|devblocks_date}">{$article->updated|devblocks_prettytime}</abbr> &nbsp;
	<b>{$translate->_('kb_article.views')|capitalize}:</b> {$article->views} &nbsp;
	<b>{$translate->_('common.id')|upper}:</b> {$article->id} &nbsp; 
	<br>
	
	{if !empty($breadcrumbs)}
	<b>Filed under:</b> 
	{foreach from=$breadcrumbs item=trail name=trail}
		{foreach from=$trail item=step key=cat_id name=cats}
		<a href="{devblocks_url}c=kb&a=category&id={$cat_id}{/devblocks_url}">{$categories.{$cat_id}->name}</a>
		{if !$smarty.foreach.cats.last} &raquo; {/if}
		{/foreach}
		{if !$smarty.foreach.trail.last}; {/if}
	{/foreach}
	{/if}
	
	<form style="margin:5px;">
	{if $active_worker->hasPriv('core.kb.articles.modify')}<button type="button" onclick="genericAjaxPopup('peek','c=kb.ajax&a=showArticleEditPanel&id={$article->id}&return_uri={"kb/article/{$article->id}"}',null,false,'725');"><span class="cerb-sprite sprite-document_edit"></span> {$translate->_('common.edit')|capitalize}</button>{/if}	
	</form>
</fieldset>

<div style="clear:both;"></div>

<div>
	{$article->getContent() nofilter}
</div>

{include file="devblocks:cerberusweb.core::internal/attachments/list.tpl" context={CerberusContexts::CONTEXT_KB_ARTICLE} context_id={$article->id}}