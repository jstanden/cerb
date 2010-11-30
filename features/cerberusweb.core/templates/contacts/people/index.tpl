{*
{if $active_worker->hasPriv('core.addybook.addy.actions.update')}
<div>
	<button type="button" onclick="genericAjaxPopup('peek','c=contacts&a=showPeoplePeek&id=0&view_id={$view->id}',null,false,'500');"><span class="cerb-sprite sprite-add"></span> {$translate->_('common.add')|capitalize}</button>
</div>
{/if}
*}

{include file="devblocks:cerberusweb.core::internal/views/search_and_view.tpl" view=$view}