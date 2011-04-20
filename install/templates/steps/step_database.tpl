<h2>Database Setup</h2>

<form action="index.php" method="POST">
<input type="hidden" name="step" value="{$smarty.const.STEP_DATABASE}">

{if $failed}
<span class='bad'>Database Connection Failed!  Please check your settings and try again.</span><br>
<br>
{/if}

<b>Driver:</b><br>
<select name="db_driver">
	{foreach from=$drivers item=driver key=k}
	<option value="{$k}" {if $k==$db_driver}selected{/if}>{$driver}
	{/foreach}
</select><br>
<br>

<b>Host:</b><br>
<input type="text" name="db_server" value="{$db_server}"><br>
<br>

<b>Database Name:</b><br>
<input type="text" name="db_name" value="{$db_name}"><br>
<br>

<b>Username:</b><br>
<input type="text" name="db_user" value="{$db_user}"><br>
<br>

<b>Password:</b><br>
<input type="text" name="db_pass" value="{$db_pass}"><br>
<br>

<input type="submit" value="Test Settings &gt;&gt;">
</form>