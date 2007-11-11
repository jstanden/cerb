<h1>Forgot Password Confirmation</h1>

{if !empty($register_error)}
<div class="error">{$register_error}</div>
{/if}

<form action="{devblocks_url}{/devblocks_url}" method="post">
<input type="hidden" name="a" value="doForgotConfirm">

<b>E-mail address:</b><br>
<input type="text" name="email" size="64" value="{$register_email}"><br>
<br>

<b>Enter your confirmation code:</b> (this was sent to your e-mail address)<br>
<input type="text" name="code" size="16" value="{$register_code}"><br>
<br>

<b>Choose a password:</b><br>
<input type="password" name="pass" size="16"><br>
<br>

<b>Verify your password:</b><br>
<input type="password" name="pass_confirm" size="16"><br>
<br>

<button type="submit"><img src="{devblocks_url}c=resource&p=usermeet.core&f=images/check.gif{/devblocks_url}" align="top"> Reset Password</button>

</form> 
