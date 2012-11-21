{if !empty($error)}
<div class="error-box">
	<h1>Error</h1>
	<p>{$error}</p>
</div>
{/if}

<form action="{devblocks_url}c=login&a=authenticate{/devblocks_url}" method="post" id="loginForm">
<input type="hidden" name="ext" value="password">

<fieldset>
	<legend>{$translate->_('header.signon')|capitalize}</legend>
	
	<table cellpadding="0" cellspacing="2">
	<tr>
		<td align="right" valign="middle">{'common.email'|devblocks_translate|capitalize}:</td>
		<td>
			{if !empty($email)}
			<b>{$email}</b>
			<input type="hidden" name="email" value="{$email}">
			{else}
			<input type="text" name="email" size="45" class="input_email" value="{$email}">
			{/if}
		</td>
	</tr>
	<tr>
		<td align="right" valign="middle">{'common.password'|devblocks_translate|capitalize}:</td>
		<td nowrap="nowrap">
			<input type="password" name="password" size="16">
			 &nbsp; 
			<a href="{devblocks_url}c=login&a=recover{/devblocks_url}?email={$email}">forgot your password?</a> 
		</td>
	</tr>
	</table>
	<button type="submit"><span class="cerb-sprite2 sprite-tick-circle"></span> {$translate->_('header.signon')|capitalize}</button>
</fieldset>
</form>

<script type="text/javascript">
	$(function() {
		{if !empty($email)}
			$('#loginForm input[name=password]').focus().select();
		{else}
			$('#loginForm input[name=email]').focus().select();
		{/if}
	} );
</script>