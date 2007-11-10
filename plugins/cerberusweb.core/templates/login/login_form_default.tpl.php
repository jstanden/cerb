<div class="block">
<form action="{devblocks_url}{/devblocks_url}" method="post" id="loginForm">
<h2>{$translate->_('header.signon')|capitalize}</h2>
<input type="hidden" name="c" value="login">
<input type="hidden" name="a" value="authenticate">
<input type="hidden" name="original_path" value="{$original_path}">
<input type="hidden" name="original_query" value="{$original_query}">
<table cellpadding="0" cellspacing="2">
<tr>
	<td align="right" valign="middle">E-mail:</td>
	<td><input type="text" name="email" id="loginForm_email"></td>
</tr>
<tr>
	<td align="right" valign="middle">Password:</td>
	<td nowrap="nowrap">
		<input type="password" name="password" id="loginForm_password"> 
		[ <a href="{devblocks_url}c=login&a=forgot{/devblocks_url}">recover password</a> ]
	</td>
</tr>
</table>
<input type="submit" value="{$translate->_('header.signon')|capitalize}">
</form>
</div>

<script type="text/javascript">
{literal}
YAHOO.util.Event.addListener(window,'load',function(e) {
	var f = new LiveValidation('loginForm_email');
	f.add( Validate.Presence );
	f.add( Validate.Email );
	
	var f = new LiveValidation('loginForm_password');
	f.add( Validate.Presence );
});
{/literal}	
</script>