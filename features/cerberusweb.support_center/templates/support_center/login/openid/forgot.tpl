{if !empty($error)}
<div class="error">{$error}</div>
{/if}

<form action="{devblocks_url}c=login{/devblocks_url}" method="post">
<input type="hidden" name="a" value="doRecover">

<fieldset>
	<legend>Recover my account</legend>
	
	<b>Enter any email address linked to your account:</b><br>
	<input type="text" name="email" size="64"><br>
	<br>
	
	<button type="submit"><img src="{devblocks_url}c=resource&p=cerberusweb.support_center&f=images/check.gif{/devblocks_url}" align="top"> {'portal.sc.public.register.send_confirmation'|devblocks_translate}</button><br> 
</fieldset>

<a href="{devblocks_url}c=login&a=forgot&o=confirm{/devblocks_url}">Already have a confirmation code?</a><br>
<a href="{devblocks_url}c=login{/devblocks_url}">{'common.cancel'|devblocks_translate|capitalize}</a><br>

</form> 
