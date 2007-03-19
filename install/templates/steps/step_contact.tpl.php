<h2>Configuring the Helpdesk</h2>

<form action="index.php" method="POST">
<input type="hidden" name="step" value="{$smarty.const.STEP_CONTACT}">

<H3>Administrator</H3>

<b>Superuser E-mail:</b><br>
<input type="text" name="superuser_email" value="{$superuser_email}"><br>
<br>

<b>Superuser Password:</b><br>
<input type="text" name="superuser_pass" value="{$superuser_pass}"><br>
<br>

<input type="submit" value="Continue &gt;&gt;">
</form>