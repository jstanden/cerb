<input type="hidden" name="c" value="core.module.configuration">
<input type="hidden" name="a" value="savePop3Account">
<input type="hidden" name="id" value="{if !empty($pop3_account->id)}{$pop3_account->id}{else}0{/if}">
<table cellpadding="2" cellspacing="0" border="0" width="100%" class="configTable">
	<tr>
		<td colspan="2" class="configTableTh">
			{if empty($pop3_account->id)}
			Add POP3 Account
			{else}
			Modify '{$pop3_account->nickname}'
			{/if}
		</td>
	</tr>
	<tr>
		<td width="0%" nowrap="nowrap"><b>Nickname:</b></td>
		<td width="100%"><input type="text" name="nickname" value="{$pop3_account->nickname|escape:"html"}"></td>
	</tr>
	<tr>
		<td width="0%" nowrap="nowrap"><b>Host:</b></td>
		<td width="100%"><input type="text" name="host" value="{$pop3_account->host|escape:"html"}"></td>
	</tr>
	<tr>
		<td width="0%" nowrap="nowrap"><b>Username:</b></td>
		<td width="100%"><input type="text" name="username" value="{$pop3_account->username|escape:"html"}"></td>
	</tr>
	<tr>
		<td width="0%" nowrap="nowrap"><b>Password:</b></td>
		<td width="100%"><input type="text" name="password" value="{$pop3_account->password|escape:"html"}"></td>
	</tr>
	{if !empty($pop3_account->id)}
	<tr>
		<td width="0%" nowrap="nowrap"><b>Delete:</b></td>
		<td width="100%"><label style="background-color:rgb(255,220,220);"><input type="checkbox" name="delete" value="1"> Delete this POP3 account</label></td>
	</tr>
	{/if}
	<tr>
		<td colspan="2">
			<input type="submit" value="{$translate->say('common.save_changes')}">
		</td>
	</tr>
</table>
