{if !empty($error)}
<div class="error">{$error}</div>
{/if}

<form action="{devblocks_url}c=account&a=delete{/devblocks_url}" method="post" id="frmAccountDelete">
<input type="hidden" name="a" value="doDelete">

<fieldset>
	<legend>Are you sure you want to delete your account?</legend>
	
	<b>To confirm, please enter the text from the image below:</b><br>
	<input type="text" id="captcha" name="captcha" value="" size="10" autocomplete="off"><br>
	<div style="padding-top:10px;padding-left:10px;"><img src="{devblocks_url}c=captcha{/devblocks_url}?color=0,0,0&bgcolor=235,235,235"></div>
	<br>
	
	<button type="submit"><img src="{devblocks_url}c=resource&p=cerberusweb.support_center&f=images/forbidden.png{/devblocks_url}" align="top"> Delete Account</button><br>
</fieldset>

</form>
</div>
