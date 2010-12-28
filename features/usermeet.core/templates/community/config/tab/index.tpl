<form>
	<button type="button" onclick="genericAjaxPopup('peek','c=community&a=showAddPortalPeek&view_id={$view->id|escape:'url'}',null,false,'500');"><span class="cerb-sprite sprite-add"></span> Add Community Portal</button>	
</form>

{include file="devblocks:cerberusweb.core::internal/views/search_and_view.tpl" view=$view}