<div class="block">
	<table cellpadding="0" cellspacing="0" border="0" width="100%">
		<tr>
			<td width="0%" nowrap="nowrap" valign="top"><b>Password:</b>&nbsp;</td>
			<td width="100%">
				<input type="password" name="auth_params[password_new]" value=""  style="width:90%;" placeholder="{if $worker->id}(leave blank for unchanged){else}(leave blank to send a random password by email){/if}">
			</td>
		</tr>
		<tr>
			<td width="0%" nowrap="nowrap" valign="top"><b>Verify:</b>&nbsp;</td>
			<td width="100%">
				<input type="password" name="auth_params[password_verify]" value="" style="width:90%;">
			</td>
		</tr>
	</table>
</div>