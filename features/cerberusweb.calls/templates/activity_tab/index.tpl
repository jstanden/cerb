<form action="{devblocks_url}{/devblocks_url}" style="margin-bottom:5px;">
	<button type="button" onclick="genericAjaxPopup('peek','c=calls&a=showEntry&id=0&view_id={$view->id}',null,false,'500');"><img src="{devblocks_url}c=resource&p=cerberusweb.calls&f=images/phone_call.png{/devblocks_url}" align="top"> {$translate->_('calls.ui.log_call')|capitalize}</button>
</form>

{include file="devblocks:cerberusweb.core::internal/views/search_and_view.tpl" view=$view}