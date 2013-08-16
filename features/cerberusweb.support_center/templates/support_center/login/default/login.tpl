{if !empty($error)}
<div class="error">{$error}</div>
{/if}

<form action="{devblocks_url}c=login&a=authenticate{/devblocks_url}" method="post" id="loginForm">
<fieldset>
	<legend>{$translate->_('header.signon')}</legend>
	
	<b>{'common.email'|devblocks_translate|capitalize}:</b><br>
	<input type="text" name="email" size="45"><br>
	
	<b>{'common.password'|devblocks_translate|capitalize}:</b><br>
	<input type="password" name="password" size="45" autocomplete="off"><br>
	
	<br>
	<button type="submit">{$translate->_('header.signon')|capitalize}</button><br>
</fieldset>
</form>

<a href="{devblocks_url}c=login&a=register{/devblocks_url}">{'portal.sc.public.login.register'|devblocks_translate}</a><br>
<a href="{devblocks_url}c=login&a=forgot{/devblocks_url}">{'portal.sc.public.login.forgot'|devblocks_translate}</a><br>

{include file="devblocks:cerberusweb.support_center::support_center/login/switcher.tpl"}

<script type="text/javascript">
	$(function() {
		$('#loginForm input:text[name=email]').focus().select();
	});
</script>
