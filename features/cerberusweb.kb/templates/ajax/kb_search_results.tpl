Search results for '<b><i>{$q|escape}</i></b>':<br>

{if !empty($results)}
	<ul>
	{foreach from=$results item=article key=article_id}
		<li><a href="javascript:;" onclick="genericAjaxPanel('c=kb.ajax&a=showArticlePeekPanel&id={$article.kb_id}&view_id=',null,false,'700px');">{$article.kb_title|escape}</a></li>
	{/foreach}
	</ul>
{else}
	<p>
	No matching articles were found.
	</p>
{/if}
