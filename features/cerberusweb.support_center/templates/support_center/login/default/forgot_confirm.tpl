{if !empty($error)}
<div class="error">{$error}</div>
{/if}

<form action="{devblocks_url}c=login{/devblocks_url}" method="post">
<input type="hidden" name="a" value="recoverAccount">

<fieldset>
	<legend>Recover my account</legend>
	
	<b>Enter your email address:</b><br>
	<input type="text" name="email" size="64" value="{$email}"><br>
	<br>
	
	<b>Enter the confirmation code sent to your email address:</b><br>
	<input type="text" name="confirm" size="10" maxlength="8"><br>
	<br>
	
	<button type="submit"><img src="{devblocks_url}c=resource&p=cerberusweb.support_center&f=images/check.gif{/devblocks_url}" align="top"> {$translate->_('common.save_changes')|capitalize}</button><br>
	<br>
	
	<a href="{devblocks_url}c=login{/devblocks_url}">{$translate->_('common.cancel')|capitalize}</a><br>
</fieldset>
</form>
