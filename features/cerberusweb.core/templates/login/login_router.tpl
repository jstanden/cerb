<div class="cerb-login-bg">
<form action="{devblocks_url}c=login&a=authenticate{/devblocks_url}" method="post" id="loginForm">
<input type="hidden" name="_csrf_token" value="{$csrf_token}">

<div class="cerb-login-card cerb-ui-panel cerb-ui-panel--filled">
	<div class="cerb-login-brand">
		<a href="{devblocks_url}{/devblocks_url}" tabindex="-1"><div id="cerb-logo"></div></a>
	</div>

	<div class="cerb-ui-header">
		<div>
			<div class="cerb-ui-header--title-sm">Sign in to your workspace</div>
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

	{if $sso_services}
	<div class="cerb-login-sso-grid">
		{foreach from=$sso_services item=sso_service}
		<button type="button" class="cerb-login-sso-btn cerb-ui-button cerb-ui-button--subtle" data-cerb-button-sso="{devblocks_url}c=sso&uri={$sso_service->uri}{/devblocks_url}">
			<img src="{devblocks_url}c=sso&a=_avatar&uri={$sso_service->uri}{/devblocks_url}?v={$sso_service->updated_at}" alt="">
			<span>{$sso_service->name}</span>
		</button>
		{/foreach}
	</div>

	<div class="cerb-login-or"><span>or sign in with email</span></div>
	{/if}

	<div class="cerb-ui-form">
		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">Email address</label>
			<div class="cerb-ui-form--control">
				<span class="cerb-ui-form--control-icon cerb-icons cerb-icon-mail"></span>
				<input type="email" name="email" value="{$email}" placeholder="you@company.com" autocomplete="username">
			</div>
		</div>

		<div class="cerb-ui-form--field">
			<div class="cerb-login-field-row">
				<label class="cerb-ui-form--label">Password</label>
				<a href="{devblocks_url}c=login&a=recover{/devblocks_url}" tabindex="-1">Forgot password?</a>
			</div>
			<div class="cerb-ui-form--control">
				<span class="cerb-ui-form--control-icon cerb-icons cerb-icon-lock"></span>
				<input type="password" name="password" value="" placeholder="Enter your password" autocomplete="current-password" spellcheck="false">
			</div>
		</div>

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
	let $frm = $('#loginForm');
	let $submit = $frm.find('.submit').attr('disabled', null);
	let $password = $frm.find('[name=password]');

	Devblocks.formDisableSubmit($frm);

	// Auto-focus the email input field
	{if $email}
	$password.focus();
	{else}
	$frm.find('input[name=email]').focus().select();
	{/if}

	$password.on('keyup', function(e) {
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

	// SSO
	$frm.find('[data-cerb-button-sso]').on('click', function(e) {
		e.stopPropagation();
		const url = $(this).attr('data-cerb-button-sso');
		if(url) document.location.href = url;
	});
});
</script>
