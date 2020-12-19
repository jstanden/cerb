{include file="devblocks:cerberusweb.core::portals/idp/includes/header.tpl"}

<div class="cerb-portal-wrapper-narrow">
	<article class="cerb-portal-page-login-oauth-consent cerb-portal-page--shadow">
		{if !empty($error)}
		<div class="cerb-portal-form-error">
			<h1>{'common.error'|devblocks_translate|capitalize}</h1>
			<p>{Portal_IdentityProvider::getErrorMessage($error)}</p>
		</div>
		{/if}
		
		<header>
			<h1>{$oauth_app->name}</h1>
			<h3>This app would like to:</h3>
		</header>
		
		<form action="{devblocks_url}c=login&a=consent{/devblocks_url}" method="post" class="cerb-portal-form">
			<ul>
				{foreach from=$scopes item=scope}
				<li>{$scope.label}</li>
				{/foreach}
			</ul>
		
			<button type="submit" name="accept" class="cerb-button" value="1">
				{'common.accept'|devblocks_translate|capitalize}
			</button>
			
			<button type="submit" name="accept" class="cerb-button" value="0">
				{'common.cancel'|devblocks_translate|capitalize}
			</button>
		</form>
	</article>
</div>

{include file="devblocks:cerberusweb.core::portals/idp/includes/footer.tpl"}