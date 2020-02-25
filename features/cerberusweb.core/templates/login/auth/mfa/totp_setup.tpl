<form action="{devblocks_url}c=login&a=mfa{/devblocks_url}" method="post" id="setupMfaForm">
<input type="hidden" name="_csrf_token" value="{$csrf_token}">

<div style="vertical-align:middle;max-width:500px;margin:20px auto 20px auto;padding:5px 20px 20px 20px;border-radius:5px;box-shadow:darkgray 0px 0px 5px;">
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
	<div class="error-box" style="border:0;">
		<h1>{'common.error'|devblocks_translate|capitalize}</h1>
		<p>{Page_Login::getErrorMessage($error)}</p>
	</div>
	{/if}
	
	<div>
		<h3>Step 1: Scan this QR code with your app (e.g. 1Password, Authy, Google Authenticator):</h3>
		
		<div id="qrcode"></div>
		
		<p style="margin-top:10px;">
			or type this code manually: <b>{$seed}</b>
		</p>
		
		<p style="margin-top:10px;">
			Need help? See: <a href="https://cerb.ai/guides/security/two-factor-auth/" target="_blank" rel="noopener noreferrer" tabindex="-1">Configure two-factor authentication</a>
		</p>
		
		<h3>Step 2: Type the current access code from your two-factor app:</h3>
		
		<div>
			<input type="text" name="otp" size="45" value="" placeholder="e.g. 123456" style="width:100%;line-height:1.5em;height:24px;padding:0px 5px;border-radius:5px;box-sizing:border-box;">
		</div>

		<div style="margin-top:10px;">
			<button type="submit" name="action" value="new_otp" style="width:100%;">
				{'common.verify'|devblocks_translate|capitalize}
			</button>
		</div>
	</div>
</div>
</form>

<script type="text/javascript">
$(function() {
	var options = { width:192, height:192, text:"otpauth://totp/Cerb:{$seed_name}?secret={$seed}" };
	var hasCanvasSupport = !!window.CanvasRenderingContext2D;

	// If no <canvas> tag, use <table> instead
	if(!hasCanvasSupport)
		options.render = 'table';

	$('#qrcode').qrcode(options);
	$('#setupMfaForm').find('input[name=otp]').first().focus();
});
</script>
