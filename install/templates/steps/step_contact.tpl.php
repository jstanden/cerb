<h2>General Settings</h2>

<form action="index.php" method="POST">
<input type="hidden" name="step" value="{$smarty.const.STEP_CONTACT}">
<input type="hidden" name="form_submit" value="1">

{if $failed}
<span class='bad'>Oops!  Required information was not provided. You must provide a 
valid Default Sender e-mail address to continue.</span><br>
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
team may override their sender information (e.g., sales@yourcompany, 
support@yourcompany, marketing@yourcompany).<br>
<br>
The sender <b>absolutely must</b> be an e-mail address that routes back into 
the helpdesk so replies are properly collected.<br>
<br>

<b>What should the default "From:" address be?</b><br>
<input type="text" name="default_reply_from" value="{$default_reply_from}" size="64"><br>
<br>

The sender of an e-mail message may also contain a friendly name along with 
the e-mail address, such as <i>"Acme Widgets Support Team"</i>.  This is 
optional and you may leave it blank.<br>
<br>

<b>How would you like to identify your helpdesk default sender?</b><br>
<input type="text" name="default_reply_personal" value="{$default_reply_personal}" size="64"><br>
<br>

<input type="submit" value="Continue &gt;&gt;">
</form>