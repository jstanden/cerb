{if !empty($error)}
<div class="error">{$error}</div>
{/if}

<form action="{devblocks_url}c=login&a=register{/devblocks_url}" method="post" id="frmRegister">
<input type="hidden" name="a" value="doRegister">
<input type="hidden" name="_csrf_token" value="{$session->csrf_token}">

<fieldset>
	<legend>Register</legend>

	<b>What is your primary email address?</b><br>
	<input type="text" name="email" size="64" value="{$email}"><br>
	<br>
	
	<button type="submit"><span class="glyphicons glyphicons-circle-ok"></span> {'portal.sc.public.register.send_confirmation'|devblocks_translate}</button><br> 
</fieldset>

</form> 
