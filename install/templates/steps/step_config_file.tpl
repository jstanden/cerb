<h2>Save framework.config.php</h2>

<form action="index.php" method="POST">
<input type="hidden" name="step" value="{$smarty.const.STEP_SAVE_CONFIG_FILE}">
<input type="hidden" name="overwrite" value="1">
<input type="hidden" name="db_driver" value="{$db_driver}">
<input type="hidden" name="db_engine" value="{$db_engine}">
<input type="hidden" name="db_server" value="{$db_server}">
<input type="hidden" name="db_name" value="{$db_name}">
<input type="hidden" name="db_user" value="{$db_user}">
<input type="hidden" name="db_pass" value="{$db_pass}">

{if $failed}
<span class='bad'>The framework.config.php file does not appear to have updated settings.  Please try again.</span><br>
<br>
{/if}

Since your environment did not support the writing of your <b>framework.config.php</b> file automatically, 
you'll need to overwrite the existing contents of the file with the following:<br>
<br>
<i>{$config_path}</i>:<br>
<textarea cols="80" rows="10" name="result">{$result}</textarea><br>
<br>
<input type="submit" value="Test My Changes&gt;&gt;">
</form>