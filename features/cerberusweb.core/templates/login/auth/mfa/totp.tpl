<div class="cerb-login-bg">
<form action="{devblocks_url}c=login&a=mfa{/devblocks_url}" method="post" id="loginMfaForm">
<input type="hidden" name="_csrf_token" value="{$csrf_token}">

<div class="cerb-login-card">
	<div class="cerb-login-brand">
		<a href="{devblocks_url}{/devblocks_url}" tabindex="-1"><div id="cerb-logo"></div></a>
	</div>

	<h1 class="cerb-login-h1">Two-factor authentication</h1>
	<p class="cerb-login-sub">Enter the security code from your authenticator app.</p>

	{if !empty($error)}
	<div class="error-box">
		<h1>{'common.error'|devblocks_translate|capitalize}</h1>
		<p>{Page_Login::getErrorMessage($error)}</p>
	</div>
	{/if}

	<label class="cerb-login-field">
		<span class="cerb-login-label">Security code</span>
		<div class="cerb-login-input-wrap">
			<span class="cerb-login-icon">
				<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
			</span>
			<input type="text" name="otp" value="" placeholder="e.g. 123456" inputmode="numeric" autocomplete="one-time-code">
		</div>
	</label>

	{if $setting_mfa_can_remember && $setting_mfa_remember_days}
	<label class="cerb-login-check">
		<input type="checkbox" name="remember_device" value="1">
		<span>Remember this device for {$setting_mfa_remember_days} days</span>
	</label>
	{/if}

	<button type="button" class="submit cerb-login-submit" disabled>
		<span>{'common.continue'|devblocks_translate|capitalize}</span>
		<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
	</button>
</div>
</form>
</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	let $frm = $('#loginMfaForm');
	let $otp = $frm.find('[name=otp]');
	let $submit = $frm.find('.submit').attr('disabled', null);

	Devblocks.formDisableSubmit($frm);

	$otp.focus();

	$otp.on('keyup', function(e) {
		e.stopPropagation();
		const keycode = e.keyCode || e.which;

		if(13 === keycode) {
			// Focus the submit button so its :focus orange paints before
			// the click handler disables and navigates
			$submit.focus();
			setTimeout(function() { $submit.click(); }, 100);
		}
	});

	$submit.on('click', function(e) {
		e.stopPropagation();
		$frm[0].onsubmit = null;
		$submit.attr('disabled', 'disabled');
		$frm.submit();
	});
});
</script>
