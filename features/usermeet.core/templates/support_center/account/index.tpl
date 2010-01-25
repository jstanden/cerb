<div id="account">
<div class="header"><h1>{$translate->_('portal.sc.public.my_account')}</h1></div>

{if !empty($account_error)}
<div class="error">{$account_error}</div>
{elseif !empty($account_success)}
<div class="success">{$translate->_('portal.sc.public.my_account.settings_saved')}</div>
{/if}

<form action="{devblocks_url}c=account{/devblocks_url}" method="post" id="myAccountForm">
<input type="hidden" name="a" value="saveAccount">

<b>{$translate->_('common.email')}:</b><br>
{$address->email}<br>
<br>

<b>{$translate->_('contact_person.first_name')|capitalize}:</b><br>
<input type="text" name="first_name" size="35" value="{$address->first_name}"><br>
<br>

<b>{$translate->_('contact_person.last_name')|capitalize}:</b><br>
<input type="text" name="last_name" size="35" value="{$address->last_name}"><br>
<br>

{if !empty($login_handler) && 0==strcasecmp($login_handler->manifest->id,'sc.login.auth.default')}
<b>{$translate->_('portal.sc.public.my_account.change_password')}</b><br>
<input type="password" id="change_password" name="change_password" size="35" value=""><br>
<br>

<b>{$translate->_('portal.sc.public.my_account.change_password_verify')}</b><br>
<input type="password" name="change_password2" size="35" value=""><br>
<br>
{/if}

<button type="submit"><img src="{devblocks_url}c=resource&p=usermeet.core&f=images/check.gif{/devblocks_url}" align="top"> {$translate->_('common.save_changes')}</button><br>
</form>
</div>

{if !empty($login_handler) && 0==strcasecmp($login_handler->manifest->id,'sc.login.auth.default')}
{literal}
<script language="JavaScript1.2" type="text/javascript">
  $(document).ready(function(){
    $("#myAccountForm").validate({
		rules: {
			change_password2: {
				equalTo: "#change_password"
			}
		},
		messages: {
			change_password2: {
				equalTo: "The passwords don't match."
			}
		}		
	});
  });
</script>
{/literal}
{/if}