<div style="float:right;">
	{include file="devblocks:cerberusweb.core::workspaces/quick_search.tpl" view_id=$view->id}
</div>

<div style="float:right;margin-right:10px;">
<form action="javascript:;">
	{if $context_ext instanceof IDevblocksContextPeek}
	<button type="button" onclick="genericAjaxPopup('peek','c=internal&a=showPeekPopup&context={$context_ext->id}&context_id=0&view_id={$view->id}',null,false,'500');"><span class="cerb-sprite2 sprite-plus-circle-frame"></span> {$translate->_('common.add')|capitalize}</button>
	{/if}
	{if $context_ext instanceof IDevblocksContextImport}
	<button type="button" onclick="genericAjaxPopup('import','c=internal&a=showImportPopup&context={$context_ext->id}&view_id={$view->id}',null,false,'500');"><span class="cerb-sprite sprite-import"></span> {$translate->_('common.import')|capitalize}</button>
	{/if}
</form>
</div>

<div style="float:left;">
	<h2>{$context_ext->manifest->name}</h2>
</div>

<div style="clear:both;"></div>

{include file="devblocks:cerberusweb.core::internal/views/search_and_view.tpl" view=$view}

{include file="devblocks:cerberusweb.core::internal/views/view_workflow_keyboard_shortcuts.tpl" view=$view}
