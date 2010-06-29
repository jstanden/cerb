<form>
	<button type="button" onclick="genericAjaxPopup('peek','c=community&a=showAddPortalPeek&view_id={$view->id|escape:'url'}',null,false,'500');"><img src="{devblocks_url}c=resource&p=usermeet.core&f=images/text_code_add.png{/devblocks_url}" align="top"> Add Community Portal</button>	
</form>

{include file="$core_tpl/internal/views/search_and_view.tpl" view=$view}