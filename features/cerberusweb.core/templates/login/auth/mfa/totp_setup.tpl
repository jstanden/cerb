<div class="cerb-login-bg">
<form action="{devblocks_url}c=login&a=mfa{/devblocks_url}" method="post" id="setupMfaForm">
<input type="hidden" name="_csrf_token" value="{$csrf_token}">

<div class="cerb-login-card">
	<div class="cerb-login-brand">
		<a href="{devblocks_url}{/devblocks_url}" tabindex="-1"><div id="cerb-logo"></div></a>
	</div>

	<h1 class="cerb-login-h1">Set up two-factor authentication</h1>
	<p class="cerb-login-sub">Access to your account requires a one-time code that changes every 30 seconds.</p>

	<div class="help-box" style="margin-bottom:16px;">
		<p style="margin:0;">
			"Two factor" means <b>something you know</b> (your password) and <b>something you have</b> (a one-time code from your mobile device). Requiring both makes it much harder for someone else to access your account.
		</p>
	</div>

	{if !empty($error)}
	<div class="error-box">
		<h1>{'common.error'|devblocks_translate|capitalize}</h1>
		<p>{Page_Login::getErrorMessage($error)}</p>
	</div>
	{/if}

	<h3 style="margin:0 0 8px 0;">Step 1: Scan this QR code with your app</h3>
	<p class="cerb-login-sub" style="margin:0 0 12px 0;">e.g. Apple Keychain, 1Password, Google Authenticator</p>

	<div id="qrcode" style="margin:0 0 12px 0;"></div>

	<p style="margin:0 0 8px 0;">or enter this code manually: <b>{$seed}</b></p>

	<p style="margin:0 0 20px 0;">
		Need help? See: <a href="https://cerb.ai/guides/security/two-factor-auth/" target="_blank" rel="noopener noreferrer" tabindex="-1" class="cerb-login-forgot">Configure two-factor authentication</a>
	</p>

	<label class="cerb-login-field">
		<span class="cerb-login-label">Step 2: Type the current access code from your app</span>
		<div class="cerb-login-input-wrap">
			<span class="cerb-login-icon">
				<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
			</span>
			<input type="text" name="otp" value="" placeholder="e.g. 123456" inputmode="numeric" autocomplete="one-time-code">
		</div>
	</label>

	<button type="submit" name="action" value="new_otp" class="cerb-login-submit">
		<span>{'common.verify'|devblocks_translate|capitalize}</span>
		<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
	</button>
</div>
</form>
</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	const options = { width:192, height:192, text:"otpauth://totp/Cerb:{$seed_name}?secret={$seed}" };
	const hasCanvasSupport = !!window.CanvasRenderingContext2D;

	// If no <canvas> tag, use <table> instead
	if(!hasCanvasSupport)
		options.render = 'table';

	$('#qrcode').qrcode(options);
	$('#setupMfaForm').find('input[name=otp]').first().focus();
});
</script>
