<h2>Advanced Configuration</h2>

<form action="index.php" method="POST">
<input type="hidden" name="step" value="{$smarty.const.STEP_ANTISPAM}">
<input type="hidden" name="form_submit" value="1">

<H3>Anti-Spam</H3>

Cerberus Helpdesk uses an approach known as "Bayesian Spam Filtering" to 
statistically analyze the language and context of new e-mail messages.  
It is highly recommended you allow the installer to automatically create 
a "Spam" mailbox and configure your helpdesk to redirect probable spam 
into it.<br>
<br>

<b>Would you like to have anti-spam functionality configured for you?</b><br>
<label><input type="radio" name="setup_antispam" value="1" checked> Yes!</label>
<label><input type="radio" name="setup_antispam" value="0"> No thanks</label>
<br>
<br>

<input type="submit" value="Continue &gt;&gt;">
</form>