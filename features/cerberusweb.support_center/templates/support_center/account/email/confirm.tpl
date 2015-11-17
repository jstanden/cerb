{if !empty($error)}
<div class="error">{$error}</div>
{/if}

<fieldset>
	<legend>Please confirm ownership of this email address</legend>
	
	<form action="{devblocks_url}c=account{/devblocks_url}" method="POST" style="margin-top:5px;">
		<input type="hidden" name="a" value="doEmailConfirm">
		<input type="hidden" name="_csrf_token" value="{$session->csrf_token}">
		
		<b>Enter the email address you want to add to your account:</b><br> 
		<input type="text" name="email" class="input_email" size="45" value="{$email}"><br>
		<br>
		
		<b>Enter the confirmation code you received at this email address:</b><br> 
		<input type="text" name="confirm" class="" style="" size="10" maxlength="8" value="{$confirm}"><br>
		<br>
		
		<button type="submit"><span class="glyphicons glyphicons-circle-ok"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
	</form>
</fieldset>
