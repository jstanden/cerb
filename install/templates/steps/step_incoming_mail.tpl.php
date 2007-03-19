<h2>Incoming Mail</h2>

<form action="index.php" method="POST">
<input type="hidden" name="step" value="{$smarty.const.STEP_INCOMING_MAIL}">
<input type="hidden" name="form_submit" value="1">

<H3>POP3/IMAP</H3>

The recommended process is setting up a mailbox which receives all your redirected mail. [ELABORATE]<br>
[TODO] IMAP/POP3<br>
[TODO] SSL/TLS<br>
<br>

{if $failed}
<span class='bad'>
Could not connect to your mailbox.  Please correct the following errors:
{if !empty($error_msgs)}
<ul>
	{foreach from=$error_msgs item=error}
		<li>{$error}</li>
	{/foreach}
</ul>  
{/if}
</span>
<br>
{/if}

<b>Incoming Mail Service:</b><br>
<select name="imap_service">
	<option value="pop3">POP3
	<option value="pop3-ssl">POP3-SSL
	<option value="imap">IMAP
</select><br>
<br>

<b>Server:</b><br>
<input type="text" name="imap_host" value="{$imap_host}"><br>
<br>

<b>User:</b><br>
<input type="text" name="imap_user" value="{$imap_user}"><br>
<br>

<b>Password:</b><br>
<input type="text" name="imap_pass" value="{$imap_pass}"><br>
<br>

<b>Port:</b><br>
<input type="text" name="imap_port" value="{$imap_port}"><br>
<br>

<input type="submit" value="Test Incoming Mail &gt;&gt;">
</form>