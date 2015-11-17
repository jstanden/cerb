{if !empty($account_error)}
<div class="error">{$account_error}</div>
{elseif !empty($account_success)}
<div class="success">{'portal.sc.public.my_account.settings_saved'|devblocks_translate}</div>
{/if}

<form action="{devblocks_url}c=account{/devblocks_url}" method="post" id="profileForm">
<input type="hidden" name="a" value="doProfileUpdate">
<input type="hidden" name="_csrf_token" value="{$session->csrf_token}">

<fieldset>
	<legend>{'common.profile'|devblocks_translate|capitalize}</legend>

	<table cellpadding="2" cellspacing="2" border="0">
	
	{if $show_fields.contact_first_name}
	<tr>
		<td width="1%" nowrap="nowrap" valign="top"><b>{'common.name.first'|devblocks_translate|capitalize}:</b></td>
		<td width="99%">
			{if 2 == $show_fields.contact_first_name}
			<input type="text" name="first_name" size="35" value="{$active_contact->first_name}">
			{else}
			{$active_contact->first_name}
			{/if}
		</td>
	</tr>
	{/if}
	
	{if $show_fields.contact_last_name}
	<tr>
		<td width="1%" nowrap="nowrap" valign="top"><b>{'common.name.last'|devblocks_translate|capitalize}:</b></td>
		<td width="99%">
			{if 2 == $show_fields.contact_last_name}
			<input type="text" name="last_name" size="35" value="{$active_contact->last_name}">
			{else}
			{$active_contact->last_name}
			{/if}
		</td>
	</tr>
	{/if}
	
	<tr>
		<td width="1%" nowrap="nowrap" valign="top"><b>{'common.email'|devblocks_translate|capitalize}:</b></td>
		<td width="99%">
			<select name="primary_email_id">
				<option value=""></option>
				{$addys = $active_contact->getEmails()}
				{foreach from=$addys item=addy key=addy_id}
				<option value="{$addy_id}" {if $active_contact->primary_email_id==$addy_id}selected="selected"{/if}>{$addy->email}</option>
				{/foreach}
			</select>
		</td>
	</tr>
	
	{if $show_fields.contact_title}
	<tr>
		<td width="1%" nowrap="nowrap" valign="top"><b>{'common.title'|devblocks_translate|capitalize}:</b></td>
		<td width="99%">
			{if 2 == $show_fields.contact_last_name}
			<input type="text" name="title" size="35" value="{$active_contact->title}">
			{else}
			{$active_contact->title}
			{/if}
		</td>
	</tr>
	{/if}
	
	{if $active_contact->org_id}
	<tr>
		<td width="1%" nowrap="nowrap" valign="top"><b>{'common.organization'|devblocks_translate|capitalize}:</b></td>
		<td width="99%">
			{$org = $active_contact->getOrg()}
			{$org->name}
		</td>
	</tr>
	{/if}
	
	{if $show_fields.contact_username}
	<tr>
		<td width="1%" nowrap="nowrap" valign="top"><b>{'common.username'|devblocks_translate|capitalize}:</b></td>
		<td width="99%">
			{if 2 == $show_fields.contact_username}
			<input type="text" name="username" size="35" value="{$active_contact->username}">
			{else}
			{$active_contact->username}
			{/if}
		</td>
	</tr>
	{/if}
	
	{if $show_fields.contact_gender}
	<tr>
		<td width="1%" nowrap="nowrap" valign="top"><b>{'common.gender'|devblocks_translate|capitalize}:</b></td>
		<td width="99%">
			{if 2 == $show_fields.contact_gender}
				<label><input type="radio" name="gender" value="M" {if 'M' == $active_contact->gender}checked="checked"{/if}> {'common.gender.male'|devblocks_translate|capitalize}</label>
				<label><input type="radio" name="gender" value="F" {if 'F' == $active_contact->gender}checked="checked"{/if}> {'common.gender.female'|devblocks_translate|capitalize}</label>
				<label><input type="radio" name="gender" value="" {if empty($active_contact->gender)}checked="checked"{/if}> I'd prefer to not say</label>
			{else}
				{if $active_contact->gender == 'M'}
				{'common.gender.male'|devblocks_translate|capitalize}
				{else if $active_contact->gender == 'F'}
				{'common.gender.female'|devblocks_translate|capitalize}
				{else}
				{/if}
			{/if}
		</td>
	</tr>
	{/if}
	
	{if $show_fields.contact_location}
	<tr>
		<td width="1%" nowrap="nowrap" valign="top"><b>{'common.location'|devblocks_translate|capitalize}:</b></td>
		<td width="99%">
			{if 2 == $show_fields.contact_last_name}
			<input type="text" name="location" size="35" value="{$active_contact->location}">
			{else}
			{$active_contact->location}
			{/if}
		</td>
	</tr>
	{/if}
	
	{if $show_fields.contact_dob}
	<tr>
		<td width="1%" nowrap="nowrap" valign="top"><b>{'common.dob.abbr'|devblocks_translate|capitalize}:</b></td>
		<td width="99%">
			{if 2 == $show_fields.contact_dob}
			<input type="text" name="dob" size="35" value="{$active_contact->dob|devblocks_date:'d M Y'}">
			{else}
			{$active_contact->dob|devblocks_date:'d M Y'}
			{/if}
		</td>
	</tr>
	{/if}
	
	{if $show_fields.contact_phone}
	<tr>
		<td width="1%" nowrap="nowrap" valign="top"><b>{'common.phone'|devblocks_translate|capitalize}:</b></td>
		<td width="99%">
			{if 2 == $show_fields.contact_phone}
			<input type="text" name="phone" size="35" value="{$active_contact->phone}">
			{else}
			{$active_contact->phone}
			{/if}
		</td>
	</tr>
	{/if}
	
	{if $show_fields.contact_mobile}
	<tr>
		<td width="1%" nowrap="nowrap" valign="top"><b>{'common.mobile'|devblocks_translate|capitalize}:</b></td>
		<td width="99%">
			{if 2 == $show_fields.contact_mobile}
			<input type="text" name="mobile" size="35" value="{$active_contact->mobile}">
			{else}
			{$active_contact->mobile}
			{/if}
		</td>
	</tr>
	{/if}
	
	</tbody>
</table>
</fieldset>

<button type="submit"><span class="glyphicons glyphicons-circle-ok"></span> {'common.save_changes'|devblocks_translate}</button>

</form>