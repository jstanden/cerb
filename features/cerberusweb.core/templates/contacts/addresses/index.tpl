<table cellspacing="0" cellpadding="0" border="0" width="100%">
<tr>
	<td width="1%" nowrap="nowrap" valign="top" style="padding-right:5px;">
		<form action="{devblocks_url}{/devblocks_url}" style="margin-bottom:5px;">
		{if $active_worker->hasPriv('core.addybook.addy.actions.update')}
			<button type="button" onclick="genericAjaxPopup('peek','c=contacts&a=showAddressPeek&id=0&org_id={$contact->id}&view_id={$view->id}',null,false,'500');"><span class="cerb-sprite2 sprite-plus-circle-frame"></span> {$translate->_('addy_book.address.add')}</button>
		{/if}
		</form>
	</td>
	<td width="98%" valign="middle">
	</td>
	<td width="1%" nowrap="nowrap" valign="middle" align="right">
		{include file="devblocks:cerberusweb.core::contacts/addresses/quick_search.tpl"}
	</td>
</tr>
</table>

{include file="devblocks:cerberusweb.core::internal/views/search_and_view.tpl" view=$view}

{include file="devblocks:cerberusweb.core::internal/views/view_workflow_keyboard_shortcuts.tpl" view=$view}
