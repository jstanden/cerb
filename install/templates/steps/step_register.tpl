<h2>Register now and receive your first 3 seats for free.</h2>

<form action="index.php" method="POST">
<input type="hidden" name="step" value="{$smarty.const.STEP_REGISTER}">
<input type="hidden" name="form_submit" value="1">
<input type="hidden" name="skip" value="1">

<h3>You are now running in Evaluation mode</h3>

<div style="margin-left:20px;">
	Welcome to the <b>Cerb</b> community!
	<br>
	<br>

	Each new Cerb installation defaults to <b>evaluation mode</b>, which allows full functionality with no time limit for a single seat (simulatenous user).
	<br>
	<br>
	
	<b><a href="https://cerb.ai/pricing/#bot/license.free" target="_blank">Introduce yourself</a> on our website and we'll send over a free 3-seat license to help you get started. Your free license will never expire for version {$smarty.const.APP_VERSION}.</b></b>
	<br>
	<br>
	
	Enjoy!<br>
	<br>
</div>
<br>

<button type="submit">Continue &raquo;</button>
<br>
<br>

</form>