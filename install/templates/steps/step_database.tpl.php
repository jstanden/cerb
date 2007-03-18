<h2>Database Setup</h2>

<form action="index.php" method="POST">
<input type="hidden" name="step" value="{$smarty.const.STEP_DATABASE}">

{if $failed}
<span class='bad'>Database Connection Failed!  Please check your settings and try again.</span><br>
<br>
{/if}

<b>Database Driver:</b><br>
<select name="db_driver">
	<option value="mysql">MySQL 3.23/4.x/5.x
	<option value="postgres8">PostgreSQL 8.x
	<option value="postgres7">PostgreSQL 7.x
	<option value="postgres64">PostgreSQL 6.4
	<option value="mssql">Microsoft SQL Server 7.x/2000/2005
	<option value="oci8">Oracle 8/9
</select><br>
<br>

<b>Database Server:</b><br>
<input type="text" name="db_server" value="{$db_server}"><br>
<br>

<b>Database Name:</b><br>
<input type="text" name="db_name" value="{$db_name}"><br>
<br>

<b>Database User:</b><br>
<input type="text" name="db_user" value="{$db_user}"><br>
<br>

<b>Database Password:</b><br>
<input type="text" name="db_pass" value="{$db_pass}"><br>
<br>

<input type="submit" value="Test Settings &gt;&gt;">
</form>