<h2>{'common.workers'|devblocks_translate|capitalize}</h2>

<form>
	<button type="button" onclick="genericAjaxPopup('peek','c=config&a=handleSectionAction&section=workers&action=showWorkerPeek&id=0&view_id={$view->id|escape:'url'}',null,false,'500');"><span class="cerb-sprite2 sprite-plus-circle"></span> Add Worker</button>
</form>

{include file="devblocks:cerberusweb.core::internal/views/search_and_view.tpl" view=$view}