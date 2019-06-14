<div>
	<h2>Storage Profiles</h2>
</div>

<div>
	{include file="devblocks:cerberusweb.core::search/quick_search.tpl" view=$view return_url=null reset=false focus=true}
</div>

<form action="{devblocks_url}{/devblocks_url}" method="POST" style="margin-bottom:5px;">
	<button type="button" onclick="genericAjaxPopup('peek','c=config&a=handleSectionAction&section=storage_profiles&action=showStorageProfilePeek&id=0&view_id={$view->id|escape:'url'}',null,false,'50%');"><span class="glyphicons glyphicons-circle-plus" style="color:rgb(0,180,0);"></span> {'common.add'|devblocks_translate|capitalize}</button>
</form>

{include file="devblocks:cerberusweb.core::internal/views/search_and_view.tpl" view=$view}
