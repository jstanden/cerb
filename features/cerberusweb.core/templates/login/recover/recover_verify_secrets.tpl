<form action="{devblocks_url}c=login&a=recover&step=verify{/devblocks_url}" method="post" id="recoverForm">
<input type="hidden" name="_csrf_token" value="{$csrf_token}">

<div style="vertical-align:middle;max-width:500px;margin:20px auto 20px auto;padding:5px 20px 20px 20px;border-radius:5px;box-shadow:darkgray 0px 0px 5px;">
	<div class="help-box">
		<h1>Answer your secret questions</h1>
		
		<p>
			To finish recovering your account, you must verify your identity by correctly answering your previously configured secret questions.
		</p>
	</div>

	{if !empty($error)}
	<div class="error-box" style="border:0;">
		<h1>{'common.error'|devblocks_translate|capitalize}</h1>
		<p>{Page_Login::getErrorMessage($error)}</p>
	</div>
	{/if}
	
	<div>
		{foreach from=$secret_questions item=secret key=idx}
		{if !empty($secret.q)}
		<h3>{$secret.q}</h3>
		<div>
			<input type="text" name="secrets[{$idx}]" size="45" value="" placeholder="{$secret.h}" style="width:100%;line-height:1.5em;height:24px;padding:0px 5px;border-radius:5px;box-sizing:border-box;" autocomplete="off">
		</div>
		{/if}
		{/foreach}
		
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
	$('#recoverForm').find('input:first').focus().select();
});
</script>
