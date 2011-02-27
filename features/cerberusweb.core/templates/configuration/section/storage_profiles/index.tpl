<h2>Storage Profiles</h2>

<form action="{devblocks_url}{/devblocks_url}" method="POST" style="margin-bottom:5px;">
	<button type="button" onclick="genericAjaxPopup('peek','c=config&a=handleSectionAction&section=storage_profiles&action=showStorageProfilePeek&id=0&view_id={$view->id|escape:'url'}',null,false,'500');"><span class="cerb-sprite sprite-add"></span> {'common.add'|devblocks_translate|capitalize}</button>
</form>

<div id="view{$view->id}">{$view->render()}</div>

{*{include file="devblocks:cerberusweb.core::internal/views/search_and_view.tpl" view=$view}*}
