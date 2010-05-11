<div id="account">
<div class="header"><h1>{$translate->_('portal.sc.public.my_account')}</h1></div>

{if !empty($account_error)}
<div class="error">{$account_error}</div>
{elseif !empty($account_success)}
<div class="success">{$translate->_('portal.sc.public.my_account.settings_saved')}</div>
{/if}

<form action="{devblocks_url}c=account{/devblocks_url}" method="post" id="myAccountForm">
<input type="hidden" name="a" value="saveAccount">

<table cellpadding="2" cellspacing="2" border="0">

<tbody>
<tr>
	<td width="1%" nowrap="nowrap" valign="top"><b>{$translate->_('common.email')}:</b></td>
	<td width="99%">{$address->email}</td>
</tr>

{if $show_fields.addy_first_name}
<tr>
	<td width="1%" nowrap="nowrap" valign="top"><b>{$translate->_('contact_person.first_name')|capitalize}:</b></td>
	<td width="99%">
		{if 1==$show_fields.addy_first_name}
		{$address->first_name|escape}
		{else}
		<input type="text" name="addy_first_name" size="35" value="{$address->first_name|escape}">
		{/if}
	</td>
</tr>
{/if}

{if $show_fields.addy_last_name}
<tr>
	<td width="1%" nowrap="nowrap" valign="top"><b>{$translate->_('contact_person.last_name')|capitalize}:</b></td>
	<td width="99%">
		{if 1==$show_fields.addy_last_name}
		{$address->last_name|escape}
		{else}
		<input type="text" name="addy_last_name" size="35" value="{$address->last_name|escape}">
		{/if}
	</td>
</tr>
{/if}

{foreach from=$address_custom_fields item=field key=field_id}
{if $show_fields.{"addy_custom_"|cat:$field_id}}
<tr>
	<td width="1%" nowrap="nowrap" valign="top"><b>{$field->name|escape}:</b></td>
	<td width="99%">
		{if 1==$show_fields.{"addy_custom_"|cat:$field_id}}
		{include file="devblocks:usermeet.core:support_center/account/customfields_readonly.tpl:portal_{$portal_code}" values=$address_custom_field_values}
		{elseif 2==$show_fields.{"addy_custom_"|cat:$field_id}}
		{include file="devblocks:usermeet.core:support_center/account/customfields_writeable.tpl:portal_{$portal_code}" values=$address_custom_field_values field_prefix="addy_custom"}
		{else}
		{/if}
	</td>
</tr>
{/if}
{/foreach}

</tbody>

{if !empty($org)}
<tbody>
<tr>
	<td width="1%" nowrap="nowrap" valign="top"></td>
	<td width="99%">&nbsp;</td>
</tr>

{if $show_fields.org_name}
<tr>
	<td width="1%" nowrap="nowrap" valign="top"><b>{$translate->_('contact_org.name')|capitalize}:</b></td>
	<td width="99%">
		{if 1==$show_fields.org_name}
		{$org->name|escape}
		{else}
		<input type="text" name="org_name" size="35" value="{$org->name|escape}">
		{/if}
	</td>
</tr>
{/if}

{if $show_fields.org_street}
<tr>
	<td width="1%" nowrap="nowrap" valign="top"><b>{$translate->_('contact_org.street')|capitalize}:</b></td>
	<td width="99%">
		{if 1==$show_fields.org_street}
		{$org->street|escape}
		{else}
		<input type="text" name="org_street" size="35" value="{$org->street|escape}">
		{/if}
	</td>
</tr>
{/if}

{if $show_fields.org_city}
<tr>
	<td width="1%" nowrap="nowrap" valign="top"><b>{$translate->_('contact_org.city')|capitalize}:</b></td>
	<td width="99%">
		{if 1==$show_fields.org_city}
		{$org->city|escape}
		{else}
		<input type="text" name="org_city" size="35" value="{$org->city|escape}">
		{/if}
	</td>
</tr>
{/if}

{if $show_fields.org_province}
<tr>
	<td width="1%" nowrap="nowrap" valign="top"><b>{$translate->_('contact_org.province')|capitalize}:</b></td>
	<td width="99%">
		{if 1==$show_fields.org_province}
		{$org->province|escape}
		{else}
		<input type="text" name="org_province" size="35" value="{$org->province|escape}">
		{/if}
	</td>
</tr>
{/if}

{if $show_fields.org_postal}
<tr>
	<td width="1%" nowrap="nowrap" valign="top"><b>{$translate->_('contact_org.postal')|capitalize}:</b></td>
	<td width="99%">
		{if 1==$show_fields.org_postal}
		{$org->postal|escape}
		{else}
		<input type="text" name="org_postal" size="35" value="{$org->postal|escape}">
		{/if}
	</td>
</tr>
{/if}

{if $show_fields.org_country}
<tr>
	<td width="1%" nowrap="nowrap" valign="top"><b>{$translate->_('contact_org.country')|capitalize}:</b></td>
	<td width="99%">
		{if 1==$show_fields.org_country}
		{$org->country|escape}
		{else}
		<input type="text" name="org_country" size="35" value="{$org->country|escape}">
		{/if}
	</td>
</tr>
{/if}

{if $show_fields.org_phone}
<tr>
	<td width="1%" nowrap="nowrap" valign="top"><b>{$translate->_('contact_org.phone')|capitalize}:</b></td>
	<td width="99%">
		{if 1==$show_fields.org_phone}
		{$org->phone|escape}
		{else}
		<input type="text" name="org_phone" size="35" value="{$org->phone|escape}">
		{/if}
	</td>
</tr>
{/if}

{if $show_fields.org_website}
<tr>
	<td width="1%" nowrap="nowrap" valign="top"><b>{$translate->_('contact_org.website')|capitalize}:</b></td>
	<td width="99%">
		{if 1==$show_fields.org_website}
		{$org->website|escape}
		{else}
		<input type="text" name="org_website" size="35" value="{$org->website|escape}">
		{/if}
	</td>
</tr>
{/if}

{foreach from=$org_custom_fields item=field key=field_id}
{if $show_fields.{"org_custom_"|cat:$field_id}}
<tr>
	<td width="1%" nowrap="nowrap" valign="top"><b>{$field->name|escape}:</b></td>
	<td width="99%">
		{if 1==$show_fields.{"org_custom_"|cat:$field_id}}
		{include file="devblocks:usermeet.core:support_center/account/customfields_readonly.tpl:portal_{$portal_code}" values=$org_custom_field_values}
		{elseif 2==$show_fields.{"org_custom_"|cat:$field_id}}
		{include file="devblocks:usermeet.core:support_center/account/customfields_writeable.tpl:portal_{$portal_code}" values=$org_custom_field_values field_prefix="org_custom"}
		{/if}
	</td>
</tr>
{/if}
{/foreach}

</tbody>
{/if}
	
{if !empty($login_handler) && 0==strcasecmp($login_handler,'sc.login.auth.default')}
<tbody>
<tr>
	<td width="1%" nowrap="nowrap" valign="top"></td>
	<td width="99%">&nbsp;</td>
</tr>
<tr>
	<td width="1%" nowrap="nowrap" valign="top"><b>{$translate->_('portal.sc.public.my_account.change_password')}</b></td>
	<td width="99%"><input type="password" id="change_password" name="change_password" size="35" value=""></td>
</tr>
<tr>
	<td width="1%" nowrap="nowrap" valign="top"><b>{$translate->_('portal.sc.public.my_account.change_password_verify')}</b></td>
	<td width="99%"><input type="password" name="change_password2" size="35" value=""></td>
</tr>
</tbody>
{/if}

</table>
<br>

<button type="submit"><img src="{devblocks_url}c=resource&p=usermeet.core&f=images/check.gif{/devblocks_url}" align="top"> {$translate->_('common.save_changes')}</button><br>
</form>
</div>

{if !empty($login_handler) && 0==strcasecmp($login_handler,'sc.login.auth.default')}
{literal}
<script language="JavaScript1.2" type="text/javascript">
  $(document).ready(function(){
    $("#myAccountForm").validate({
		rules: {
			change_password2: {
				equalTo: "#change_password"
			}
		},
		messages: {
			change_password2: {
				equalTo: "The passwords don't match."
			}
		}		
	});
  });
</script>
{/literal}
{/if}