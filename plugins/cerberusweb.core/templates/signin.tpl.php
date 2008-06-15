<form action="{devblocks_url}{/devblocks_url}" method="post">
<h1>{$translate->_('login.signon')|capitalize}</h1>
<input type="hidden" name="c" value="login">
<input type="hidden" name="a" value="signin">
Login: <input type="text" name="email"><br>
Password: <input type="password" name="password"><br>
<button type="submit">{$translate->_('login.signon')|capitalize}</button>
</form>