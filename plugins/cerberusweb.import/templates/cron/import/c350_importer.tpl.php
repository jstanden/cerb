<table cellpadding="2" cellspacing="0" border="0" width="100%">
	<tr>
		<td colspan="2">
			<h3>Cerberus Helpdesk 3.5 Mail Importer</h3>
		</td>
	</tr>
	<tr>
		<td width="0%" nowrap="nowrap"><b>Enabled:</b></td>
		<td width="100%"><input type="checkbox" name="enabled[]" value="" {if $pop3_account->enabled}checked{/if}></td>
	</tr>
	<tr>
		<td width="0%" nowrap="nowrap"><b>Database Host/IP:</b></td>
		<td width="100%"><input type="text" name="db_host[]" value="" size="45"></td>
	</tr>
	<tr>
		<td width="0%" nowrap="nowrap"><b>Database Name:</b></td>
		<td width="100%"><input type="text" name="db_name[]" value=""></td>
	</tr>
	<tr>
		<td width="0%" nowrap="nowrap"><b>Database User:</b></td>
		<td width="100%"><input type="text" name="db_user[]" value=""></td>
	</tr>
	<tr>
		<td width="0%" nowrap="nowrap"><b>Database Password:</b></td>
		<td width="100%"><input type="password" name="db_pass[]" value=""></td>
	</tr>
	 
</table>

<br>