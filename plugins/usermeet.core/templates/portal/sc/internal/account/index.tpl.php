<div style="border-bottom:1px solid rgb(180,180,180);margin-bottom:10px;">
<h1 style="margin-bottom:0px;">My Account</h1>
</div>

{if !empty($account_error)}
<div class="error">{$account_error}</div>
{elseif !empty($account_success)}
<div class="success">Account settings saved!</div>
{/if}

<form action="{devblocks_url}{/devblocks_url}" method="post" name="">
<input type="hidden" name="a" value="saveAccount">

<b>E-mail:</b><br>
{$address->email}<br>
<br>

<b>First Name:</b><br>
<input type="text" name="first_name" size="35" value="{$address->first_name}"><br>
<br>

<b>Last Name:</b><br>
<input type="text" name="last_name" size="35" value="{$address->last_name}"><br>
<br>

<b>Change Password:</b><br>
<input type="password" name="change_password" size="35" value=""><br>
<br>

<b>Change Password (verify):</b><br>
<input type="password" name="change_password2" size="35" value=""><br>
<br>

<button type="submit"><img src="{devblocks_url}c=resource&p=usermeet.core&f=images/check.gif{/devblocks_url}" align="top"> Save Changes</button><br>
</form>