<div>
	<h2>{'common.package.library'|devblocks_translate|capitalize}</h2>
</div>

<div>
	{include file="devblocks:cerberusweb.core::search/quick_search.tpl" view=$view return_url=null reset=false}
</div>

{include file="devblocks:cerberusweb.core::internal/views/search_and_view.tpl" view=$view}
