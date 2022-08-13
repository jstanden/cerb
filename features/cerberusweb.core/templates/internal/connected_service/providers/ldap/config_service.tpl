<fieldset class="peek black">
	<b>Host:</b><br>
	<input type="text" name="params[host]" value="{$params.host}" size="50" spellcheck="false" placeholder="ldap.example.com"><br>
	<br>
	
	<b>Port:</b><br>
	<input type="text" name="params[port]" value="{$params.port}" size="6" spellcheck="false" placeholder="389"><br>
	<br>
	
	<b>Encryption:</b><br>
	<select name="params[encryption]">
		<option value="" {if !$params.encryption}selected="selected"{/if}>({'common.automatic'|devblocks_translate|lower})</option>
		<option value="tls" {if 'tls' == $params.encryption}selected="selected"{/if}>TLS</option>
		<option value="ssl" {if 'ssl' == $params.encryption}selected="selected"{/if}>SSL</option>
		<option value="disabled" {if 'disabled' == $params.encryption}selected="selected"{/if}>{'common.disabled'|devblocks_translate|capitalize}</option>
	</select>
	<br>
	<br>
	
	<b>Bind DN:</b><br>
	<input type="text" name="params[bind_dn]" value="{$params.bind_dn}" size="45" spellcheck="false" placeholder="cn=read-only-admin,dc=example,dc=com"><br>
	<br>
	
	<b>Bind Password:</b><br>
	<input type="password" name="params[bind_password]" value="{$params.bind_password}" size="45" autocomplete="off" spellcheck="false"><br>
</fieldset>

<fieldset class="peek black">
	<b>Search context:</b><br>
	<input type="text" name="params[context_search]" value="{$params.context_search}" size="64"><br>
	<i>example: OU=customers,DC=example,DC=com</i><br>
	<br>
	
	<b>Email field:</b><br>
	<input type="text" name="params[field_email]" value="{$params.field_email}" size="64"><br>
	<i>example: mail</i><br>
	<br>
	
	<b>First name (given name) field:</b> (optional)<br>
	<input type="text" name="params[field_firstname]" value="{$params.field_firstname}" size="64"><br>
	<i>example: givenName</i><br>
	<br>
	
	<b>Last name (surname) field:</b> (optional)<br>
	<input type="text" name="params[field_lastname]" value="{$params.field_lastname}" size="64"><br>
	<i>example: sn</i><br>
</fieldset>