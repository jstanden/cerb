<form action="{devblocks_url}{/devblocks_url}" method="post">
<h1>{$translate->_('login.signon')|capitalize}</h1>
<input type="hidden" name="c" value="login.ldap">
<input type="hidden" name="a" value="authenticate">
LDAP Server: <input type="text" name="server" value="localhost"><br>
LDAP Port: <input type="text" name="port" value="389"><br>
LDAP Login: <input type="text" name="dn"><br>
LDAP Password: <input type="password" name="password"><br>
<input type="submit" value="{$translate->_('login.signon')|capitalize}">
</form>