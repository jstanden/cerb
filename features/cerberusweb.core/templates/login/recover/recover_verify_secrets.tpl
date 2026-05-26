<div class="cerb-login-bg">
<form action="{devblocks_url}c=login&a=recover&step=verify{/devblocks_url}" method="post" id="recoverForm">
<input type="hidden" name="_csrf_token" value="{$csrf_token}">

<div class="cerb-login-card">
	<div class="cerb-login-brand">
		<a href="{devblocks_url}{/devblocks_url}" tabindex="-1"><div id="cerb-logo"></div></a>
	</div>

	<h1 class="cerb-login-h1">Answer your secret questions</h1>
	<p class="cerb-login-sub">To finish recovering your account, correctly answer your previously configured secret questions.</p>

	{if !empty($error)}
	<div class="error-box">
		<h1>{'common.error'|devblocks_translate|capitalize}</h1>
		<p>{Page_Login::getErrorMessage($error)}</p>
	</div>
	{/if}

	{foreach from=$secret_questions item=secret key=idx}
	{if !empty($secret.q)}
	<label class="cerb-login-field">
		<span class="cerb-login-label">{$secret.q}</span>
		<div class="cerb-login-input-wrap">
			<input type="text" name="secrets[{$idx}]" value="" placeholder="{$secret.h}" autocomplete="off" style="padding-left:12px;">
		</div>
	</label>
	{/if}
	{/foreach}

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
	$('#recoverForm').find('input:first').focus().select();
});
</script>
