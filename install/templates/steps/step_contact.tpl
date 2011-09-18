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

<H3>Helpdesk Title</H3>

The title of your helpdesk is displayed in the "title bar" of a web browser. 
It is especially useful for a description when bookmarking web sites.  Generally 
you'll want to personalize this, such as "Acme Widgets Helpdesk".<br>
<br>

<b>What would you like to use for a helpdesk title?</b><br>
<input type="text" name="helpdesk_title" value="{$helpdesk_title}" size="64"><br>
<br>

<H3>Helpdesk Default Sender</H3>

When a worker responds to e-mail in the helpdesk this e-mail address will be 
used as the sender by default.  This proxy protects your workers' direct email 
addresses and ensures all replies are routed back to the helpdesk.  Each 
group may override their sender information (e.g., sales@yourcompany, 
support@yourcompany, marketing@yourcompany).<br>
<br>
The sender <b>absolutely must</b> be an e-mail address that routes back into 
the helpdesk (by POP3, for example) so replies to your messages are properly 
received.<br>
<br>

<b>What e-mail address should be the default sender for outgoing e-mail?</b><br>
<input type="text" name="default_reply_from" value="{$default_reply_from}" size="64">
(e.g. support@yourcompany.com)
<br>
<br>

<b>Would you like to use a personalized sender name for outgoing e-mail?</b> (optional)<br>
<input type="text" name="default_reply_personal" value="{$default_reply_personal}" size="64">
(e.g. "Acme Widgets Helpdesk")
<br>
<br>

<input type="submit" value="Continue &gt;&gt;">
</form>