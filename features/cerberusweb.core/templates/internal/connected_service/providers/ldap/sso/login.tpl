<form action="{devblocks_url}c=sso&service={$service->uri}&uri=authenticate{/devblocks_url}" method="post" id="ssoLdapLoginForm">

<div style="text-align:center;">
	<a href="{devblocks_url}{/devblocks_url}"><div id="cerb-logo" style="background-position:center;"></div></a>
</div>

<div style="vertical-align:middle;max-width:500px;margin:20px auto 20px auto;padding:5px 20px 20px 20px;border-radius:5px;box-shadow:darkgray 0px 0px 5px;">
	{if !empty($error)}
	<div class="error-box" style="border:0;">
		<h1>{'common.error'|devblocks_translate|capitalize}</h1>
		<p>{Page_Login::getErrorMessage($error)}</p>
	</div>
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
	</div>
</div>

</form>

<script type="text/javascript">
$(function() {
	// Auto-focus the email input field
	{if $email}
	$('#ssoLdapLoginForm input[name=password]').focus();
	{else}
	$('#ssoLdapLoginForm input[name=email]').focus().select();
	{/if}
});
</script>