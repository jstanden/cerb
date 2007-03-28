<h2>Upgrade</h2>

<form action="index.php" method="POST">
<input type="hidden" name="step" value="{$smarty.const.STEP_UPGRADE}">

{if $failed}
	<span class='bad'>Database Upgrade Failed!</span><br>
	<br>
	
	<i>Error:</i><br>
	{* [TODO] Print out the specific error from the patcher service *}
	{* [TODO] Contact support email/forums link *}
	
	<input type="submit" value="Try Again &gt;&gt;">
{else}
	

{/if}

</form>