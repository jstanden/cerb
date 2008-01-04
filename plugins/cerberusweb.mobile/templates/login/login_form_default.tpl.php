<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE html PUBLIC "-//WAPFORUM//DTD XHTML Mobile 1.0//EN" "http://www.wapforum.org/DTD/xhtml-mobile10.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head><title>Cerberus Helpdesk</title></head>
<body style="font-size:small;font-weight:normal;">

<div class="block">
<form action="{devblocks_url}{/devblocks_url}" method="post" id="loginForm">
<h2>{$translate->_('header.signon')|capitalize}</h2>
<input type="hidden" name="c" value="mobile">
<input type="hidden" name="a" value="login">
<input type="hidden" name="a2" value="authenticate">
<input type="hidden" name="original_path" value="{$original_path}">
<input type="hidden" name="original_query" value="{$original_query}">
<table cellpadding="0" cellspacing="2">
<tr>
	<td align="right" valign="middle">E-mail:</td>
	<td><input type="text" name="email" id="loginForm_email"></td>
</tr>
<tr>
	<td align="right" valign="middle">Password:</td>
	<td>
		<input type="password" name="password" id="loginForm_password"> 
		<br/>[ <a href="{devblocks_url}c=login&a=forgot{/devblocks_url}">recover password</a> ]
	</td>
</tr>
</table>
<input type="submit" value="{$translate->_('header.signon')|capitalize}">
</form>
</div>

</body>
</html>