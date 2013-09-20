{if !empty($error)}
<div class="error-box">
	<h1>Error</h1>
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
		<td><input type="text" name="email" size="45" class="input_email" value="{$worker->email}"></td>
		{else}
		<td>
			<b>{$worker->email}</b>
			<input type="hidden" name="email" value="{$worker->email}">
		</td>
		{/if}
		<td>
			{if !empty($worker)}
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
			<a href="{devblocks_url}c=login&a=recover{/devblocks_url}?email={$worker->email}" tabindex="-1">can't log in?</a>
		</td>
	</tr>
	<tr>
		<td align="right" valign="middle">Access Code:</td>
		<td nowrap="nowrap">
			<input type="text" name="access_code" size="8" maxlength="6" autocomplete="off">
		</td>
		<td>
			(from your Google Authenticator mobile app)
		</td>
	</tr>
	</table>
	<button type="submit"><span class="cerb-sprite2 sprite-tick-circle"></span> {'header.signon'|devblocks_translate|capitalize}</button>
</fieldset>
</form>

<script type="text/javascript">
	$(function() {
		{if !empty($worker->email)}
			$('#loginForm input[name=password]').focus().select();
		{else}
			$('#loginForm input[name=email]').focus().select();
		{/if}
	} );
</script>