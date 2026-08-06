<div class="cerb-login-bg">
<form action="{devblocks_url}c=login&a=consent{/devblocks_url}" method="post" id="loginConsentForm">
<input type="hidden" name="_csrf_token" value="{$csrf_token}">

<div class="cerb-login-card cerb-ui-panel cerb-ui-panel--filled">
	<div class="cerb-login-brand">
		<a href="{devblocks_url}{/devblocks_url}" tabindex="-1"><div id="cerb-logo"></div></a>
	</div>

	<div class="cerb-ui-header">
		<div>
			<div class="cerb-ui-header--title-sm">{$oauth_app->name}</div>
			<div class="cerb-ui-header--subtitle">This app would like to:</div>
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

	<ul style="margin:0 0 24px 0;padding-left:20px;color:var(--cerb-color-text);">
		{foreach from=$scopes item=scope}
		<li>{$scope.label}</li>
		{/foreach}
	</ul>

	<div style="display:flex;gap:10px;">
		<button type="submit" name="accept" value="0" class="cerb-login-submit cerb-ui-button cerb-ui-button--subtle" style="flex:1;">
			<span>{'common.cancel'|devblocks_translate|capitalize}</span>
		</button>
		<button type="submit" name="accept" value="1" class="cerb-login-submit cerb-ui-button" style="flex:1;">
			<span>{'common.accept'|devblocks_translate|capitalize}</span>
		</button>
	</div>
</div>
</form>
</div>
