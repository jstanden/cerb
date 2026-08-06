<div class="cerb-login-bg">
<form action="{devblocks_url}c=login&a=recover&step=code{/devblocks_url}" method="post" id="recoverForm">
<input type="hidden" name="_csrf_token" value="{$csrf_token}">

<div class="cerb-login-card cerb-ui-panel cerb-ui-panel--filled">
	<div class="cerb-login-brand">
		<a href="{devblocks_url}{/devblocks_url}" tabindex="-1"><div id="cerb-logo"></div></a>
	</div>

	<div class="cerb-ui-header">
		<div>
			<div class="cerb-ui-header--title-sm">Check your email</div>
			<div class="cerb-ui-header--subtitle">Enter the recovery code we sent to <b>{$email}</b>.</div>
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
			<label class="cerb-ui-form--label">Recovery code</label>
			<div class="cerb-ui-form--control">
				<span class="cerb-ui-form--control-icon cerb-icons cerb-icon-lock"></span>
				<input type="text" name="code" value="" placeholder="e.g. 12345678" autocomplete="one-time-code">
			</div>
		</div>

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
	$('#recoverForm').find('input[name=code]').focus().select();
});
</script>
