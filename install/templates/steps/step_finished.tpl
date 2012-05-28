<h2>Congratulations!  Setup Complete.</h2>

<form action="index.php" method="POST">
<input type="hidden" name="step" value="{$smarty.const.STEP_FINISHED}">
<input type="hidden" name="form_submit" value="1">

<H3>Your new copy of Cerb is ready for business!</H3>
<a href="{devblocks_url}c=login{/devblocks_url}">Take me there!</a><br>
<br>

<H3>Welcome to the community!</H3>

Cerb6 is the culmination of over 10 years of R&amp;D.  As with any innovation, there may be some 
concepts introduced that are completely new to you and your team.<br>
<br>
The best place to become familiar with Cerb concepts is the <a href="http://www.cerberusweb.com/book/" target="_blank">online documentation</a>.<br>
<br>

<div class="error">
	You should delete the 'install' directory now.
</div>

<br>

</form>