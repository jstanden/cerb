{include file="devblocks:cerberusweb.core::portals/idp/includes/header.tpl"}

<div class="cerb-portal-wrapper-narrow">
	<article class="cerb-portal-page-login-mfa cerb-portal-page--shadow">
		{if !empty($error)}
		<div class="cerb-portal-form-error">
			<h1>{'common.error'|devblocks_translate|capitalize}</h1>
			<p>{Portal_IdentityProvider::getErrorMessage($error)}</p>
		</div>
		{/if}
		
		<header>
			<h1>Enter the security code from your device</h1>
		</header>
		
		<form action="{devblocks_url}c=login&a=mfa{/devblocks_url}" method="post" class="cerb-portal-form">
			<input type="text" name="otp" value="" autofocus="autofocus" spellcheck="false" autocomplete="off">
			
			{if $setting_mfa_can_remember && $setting_mfa_remember_days}
			<div>
				<label>
					<input type="checkbox" name="remember_device" value="1"> 
					Remember this device for {$setting_mfa_remember_days} days
				</label>
			</div>
			{/if}
	
			<button type="submit" class="cerb-button">{'common.continue'|devblocks_translate|capitalize}</button>
		</form>
	</article>
</div>

{include file="devblocks:cerberusweb.core::portals/idp/includes/footer.tpl"}