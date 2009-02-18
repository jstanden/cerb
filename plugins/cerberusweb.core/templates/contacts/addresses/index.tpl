{include file="file:$core_tpl/contacts/submenu.tpl"}

<table cellspacing="0" cellpadding="0" border="0" width="100%">
<tr>
	<td width="1%" nowrap="nowrap" valign="top" style="padding-right:5px;">
		<h1>Addresses</h1>
	</td>
	<td width="98%" valign="middle">
	</td>
	<td width="1%" nowrap="nowrap" valign="middle" align="right">
		<form action="{devblocks_url}{/devblocks_url}" method="post">
		<input type="hidden" name="c" value="contacts">
		<input type="hidden" name="a" value="doAddressQuickSearch">
		<span><b>Quick Search:</b></span> <select name="type">
			<option value="email">E-mail</option>
			<option value="org">Organization</option>
		</select><input type="text" name="query" size="24"><button type="submit">go!</button>
		</form>
	</td>
</tr>
</table>

<form action="{devblocks_url}{/devblocks_url}" style="margin-bottom:5px;">
{if $active_worker->hasPriv('core.addybook.addy.actions.update')}
	<button type="button" onclick="genericAjaxPanel('c=contacts&a=showAddressPeek&id=0&org_id={$contact->id}&view_id={$view->id}',this,false,'500px',ajax.cbAddressPeek);"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/businessman_add.gif{/devblocks_url}" align="top"> Add Contact</button>
{/if}
</form>

<table cellpadding="0" cellspacing="0" border="0">
	<tr>
		<td valign="top" width="0%" nowrap="nowrap">
			{include file="file:$core_tpl/internal/views/criteria_list.tpl" divName="searchCriteriaDialog"}
			<div id="searchCriteriaDialog" style="visibility:visible;"></div>
		</td>
		<td valign="top" width="0%" nowrap="nowrap"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/spacer.gif{/devblocks_url}" width="5" height="1"></td>
		<td valign="top" width="100%">
			<div id="view{$view->id}">{$view->render()}</div>
		</td>
	</tr>
</table>