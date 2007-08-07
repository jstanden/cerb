<form action="{devblocks_url}{/devblocks_url}" method="post">
<input type="hidden" name="c" value="contacts">
<input type="hidden" name="a" value="updateContactOrg">
<input type="hidden" name="id" value="{$contact->id}">

<div class="block">
<h2>Details</h2>
<table cellpadding="0" cellspacing="2" border="0">
	<tr>
		<td width="0%" nowrap="nowrap" align="right">Org. Name: </td>
		<td width="100%"><input type="text" name="org_name" value="{$contact->name}" style="width:50%;"></td>
	</tr>
	<tr>
		<td width="0%" nowrap="nowrap" align="right">Account #: </td>
		<td width="100%"><input type="text" name="account_num" value="{$contact->account_number}" style="width:50%;"></td>
	</tr>
	<tr>
		<td align="right" valign="top">Street: </td>
		<td><textarea name="street" style="width:50%;height:50px;">{$contact->street}</textarea></td>
	</tr>
	<tr>
		<td align="right">City: </td>
		<td><input type="text" name="city" value="{$contact->city}" style="width:50%;"></td>
	</tr>
	<tr>
		<td align="right">Province: </td>
		<td><input type="text" name="province" value="{$contact->province}" style="width:50%;"></td>
	</tr>
	<tr>
		<td align="right">Postal: </td>
		<td><input type="text" name="postal" value="{$contact->postal}" style="width:50%;"></td>
	</tr>
	<tr>
		<td align="right">Country: </td>
		<td><input type="text" name="country" value="{$contact->country}" style="width:50%;"></td>
	</tr>
	<tr>
		<td align="right">Phone: </td>
		<td><input type="text" name="phone" value="{$contact->phone}" style="width:50%;"></td>
	</tr>
	<tr>
		<td align="right">Fax: </td>
		<td><input type="text" name="fax" value="{$contact->fax}" style="width:50%;"></td>
	</tr>
	<tr>
		<td align="right">Website: </td>
		<td><input type="text" name="website" value="{$contact->website}" style="width:50%;"></td>
	</tr>
</table>
<button type="submit"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/check.gif{/devblocks_url}" align="top"> {$translate->_('common.save_changes')|capitalize}</button>
<!-- <button type="button" onclick="document.location='{devblocks_url}c=contacts&a=orgs{/devblocks_url}';"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/delete.gif{/devblocks_url}" align="top"> {$translate->_('common.cancel')|capitalize}</button>  -->
</div>
</form>
