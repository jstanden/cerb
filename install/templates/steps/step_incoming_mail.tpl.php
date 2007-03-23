<h2>Incoming Mail</h2>

<form action="index.php" method="POST">
<input type="hidden" name="step" value="{$smarty.const.STEP_INCOMING_MAIL}">
<input type="hidden" name="form_submit" value="1">

<H3>POP3/IMAP</H3>

The helpdesk can retrieve mail from most mail servers through POP3/IMAP.  If you're 
migrating from a desktop e-mail client (such as Outlook, Thunderbird or Evolution) you 
can likely use the same mail server information.<br>
<br>The recommended practice is creating a single POP3 box (cerberus@company) that has other addresses 
(sales@company, marketing@company, etc.) redirected to it by your mail server.  With 
many popular server-side control panels you may alternatively set up these addresses as 'aliases' on 
your POP3 mailbox without having to create separate redirects for each address.<br>
<br>
This practice saves you the hassle of checking a POP3 mailbox for every e-mail address, which is
definitely NOT recommended.<br>
<br>
<i>If for any reason you need to skip this section you may leave the form blank and provide 
the information later from Configuration-&gt;Mail.</i><br>
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
<input type="text" name="imap_host" value="{$imap_host}" size="45"><br>
<br>

<b>User:</b><br>
<input type="text" name="imap_user" value="{$imap_user}"><br>
<br>

<b>Password:</b><br>
<input type="text" name="imap_pass" value="{$imap_pass}"><br>
<br>

<b>Port:</b><br>
<input type="text" name="imap_port" value="{$imap_port}" size="5"><br>
<br>

<input type="submit" value="Test Incoming Mail &gt;&gt;">
</form>