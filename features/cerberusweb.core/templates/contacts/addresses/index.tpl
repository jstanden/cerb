<table cellspacing="0" cellpadding="0" border="0" width="100%">
<tr>
	<td width="1%" nowrap="nowrap" valign="top" style="padding-right:5px;">
		<form action="{devblocks_url}{/devblocks_url}" style="margin-bottom:5px;">
		{if $active_worker->hasPriv('core.addybook.addy.actions.update')}
			<button type="button" onclick="genericAjaxPanel('c=contacts&a=showAddressPeek&id=0&org_id={$contact->id}&view_id={$view->id}',null,false,'500');"><span class="cerb-sprite sprite-add"></span> {$translate->_('addy_book.address.add')}</button>
		{/if}
		</form>
	</td>
	<td width="98%" valign="middle">
	</td>
	<td width="1%" nowrap="nowrap" valign="middle" align="right">
		<form action="{devblocks_url}{/devblocks_url}" method="post">
		<input type="hidden" name="c" value="contacts">
		<input type="hidden" name="a" value="doAddressQuickSearch">
		<span><b>{$translate->_('common.quick_search')|capitalize}:</b></span> <select name="type">
			<option value="email">{$translate->_('address.email')|capitalize}</option>
			<option value="org">{$translate->_('contact_org.name')|capitalize}</option>
		</select><input type="text" name="query" size="24"><button type="submit">{$translate->_('common.search_go')|lower}</button>
		</form>
	</td>
</tr>
</table>

<form action="#" method="POST" id="filter{$view->id}">
<input type="hidden" name="c" value="internal">
<input type="hidden" name="a" value="">
<input type="hidden" name="id" value="{$view->id}">

<div id="viewCustomFilters{$view->id}" style="margin:10px;">
{include file="$core_tpl/internal/views/customize_view_criteria.tpl"}
</div>
</form>

<div id="view{$view->id}">{$view->render()}</div>

<script>
	$('#viewCustomFilters{$view->id}').bind('devblocks.refresh', function(event) {
		if(event.target == event.currentTarget)
			genericAjaxGet('view{$view->id}','c=internal&a=viewRefresh&id={$view->id|escape}');
	} );
</script>
