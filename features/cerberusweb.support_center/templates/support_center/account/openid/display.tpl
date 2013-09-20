{if !empty($account_error)}
<div class="error">{$account_error}</div>
{elseif !empty($account_success)}
<div class="success">{'portal.sc.public.my_account.settings_saved'|devblocks_translate}</div>
{/if}

<form action="{devblocks_url}c=account{/devblocks_url}" method="post" id="frmMyOpenID">
<input type="hidden" name="a" value="doOpenIdUpdate">
<input type="hidden" name="hash_key" value="{$openid->hash_key}">

<fieldset>
	<legend>OpenID</legend>
	
	<b>Claimed ID:</b><br>
	{$openid->openid_claimed_id}<br>
</fieldset>

<button name="action" type="submit" value=""><img src="{devblocks_url}c=resource&p=cerberusweb.support_center&f=images/check.gif{/devblocks_url}" align="top"> {'common.save_changes'|devblocks_translate}</button>
{if $active_contact->id == $openid->contact_person_id}
<button name="action" type="submit" value="remove"><img src="{devblocks_url}c=resource&p=cerberusweb.support_center&f=images/forbidden.png{/devblocks_url}" align="top"> Remove from account</button><br>
{/if}
</form>
