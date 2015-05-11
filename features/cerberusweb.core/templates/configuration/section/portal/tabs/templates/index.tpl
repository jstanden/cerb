<form action="#" style="margin-bottom:5px;float:left;">
	<button type="button" onclick="genericAjaxPopup('peek','c=config&a=handleSectionAction&section=portal&action=showAddTemplatePeek&portal={$tool->code}&view_id={$view->id|escape:'url'}',null,false,'600');"><span class="glyphicons glyphicons-circle-plus" style="color:rgb(0,180,0);"></span> {'common.add'|devblocks_translate|capitalize}</button></a>
</form>

<div style="float:right;">
	{include file="devblocks:cerberusweb.core::search/quick_search.tpl" view=$view return_url=null reset=false}
</div>

{include file="devblocks:cerberusweb.core::internal/views/search_and_view.tpl" view=$view}