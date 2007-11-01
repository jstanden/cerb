<table cellpadding="0" cellspacing="0" border="0" width="98%">
	<tr>
		<td align="left" width="0%" nowrap="nowrap" style="padding-right:5px;"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/businessmen.gif{/devblocks_url}" align="absmiddle"></td>
		<td align="left" width="100%" nowrap="nowrap"><h1>Address Book</h1></td>
	</tr>
</table>

<form action="{devblocks_url}{/devblocks_url}" method="POST" id="formAddressPeek" name="formAddressPeek">
<!-- <input type="hidden" name="action_id" value="{$id}"> -->
<input type="hidden" name="c" value="contacts">
<input type="hidden" name="a" value="saveContact">
<input type="hidden" name="id" value="{$address.a_id}">
<input type="hidden" name="view_id" value="{$view_id}">

<table cellpadding="0" cellspacing="2" border="0">
	<tr>
		<td width="0%" nowrap="nowrap" align="right">E-mail: </td>
		<td width="100%"><b>{$address.a_email}</b></td>
	</tr>
	<tr>
		<td width="0%" nowrap="nowrap" align="right">First Name: </td>
		<td width="100%"><input type="text" name="first_name" value="{$address.a_first_name}" style="width:98%;"></td>
	</tr>
	<tr>
		<td width="0%" nowrap="nowrap" align="right">Last Name: </td>
		<td width="100%"><input type="text" name="last_name" value="{$address.a_last_name}" style="width:98%;"></td>
	</tr>
	<tr>
		<td width="0%" nowrap="nowrap" align="right" valign="top">Organization: </td>
		<td width="100%" valign="top">
			<div id="contactautocomplete" style="width:98%;" class="yui-ac">
				<input type="text" name="contact_org" id="contactinput" value="{$address.o_name}" class="yui-ac-input">
				<div id="contactcontainer" class="yui-ac-container"></div>
			</div>			
			<input type="hidden" name="contact_orgid" value="{$address.a_contact_org_id}"/>
			<br>
			<br>
		</td>
	</tr>
	{if !empty($slas)}
	<tr>
		<td width="0%" nowrap="nowrap" align="right">Service Level: </td>
		<td width="100%">
			<select name="sla_id">
				<option value="0">-- none --</option>
				{foreach from=$slas item=sla key=sla_id}
					<option value="{$sla_id}" {if $sla_id==$address.a_sla_id}selected{/if}>{$sla->name|escape}</option>
				{/foreach}
			</select>
		</td>
	</tr>
	{/if}
</table>

<input type="button" value="{$translate->_('common.save_changes')}" onclick="genericPanel.hide();genericAjaxPost('formAddressPeek', 'view{$view_id}')">
<br>
</form>
