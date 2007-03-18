<h2>Configuring the Helpdesk</h2>

<form action="index.php" method="POST">
<input type="hidden" name="step" value="{$smarty.const.STEP_OUTGOING_MAIL}">

<H3>Outgoing Mail</H3>

<b>SMTP Server:</b><br>
<input type="text" name="smtp_host" value="{$smtp_host}"><br>
<br>

<b>SMTP Auth. User (optional):</b><br>
<input type="text" name="smtp_auth_user" value="{$smtp_auth_user}"><br>
<br>

<b>SMTP Auth. Password (optional):</b><br>
<input type="text" name="smtp_auth_pass" value="{$smtp_auth_pass}"><br>
<br>

<input type="submit" value="Test Outgoing Mail &gt;&gt;">
</form>