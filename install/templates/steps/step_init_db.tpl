<h2>Database Problems</h2>

<form action="index.php" method="POST">
<input type="hidden" name="step" value="{$smarty.const.STEP_INIT_DB}">

<span class='bad'>{$error|default:'Database initialization failed!'}</span><br>
<br>

<input type="submit" value="Try Again &gt;&gt;">
</form>