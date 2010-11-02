<table cellspacing="2" cellpadding="2" border="0">

{$account_fields = [addy_first_name,addy_last_name]}
{$account_labels = ['First Name','Last Name']}

{if !empty($address_custom_fields)}
{foreach from=$address_custom_fields item=field key=field_id}
	{$account_fields[] = 'addy_custom_'|cat:$field_id}
	{$account_labels[] = ''|cat:$field->name|cat:' ('|cat:$field_types.{$field->type}|cat:')'}
{/foreach}
{/if}

<tr>
	<td colspan="2"><b>Person</b></td>
</tr>
{foreach from=$account_fields item=field name=fields}
<tr>
	<td>
		<input type="hidden" name="fields[]" value="{$field}">
		<select name="fields_visible[]">
			<option value="0">Hidden</option>
			<option value="1" {if 1==$show_fields.{$field}}selected="selected"{/if}>Read Only</option>
			<option value="2" {if 2==$show_fields.{$field}}selected="selected"{/if}>Editable</option>
		</select>
	</td>
	<td>
		{$account_labels.{$smarty.foreach.fields.index}|capitalize}
	</td>
</tr>
{/foreach}

<tr>
	<td colspan="2">&nbsp;</td>
</tr>

{$account_fields = [org_name,org_street,org_city,org_province,org_postal,org_country,org_phone,org_website]}
{$account_labels = [Name,Street,City,Province,Postal,Country,Phone,Website]}

{if !empty($org_custom_fields)}
{foreach from=$org_custom_fields item=field key=field_id}
	{$account_fields[] = 'org_custom_'|cat:$field_id}
	{$account_labels[] = ''|cat:$field->name|cat:' ('|cat:$field_types.{$field->type}|cat:')'}
{/foreach}
{/if}

<tr>
	<td colspan="2"><b>Organization</b></td>
</tr>
{foreach from=$account_fields item=field name=fields}
<tr>
	<td>
		<input type="hidden" name="fields[]" value="{$field}">
		<select name="fields_visible[]">
			<option value="0">Hidden</option>
			<option value="1" {if 1==$show_fields.{$field}}selected="selected"{/if}>Read Only</option>
			<option value="2" {if 2==$show_fields.{$field}}selected="selected"{/if}>Editable</option>
		</select>
	</td>
	<td>
		{$account_labels.{$smarty.foreach.fields.index}|capitalize}
	</td>
</tr>
{/foreach}

</table>
<br>