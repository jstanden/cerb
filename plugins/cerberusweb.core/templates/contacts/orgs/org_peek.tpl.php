<table cellpadding="0" cellspacing="0" border="0" width="98%">
	<tr>
		<td align="left" width="0%" nowrap="nowrap" style="padding-right:5px;"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/businessmen.gif{/devblocks_url}" align="absmiddle"></td>
		<td align="left" width="100%" nowrap="nowrap"><h1>Address Book</h1></td>
	</tr>
</table>

<form action="{devblocks_url}{/devblocks_url}" method="POST" id="formBatchUpdate" name="formBatchUpdate">
<!-- <input type="hidden" name="action_id" value="{$id}"> -->
<input type="hidden" name="c" value="contacts">
<input type="hidden" name="a" value="saveOrgPeek">
<input type="hidden" name="view_id" value="{$view_id}">
<input type="hidden" name="id" value="{$contact->id}">
<input type="hidden" name="do_delete" value="0">

<div style="height:300px;overflow:auto;border:1px solid rgb(180,180,180);margin:2px;padding:3px;background-color:rgb(255,255,255);">

<table cellpadding="0" cellspacing="2" border="0" width="98%">
	<tr>
		<td width="0%" nowrap="nowrap" align="right">Org. Name: </td>
		<td width="100%"><input type="text" name="org_name" value="{$contact->name}" style="width:98%;"></td>
	</tr>
	<tr>
		<td width="0%" nowrap="nowrap" align="right">Account #: </td>
		<td width="100%"><input type="text" name="account_num" value="{$contact->account_number}" style="width:98%;"></td>
	</tr>
	<tr>
		<td align="right" valign="top">Street: </td>
		<td><textarea name="street" style="width:98%;height:50px;">{$contact->street}</textarea></td>
	</tr>
	<tr>
		<td align="right">City: </td>
		<td><input type="text" name="city" value="{$contact->city}" style="width:98%;"></td>
	</tr>
	<tr>
		<td align="right">Province: </td>
		<td><input type="text" name="province" value="{$contact->province}" style="width:98%;"></td>
	</tr>
	<tr>
		<td align="right">Postal: </td>
		<td><input type="text" name="postal" value="{$contact->postal}" style="width:98%;"></td>
	</tr>
	<tr>
		<td align="right">Country: </td>
		<td><input type="text" name="country" value="{$contact->country}" style="width:98%;"></td>
	</tr>
	<tr>
		<td align="right">Phone: </td>
		<td><input type="text" name="phone" value="{$contact->phone}" style="width:98%;"></td>
	</tr>
	<tr>
		<td align="right">Fax: </td>
		<td><input type="text" name="fax" value="{$contact->fax}" style="width:98%;"></td>
	</tr>
	<tr>
		<td align="right">Website: </td>
		<td><input type="text" name="website" value="{$contact->website}" style="width:98%;"></td>
	</tr>
	{if !empty($slas)}
	<tr>
		<td width="0%" nowrap="nowrap" align="right">Service Level: </td>
		<td width="100%">
			<select name="sla_id">
				<option value="0">-- none --</option>
				{foreach from=$slas item=sla key=sla_id}
					<option value="{$sla_id}" {if $sla_id==$contact->sla_id}selected{/if}>{$sla->name|escape}</option>
				{/foreach}
			</select>
		</td>
	</tr>
	{/if}
</table>

</div>

<button type="button" onclick="genericPanel.hide();genericAjaxPost('formBatchUpdate', 'view{$view_id}')"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/check.gif{/devblocks_url}" align="top"> {$translate->_('common.save_changes')|capitalize}</button>
<button type="button" onclick="genericPanel.hide();"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/delete.gif{/devblocks_url}" align="top"> {$translate->_('common.close')|capitalize}</button>
{if !empty($contact->id)}<button type="button" onclick="{literal}if(confirm('Are you sure you want to permanently delete this contact?')){this.form.do_delete.value='1';genericPanel.hide();genericAjaxPost('formBatchUpdate', 'view{/literal}{$view_id}{literal}');}{/literal}"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/delete2.gif{/devblocks_url}" align="top"> {$translate->_('common.delete')|capitalize}</button>{/if}
<br>
</form>
