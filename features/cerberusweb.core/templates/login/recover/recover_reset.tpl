<div class="cerb-login-bg">
<form action="{devblocks_url}c=login&a=recover&step=reset{/devblocks_url}" method="post" id="recoverForm">
<input type="hidden" name="_csrf_token" value="{$csrf_token}">

<div class="cerb-login-card">
	<div class="cerb-login-brand">
		<a href="{devblocks_url}{/devblocks_url}" tabindex="-1"><div id="cerb-logo"></div></a>
	</div>

	<h1 class="cerb-login-h1">Choose a new password</h1>
	<p class="cerb-login-sub">Must be at least 8 characters.</p>

	{if !empty($error)}
	<div class="error-box">
		<h1>{'common.error'|devblocks_translate|capitalize}</h1>
		<p>{Page_Login::getErrorMessage($error)}</p>
	</div>
	{/if}

	<label class="cerb-login-field">
		<span class="cerb-login-label">New password</span>
		<div class="cerb-login-input-wrap">
			<span class="cerb-login-icon">
				<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
			</span>
			<input type="password" name="password" value="" placeholder="Something very hard to guess" autocomplete="new-password" spellcheck="false">
		</div>
	</label>

	<label class="cerb-login-field">
		<span class="cerb-login-label">Verify the new password</span>
		<div class="cerb-login-input-wrap">
			<span class="cerb-login-icon">
				<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
			</span>
			<input type="password" name="password_verify" value="" placeholder="Type it again" autocomplete="new-password" spellcheck="false">
		</div>
	</label>

	<button type="submit" class="cerb-login-submit">
		<span>{'common.continue'|devblocks_translate|capitalize}</span>
		<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
	</button>

	<div class="cerb-login-foot">
		<a href="{devblocks_url}c=login{/devblocks_url}">&larr; Back to sign in</a>
	</div>
</div>
</form>
</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	$('#recoverForm').find('input[name=password]').focus().select();
});
</script>
