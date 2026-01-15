<h2>Create Your Admin Account</h2>

{include file="includes/jstz.js.tpl"}

{if $failed}
<div class="alert alert-error">
	<span class="icon">{call name="icon" icon="circle-x" size=20}</span>
	<div class="content">
		<strong>Please check your input</strong>
		<p class="mb-0">Some required information was not provided, or your passwords do not match.</p>
	</div>
</div>
{/if}

{if $error_display}
<div class="alert alert-error">
	<span class="icon">{call name="icon" icon="circle-x" size=20}</span>
	<div class="content">{$error_display}</div>
</div>
{/if}

<form action="index.php" method="POST">
	<input type="hidden" name="step" value="{$smarty.const.STEP_DEFAULTS}">
	<input type="hidden" name="form_submit" value="1">

	<div class="form-row">
		<div class="form-group">
			<label for="worker_firstname">First Name</label>
			<input type="text" name="worker_firstname" id="worker_firstname" value="{$worker_firstname}" placeholder="First name">
		</div>
		<div class="form-group">
			<label for="worker_lastname">Last Name</label>
			<input type="text" name="worker_lastname" id="worker_lastname" value="{$worker_lastname}" placeholder="Last name">
		</div>
	</div>

	<div class="form-row">
		<div class="form-group">
			<label for="worker_email">Email Address <span class="text-muted">(your login)</span></label>
			<input type="text" name="worker_email" id="worker_email" value="{$worker_email}" placeholder="you@company.com">
		</div>
		<div class="form-group">
			<label for="org_name">Organization</label>
			<input type="text" name="org_name" id="org_name" value="{$org_name}" placeholder="Example, Inc.">
		</div>
	</div>

	<div class="form-row">
		<div class="form-group">
			<label for="worker_pass">Password <span class="text-muted">(8+ characters)</span></label>
			<input type="password" name="worker_pass" id="worker_pass" value="{$worker_pass}" autocomplete="new-password" spellcheck="false">
		</div>
		<div class="form-group">
			<label for="worker_pass2">Confirm Password</label>
			<input type="password" name="worker_pass2" id="worker_pass2" value="" autocomplete="new-password" spellcheck="false">
		</div>
	</div>

	<div class="form-group">
		<label for="timezone">Timezone</label>
		<select name="timezone" id="timezone">
			<option value=""></option>
			{foreach from=$timezones item=tz}
			<option value="{$tz}" {if $timezone==$tz}selected="selected"{/if}>{$tz}</option>
			{/foreach}
		</select>
	</div>

	<h2>Default Sender</h2>

	<div class="form-row">
		<div class="form-group">
			<label for="default_reply_from">Email Address <span class="text-muted">(routes replies to Cerb)</span></label>
			<input type="text" name="default_reply_from" id="default_reply_from" value="{$default_reply_from}" placeholder="support@example.com">
		</div>
		<div class="form-group">
			<label for="default_reply_personal">Display Name <span class="text-muted">(optional)</span></label>
			<input type="text" name="default_reply_personal" id="default_reply_personal" value="{$default_reply_personal}" placeholder="Example Support">
		</div>
	</div>

	<div class="button-row">
		<button type="submit">
			Continue
			{call name="icon" icon="arrow-right" size=18}
		</button>
	</div>
</form>

<script type="text/javascript">
let $select_tz = document.querySelector('select[name=timezone]');

if($select_tz.value === '') {
	let tz = jstz.determine();
	$select_tz.value = tz.name();
}
</script>
