<h2>Community Portals</h2>

<form>
	<button type="button" onclick="genericAjaxPopup('peek','c=config&a=handleSectionAction&section=portals&action=showAddPortalPeek&view_id={$view->id|escape:'url'}',null,false,'500');"><span class="cerb-sprite2 sprite-plus-circle-frame"></span> {'common.add'|devblocks_translate|capitalize}</button>	
</form>

{include file="devblocks:cerberusweb.core::internal/views/search_and_view.tpl" view=$view}