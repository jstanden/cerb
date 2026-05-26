<div class="cerb-login-bg">
<form action="{devblocks_url}c=login&a=consent{/devblocks_url}" method="post" id="loginConsentForm">
<input type="hidden" name="_csrf_token" value="{$csrf_token}">

<div class="cerb-login-card">
	<div class="cerb-login-brand">
		<a href="{devblocks_url}{/devblocks_url}" tabindex="-1"><div id="cerb-logo"></div></a>
	</div>

	<h1 class="cerb-login-h1">{$oauth_app->name}</h1>
	<p class="cerb-login-sub">This app would like to:</p>

	{if !empty($error)}
	<div class="error-box">
		<h1>{'common.error'|devblocks_translate|capitalize}</h1>
		<p>{Page_Login::getErrorMessage($error)}</p>
	</div>
	{/if}

	<ul style="margin:0 0 24px 0;padding-left:20px;color:var(--cerb-color-text);">
		{foreach from=$scopes item=scope}
		<li>{$scope.label}</li>
		{/foreach}
	</ul>

	<div style="display:flex;gap:10px;">
		<button type="submit" name="accept" value="0" class="cerb-login-sso-btn" style="flex:1;">
			{'common.cancel'|devblocks_translate|capitalize}
		</button>
		<button type="submit" name="accept" value="1" class="cerb-login-submit" style="flex:1;width:auto;">
			<span>{'common.accept'|devblocks_translate|capitalize}</span>
		</button>
	</div>
</div>
</form>
</div>
