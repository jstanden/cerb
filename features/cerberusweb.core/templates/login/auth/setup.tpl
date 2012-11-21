<form action="{devblocks_url}c=login&ext=password&a=setup{/devblocks_url}" method="post" id="loginForm">
<input type="hidden" name="email" value="{$worker->email}">

<div class="help-box">
	<h1>You need to finish setting up your account</h1>
	
	<p>
		Access to your account requires a password.  You haven't set this up yet.
	</p>
		
	<p>
		To finish setting up your account, please follow the simple steps below.
	</p>
</div>

{if !empty($error)}
<div class="error-box">
	<h1>Error</h1>
	<p>{$error}</p>
</div>
{/if}

<fieldset>
	<legend>Step 1: Type the confirmation code that was sent to {$worker->email}</legend>

	<input type="text" name="confirm_code" value="{$code}" autocomplete="off">
	
	<a href="{devblocks_url}c=login&a=recover{/devblocks_url}?email={$worker->email}" tabindex="-1">can't find it?</a>
</fieldset>

<fieldset>
	<legend>Step 2: Choose a password</legend>

	<table cellpadding="0" cellspacing="0">
		<tr>
			<td style="padding-right:5px;">
				<b>{'preferences.account.password.new'|devblocks_translate|capitalize}</b>
			</td>
			<td>
				<input type="password" name="password" size="32" autocomplete="off">
			</td>
		</tr>
		<tr>
			<td style="padding-right:5px;">
				<b>{'preferences.account.password.verify'|devblocks_translate|capitalize}</b>
			</td>
			<td>
				<input type="password" name="password_confirm" size="32" autocomplete="off">
			</td>
		</tr>
	</table>
</fieldset>

<button type="submit" name="do_submit" value="1"><span class="cerb-sprite2 sprite-tick-circle"></span> {'common.continue'|devblocks_translate|capitalize}</button>

</form>

<script type="text/javascript">
$('#loginForm').find('input:text').first().focus();
</script>
