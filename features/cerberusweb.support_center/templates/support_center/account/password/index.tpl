{if !empty($error)}
<div class="error">{$error}</div>
{elseif !empty($success)}
<div class="success">{'portal.sc.public.my_account.settings_saved'|devblocks_translate}</div>
{/if}

<form action="{devblocks_url}c=account&a=password{/devblocks_url}" method="post" id="frmAcctPasswd">
<input type="hidden" name="a" value="doPasswordUpdate">
<input type="hidden" name="_csrf_token" value="{$session->csrf_token}">

<fieldset>
	<legend>Change Password</legend>
	
	<b>Enter your current password:</b><br>
	<input type="password" name="current_password" size="35" value=""><br>
	
	<b>Choose a password:</b><br>
	<input type="password" name="change_password" size="35" value=""><br>

	<b>Confirm your desired password:</b><br>
	<input type="password" name="verify_password" size="35" value=""><br>

	<br>
	<button type="submit"><span class="glyphicons glyphicons-circle-ok"></span> {'common.save_changes'|devblocks_translate}</button><br>
</fieldset>

</form>
