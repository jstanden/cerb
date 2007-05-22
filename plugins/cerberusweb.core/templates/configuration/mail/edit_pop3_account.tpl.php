<input type="hidden" name="c" value="config">
<input type="hidden" name="a" value="savePop3Account">
<input type="hidden" name="id" value="{if !empty($pop3_account->id)}{$pop3_account->id}{else}0{/if}">
<div class="block">
<table cellpadding="2" cellspacing="0" border="0" width="100%">
	<tr>
		<td colspan="2">
			{if empty($pop3_account->id)}
			<h2>Add POP3 Account</h2>
			{else}
			<h2>Modify '{$pop3_account->nickname}'</h2>
			{/if}
		</td>
	</tr>
	<tr>
		<td width="0%" nowrap="nowrap"><b>Nickname:</b></td>
		<td width="100%"><input type="text" name="nickname" value="{$pop3_account->nickname|escape:"html"}" size="45"></td>
	</tr>
	<tr>
		<td width="0%" nowrap="nowrap"><b>Host:</b></td>
		<td width="100%"><input type="text" name="host" value="{$pop3_account->host|escape:"html"}" size="45"></td>
	</tr>
	<tr>
		<td width="0%" nowrap="nowrap"><b>Username:</b></td>
		<td width="100%"><input type="text" name="username" value="{$pop3_account->username|escape:"html"}"></td>
	</tr>
	<tr>
		<td width="0%" nowrap="nowrap"><b>Password:</b></td>
		<td width="100%"><input type="password" name="password" value="{$pop3_account->password|escape:"html"}"></td>
	</tr>
	<tr>
		<td width="0%" nowrap="nowrap"><b>Port:</b></td>
		<td width="100%"><input type="text" name="port" value="{$pop3_account->port|escape:"html"}" size="5"></td>
	</tr>
	{if !empty($pop3_account->id)}
	<tr>
		<td width="0%" nowrap="nowrap"><b>Delete:</b></td>
		<td width="100%"><label style="background-color:rgb(255,220,220);"><input type="checkbox" name="delete" value="1"> Delete this POP3 account</label></td>
	</tr>
	{/if}
	<tr>
		<td colspan="2">
			<button type="submit"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/check.gif{/devblocks_url}" align="top"> {$translate->_('common.save_changes')|capitalize}</button>
		</td>
	</tr>
</table>
</div>