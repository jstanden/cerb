<h2>Outgoing Mail</h2>

<form action="index.php" method="POST">
<input type="hidden" name="step" value="{$smarty.const.STEP_OUTGOING_MAIL}">
<input type="hidden" name="form_submit" value="1">

<H3>SMTP</H3>

{if $smtp_error_display}
	<div class="error">
		{$smtp_error_display}
	</div>
{/if}

<b>SMTP Server:</b><br>
<input type="text" name="smtp_host" value="{$smtp_host}" size="45"><br>
<br>

<b>SMTP Port:</b><br>
<input type="text" name="smtp_port" value="{$smtp_port}" size="5"><br>
<br>

<i>SMTP Auth. User (optional):</i><br>
<input type="text" name="smtp_auth_user" value="{$smtp_auth_user}"><br>
<br>

<i>SMTP Auth. Password (optional):</i><br>
<input type="text" name="smtp_auth_pass" value="{$smtp_auth_pass}"><br>
<br>

{if !empty($form_submit)}
	<b>SENT! Did you receive the test e-mail to {$smtp_to}?</b> (It may take a few moments)<br>
	<label><input type="radio" name="passed" value="1" checked> Yes!</label> 
	<label><input type="radio" name="passed" value="0"> No, please retry.</label>
	<br> 
	<br>
	<input type="submit" value="Continue &gt;&gt;">
{else}
	<input type="submit" value="Test Outgoing Mail &gt;&gt;">
{/if}

</form>