<div class="cerb-ui-header">
	<div>
		<div class="cerb-ui-header--title">{{'common.snippets'|devblocks_translate|capitalize}}</div>
		<div class="cerb-ui-header--subtitle">Reusable text fragments with optional placeholders</div>
	</div>
</div>

<div>
	{include file="devblocks:cerberusweb.core::search/quick_search.tpl" view=$view return_url=null reset=false focus=true}
</div>

{include file="devblocks:cerberusweb.core::internal/views/search_and_view.tpl" view=$view}