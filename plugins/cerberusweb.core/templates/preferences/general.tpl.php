{include file="$tpl_path/preferences/menu.tpl.php"}

<div class="block">
<h2>General</h2>

<form action="{devblocks_url}{/devblocks_url}" method="post">
<input type="hidden" name="c" value="preferences">
<input type="hidden" name="a" value="saveDefaults">

<b>Change Password:</b><br>
New Password <input type="password" name="change_pass" value=""><br>
Verify Password <input type="password" name="change_pass_verify"=""><br><br>

<b>Assist Mode:</b><br>
<label><input type="checkbox" name="assist_mode" value="1"> Enable Helpdesk Assistant</label><br>
<br>

<b>Timezone:</b><br>
<select name="timezone">
	<option value="">---</option>
</select><br>
<br>

<input type="submit" value="{$translate->_('common.save_changes')}">
</form>
</div>
