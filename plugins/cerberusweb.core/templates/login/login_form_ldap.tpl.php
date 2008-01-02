<div class="block">
<form action="{devblocks_url}{/devblocks_url}" method="post" id="loginFormLDAP">
<h2>{$translate->_('header.signon')|capitalize}</h2>
<input type="hidden" name="c" value="login">
<input type="hidden" name="a" value="authenticateLDAP">
<input type="hidden" name="original_path" value="{$original_path}">
<input type="hidden" name="original_query" value="{$original_query}">
<table cellpadding="0" cellspacing="2">
<tr>
	<td align="right" valign="middle">LDAP Server:</td>
	<td><input type="text" name="server" id="loginFormLDAP_server" value="{$server}"></td>
</tr>
<tr>
	<td align="right" valign="middle">LDAP Port:</td>
	<td><input type="text" name="port" id="loginFormLDAP_port" value="{$port}"></td>
</tr>
<tr>
	<td align="right" valign="middle">LDAP Login (DN):</td>
	<td><input type="text" name="dn" id="loginFormLDAP_dn" value="{$default_dn}"></td>
</tr>
<tr>
	<td align="right" valign="middle">Password:</td>
	<td nowrap="nowrap">
		<input type="password" name="password" id="loginFormLDAP_password"> 
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
	var f = new LiveValidation('loginFormLDAP_dn');
	f.add( Validate.Presence );
	
	var f = new LiveValidation('loginFormLDAP_server');
	f.add( Validate.Presence );
	
	var f = new LiveValidation('loginFormLDAP_port');
	f.add( Validate.Presence );
	
	var f = new LiveValidation('loginFormLDAP_password');
	f.add( Validate.Presence );
});
{/literal}	
</script>