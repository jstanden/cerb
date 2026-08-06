<div class="cerb-login-bg">
<form action="{devblocks_url}c=login&a=recover&step=verify{/devblocks_url}" method="post" id="recoverForm">
<input type="hidden" name="_csrf_token" value="{$csrf_token}">

<div class="cerb-login-card cerb-ui-panel cerb-ui-panel--filled">
	<div class="cerb-login-brand">
		<a href="{devblocks_url}{/devblocks_url}" tabindex="-1"><div id="cerb-logo"></div></a>
	</div>

	<div class="cerb-ui-header">
		<div>
			<div class="cerb-ui-header--title-sm">Answer your secret questions</div>
			<div class="cerb-ui-header--subtitle">To finish recovering your account, correctly answer your previously configured secret questions.</div>
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
		{foreach from=$secret_questions item=secret key=idx}
		{if !empty($secret.q)}
		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">{$secret.q}</label>
			<input type="text" name="secrets[{$idx}]" value="" placeholder="{$secret.h}" autocomplete="off">
		</div>
		{/if}
		{/foreach}

		<button type="submit" class="cerb-login-submit cerb-ui-button">
			<span>{'common.continue'|devblocks_translate|capitalize}</span>
			<span class="cerb-icons cerb-icon-right-arrow"></span>
		</button>
	</div>

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
