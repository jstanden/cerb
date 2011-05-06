<h2>Database Setup</h2>

<form action="index.php" method="POST">
<input type="hidden" name="step" value="{$smarty.const.STEP_DATABASE}">

{if $failed && !empty($errors)}
<ul>
{foreach from=$errors item=error}
<li style="font-weight:bold;color:rgb(220,0,0);">{$error}</li>
{/foreach}
</ul>
{/if}

<b>Driver:</b><br>
<select name="db_driver">
	{foreach from=$drivers item=driver key=k}
	<option value="{$k}" {if $k==$db_driver}selected{/if}>{$driver}
	{/foreach}
</select><br>
<br>

<b>Engine:</b><br>
<select name="db_engine">
	{foreach from=$engines item=engine key=k}
	<option value="{$k}" {if $k==$db_engine}selected{/if}>{$engine}
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