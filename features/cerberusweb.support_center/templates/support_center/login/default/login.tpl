{if !empty($error)}
<div class="error">{$error}</div>
{/if}

<form action="{devblocks_url}c=login&a=authenticate{/devblocks_url}" method="post" id="loginForm">
<input type="hidden" name="_csrf_token" value="{$session->csrf_token}">
<fieldset>
	<legend>{'header.signon'|devblocks_translate}</legend>
	
	<b>{'common.email'|devblocks_translate|capitalize}:</b><br>
	<input type="text" name="email" size="45"><br>
	
	<b>{'common.password'|devblocks_translate|capitalize}:</b><br>
	<input type="password" name="password" size="45" autocomplete="off" spellcheck="false"><br>
	
	<br>
	<button type="submit">{'header.signon'|devblocks_translate|capitalize}</button><br>
</fieldset>
</form>

{if !$params['auth.register.disabled']}
<a href="{devblocks_url}c=login&a=register{/devblocks_url}">{'portal.sc.public.login.register'|devblocks_translate}</a><br>
{/if}

{if !$params['auth.recover.disabled']}
<a href="{devblocks_url}c=login&a=forgot{/devblocks_url}">{'portal.sc.public.login.forgot'|devblocks_translate}</a><br>
{/if}

{include file="devblocks:cerberusweb.support_center::support_center/login/switcher.tpl"}

<script type="text/javascript">
	document.querySelector('#loginForm input[name=email]').focus();
</script>
