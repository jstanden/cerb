{if !empty($error)}
<div class="ui-widget">
	<div class="ui-state-error ui-corner-all" style="padding: 0 .7em; margin: 0.2em; "> 
		<p>
		<span class="ui-icon ui-icon-alert" style="float: left; margin-right: .3em;"></span> 
		<strong>{$error}</strong><br>
		</p>
	</div>
</div>
{/if}

<div class="block">
<h2>{$translate->_('header.signon')|capitalize}</h2>

<form action="{devblocks_url}{/devblocks_url}" method="post" id="loginForm">
<input type="hidden" name="c" value="login">
<input type="hidden" name="a" value="authenticate">
<input type="hidden" name="original_path" value="{$original_path}">
<table cellpadding="0" cellspacing="2">
<tr>
	<td align="right" valign="middle">E-mail:</td>
	<td><input type="text" name="email" size="45" id="loginForm_email"></td>
</tr>
<tr>
	<td align="right" valign="middle">Password:</td>
	<td nowrap="nowrap">
		<input type="password" name="password" size="16" id="loginForm_password">
		 &nbsp; 
		<a href="{devblocks_url}c=login&a=forgot{/devblocks_url}">forgot your password?</a> 
	</td>
</tr>
</table>
<button type="submit">{$translate->_('header.signon')|capitalize}</button>
</form>
</div>

<script type="text/javascript">
	$(function() {
		$('#loginForm_email').focus().select();
	} );
</script>