<h2>Congratulations!  Setup Complete.</h2>

<form action="index.php" method="POST">
<input type="hidden" name="step" value="{$smarty.const.STEP_FINISHED}">
<input type="hidden" name="form_submit" value="1">

<H3>Your new helpdesk is ready for business!</H3>
<a href="{devblocks_url}c=login{/devblocks_url}">Take me there!</a><br>
<br>

<H3>Welcome to the Cerberus Helpdesk community!</H3>

Cerberus Helpdesk is the culmination of over 8 years of R&amp;D to
improve team-based webmail.  As with any such innovation, there may be some 
concepts introduced that are completely new to you and your team.  This is 
especially likely if you are migrating away from desktop e-mail programs to the 
web for the first time.<br>
<br>
The best place to become familiar with the concepts used in Cerberus Helpdesk 
is the <a href="http://wiki.cerb5.com/wiki/" target="_blank">online documentation</a>. 
This area is dedicated to creating and maintaining tutorials, feature guides and best practices.<br>
<br>

<div class="error">
	You should delete the 'install' directory now.
</div>

<br>

</form>