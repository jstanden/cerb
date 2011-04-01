<form action="{devblocks_url}{/devblocks_url}" style="margin-bottom:5px;">
	{if $active_worker->hasPriv('core.addybook.addy.actions.update')}
	<button type="button" onclick="genericAjaxPopup('peek','c=contacts&a=showAddressPeek&id=0&org_id={$contact->id}&view_id={$view->id}',null,false,'500');"><span class="cerb-sprite2 sprite-plus-circle-frame"></span> {'addy_book.address.add'|devblocks_translate}</button>
	{/if}
</form>

<div id="vieworg_contacts">{$view->render()}</div>