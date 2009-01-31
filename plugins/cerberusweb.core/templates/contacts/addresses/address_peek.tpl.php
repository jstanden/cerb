<table cellpadding="0" cellspacing="0" border="0" width="98%">
	<tr>
		<td align="left" width="1%" nowrap="nowrap" style="padding-right:5px;"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/businessmen.gif{/devblocks_url}" align="absmiddle"></td>
		<td align="left" width="98%"><h1>E-mail Contact</h1></td>
		{*<td align="left" width="1%" nowrap="nowrap"><a href="{devblocks_url}&c=contacts&a=addresses&id={$address.a_id}{/devblocks_url}">view full record</a></td>*}
	</tr>
</table>

<form action="{devblocks_url}{/devblocks_url}" method="POST" id="formAddressPeek" name="formAddressPeek" onsubmit="return false;">
<!-- <input type="hidden" name="action_id" value="{$id}"> -->
<input type="hidden" name="c" value="contacts">
<input type="hidden" name="a" value="saveContact">
<input type="hidden" name="id" value="{$address.a_id}">
<input type="hidden" name="view_id" value="{$view_id}">
<input type="hidden" name="closed" value="0">

<div style="height:250px;overflow:auto;margin:2px;padding:3px;">

<table cellpadding="0" cellspacing="2" border="0" width="98%">
	<tr>
		<td width="0%" nowrap="nowrap" align="right">E-mail: </td>
		<td width="100%">
			{if $id == 0}
				{if !empty($email)}
					<input type="hidden" name="email" value="{$email|escape}">
					<b>{$email}</b>
				{else}
					<input type="text" name="email" style="width:98%;" value="{$email|escape}">
				{/if}
			{else}
				<b>{$address.a_email}</b>

				{* Domain Shortcut *}
				{assign var=email_parts value=$address.a_email|explode:'@'}
				{if is_array($email_parts) && 2==count($email_parts)}
					(<a href="http://www.{$email_parts.1}" target="_blank" title="www.{$email_parts.1}">web site</a>)
				{/if}
			{/if}
		</td>
	</tr>
	
	<tr>
		<td width="0%" nowrap="nowrap" align="right">First Name: </td>
		<td width="100%"><input type="text" name="first_name" value="{$address.a_first_name|escape}" style="width:98%;"></td>
	</tr>
	<tr>
		<td width="0%" nowrap="nowrap" align="right">Last Name: </td>
		<td width="100%"><input type="text" name="last_name" value="{$address.a_last_name|escape}" style="width:98%;"></td>
	</tr>
	<tr>
		<td width="0%" nowrap="nowrap" align="right" valign="top">Organization: </td>
		<td width="100%" valign="top">
			{if !empty($address.a_contact_org_id)}
				<b>{if !empty($address.o_name)}{$address.o_name}{else if !empty({$org_name})}{$org_name}{/if}</b>
				(<a href="javascript:;" onclick="genericAjaxPanel('c=contacts&a=showOrgPeek&id={if !empty($address.a_contact_org_id)}{$address.a_contact_org_id}{else}{$org_id}{/if}&view_id={$view->id}',null,false,'500px',ajax.cbOrgCountryPeek);">peek</a>)
				(<a href="javascript:;" onclick="toggleDiv('divAddressOrg');">change</a>)
				<br>
			{/if}
			<div id="divAddressOrg" style="display:{if empty($address.a_contact_org_id)}block{else}none{/if};">
				<div id="contactautocomplete" style="width:98%;" class="yui-ac">
					<input type="text" name="contact_org" id="contactinput" value="{if !empty($address.a_contact_org_id)}{$address.o_name|escape}{else}{$org_name|escape}{/if}" class="yui-ac-input">
					<div id="contactcontainer" class="yui-ac-container"></div>
					<br>
					<br>
				</div>
			</div>
		</td>
	</tr>
	<tr>
		<td width="0%" nowrap="nowrap" align="right">Banned: </td>
		<td width="100%">
			<select name="is_banned">
				<option value="0"></option>
				<option value="0" {if !$address.a_is_banned}selected{/if}>No</option>
				<option value="1" {if $address.a_is_banned}selected{/if}>Yes</option>
			</select>
		</td>
	</tr>
</table>

{include file="file:$core_tpl/internal/custom_fields/bulk/form.tpl.php" checkboxes=false}
<br>

</div>


<button type="button" onclick="genericPanel.hide();genericAjaxPost('formAddressPeek', 'view{$view_id}', '');"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/check.gif{/devblocks_url}" align="top"> {$translate->_('common.save_changes')}</button>
<button type="button" onclick="genericPanel.hide();genericAjaxPostAfterSubmitEvent.unsubscribeAll();"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/delete.gif{/devblocks_url}" align="top"> {$translate->_('common.cancel')|capitalize}</button>

{if $id != 0}
	&nbsp; 
	<a href="javascript:;" onclick="document.formAddressPeek.a.value='showAddressTickets';document.formAddressPeek.closed.value='0';document.formAddressPeek.submit();">{$open_count} open ticket(s)</a>
	 | 
	<a href="javascript:;" onclick="document.formAddressPeek.a.value='showAddressTickets';document.formAddressPeek.closed.value='1';document.formAddressPeek.submit();">{$closed_count} closed ticket(s)</a>
	 | 
	<a href="javascript:;" onclick="genericAjaxPanel('c=tickets&a=showComposePeek&view_id=&to={$address.a_email}',null,false,'600px',{literal}function(o){ajax.cbEmailMultiplePeek(o);document.getElementById('formComposePeek').team_id.focus();}{/literal});"> compose</a>
{/if}

<br>
</form>
