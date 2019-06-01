<form action="#" style="margin-bottom:5px;float:left;">
	<button type="button" onclick="genericAjaxPopup('peek','c=profiles&a=handleSectionAction&section=community_portal&action=handleProfileTabAction&tab_action=showAddTemplatePeek&portal_id={$portal->id}&view_id={$view->id|escape:'url'}',null,false,'80%');"><span class="glyphicons glyphicons-circle-plus"></span> {'common.add'|devblocks_translate|capitalize}</button></a>
	<button type="button" onclick="genericAjaxPopup('import','c=internal&a=showImportTemplatesPeek&portal_id={$portal->id}&view_id={$view->id|escape:'url'}',null,false,'50%');"><span class="glyphicons glyphicons-file-import"></span> {'common.import'|devblocks_translate|capitalize}</button></a>
</form>

{include file="devblocks:cerberusweb.core::internal/views/search_and_view.tpl" view=$view}