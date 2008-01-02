<table cellpadding="2" cellspacing="0" border="0">
	<tr>
		<td colspan="2">
			{if empty($pop3_account->id)}
			<h3>Add POP3 Account</h3>
			<input type="hidden" name="account_id[]" value="0">
			{else}
			<h3>Modify '{$pop3_account->nickname}'</h3>
			<input type="hidden" name="account_id[]" value="{$pop3_account->id}">
			{/if}
		</td>
	</tr>
	{if !empty($pop3_account->id)}
	<tr>
		<td width="0%" nowrap="nowrap"><b>Enabled:</b></td>
		<td width="100%"><input type="checkbox" name="pop3_enabled[]" value="{$pop3_account->id}" {if $pop3_account->enabled}checked{/if}></td>
	</tr>
	{/if}
	<tr>
		<td width="0%" nowrap="nowrap"><b>Nickname:</b></td>
		<td width="100%"><input type="text" name="nickname[]" value="{$pop3_account->nickname|escape:"html"}" size="45"></td>
	</tr>
	<tr>
		<td width="0%" nowrap="nowrap"><b>Protocol:</b></td>
		<td width="100%"><select name="protocol[]">
			<option value="pop3" {if $pop3_account->protocol=='pop3'}selected{/if}>POP3
			<option value="pop3-ssl" {if $pop3_account->protocol=='pop3-ssl'}selected{/if}>POP3-SSL
			<option value="imap" {if $pop3_account->protocol=='imap'}selected{/if}>IMAP
			<option value="imap-ssl" {if $pop3_account->protocol=='imap-ssl'}selected{/if}>IMAP-SSL
		</select></td>
	</tr>
	<tr>
		<td width="0%" nowrap="nowrap"><b>Host:</b></td>
		<td width="100%"><input type="text" name="host[]" value="{$pop3_account->host|escape:"html"}" size="45"></td>
	</tr>
	<tr>
		<td width="0%" nowrap="nowrap"><b>Username:</b></td>
		<td width="100%"><input type="text" name="username[]" value="{$pop3_account->username|escape:"html"}"></td>
	</tr>
	<tr>
		<td width="0%" nowrap="nowrap"><b>Password:</b></td>
		<td width="100%"><input type="password" name="password[]" value="{$pop3_account->password|escape:"html"}"></td>
	</tr>
	<tr>
		<td width="0%" nowrap="nowrap"><b>Port:</b></td>
		<td width="100%"><input type="text" name="port[]" value="{$pop3_account->port|escape:"html"}" size="5"> (leave blank for default)</td>
	</tr>
	{if !empty($pop3_account->id)}
	<tr>
		<td width="0%" nowrap="nowrap"><b>Delete:</b></td>
		<td width="100%"><label style="background-color:rgb(255,220,220);"><input type="checkbox" name="delete[]" value="{$pop3_account->id}"> Delete this POP3 account</label></td>
	</tr>
	{/if}
	 
</table>

<br>