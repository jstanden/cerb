<div class="block">
<input type="hidden" name="c" value="config">
<input type="hidden" name="a" value="saveMailbox">

<table cellpadding="2" cellspacing="0" border="0">
	<tr>
		<td colspan="2">
			{if empty($pop3_account->id)}
			<h2>Add New Mail Server</h2>
			<input type="hidden" name="account_id" value="0">
			{else}
			<h2>Mail Server '{$pop3_account->nickname}'</h2>
			<input type="hidden" name="account_id" value="{$pop3_account->id}">
			{/if}
		</td>
	</tr>
	<tr>
		<td width="0%" nowrap="nowrap"><b>Enabled:</b></td>
		<td width="100%"><input type="checkbox" name="pop3_enabled" value="1" {if $pop3_account->enabled || empty($pop3_account)}checked{/if}></td>
	</tr>
	<tr>
		<td width="0%" nowrap="nowrap"><b>Nickname:</b></td>
		<td width="100%"><input type="text" name="nickname" value="{$pop3_account->nickname|escape:"html"}" size="45"></td>
	</tr>
	<tr>
		<td width="0%" nowrap="nowrap"><b>Protocol:</b></td>
		<td width="100%"><select name="protocol">
			<option value="pop3" {if $pop3_account->protocol=='pop3'}selected{/if}>POP3
			<option value="pop3-ssl" {if $pop3_account->protocol=='pop3-ssl'}selected{/if}>POP3-SSL
			<option value="imap" {if $pop3_account->protocol=='imap'}selected{/if}>IMAP
			<option value="imap-ssl" {if $pop3_account->protocol=='imap-ssl'}selected{/if}>IMAP-SSL
		</select></td>
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
		<td width="100%"><input type="password" name="password" value="{if !$smarty.const.DEMO_MODE}{$pop3_account->password|escape:"html"}{/if}"></td>
	</tr>
	<tr>
		<td width="0%" nowrap="nowrap"><b>Port:</b></td>
		<td width="100%"><input type="text" name="port" value="{$pop3_account->port|escape:"html"}" size="5"> (leave blank for default)</td>
	</tr>
	{if !empty($pop3_account->id)}
	<tr>
		<td width="0%" nowrap="nowrap"><b>Delete:</b></td>
		<td width="100%"><label style="background-color:rgb(255,220,220);"><input type="checkbox" name="delete" value="{$pop3_account->id}"> Delete this mail account</label></td>
	</tr>
	{/if}

	<tr>
		<td colspan="2">
			<br>
			<div id="configPopTest"></div>	
		
			<button type="submit"><span class="cerb-sprite sprite-check"></span> {$translate->_('common.save_changes')|capitalize}</button>
			<button type="button" onclick="document.getElementById('configPopTest').innerHTML='Testing mailbox settings...<br>';genericAjaxGet('configPopTest','c=config&a=getMailboxTest&host='+encodeURIComponent(this.form.host.value)+'&protocol='+selectValue(this.form.protocol)+'&port='+this.form.port.value+'&user='+encodeURIComponent(this.form.username.value)+'&pass='+encodeURIComponent(this.form.password.value));"><span class="cerb-sprite sprite-gear"></span> Test Mailbox</button>
		</td>
	</tr>
	 
</table>
</div>
