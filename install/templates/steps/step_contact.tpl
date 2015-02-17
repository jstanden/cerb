<h2>General Settings</h2>

<form action="index.php" method="POST">
<input type="hidden" name="step" value="{$smarty.const.STEP_CONTACT}">
<input type="hidden" name="form_submit" value="1">

{if $failed}
<div class="error">
Oops!  Required information was not provided. 
You must provide a valid Default Sender e-mail address to continue.
</div>
<br>
{/if}

<H3>Browser Title</H3>

This is the label that is displayed in the tab of a web browser to describe this website. 
It is also used as a description when bookmarking pages of this web site.  Generally 
you'll want to personalize this, such as "Acme Widgets Helpdesk".<br>
<br>

<b>What would you like to use for a website title?</b><br>
<input type="text" name="helpdesk_title" value="{$helpdesk_title}" size="64" style="width:95%;"><br>
<br>

<H3>Default Sender</H3>

When a worker replies to messages from Cerb, this email address will be 
used as the sender by default.  This proxy protects your workers' direct email 
addresses and ensures that all replies are routed back to Cerb.  Each 
group may configure their own sender information (e.g., sales@yourcompany, 
support@yourcompany, marketing@yourcompany).<br>
<br>
The sender <b>absolutely must</b> be an email address that routes back into 
Cerb (e.g. by POP3) so that incoming replies to your messages are properly 
received.<br>
<br>

<b>What email address should be the default sender for outgoing email?</b><br>
<input type="text" name="default_reply_from" value="{$default_reply_from}" size="64">
(e.g. support@yourcompany.com)
<br>
<br>

<b>Would you like to use a personalized sender name for outgoing email?</b> (optional)<br>
<input type="text" name="default_reply_personal" value="{$default_reply_personal}" size="64">
(e.g. "Acme Widgets Helpdesk")
<br>
<br>

<input type="submit" value="Continue &gt;&gt;">
</form>