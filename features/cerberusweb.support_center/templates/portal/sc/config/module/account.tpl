<table cellspacing="2" cellpadding="2" border="0">

{$account_fields = [contact_first_name,contact_last_name,contact_title,contact_username,contact_gender,contact_location,contact_dob,contact_phone,contact_mobile,contact_photo]}
{$account_labels = ['First Name','Last Name','Title','Username','Gender','Location','Date of Birth','Phone','Mobile','Photo']}

<tr>
	<td colspan="2"><b>{'common.contact'|devblocks_translate|capitalize}</b></td>
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