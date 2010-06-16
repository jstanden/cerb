<form>
	<button type="button" onclick="genericAjaxPanel('c=config&a=showWorkerPeek&id=0&view_id={$view->id|escape:'url'}',null,false,'500');"><span class="cerb-sprite sprite-add"></span> Add Worker</button>
</form>

{include file="$core_tpl/internal/views/search_and_view.tpl" view=$view}