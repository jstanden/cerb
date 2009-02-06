<h2>Database Problems</h2>

<form action="index.php" method="POST">
<input type="hidden" name="step" value="{$smarty.const.STEP_INIT_DB}">

<span class='bad'>Database Initialization Failed!</span><br>
<br>

<i>Error:</i><br>
{* [TODO] Print out the specific error from the patcher service *}
{* [TODO] Contact support email/forums link *}

<input type="submit" value="Try Again &gt;&gt;">
</form>