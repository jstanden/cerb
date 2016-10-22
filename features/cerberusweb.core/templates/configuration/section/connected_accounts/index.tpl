{if $view instanceof IAbstractView_QuickSearch}
<div style="float:right;">
	{include file="devblocks:cerberusweb.core::search/quick_search.tpl" view=$view return_url=null reset=false}
</div>
{/if}

<div style="float:left;">
	<h2>{'common.connected_accounts'|devblocks_translate|capitalize}</h2>
</div>

<div style="clear:both;"></div>

{include file="devblocks:cerberusweb.core::internal/views/search_and_view.tpl" view=$view}
