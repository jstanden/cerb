{if !empty($error)}
<div class="error">{$error}</div>
{/if}

<fieldset>
	<legend>Please confirm ownership of this email address</legend>
	
	<form action="{devblocks_url}c=account{/devblocks_url}" method="POST" style="margin-top:5px;">
		<input type="hidden" name="a" value="doEmailConfirm">
		
		<b>Enter the email address you want to add to your account:</b><br> 
		<input type="text" name="email" class="input_email" style="background:url('{devblocks_url}c=resource&p=cerberusweb.support_center&f=images/mail.png{/devblocks_url}') no-repeat scroll 5px 50% #ffffff;padding-left:25px;" size="45" value="{$email}"><br>
		<br>
		
		<b>Enter the confirmation code you received at this email address:</b><br> 
		<input type="text" name="confirm" class="" style="" size="10" maxlength="8" value="{$confirm}"><br>
		<br>
		
		<button type="submit">&nbsp;<img src="{devblocks_url}c=resource&p=cerberusweb.support_center&f=images/check.gif{/devblocks_url}" align="top"> {'common.save_changes'|devblocks_translate|capitalize}</button>
	</form>
</fieldset>
