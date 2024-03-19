{if !empty($error)}
<div class="error">{$error}</div>
{/if}

<form action="{devblocks_url}c=login&a=register{/devblocks_url}" method="post" id="frmRegister">
<input type="hidden" name="a" value="doRegister">
<input type="hidden" name="_csrf_token" value="{$session->csrf_token}">

<fieldset>
	<legend>{'portal.sc.public.register'|devblocks_translate|capitalize}</legend>

	<b>What is your primary email address?</b><br>
	<input type="text" name="email" size="64" value="{$email}"><br>
	<br>

	<b>{'portal.public.captcha_instructions'|devblocks_translate}</b><br>
	<input type="text" id="captcha" name="captcha" value="" size="10" autocomplete="off"><br>
	<div style="padding-top:10px;padding-left:10px;"><img src="{devblocks_url}c=captcha{/devblocks_url}"></div>
	<br>

	<button type="submit"><span class="glyphicons glyphicons-circle-ok"></span> {'portal.sc.public.register.send_confirmation'|devblocks_translate}</button><br>
	<br>

	<a href="{devblocks_url}c=login&a=register&o=confirm{/devblocks_url}">Already have a confirmation code?</a><br>
</fieldset>

</form> 
