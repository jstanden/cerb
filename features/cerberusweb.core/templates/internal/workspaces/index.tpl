<form action="{devblocks_url}{/devblocks_url}" method="POST" style="margin:5px;">
	<input type="hidden" name="c" value="internal">
	<input type="hidden" name="a" value="">
	<button type="button" onclick="genericAjaxPopup('peek','c=internal&a=showEditWorkspacePanel&id={$workspace->id}&request={$request|escape:'url'}',null,true,'600');"><span class="cerb-sprite sprite-gear"></span> {$translate->_('dashboard.edit')|capitalize}</button>
</form>
