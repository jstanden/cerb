<h2>{'common.workers'|devblocks_translate|capitalize}</h2>

<form>
	<button type="button" onclick="genericAjaxPopup('peek','c=config&a=handleSectionAction&section=workers&action=showWorkerPeek&id=0&view_id={$view->id|escape:'url'}',null,false,'50%');"><span class="glyphicons glyphicons-circle-plus" style="color:rgb(0,180,0);"></span> Add Worker</button>
</form>

{include file="devblocks:cerberusweb.core::internal/views/search_and_view.tpl" view=$view}