<form action="{devblocks_url}{/devblocks_url}" method="post">
<h1>{$translate->say('login.signon')|capitalize}</h1>
<input type="hidden" name="c" value="login.default">
<input type="hidden" name="a" value="authenticate">
Login: <input type="text" name="email"><br>
Password: <input type="password" name="password"><br>
<input type="submit" value="{$translate->say('login.signon')|capitalize}">
</form>