<form>
	<button type="button" onclick="genericAjaxPopup('peek','c=community&a=showAddTemplatePeek&portal={$tool->code}&view_id={$view->id|escape:'url'}',null,false,'600');"><img src="{devblocks_url}c=resource&p=usermeet.core&f=images/text_code_add.png{/devblocks_url}" align="top"> Add Custom Template</button>	
	<button type="button" onclick="genericAjaxPopup('peek','c=community&a=showImportTemplatesPeek&portal={$tool->code}&view_id={$view->id|escape:'url'}',null,false,'500');"><span class="cerb-sprite sprite-import"></span> Import Templates</button>	
</form>

<div id="view{$view->id}">{$view->render()}</div>
