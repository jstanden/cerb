{if !empty($error)}
<div class="error-box">
	<h1>Error</h1>
	<p>{$error}</p>
</div>
{/if}

<form action="{devblocks_url}c=login&a=router{/devblocks_url}" method="post" id="loginForm">
<fieldset>
	<legend>{$translate->_('header.signon')|capitalize}</legend>
	
	<b>{'common.email'|devblocks_translate|capitalize}:</b>
	<br>
	
	<input type="text" name="email" size="45" value="{$email}">
	<br>
	
	<p>
		<button type="submit"><span class="cerb-sprite2 sprite-tick-circle"></span> {$translate->_('common.continue')|capitalize}</button>
	</p>
</fieldset>
</form>

<script type="text/javascript">
$(function() {
	$('#loginForm input[name=email]').focus().select();
});
</script>