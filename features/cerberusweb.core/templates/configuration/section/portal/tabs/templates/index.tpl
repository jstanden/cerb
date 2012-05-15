<form style="margin:5px 0px;">
	<button type="button" onclick="genericAjaxPopup('peek','c=config&a=handleSectionAction&section=portal&action=showAddTemplatePeek&portal={$tool->code}&view_id={$view->id|escape:'url'}',null,false,'600');"><span class="cerb-sprite2 sprite-plus-circle"></span> Add Custom Template</button>	
	<button type="button" onclick="genericAjaxPopup('peek','c=config&a=handleSectionAction&section=portal&action=showImportTemplatesPeek&portal={$tool->code}&view_id={$view->id|escape:'url'}',null,false,'500');"><span class="cerb-sprite sprite-import"></span> Import Templates</button>	
</form>

<div id="view{$view->id}">{$view->render()}</div>
