<h2>Creating Your Account</h2>

<script type="text/javascript" src="jstz.min.js"></script>

<form action="index.php" method="POST">
<input type="hidden" name="step" value="{$smarty.const.STEP_DEFAULTS}">
<input type="hidden" name="form_submit" value="1">

<h3>Your Account</h3>

Next, we'll create your administrator account.<br>
<br>

<b>What is your name?</b><br>
<input type="text" name="worker_firstname" value="{$worker_firstname}" size="16" placeholder="First name"><!--
--><input type="text" name="worker_lastname" value="{$worker_lastname}" size="32" placeholder="Last name">
<br>
<br>

<b>What is your personal email address?</b> (this will be your login)<br>
<input type="text" name="worker_email" value="{$worker_email}" size="64" placeholder="me@company"><br>
<br>

<b>What is your organization's name?</b><br>
<input type="text" name="org_name" value="{$org_name}" size="64" placeholder="Example, Inc."><br>
<br>

<b>Choose a password:</b> (must be at least 8 characters)<br>
<input type="password" name="worker_pass" value="{$worker_pass}" size="16" autocomplete="off" spellcheck="false"><br>
<br>

<b>Confirm your password:</b><br>
<input type="password" name="worker_pass2" value="" size="16" autocomplete="off" spellcheck="false"><br>
<br>

<b>Timezone:</b><br>
<select name="timezone">
<option value=""></option>
{foreach from=$timezones item=tz}
	<option value="{$tz}" {if $timezone==$tz}selected="selected"{/if}>{$tz}</option>
{/foreach}
</select>
<br>
<br>

{if $failed}
<div class="error">
	Oops! Some required information was not provided, or your passwords do not match.
</div>
{/if}

<button type="submit">Continue &raquo;</button>
</form>

<script type="text/javascript">
$(function() {
	var $select_tz = $('FORM SELECT[name=timezone]');
	
	if($select_tz.val() == '') {
		var tz = jstz.determine();
		$select_tz.val(tz.name());
	}
});
</script>