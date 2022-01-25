{$form_id = uniqid()}
<form id="{$form_id}" action="{devblocks_url}{/devblocks_url}" method="post">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="community_portal">
<input type="hidden" name="action" value="saveConfigTabJson">
<input type="hidden" name="portal_id" value="{$portal->id}">
<input type="hidden" name="config_tab" value="account">

{$account_fields = [contact_first_name,contact_last_name,contact_title,contact_username,contact_gender,contact_location,contact_dob,contact_phone,contact_mobile,contact_photo]}
{$account_labels = ['First Name','Last Name','Title','Username','Gender','Location','Date of Birth','Phone','Mobile','Photo']}

<fieldset class="peek">
	<legend>{'common.contact'|devblocks_translate|capitalize}</legend>

	{foreach from=$account_fields item=field name=fields}
	<div>
		<input type="hidden" name="fields[]" value="{$field}">
		<select name="fields_visible[]">
			<option value="0">Hidden</option>
			<option value="1" {if 1==$show_fields.{$field}}selected="selected"{/if}>Read Only</option>
			<option value="2" {if 2==$show_fields.{$field}}selected="selected"{/if}>Editable</option>
		</select>
		{$account_labels.{$smarty.foreach.fields.index}|capitalize}
	</div>
	{/foreach}
</fieldset>

<button type="button" class="submit" style="margin-top:10px;"><span class="glyphicons glyphicons-circle-ok"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
</form>

<script type="text/javascript">
$(function() {
	var $frm = $('#{$form_id}');
	
	$frm.find('button.submit').on('click', function(e) {
		genericAjaxPost($frm, '', null, function(json) {
			Devblocks.clearAlerts();
			if(json && typeof json == 'object') {
				if(json.error) {
					Devblocks.createAlertError(json.error);
				} else if (json.message) {
					Devblocks.createAlert(json.message, 'success', 5000);
				} else {
					Devblocks.createAlert('Saved!', 'success', 5000);
				}
			}
		});
	});
});
</script>