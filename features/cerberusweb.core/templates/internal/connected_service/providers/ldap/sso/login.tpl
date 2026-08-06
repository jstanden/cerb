<div class="cerb-login-bg">
<form action="{devblocks_url}c=sso&service={$service->uri}&uri=authenticate{/devblocks_url}" method="post" id="ssoLdapLoginForm">

<div class="cerb-login-card cerb-ui-panel cerb-ui-panel--filled">
	<div class="cerb-login-brand">
		<a href="{devblocks_url}{/devblocks_url}" tabindex="-1"><div id="cerb-logo"></div></a>
	</div>

	<div class="cerb-ui-header">
		<div>
			<div class="cerb-ui-header--title-sm">Log in with your email address and password</div>
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
			<label class="cerb-ui-form--label">Email address</label>
			<div class="cerb-ui-form--control">
				<span class="cerb-ui-form--control-icon cerb-icons cerb-icon-mail"></span>
				<input type="email" name="email" value="{$email}" placeholder="you@example.com" autocomplete="username">
			</div>
		</div>

		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">Password</label>
			<div class="cerb-ui-form--control">
				<span class="cerb-ui-form--control-icon cerb-icons cerb-icon-lock"></span>
				<input type="password" name="password" value="" placeholder="Enter your password" autocomplete="current-password" spellcheck="false">
			</div>
		</div>

		<button type="submit" class="submit cerb-login-submit cerb-ui-button">
			<span>{'common.continue'|devblocks_translate|capitalize}</span>
			<span class="cerb-icons cerb-icon-right-arrow"></span>
		</button>
	</div>
</div>

</form>
</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	let $frm = $('#ssoLdapLoginForm');

	// Auto-focus the email input field
	{if $email}
	$frm.find('input[name=password]').focus();
	{else}
	$frm.find('input[name=email]').focus().select();
	{/if}
});
</script>
