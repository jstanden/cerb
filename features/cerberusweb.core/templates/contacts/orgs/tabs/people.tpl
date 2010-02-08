<form action="{devblocks_url}{/devblocks_url}" style="margin-bottom:5px;">
	{if $active_worker->hasPriv('core.addybook.addy.actions.update')}
	<button type="button" onclick="genericAjaxPanel('c=contacts&a=showAddressPeek&id=0&org_id={$contact->id}&view_id={$view->id}',null,false,'500');"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/add.png{/devblocks_url}" align="top"> {'addy_book.address.add'|devblocks_translate}</button>
	{/if}
</form>

<div id="vieworg_contacts">{$view->render()}</div>