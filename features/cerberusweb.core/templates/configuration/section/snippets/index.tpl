<div>
	<h2>{'common.snippets'|devblocks_translate|capitalize}</h2>
</div>

<div>
	{include file="devblocks:cerberusweb.core::search/quick_search.tpl" view=$view return_url=null reset=false focus=true}
</div>

{include file="devblocks:cerberusweb.core::internal/views/search_and_view.tpl" view=$view}