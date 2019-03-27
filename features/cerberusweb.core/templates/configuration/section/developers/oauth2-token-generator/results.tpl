<fieldset>
	<legend>{'common.results'|devblocks_translate|capitalize}</legend>
	
	<table>
		<tr>
			<td width="1%" nowrap="nowrap" valign="top">
				<b>Token Type:</b>
			</td>
			<td width="99%" valign="top">
				{$bearer_token.token_type}
			</td>
		</tr>
		
		<tr>
			<td width="1%" nowrap="nowrap" valign="top">
				<b>Expires:</b>
			</td>
			<td width="99%" valign="top">
				{$bearer_token.expires_in} ({$bearer_token.expires_in|devblocks_prettysecs})
			</td>
		</tr>
		
		<tr>
			<td width="1%" nowrap="nowrap" valign="top">
				<b>Access Token:</b>
			</td>
			<td width="99%" valign="top">
				<textarea style="height:100px;width:100%;" spellcheck="false">{$bearer_token.access_token}</textarea>
			</td>
		</tr>
		
		<tr>
			<td width="1%" nowrap="nowrap" valign="top">
				<b>Refresh Token:</b>
			</td>
			<td width="99%" valign="top">
				<textarea style="height:100px;width:100%;" spellcheck="false">{$bearer_token.refresh_token}</textarea>
			</td>
		</tr>
	</table>
</fieldset>