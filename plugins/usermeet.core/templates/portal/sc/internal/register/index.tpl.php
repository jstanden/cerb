<div style="border-bottom:1px solid rgb(180,180,180);margin-bottom:10px;">
<h1 style="margin-bottom:0px;">Register</h1>
</div>


{if !empty($register_error)}
<div class="error">{$register_error}</div>
{/if}

<form action="{devblocks_url}{/devblocks_url}" method="post" name="loginForm">
<input type="hidden" name="a" value="doRegister">

<b>E-mail address:</b><br>
<input type="text" name="email" size="64"><br>
<i>(if you've contacted us in the past, please enter the same e-mail address)</i><br>
<br>

<button type="submit"><img src="{devblocks_url}c=resource&p=usermeet.core&f=images/check.gif{/devblocks_url}" align="top"> Send Confirmation E-mail</button><br>
<br>

<a href="{devblocks_url}c=register&a=confirm{/devblocks_url}">Already have a confirmation code?</a><br>

</form> 
