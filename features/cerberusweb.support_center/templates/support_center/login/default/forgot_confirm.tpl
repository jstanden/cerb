{if !empty($error)}
<div class="error">{$error}</div>
{/if}

<form action="{devblocks_url}c=login{/devblocks_url}" method="post">
<input type="hidden" name="a" value="recoverAccount">
<input type="hidden" name="_csrf_token" value="{$session->csrf_token}">

<fieldset>
	<legend>Recover my account</legend>
	
	<b>Enter your email address:</b><br>
	<input type="text" name="email" size="64" value="{$email}"><br>
	<br>
	
	<b>Enter the confirmation code sent to your email address:</b><br>
	<input type="text" name="confirm" size="10" maxlength="8" autofocus="autofocus"><br>
	<br>

	<b>Choose a new password:</b><br>
	<input type="password" name="password_new" size="24"><br>
	<small>(at least 8 characters)</small><br>
	<br>

	<b>Confirm your new password:</b><br>
	<input type="password" name="password_new_confirm" size="24"><br>
	<br>

	<button type="submit"><span class="glyphicons glyphicons-circle-ok"></span> {'common.save_changes'|devblocks_translate|capitalize}</button><br>
	<br>
	
	<a href="{devblocks_url}c=login{/devblocks_url}">{'common.cancel'|devblocks_translate|capitalize}</a><br>
</fieldset>
</form>
