<h2>Advanced Configuration</h2>

<form action="index.php" method="POST">
<input type="hidden" name="step" value="{$smarty.const.STEP_CATCHALL}">
<input type="hidden" name="form_submit" value="1">

<H3>Default Mailbox</H3>

Cerberus Helpdesk allows you to create flexible routing rules to 
determine how incoming mail is assigned to mailboxes.<br>
<br>
In the event none of your routing rules specify a destination for a message 
you have two choices:<br>
<ul>
	<li>You may bounce the message back to the sender.</li>
	<li>You may assign a Default Mailbox to catch unrouted mail.</li>
</ul>
<br>

<b>Where should Cerberus Helpdesk send unrouted mail?</b><br>
<select name="default_mailbox_id">
	<option value="">-- Nowhere (Bounce) --
	{foreach from=$mailboxes item=mail key=mailbox_id}
	<option value="{$mailbox_id}">{$mail->name}
	{/foreach}
</select>
<br>
<br>

<input type="submit" value="Continue &gt;&gt;">
</form>