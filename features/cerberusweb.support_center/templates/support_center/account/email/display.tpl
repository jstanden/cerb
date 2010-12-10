{if !empty($account_error)}
<div class="error">{$account_error}</div>
{elseif !empty($account_success)}
<div class="success">{$translate->_('portal.sc.public.my_account.settings_saved')}</div>
{/if}

<form action="{devblocks_url}c=account{/devblocks_url}" method="post" id="myAccountForm">
<input type="hidden" name="a" value="doEmailUpdate">
<input type="hidden" name="id" value="{$address->id}">

<fieldset>
	<legend>{$address->email}</legend>

	<table cellpadding="2" cellspacing="2" border="0">
	<tr>
		<td colspan="2">
			{if $active_contact->email_id != $address->id}
				<label><input type="checkbox" name="is_primary" value="1" {if $active_contact->email_id == $address->id}checked="checked"{/if}> This is my primary email address.</label>
			{else}
				<input type="hidden" name="is_primary" value="1">
			{/if}
		</td>
	</tr>
	
	<tr>
		<td width="1%" nowrap="nowrap" valign="top"><b>{$translate->_('portal.sc.public.my_account.profile_picture')}:</b></td>
		<td width="99%">
			<img src="http://www.gravatar.com/avatar/{$address->email|trim|lower|md5}?s=64&d=mm" border="0" style="margin:0px 5px 5px 0px;" align="bottom">
			<div>
				[<a href="http://en.gravatar.com/" target="_blank">change</a>]
			</div>
		</td>
	</tr>
	
	{if $show_fields.addy_first_name}
	<tr>
		<td width="1%" nowrap="nowrap" valign="top"><b>{$translate->_('contact_person.first_name')|capitalize}:</b></td>
		<td width="99%">
			{if 1==$show_fields.addy_first_name}
			{$address->first_name}
			{else}
			<input type="text" name="addy_first_name" size="35" value="{$address->first_name}">
			{/if}
		</td>
	</tr>
	{/if}
	
	{if $show_fields.addy_last_name}
	<tr>
		<td width="1%" nowrap="nowrap" valign="top"><b>{$translate->_('contact_person.last_name')|capitalize}:</b></td>
		<td width="99%">
			{if 1==$show_fields.addy_last_name}
			{$address->last_name}
			{else}
			<input type="text" name="addy_last_name" size="35" value="{$address->last_name}">
			{/if}
		</td>
	</tr>
	{/if}
	
	{foreach from=$address_custom_fields item=field key=field_id}
	{if $show_fields.{"addy_custom_"|cat:$field_id}}
	<tr>
		<td width="1%" nowrap="nowrap" valign="top"><b>{$field->name}:</b></td>
		<td width="99%">
			{if 1==$show_fields.{"addy_custom_"|cat:$field_id}}
			{include file="devblocks:cerberusweb.support_center:portal_{$portal_code}:support_center/account/customfields_readonly.tpl" values=$address_custom_field_values}
			{elseif 2==$show_fields.{"addy_custom_"|cat:$field_id}}
			{include file="devblocks:cerberusweb.support_center:portal_{$portal_code}:support_center/account/customfields_writeable.tpl" values=$address_custom_field_values field_prefix="addy_custom"}
			{else}
			{/if}
		</td>
	</tr>
	{/if}
	{/foreach}
	</tbody>
</table>
</fieldset>

{if !empty($org)}
<fieldset>
	<legend>{$org->name}</legend>
	
	<table cellpadding="2" cellspacing="2" border="0">
		{if $show_fields.org_name}
		<tr>
			<td width="1%" nowrap="nowrap" valign="top"><b>{$translate->_('contact_org.name')|capitalize}:</b></td>
			<td width="99%">
				{if 1==$show_fields.org_name}
				{$org->name}
				{else}
				<input type="text" name="org_name" size="35" value="{$org->name}">
				{/if}
			</td>
		</tr>
		{/if}
		
		{if $show_fields.org_street}
		<tr>
			<td width="1%" nowrap="nowrap" valign="top"><b>{$translate->_('contact_org.street')|capitalize}:</b></td>
			<td width="99%">
				{if 1==$show_fields.org_street}
				{$org->street}
				{else}
				<input type="text" name="org_street" size="35" value="{$org->street}">
				{/if}
			</td>
		</tr>
		{/if}
		
		{if $show_fields.org_city}
		<tr>
			<td width="1%" nowrap="nowrap" valign="top"><b>{$translate->_('contact_org.city')|capitalize}:</b></td>
			<td width="99%">
				{if 1==$show_fields.org_city}
				{$org->city}
				{else}
				<input type="text" name="org_city" size="35" value="{$org->city}">
				{/if}
			</td>
		</tr>
		{/if}
		
		{if $show_fields.org_province}
		<tr>
			<td width="1%" nowrap="nowrap" valign="top"><b>{$translate->_('contact_org.province')|capitalize}:</b></td>
			<td width="99%">
				{if 1==$show_fields.org_province}
				{$org->province}
				{else}
				<input type="text" name="org_province" size="35" value="{$org->province}">
				{/if}
			</td>
		</tr>
		{/if}
		
		{if $show_fields.org_postal}
		<tr>
			<td width="1%" nowrap="nowrap" valign="top"><b>{$translate->_('contact_org.postal')|capitalize}:</b></td>
			<td width="99%">
				{if 1==$show_fields.org_postal}
				{$org->postal}
				{else}
				<input type="text" name="org_postal" size="35" value="{$org->postal}">
				{/if}
			</td>
		</tr>
		{/if}
		
		{if $show_fields.org_country}
		<tr>
			<td width="1%" nowrap="nowrap" valign="top"><b>{$translate->_('contact_org.country')|capitalize}:</b></td>
			<td width="99%">
				{if 1==$show_fields.org_country}
				{$org->country}
				{else}
				<input type="text" name="org_country" size="35" value="{$org->country}">
				{/if}
			</td>
		</tr>
		{/if}
		
		{if $show_fields.org_phone}
		<tr>
			<td width="1%" nowrap="nowrap" valign="top"><b>{$translate->_('contact_org.phone')|capitalize}:</b></td>
			<td width="99%">
				{if 1==$show_fields.org_phone}
				{$org->phone}
				{else}
				<input type="text" name="org_phone" size="35" value="{$org->phone}">
				{/if}
			</td>
		</tr>
		{/if}
		
		{if $show_fields.org_website}
		<tr>
			<td width="1%" nowrap="nowrap" valign="top"><b>{$translate->_('contact_org.website')|capitalize}:</b></td>
			<td width="99%">
				{if 1==$show_fields.org_website}
				{$org->website}
				{else}
				<input type="text" name="org_website" size="35" value="{$org->website}">
				{/if}
			</td>
		</tr>
		{/if}
		
		{foreach from=$org_custom_fields item=field key=field_id}
		{if $show_fields.{"org_custom_"|cat:$field_id}}
		<tr>
			<td width="1%" nowrap="nowrap" valign="top"><b>{$field->name}:</b></td>
			<td width="99%">
				{if 1==$show_fields.{"org_custom_"|cat:$field_id}}
				{include file="devblocks:cerberusweb.support_center:portal_{$portal_code}:support_center/account/customfields_readonly.tpl" values=$org_custom_field_values}
				{elseif 2==$show_fields.{"org_custom_"|cat:$field_id}}
				{include file="devblocks:cerberusweb.support_center:portal_{$portal_code}:support_center/account/customfields_writeable.tpl" values=$org_custom_field_values field_prefix="org_custom"}
				{/if}
			</td>
		</tr>
		{/if}
	{/foreach}
	</table>		
</fieldset>
{/if}

<button name="action" type="submit" value=""><img src="{devblocks_url}c=resource&p=cerberusweb.support_center&f=images/check.gif{/devblocks_url}" align="top"> {$translate->_('common.save_changes')}</button>
{if $active_contact->email_id != $address->id}
<button name="action" type="submit" value="remove"><img src="{devblocks_url}c=resource&p=cerberusweb.support_center&f=images/forbidden.png{/devblocks_url}" align="top"> Remove from account</button><br>
{/if}
</form>
