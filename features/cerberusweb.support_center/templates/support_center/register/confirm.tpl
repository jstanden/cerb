<div class="header"><h1>{$translate->_('portal.sc.public.register.confirm_registration')}</h1></div>

{if !empty($register_error)}
<div class="error">{$register_error}</div>
{/if}

<form action="{devblocks_url}c=register{/devblocks_url}" method="post" id="registerForm">
<input type="hidden" name="a" value="doRegisterConfirm">

<b>{$translate->_('portal.sc.public.register.email_address')}</b><br>
<input type="text" name="email" size="64" value="{$register_email}" class="required email"><br>
<br>

<b>{$translate->_('portal.sc.public.register.enter_confirmation')}</b> {$translate->_('portal.sc.public.register.enter_confirmation.hint')}<br>
<input type="text" name="code" size="16" maxlength="8" value="{$register_code}" class="required"><br>
<br>

<b>{$translate->_('portal.sc.public.register.password_choose')}</b><br>
<input type="password" id="pass" name="pass" size="16" class="required"><br>
<br>

<b>{$translate->_('portal.sc.public.register.password_verify')}</b><br>
<input type="password" name="pass_confirm" class="required" size="16"><br>
<br>

<button type="submit"><img src="{devblocks_url}c=resource&p=cerberusweb.support_center&f=images/check.gif{/devblocks_url}" align="top"> {$translate->_('portal.sc.public.register.create_account')}</button>

</form> 

{literal}
<script type="text/javascript">
  $(document).ready(function(){
    $("#registerForm").validate({
		rules: {
			pass_confirm: {
				equalTo: "#pass"
			}
		},
		messages: {
			pass_confirm: {
				equalTo: "The passwords don't match."
			}
		}		
	});
  });
</script>
{/literal}