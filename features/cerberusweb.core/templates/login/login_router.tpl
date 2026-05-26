<div class="cerb-login-bg">
<form action="{devblocks_url}c=login&a=authenticate{/devblocks_url}" method="post" id="loginForm">
<input type="hidden" name="_csrf_token" value="{$csrf_token}">

<div class="cerb-login-card">
	<div class="cerb-login-brand">
		<a href="{devblocks_url}{/devblocks_url}" tabindex="-1"><div id="cerb-logo"></div></a>
	</div>

	<h1 class="cerb-login-h1">Sign in to your workspace</h1>
	<p class="cerb-login-sub"></b></p>

	{if !empty($error)}
	<div class="error-box">
		<h1>{'common.error'|devblocks_translate|capitalize}</h1>
		<p>{Page_Login::getErrorMessage($error)}</p>
	</div>
	{/if}

	{if $sso_services}
	<div class="cerb-login-sso-grid">
		{foreach from=$sso_services item=sso_service}
		<button type="button" class="cerb-login-sso-btn" data-cerb-button-sso="{devblocks_url}c=sso&uri={$sso_service->uri}{/devblocks_url}">
			<img src="{devblocks_url}c=sso&a=_avatar&uri={$sso_service->uri}{/devblocks_url}?v={$sso_service->updated_at}" alt="">
			<span>{$sso_service->name}</span>
		</button>
		{/foreach}
	</div>

	<div class="cerb-login-or"><span>or sign in with email</span></div>
	{/if}

	<label class="cerb-login-field">
		<span class="cerb-login-label">Email address</span>
		<div class="cerb-login-input-wrap">
			<span class="cerb-login-icon">
				<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-10 6L2 7"/></svg>
			</span>
			<input type="email" name="email" value="{$email}" placeholder="you@company.com" autocomplete="username">
		</div>
	</label>

	<label class="cerb-login-field">
		<div class="cerb-login-field-row">
			<span class="cerb-login-label">Password</span>
			<a class="cerb-login-forgot" href="{devblocks_url}c=login&a=recover{/devblocks_url}" tabindex="-1">Forgot password?</a>
		</div>
		<div class="cerb-login-input-wrap">
			<span class="cerb-login-icon">
				<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
			</span>
			<input type="password" name="password" value="" placeholder="Enter your password" autocomplete="current-password" spellcheck="false">
			<button type="button" class="cerb-login-eye" title="Show password" aria-label="Show password">
				<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
			</button>
		</div>
	</label>

	<button type="button" class="submit cerb-login-submit" disabled>
		<span>{'common.continue'|devblocks_translate|capitalize}</span>
		<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
	</button>
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

	// Show/hide password toggle
	$frm.find('.cerb-login-eye').on('click', function(e) {
		e.stopPropagation();
		const isVisible = 'text' === $password.attr('type');
		$password.attr('type', isVisible ? 'password' : 'text');
		$(this).attr('title', isVisible ? 'Show password' : 'Hide password');
	});

	// SSO
	$frm.find('[data-cerb-button-sso]').on('click', function(e) {
		e.stopPropagation();
		const url = $(this).attr('data-cerb-button-sso');
		if(url) document.location.href = url;
	});
});
</script>
