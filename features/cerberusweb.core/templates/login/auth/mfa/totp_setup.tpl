<div class="cerb-login-bg">
<form action="{devblocks_url}c=login&a=mfa{/devblocks_url}" method="post" id="setupMfaForm">
<input type="hidden" name="_csrf_token" value="{$csrf_token}">

<div class="cerb-login-card cerb-ui-panel cerb-ui-panel--filled">
	<div class="cerb-login-brand">
		<a href="{devblocks_url}{/devblocks_url}" tabindex="-1"><div id="cerb-logo"></div></a>
	</div>

	<div class="cerb-ui-header">
		<div>
			<div class="cerb-ui-header--title-sm">Set up two-factor authentication</div>
			<div class="cerb-ui-header--subtitle">Access to your account requires a one-time code that changes every 30 seconds.</div>
		</div>
	</div>

	<div class="cerb-ui-panel cerb-ui-panel--spaced cerb-ui-panel--note">
		<div class="cerb-ui-header cerb-ui-header--center">
			<div class="cerb-ui-callout">
				<span class="cerb-icons cerb-icon-circle-info cerb-ui-callout--icon"></span>
				<div>
					<div class="cerb-ui-header--subtitle">"Two factor" means <b>something you know</b> (your password) and <b>something you have</b> (a one-time code from your mobile device). Requiring both makes it much harder for someone else to access your account.</div>
				</div>
			</div>
		</div>
	</div>

	{if !empty($error)}
	<div class="cerb-ui-panel cerb-ui-panel--spaced cerb-ui-panel--alert">
		<div class="cerb-ui-header cerb-ui-header--center">
			<div class="cerb-ui-callout">
				<span class="cerb-icons cerb-icon-alert cerb-ui-callout--icon"></span>
				<div>
					<div class="cerb-ui-header--title-sm">{'common.error'|devblocks_translate|capitalize}</div>
					<div class="cerb-ui-header--subtitle">{Page_Login::getErrorMessage($error)}</div>
				</div>
			</div>
		</div>
	</div>
	{/if}

	<div class="cerb-ui-header--title-sm" style="margin-bottom:8px;">Step 1: Scan this QR code with your app</div>
	<div class="cerb-ui-header--subtitle" style="margin:0 0 12px 0;">e.g. Apple Keychain, 1Password, Google Authenticator</div>

	<div id="qrcode" style="margin:0 0 12px 0;"></div>

	<p style="margin:0 0 8px 0;">or enter this code manually: <b>{$seed}</b></p>

	<p style="margin:0 0 20px 0;">
		Need help? See: <a href="https://cerb.ai/guides/security/two-factor-auth/" target="_blank" rel="noopener noreferrer" tabindex="-1">Configure two-factor authentication</a>
	</p>

	<div class="cerb-ui-header--title-sm" style="margin-bottom:8px;">Step 2: Type the current access code from your app</div>

	<div class="cerb-ui-form">
		<div class="cerb-ui-form--field">
			<div class="cerb-ui-form--control">
				<span class="cerb-ui-form--control-icon cerb-icons cerb-icon-lock"></span>
				<input type="text" name="otp" value="" placeholder="e.g. 123456" inputmode="numeric" autocomplete="one-time-code">
			</div>
		</div>

		<button type="submit" name="action" value="new_otp" class="cerb-login-submit cerb-ui-button">
			<span>{'common.verify'|devblocks_translate|capitalize}</span>
			<span class="cerb-icons cerb-icon-right-arrow"></span>
		</button>
	</div>
</div>
</form>
</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	if(window.CerbUI && CerbUI.QrCode)
		new CerbUI.QrCode(document.getElementById('qrcode'), { size:192, text:"otpauth://totp/Cerb:{$seed_name|escape:'url'}?secret={$seed}&issuer=Cerb" });

	$('#setupMfaForm').find('input[name=otp]').first().focus();
});
</script>
