<form action="{devblocks_url}c=login&a=recover{/devblocks_url}" method="post" id="recoverForm">

<fieldset>
	<legend>Recover your account</legend>
	
	{'common.email'|devblocks_translate|capitalize}:
	<br>
	
	{if !empty($email)}
		<b>{$email}</b>
		<input type="hidden" name="email" value="{$email}">
		<br>
	{else}
		<input type="text" name="email" size="45" value="{$email}">
		<br>
	{/if}
	
	<p>
		<button type="submit" name="do_submit" value="1"><span class="cerb-sprite2 sprite-tick-circle"></span> {$translate->_('common.continue')|capitalize}</button>
	</p>
</fieldset>
</form>

<script type="text/javascript">
$(function() {
	$('#recoverForm input[name=email]').focus().select();
});
</script>