<form action="{devblocks_url}c=login&a=authenticate{/devblocks_url}" method="post" id="loginForm">
<input type="hidden" name="_csrf_token" value="{$csrf_token}">

<div style="vertical-align:middle;max-width:500px;margin:20px auto 20px auto;padding:5px 20px 20px 20px;border-radius:5px;box-shadow:darkgray 0px 0px 5px;">
	{if !empty($error)}
	<div class="error-box" style="border:0;">
		<h1>{'common.error'|devblocks_translate|capitalize}</h1>
		<p>{Page_Login::getErrorMessage($error)}</p>
	</div>
	{/if}

	{if $sso_services}
	<div>
		<h3>Log in with your identity</h3>

		{foreach from=$sso_services item=sso_service}
		<button type="button" onclick="window.location.href='{devblocks_url}c=sso&uri={$sso_service->uri}{/devblocks_url}';" style="display:block;margin-bottom:5px;width:100%;">{$sso_service->name}</button>
		{/foreach}
	</div>
	
	<fieldset class="black peek" style="margin:15px 0 0 0;padding:0;">
		<legend style="margin-left:auto;margin-right:auto;">or</legend>
	</fieldset>
	{/if}
	
	<div>
		<h3>Log in with your email address and password</h3>
		
		<div>
			<input type="text" name="email" size="45" value="{$email}" placeholder="you@example.com" style="width:100%;line-height:1.5em;height:24px;padding:0px 5px;border-radius:5px;box-sizing:border-box;">
			<input type="password" name="password" size="45" value="" placeholder="Password" autocomplete="off" spellcheck="false" style="width:100%;line-height:1.5em;height:24px;margin-top:10px;padding:0 5px 0 25px;border-radius:5px;box-sizing:border-box;">
		</div>
		
		<div style="margin-top:10px;">
			<button type="submit" style="width:100%;">
				{'common.continue'|devblocks_translate|capitalize}
			</button>
		</div>
		
		<div style="margin-top:5px;text-align:right;">
			<a href="{devblocks_url}c=login&a=recover{/devblocks_url}">forgot your password?</a>
		</div>
	</div>
</div>

</form>

<script type="text/javascript">
$(function() {
	// Auto-focus the email input field
	{if $email}
	$('#loginForm input[name=password]').focus();
	{else}
	$('#loginForm input[name=email]').focus().select();
	{/if}
});
</script>