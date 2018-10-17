{if !empty($error)}
<div class="error-box">
	<h1>{'common.error'|devblocks_translate|capitalize}</h1>
	<p>{$error}</p>
</div>
{/if}

<form action="{devblocks_url}c=login&a=authenticate{/devblocks_url}" method="post" id="loginForm">
<input type="hidden" name="ext" value="password-gauth">

<fieldset>
	<legend>{'header.signon'|devblocks_translate|capitalize}</legend>
	
	<table cellpadding="2" cellspacing="0">
	<tr>
		<td align="right" valign="middle">{'common.email'|devblocks_translate|capitalize}:</td>
		{if empty($worker)}
		<td><input type="text" name="email" size="45" class="input_email" value="{$worker->getEmailString()}"></td>
		{else}
		<td>
			<b>{$worker->getEmailString()}</b>
			<input type="hidden" name="email" value="{$worker->getEmailString()}">
		</td>
		{/if}
		<td>
			{if $worker}
			<a href="{devblocks_url}c=login&a=reset{/devblocks_url}" tabindex="-1">use a different email</a>
			{/if}
		</td>
	</tr>
	<tr>
		<td align="right" valign="middle">{'common.password'|devblocks_translate|capitalize}:</td>
		<td nowrap="nowrap">
			<input type="password" name="password" size="16">
		</td>
		<td>
			<a href="{devblocks_url}c=login&a=recover{/devblocks_url}?email={$worker->getEmailString()}" tabindex="-1">can't log in?</a>
		</td>
	</tr>
	<tr>
		<td align="right" valign="middle">Access Code:</td>
		<td nowrap="nowrap">
			<input type="text" name="access_code" size="8" maxlength="6" autocomplete="off">
		</td>
		<td>
			(from 1Password, Google Authenticator, etc.)
		</td>
	</tr>
	</table>
	<button type="submit"><span class="glyphicons glyphicons-circle-ok" style="color:rgb(0,180,0);"></span> {'header.signon'|devblocks_translate|capitalize}</button>
</fieldset>
</form>

<script type="text/javascript">
	$(function() {
		{if $worker->getEmailString()}
			$('#loginForm input[name=password]').focus().select();
		{else}
			$('#loginForm input[name=email]').focus().select();
		{/if}
	} );
</script>