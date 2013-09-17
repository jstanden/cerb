<form action="{devblocks_url}c=login&a=recover{/devblocks_url}" method="post" id="recoverForm">
<input type="hidden" name="email" value="{$email}">

<div class="help-box">
	<h1>Recover account</h1>
	
	You are in the process of recovering your account's login information.  In this step you will need to confirm your identity based on your previously established settings.
</div>

<fieldset>
	<legend>Type the confirmation code that was just sent to {$email}</legend>
	
	<input type="text" name="confirm_code" value="{$code}" maxlength="8" size="10" autocomplete="off">
</fieldset>

{if !empty($secret_questions) && is_array($secret_questions) && count($secret_questions) > 0}
{foreach from=$secret_questions item=secret key=idx}
{if !empty($secret.q)}
<fieldset>
	<legend>{$secret.q}</legend>
	<input type="text" name="sq[{$idx}]" placeholder="{$secret.h}" value="" size="96" autocomplete="off">
</fieldset>
{/if}
{/foreach}
{/if}

<button type="submit" name="do_submit" value="1"><span class="cerb-sprite2 sprite-tick-circle"></span> {'common.continue'|devblocks_translate|capitalize}</button>

</form>

<script type="text/javascript">
$(function() {
	$('#recoverForm input[name=email]').focus().select();
});
</script>