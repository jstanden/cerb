<form action="{devblocks_url}{/devblocks_url}" style="margin-bottom:5px;">
	{if $active_worker->hasPriv('core.addybook.addy.actions.update')}
	<button type="button" onclick="genericAjaxPanel('c=contacts&a=showAddressPeek&id=0&org_id={$contact->id}&view_id={$view->id}',this,false,'500px',ajax.cbAddressPeek);"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/businessman_add.gif{/devblocks_url}" align="top"> Add Contact</button>
	{/if}
</form>

<div id="vieworg_contacts">{$view->render()}</div>