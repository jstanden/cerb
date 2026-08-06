<div class="cerb-login-bg">
<form action="{devblocks_url}c=login&a=mfa{/devblocks_url}" method="post" id="loginMfaForm">
<input type="hidden" name="_csrf_token" value="{$csrf_token}">

<div class="cerb-login-card cerb-ui-panel cerb-ui-panel--filled">
	<div class="cerb-login-brand">
		<a href="{devblocks_url}{/devblocks_url}" tabindex="-1"><div id="cerb-logo"></div></a>
	</div>

	<div class="cerb-ui-header">
		<div>
			<div class="cerb-ui-header--title-sm">Two-factor authentication</div>
			<div class="cerb-ui-header--subtitle">Enter the security code from your authenticator app.</div>
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

	<div class="cerb-ui-form">
		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">Security code</label>
			<div class="cerb-ui-form--control">
				<span class="cerb-ui-form--control-icon cerb-icons cerb-icon-lock"></span>
				<input type="text" name="otp" value="" placeholder="e.g. 123456" inputmode="numeric" autocomplete="one-time-code">
			</div>
		</div>

		{if $setting_mfa_can_remember && $setting_mfa_remember_days}
		<label class="cerb-login-check">
			<input type="checkbox" name="remember_device" value="1">
			<span>Remember this device for {$setting_mfa_remember_days} days</span>
		</label>
		{/if}

		<button type="button" class="submit cerb-login-submit cerb-ui-button" disabled>
			<span>{'common.continue'|devblocks_translate|capitalize}</span>
			<span class="cerb-icons cerb-icon-right-arrow"></span>
		</button>
	</div>
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
			// Focus the submit button so its :focus ring paints before
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
