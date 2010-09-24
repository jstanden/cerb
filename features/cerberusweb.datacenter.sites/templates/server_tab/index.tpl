<form action="{devblocks_url}{/devblocks_url}" style="margin-bottom:5px;">
{if 1 || $active_worker->hasPriv('xxx')}<button type="button" onclick="genericAjaxPopup('peek','c=datacenter.sites&a=showSitePeek&id=0&view_id={$view->id}',null,false,'500');"><span class="cerb-sprite sprite-add"></span> {$translate->_('cerberusweb.datacenter.sites.tab.add')}</button>{/if}
</form>

{include file="$core_tpl/internal/views/search_and_view.tpl" view=$view}