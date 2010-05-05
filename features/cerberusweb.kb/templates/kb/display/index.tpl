<div id="headerSubMenu">
	<div style="padding-bottom:5px;"></div>
</div>

<div style="float:right;">
	<form action="{devblocks_url}{/devblocks_url}" method="post">
	<input type="hidden" name="c" value="kb.ajax">
	<input type="hidden" name="a" value="doArticleQuickSearch">
	<span><b>{$translate->_('common.search')|capitalize}:</b></span> <select name="type">
		<option value="articles_all">Articles (all words)</option>
		<option value="articles_phrase">Articles (phrase)</option>
	</select><input type="text" name="query" size="24"><button type="submit">go!</button>
	</form>
</div>

<h1>{$article->title|escape}</h1>
<div style="margin-bottom:5px;">
	<b>{$translate->_('kb_article.updated')|capitalize}:</b> <abbr title="{$article->updated|devblocks_date}">{$article->updated|devblocks_prettytime}</abbr> &nbsp;
	<b>{$translate->_('kb_article.views')|capitalize}:</b> {$article->views|escape} &nbsp;
	<b>{$translate->_('common.id')|upper}:</b> {$article->id|escape} &nbsp; 
	<br>
	
	{if !empty($breadcrumbs)}
	<b>Filed under:</b> 
	{foreach from=$breadcrumbs item=trail name=trail}
		{foreach from=$trail item=step key=cat_id name=cats}
		<a href="{devblocks_url}c=kb&a=category&id={$cat_id|escape}{/devblocks_url}">{$categories.{$cat_id}->name}</a>
		{if !$smarty.foreach.cats.last} &raquo; {/if}
		{/foreach}
		{if !$smarty.foreach.trail.last}; {/if}
	{/foreach}
	<br>
	{/if}
</div>

<form>
{if $active_worker->hasPriv('core.kb.articles.modify')}<button type="button" onclick="genericAjaxPanel('c=kb.ajax&a=showArticleEditPanel&id={$article->id}&return_uri={"kb/article/{$article->id}"|escape}',null,false,'725');"><span class="cerb-sprite sprite-document_edit"></span> {$translate->_('common.edit')|capitalize}</button>{/if}	
</form>

<iframe src="{$smarty.const.DEVBLOCKS_WEBPATH}ajax.php?c=kb.ajax&a=getArticleContent&id={$article->id|escape}" style="margin:5px 0px 5px 5px;height:50%;width:98%;border:1px solid rgb(200,200,200);" frameborder="0"></iframe>
<br>
