<form action="{devblocks_url}c=login&a=authenticate{/devblocks_url}" method="post" id="loginForm">
<input type="hidden" name="original_path" value="{$original_path|escape}">

<div class="header"><h1>{$translate->_('header.signon')}</h1></div>

{if !empty($error)}
<div class="error">{$error}</div>
{/if}

<b>Email:</b><br>
<input type="text" name="email" size="45" id="loginForm_email"><br>

<b>Password:</b><br>
<input type="password" name="password" size="45" autocomplete="off"><br>

<br>
<button type="submit">{$translate->_('header.signon')|capitalize}</button><br>
<br>

<a href="{devblocks_url}c=forgot{/devblocks_url}">Forgot your password?</a><br> 	
<a href="{devblocks_url}c=register{/devblocks_url}">Register a new account</a><br>
</form>

{*include file="devblocks:cerberusweb.core::login/switcher.tpl"*}

<script type="text/javascript">
	$(function() {
		$('#loginForm_email').focus().select();
	});
</script>
