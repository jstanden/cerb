<div style="border-bottom:1px solid rgb(180,180,180);margin-bottom:10px;">
<h1 style="margin-bottom:0px;">{$translate->_('portal.sc.public.register.confirm_registration')}</h1>
</div>

{if !empty($register_error)}
<div class="error">{$register_error}</div>
{/if}

<form action="{devblocks_url}c=register{/devblocks_url}" method="post" name="loginForm">
<input type="hidden" name="a" value="doRegisterConfirm">

<b>{$translate->_('portal.sc.public.register.email_address')}</b><br>
<input type="text" name="email" size="64" value="{$register_email}"><br>
<br>

<b>{$translate->_('portal.sc.public.register.enter_confirmation')}</b> {$translate->_('portal.sc.public.register.enter_confirmation.hint')}<br>
<input type="text" name="code" size="16" value="{$register_code}"><br>
<br>

<b>{$translate->_('portal.sc.public.register.password_choose')}</b><br>
<input type="password" name="pass" size="16"><br>
<br>

<b>{$translate->_('portal.sc.public.register.password_verify')}</b><br>
<input type="password" name="pass_confirm" size="16"><br>
<br>

<button type="submit"><img src="{devblocks_url}c=resource&p=usermeet.core&f=images/check.gif{/devblocks_url}" align="top"> {$translate->_('portal.sc.public.register.create_account')}</button>

</form> 
