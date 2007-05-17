<div class="block">
<table cellpadding="2" cellspacing="0" border="0" width="100%">
	<tr>
		<td><h2>Outgoing Mail</h2></td>
	</tr>
	<tr>
		<td>
			<form action="{devblocks_url}{/devblocks_url}" method="post">
			<input type="hidden" name="c" value="config">
			<input type="hidden" name="a" value="saveOutgoingMailSettings">

			<b>SMTP Server:</b><br>
			<input type="text" name="smtp_host" value="{$settings->get('smtp_host')}" size="45"><br>
			<br>
			
			<b>SMTP Auth Username (Optional):</b><br>
			<input type="text" name="smtp_auth_user" value="{$settings->get('smtp_auth_user')}" size="45"><br>
			<br>
			
			<b>SMTP Auth Password (Optional):</b><br>
			<input type="text" name="smtp_auth_pass" value="{$settings->get('smtp_auth_pass')}" size="45"><br>
			<br>
			
			<b>Default Reply-to (E-mail Address):</b><br>
			<input type="text" name="sender_address" value="{$settings->get('default_reply_from')}" size="45"><br>
			<br>
			
			<b>Default Reply-to (Personal):</b><br>
			<input type="text" name="sender_personal" value="{$settings->get('default_reply_personal')}" size="45"><br>
			<br>
			
			<button onclick="this.form.submit();"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/check.gif{/devblocks_url}" align="top"> {$translate->_('common.save_changes')|capitalize}</button>
			</form>
		</td>
	</tr>
</table>
</div>