<div class="header"><h1>{$translate->_('portal.sc.public.register.password_forgot')}</h1></div>

{if !empty($register_error)}
<div class="error">{$register_error}</div>
{/if}

<form action="{devblocks_url}c=register{/devblocks_url}" method="post">
<input type="hidden" name="a" value="doForgot">

<b>{$translate->_('portal.sc.public.register.email_address')}</b><br>
<input type="text" name="email" size="64"><br>
<br>

<button type="submit"><img src="{devblocks_url}c=resource&p=cerberusweb.support_center&f=images/check.gif{/devblocks_url}" align="top"> {$translate->_('portal.sc.public.register.send_confirmation')}</button><br>
<br>

<a href="{devblocks_url}c=register&a=forgot2{/devblocks_url}">{$translate->_('portal.sc.public.register.already_have_confirmation')}</a><br>

</form> 
