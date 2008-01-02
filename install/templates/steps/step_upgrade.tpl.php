<h2>Upgrade</h2>

<h3>Oops! Is this an upgrade?</h3>

<form action="index.php" method="POST">
<input type="hidden" name="step" value="{$smarty.const.STEP_UPGRADE}">

Your database appears to exist already!  This installer doesn't need to be run for every upgrade.  Database patches will be applied automatically if they are required.<br>
<br>
You can return to your helpdesk: 
<a href="{devblocks_url full=true}{/devblocks_url}">{devblocks_url full=true}{/devblocks_url}</a><br>
<br>

{*
	<span class='bad'>Database Upgrade Failed!</span><br>
*}

</form>