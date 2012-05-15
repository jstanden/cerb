<ul class="submenu">
</ul>
<div style="clear:both;"></div>

<form action="{devblocks_url}{/devblocks_url}" style="margin-bottom:5px;">
	<button type="button" onclick="genericAjaxPopup('peek','c=example.objects&a=showEntryPopup&id=0&view_id={$view->id}',null,false,'500');"><span class="cerb-sprite2 sprite-plus-circle"></span> {$translate->_('common.add')|capitalize}</button>
</form>

{include file="devblocks:cerberusweb.core::internal/views/search_and_view.tpl" view=$view}