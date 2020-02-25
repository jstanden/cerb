<form action="{devblocks_url}c=login&a=mfa{/devblocks_url}" method="post" id="loginMfaForm">
<input type="hidden" name="_csrf_token" value="{$csrf_token}">

<div style="vertical-align:middle;max-width:500px;margin:20px auto 20px auto;padding:5px 20px 20px 20px;border-radius:5px;box-shadow:darkgray 0px 0px 5px;">
	{if !empty($error)}
	<div class="error-box" style="border:0;">
		<h1>{'common.error'|devblocks_translate|capitalize}</h1>
		<p>{Page_Login::getErrorMessage($error)}</p>
	</div>
	{/if}
	
	<div>
		<h3>Enter the security code from your device</h3>
		
		<div>
			<input type="text" name="otp" size="45" value="" placeholder="e.g. 123456" style="width:100%;line-height:1.5em;height:24px;padding:0px 5px;border-radius:5px;box-sizing:border-box;">
		</div>
		
		{if $setting_mfa_can_remember && $setting_mfa_remember_days}
		<div style="padding:5px 0 0 5px;">
			<label>
				<input type="checkbox" name="remember_device" value="1"> 
				Remember this device for {$setting_mfa_remember_days} days
			</label>
		</div>
		{/if}

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
	$('#loginMfaForm input[name=otp]').focus();
});
</script>
