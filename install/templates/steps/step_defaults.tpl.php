<h2>Creating Your Account</h2>

<form action="index.php" method="POST">
<input type="hidden" name="step" value="{$smarty.const.STEP_DEFAULTS}">
<input type="hidden" name="form_submit" value="1">

{if $failed}
	<div class="error">
		Oops! Some required information was not provided, or your passwords do not match.
	</div>
{/if}

<H3>Your Account</H3>

Next we need to create you an account on your new helpdesk.<br>
<br>

<b>What is your personal e-mail address?</b> (this will be your login)<br>
<input type="text" name="worker_email" value="{$worker_email}" size="64"><br>
<br>

<b>Choose a password:</b><br>
<input type="password" name="worker_pass" value="{$worker_pass}" size="16"><br>
<br>

<b>Confirm your password:</b><br>
<input type="password" name="worker_pass2" value="{$worker_pass2}" size="16"><br>
<br>

<input type="submit" value="Continue &gt;&gt;">
</form>