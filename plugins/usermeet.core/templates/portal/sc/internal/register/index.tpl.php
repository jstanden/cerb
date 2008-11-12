<div style="border-bottom:1px solid rgb(180,180,180);margin-bottom:10px;">
<h1 style="margin-bottom:0px;">{$translate->_('portal.sc.public.register')}</h1>
</div>


{if !empty($register_error)}
<div class="error">{$register_error}</div>
{/if}

<form action="{devblocks_url}{/devblocks_url}" method="post" name="loginForm">
<input type="hidden" name="a" value="doRegister">

<b>{$translate->_('portal.sc.public.register.email_address')}</b><br>
<input type="text" name="email" size="64"><br>
<i>{$translate->_('portal.sc.public.register.contacted_before')}</i><br>
<br>

<button type="submit"><img src="{devblocks_url}c=resource&p=usermeet.core&f=images/check.gif{/devblocks_url}" align="top"> {$translate->_('portal.sc.public.register.send_confirmation')}</button><br>
<br>

<a href="{devblocks_url}c=register&a=confirm{/devblocks_url}">{$translate->_('portal.sc.public.register.already_have_confirmation')}</a><br>

</form> 
