{if !empty($error)}
<div class="error">{$error}</div>
{/if}

<form action="{devblocks_url}c=login&a=register{/devblocks_url}" method="post" id="frmRegister">
<input type="hidden" name="a" value="doRegisterConfirm">

<fieldset>
	<legend>Confirm Your Registration</legend>

	{if !empty($email)}
	<b>Email:</b><br>
	{$email}<br>
	<br>
	{/if}

	<b>Enter the confirmation code sent to your email address:</b><br>
	<input type="text" name="confirm" size="10" maxlength="8" value=""><br>
	<br>
	
	<b>Choose a password:</b><br>
	<input type="password" name="password" size="35" value=""><br>
	<br>
	
	<b>Confirm your desired password:</b><br>
	<input type="password" name="password2" size="35" value=""><br>
	<br>

	<button type="submit"><img src="{devblocks_url}c=resource&p=cerberusweb.support_center&f=images/check.gif{/devblocks_url}" align="top"> Register</button><br> 
</fieldset>

</form> 
