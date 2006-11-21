<form action="index.php" method="post">
<h1>{$translate->say('login.signon')|capitalize}</h1>
<input type="hidden" name="c" value="core.module.signin">
<input type="hidden" name="a" value="signin">
Login: <input type="text" name="email"><br>
Password: <input type="password" name="password"><br>
<input type="submit" value="{$translate->say('login.signon')|capitalize}">
</form>