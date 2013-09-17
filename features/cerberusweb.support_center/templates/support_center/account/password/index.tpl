{if !empty($error)}
<div class="error">{$error}</div>
{elseif !empty($success)}
<div class="success">{'portal.sc.public.my_account.settings_saved'|devblocks_translate}</div>
{/if}

<form action="{devblocks_url}c=account&a=password{/devblocks_url}" method="post" id="frmAcctPasswd">
<input type="hidden" name="a" value="doPasswordUpdate">

<fieldset>
	<legend>Change Password</legend>
	
	<b>Choose a password:</b><br>
	<input type="password" id="change_password" name="change_password" size="35" value=""><br>
	
	<b>Confirm your desired password:</b><br>
	<input type="password" name="change_password2" size="35" value=""><br>

	<br>
	<button type="submit"><img src="{devblocks_url}c=resource&p=cerberusweb.support_center&f=images/check.gif{/devblocks_url}" align="top"> {'common.save_changes'|devblocks_translate}</button><br>
</fieldset>

</form>
