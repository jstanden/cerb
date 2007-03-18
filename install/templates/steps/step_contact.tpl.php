<h2>Configuring the Helpdesk</h2>

<form action="index.php" method="POST">
<input type="hidden" name="step" value="{$smarty.const.STEP_CONTACT}">

<H3>Administration</H3>

<b>Superuser Password:</b><br>
<input type="text" name="superuser_pass" value="{$superuser_pass}"><br>
<br>

<input type="submit" value="Continue &gt;&gt;">
</form>