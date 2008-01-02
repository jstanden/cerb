<h2>Account Settings</h2>
<br>

<form action="{devblocks_url}{/devblocks_url}" onsubmit="pwsMatch=(this.change_pass.value==this.change_pass_verify.value);if(!pwsMatch)document.getElementById('preferences_error').innerHTML='The passwords entered do not match.  Try again.';return pwsMatch;" method="post">
<input type="hidden" name="c" value="preferences">
<input type="hidden" name="a" value="saveDefaults">

<b>Change Password:</b><br>
<div id="preferences_error" style="color: red; font-weight: bold;"></div>
<table cellspacing="1" cellpadding="0" border="0">
<tr>
	<td>Change Password: </td>
	<td><input type="password" name="change_pass" value=""></td>
</tr>
<tr>
	<td>Verify Password: </td>
	<td><input type="password" name="change_pass_verify"=""></td>
</tr>
</table>
<br>

<b>Assist Mode:</b><br>
<label><input type="checkbox" name="assist_mode" value="1" {if $assist_mode eq 1}checked{/if}> Enable Helpdesk Assistant</label><br>
<br>

<h2>E-mail Addresses</h2>
These addresses will be associated with your helpdesk account.<br>
<br>
<table cellspacing="0" cellpadding="2" border="0">
	<tr>
		<td style="padding-right:10px;"><b>Address</b></td>
		<td style="padding-right:10px;"><b>Confirmed</b></td>
		<td><b>Delete</b></td>
	</tr>
	{foreach from=$addresses item=address}
	<tr>
		<td style="padding-right:10px;">{$address->address}</td>
		<td style="padding-right:10px;">
			{if $address->is_confirmed}
				yes
			{else}
				no, <a href="javascript:;" onclick="document.resendConfirmationForm.email.value='{$address->address}';document.resendConfirmationForm.submit();">resend confirmation</a>
			{/if}
		</td>
		<td>
			{if $address->address==$active_worker->email}
				(primary)
			{else}
				<input type="checkbox" name="email_delete[]" value="{$address->address}">
			{/if}
		</td>
	</tr>
	{/foreach}
</table>
<br>
<b>Add e-mail address:</b> <input type="text" name="new_email" size="45" value=""><br>
<br>

<!-- 
<b>Timezone:</b><br>
<select name="timezone">
	<option value="">---</option>
</select><br>
<br>
 -->

<button type="submit"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/check.gif{/devblocks_url}" align="top"> {$translate->_('common.save_changes')}</button>
</form>

<form action="{devblocks_url}{/devblocks_url}" name="resendConfirmationForm" method="post">
<input type="hidden" name="c" value="preferences">
<input type="hidden" name="a" value="resendConfirmation">
<input type="hidden" name="email" value="">
</form>
