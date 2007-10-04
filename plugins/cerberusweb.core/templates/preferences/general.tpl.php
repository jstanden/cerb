{include file="$tpl_path/preferences/menu.tpl.php"}

<div class="block">
<h2>General</h2>

<form action="{devblocks_url}{/devblocks_url}" onsubmit="pwsMatch=(this.change_pass.value==this.change_pass_verify.value);if(!pwsMatch)document.getElementById('preferences_error').innerHTML='The passwords entered do not match.  Try again.';return pwsMatch;" method="post">
<input type="hidden" name="c" value="preferences">
<input type="hidden" name="a" value="saveDefaults">

<b>Change Password:</b><br>
<div id="preferences_error" style="color: red; font-weight: bold;"></div>
<table cellspacing="1" cellpadding="0" border="0">
<tr>
	<td>Change Password: </td>
	<td><input type="password" name="change_pass" value=""></td>
</tr>
<tr>
	<td>Verify Password: </td>
	<td><input type="password" name="change_pass_verify"=""></td>
</tr>
</table>
<br>

<b>Assist Mode:</b><br>
<label><input type="checkbox" name="assist_mode" value="1" {if $assist_mode eq 1}checked{/if}> Enable Helpdesk Assistant</label><br>
<br>

<!-- 
<b>Timezone:</b><br>
<select name="timezone">
	<option value="">---</option>
</select><br>
<br>
 -->

<input type="submit" value="{$translate->_('common.save_changes')}">
</form>
</div>
