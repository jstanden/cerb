<form action="{devblocks_url}c=login&ext=password-gauth&a=setup{/devblocks_url}" method="post" id="loginForm">
<input type="hidden" name="do_submit" value="1">
<input type="hidden" name="email" value="{$worker->email}">

<div class="help-box">
	<h1>You need to finish setting up your account</h1>
	
	<p>
		Access to your account requires two-factor authentication.  You haven't set this up yet.
	</p>
		
	<p>
		Put simply, "two factor" authentication means "something you know" (your password) and "something you have" (a one-time password that changes every 30 seconds on your mobile device).
		Requiring two forms of identification makes it much more difficult for someone to break into your account, because they would need to know your password and have physical access to your mobile device.
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
	<legend>Step 2: Scan this QR code with the Google Authenticator app</legend>
	
	<div id="qrcode"></div>
	
	<p style="margin-top:10px;">
		If you haven't already, visit <a href="http://m.google.com/authenticator" target="_blank" tabindex="-1">http://m.google.com/authenticator</a> from your iOS, Android, or Blackberry mobile device to install the Google Authenticator app.
	</p>
</fieldset>

<fieldset>
	<legend>Step 3: Type the current access code from the Google Authenticator app</legend>

	<input type="text" name="otp_code" size="8" maxlength="6" autocomplete="off">
</fieldset>

{if empty($worker->pass)}
<fieldset>
	<legend>Step 4: Choose a password</legend>

	<table cellpadding="0" cellspacing="0">
		<tr>
			<td style="padding-right:5px;">
				<b>{'preferences.account.password.new'|devblocks_translate|capitalize}:</b>
			</td>
			<td>
				<input type="password" name="password" size="32" autocomplete="off">
			</td>
		</tr>
		<tr>
			<td style="padding-right:5px;">
				<b>{'preferences.account.password.verify'|devblocks_translate|capitalize}:</b>
			</td>
			<td>
				<input type="password" name="password_confirm" size="32" autocomplete="off">
			</td>
		</tr>
	</table>
</fieldset>
{/if}

<button type="submit"><span class="cerb-sprite2 sprite-tick-circle"></span> {'common.continue'|devblocks_translate|capitalize}</button>

</form>

<script type="text/javascript">
$('#qrcode').qrcode({ width:192, height:192, text:"otpauth://totp/Cerb:{$worker->email}?secret={$seed}" });

$('#loginForm').find('input:text').first().focus();
</script>
