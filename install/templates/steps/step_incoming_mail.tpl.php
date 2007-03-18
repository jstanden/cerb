<h2>Configuring the Helpdesk</h2>

<form action="index.php" method="POST">
<input type="hidden" name="step" value="{$smarty.const.STEP_INCOMING_MAIL}">

<H3>Incoming Mail</H3>

[TODO] IMAP/POP3<br>
[TODO] SSL/TLS<br>
<br>

<b>POP3 Server:</b><br>
<input type="text" name="pop3_host" value="{$pop3_host}"><br>
<br>

<input type="submit" value="Test Incoming Mail &gt;&gt;">
</form>