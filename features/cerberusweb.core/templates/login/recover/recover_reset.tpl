<form action="{devblocks_url}c=login&a=recover&step=reset{/devblocks_url}" method="post" id="recoverForm">
<input type="hidden" name="_csrf_token" value="{$csrf_token}">

<div style="vertical-align:middle;max-width:500px;margin:20px auto 20px auto;padding:5px 20px 20px 20px;border-radius:5px;box-shadow:darkgray 0px 0px 5px;">
	{if !empty($error)}
	<div class="error-box" style="border:0;">
		<h1>{'common.error'|devblocks_translate|capitalize}</h1>
		<p>{Page_Login::getErrorMessage($error)}</p>
	</div>
	{/if}
	
	<div>
		<h3 style="margin-bottom:0;">Choose a new password:</h3>
		
		<div>
			<input type="password" name="password" size="45" value="" placeholder="Something very hard to guess" style="width:100%;line-height:1.5em;height:24px;margin-top:10px;padding:0px 5px 0px 25px;border-radius:5px;box-sizing:border-box;">
			<div>
				(must be at least 8 characters)
			</div>
		</div>
		
		<h3 style="margin-bottom:0;">Verify the new password:</h3>
		
		<div>
			<input type="password" name="password_verify" size="45" value="" placeholder="Type it again" style="width:100%;line-height:1.5em;height:24px;margin-top:10px;padding:0px 5px 0px 25px;border-radius:5px;box-sizing:border-box;">
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
	$('#recoverForm').find('input[name=password]').focus().select();
});
</script>
