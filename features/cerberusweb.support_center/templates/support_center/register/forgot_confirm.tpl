<div class="header"><h1>Forgot Password</h1></div>

{if !empty($register_error)}
<div class="error">{$register_error}</div>
{/if}

<form action="{devblocks_url}c=register{/devblocks_url}" method="post">
<input type="hidden" name="a" value="doForgotConfirm">

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

<button type="submit"><img src="{devblocks_url}c=resource&p=cerberusweb.support_center&f=images/check.gif{/devblocks_url}" align="top"> {$translate->_('portal.sc.public.register.password_reset')}</button>

</form> 
