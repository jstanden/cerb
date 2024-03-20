{if !empty($error)}
<div class="error">{$error}</div>
{/if}

<form action="{devblocks_url}c=account&a=delete{/devblocks_url}" method="post" id="frmAccountDelete">
<input type="hidden" name="a" value="doDelete">
<input type="hidden" name="_csrf_token" value="{$session->csrf_token}">

<fieldset>
	<legend>Are you sure you want to delete your account?</legend>
	
	<b>To confirm, please enter the text from the image below:</b><br>
	<input type="text" id="captcha" name="captcha" value="" size="10" autocomplete="off"><br>
	<div style="padding-top:10px;padding-left:10px;"><img src="{devblocks_url}c=captcha{/devblocks_url}"></div>
	<br>
	
	<button type="submit"><span class="glyphicons glyphicons-circle-remove" style="color:rgb(200,0,0);"></span> Delete Account</button><br>
</fieldset>

</form>
</div>
