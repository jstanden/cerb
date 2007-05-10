<form action="{devblocks_url}{/devblocks_url}" method="post">
<h1>{$translate->_('header.signon')|capitalize}</h1>
<input type="hidden" name="c" value="login">
<input type="hidden" name="a" value="authenticate">
<table cellpadding="0" cellspacing="2">
<tr>
	<td align="right" valign="middle">E-mail:</td>
	<td><input type="text" name="email"></td>
</tr>
<tr>
	<td align="right" valign="middle">Password:</td>
	<td nowrap="nowrap">
		<input type="password" name="password"> 
		[ <a href="{devblocks_url}c=login&a=forgot{/devblocks_url}">recover password</a> ]
	</td>
</tr>
</table>
<input type="submit" value="{$translate->_('header.signon')|capitalize}">
</form>