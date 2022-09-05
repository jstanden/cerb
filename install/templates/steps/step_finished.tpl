<h2>Congratulations!  Setup has been successfully completed.</h2>

<form action="index.php" method="POST">
<input type="hidden" name="step" value="{$smarty.const.STEP_FINISHED}">
<input type="hidden" name="form_submit" value="1">

<div class="error">
	You should delete the 'install' directory now.
</div>

<h3>Welcome to the community!</h3>

<div style="margin-left:20px;">
	<b>Cerb</b> is the result of over 20 years of research &amp; development.
	The software will likely introduce some concepts that are completely new to you and your team.  
	<br>
	The best place to become familiar with Cerb is the <a href="https://cerb.ai/docs/home/" target="_blank" rel="noopener"><b>online documentation</b></a>.<br>
</div>

<h3>Your new copy of Cerb is ready for business!</h3>
<div style="margin-left:20px;">
	<a href="{devblocks_url}c=login{/devblocks_url}" style="font-size:120%;"><b>Log in and get started</b></a>
</div>
<br>

</form>