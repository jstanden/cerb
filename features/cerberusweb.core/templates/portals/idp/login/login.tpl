{include file="devblocks:cerberusweb.core::portals/idp/includes/header.tpl"}

<div class="cerb-portal-wrapper-narrow">
	<article class="cerb-portal-page-login cerb-portal-page--shadow">
		<header>
			<h1>{'header.signon'|devblocks_translate|capitalize}</h1>
		</header>
		
		{if !empty($error)}
		<div class="cerb-portal-form-error">
			<h1>{'common.error'|devblocks_translate|capitalize}</h1>
			<p>{Portal_IdentityProvider::getErrorMessage($error)}</p>
		</div>
		{/if}
		
		<form action="{devblocks_url}c=login&a=authenticate{/devblocks_url}" method="POST" class="cerb-portal-form">
			<p>
				<label>{'common.email'|devblocks_translate|capitalize}</label>
				<input type="text" name="email" value="{$email}" {if !$email}autofocus="autofocus"{/if}>
			</p>
			
			<p>
				<label>{'common.password'|devblocks_translate|capitalize}</label>
				<input type="password" name="password" value="" autocomplete="off" spellcheck="false" {if $email}autofocus="autofocus"{/if}>
			</p>
			
			<button type="submit" class="cerb-button">{'common.continue'|devblocks_translate|capitalize}</button>
			<a href="{devblocks_url}c=login&a=recover{/devblocks_url}">Forgot?</a>
		</form>
	</article>
</div>

{include file="devblocks:cerberusweb.core::portals/idp/includes/footer.tpl"}