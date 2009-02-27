<div class="block">
<form action="{devblocks_url}{/devblocks_url}" method="post" id="loginForm">
<h2>{$translate->_('header.signon')|capitalize}</h2>
<input type="hidden" name="c" value="login">
<input type="hidden" name="a" value="authenticate">
<input type="hidden" name="original_path" value="{$original_path}">
<input type="hidden" name="original_query" value="{$original_query}">
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
