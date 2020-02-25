<form action="{devblocks_url}c=login&a=consent{/devblocks_url}" method="post" id="loginConsentForm">
<input type="hidden" name="_csrf_token" value="{$csrf_token}">

<div style="vertical-align:middle;max-width:500px;margin:20px auto 20px auto;padding:5px 20px 20px 20px;border-radius:5px;box-shadow:darkgray 0px 0px 5px;">
	{if !empty($error)}
	<div class="error-box" style="border:0;">
		<h1>{'common.error'|devblocks_translate|capitalize}</h1>
		<p>{Page_Login::getErrorMessage($error)}</p>
	</div>
	{/if}
	
	<div>
		<h2 style="margin:10px 0 0 0;color:black;">{$oauth_app->name}</h2>
		
		<h3>This app would like to:</h3>
		
		<ul>
			{foreach from=$scopes item=scope}
			<li>{$scope.label}</li>
			{/foreach}
		</ul>
		
		<div style="margin-top:10px;text-align:right;">
			<button type="submit" name="accept" value="0">
				{'common.cancel'|devblocks_translate|capitalize}
			</button>
			<button type="submit" name="accept" value="1">
				{'common.accept'|devblocks_translate|capitalize}
			</button>
		</div>
	</div>
</div>
</form>

<script type="text/javascript">
$(function() {
	//$('#loginConsentForm button[name=submit]').first().focus();
});
</script>
