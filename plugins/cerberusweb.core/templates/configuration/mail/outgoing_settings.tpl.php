<div class="block" id="configMailboxOutgoing">
<table cellpadding="2" cellspacing="0" border="0">
	<tr>
		<td><h2>Outgoing Mail</h2></td>
	</tr>
	<tr>
		<td>
			<form action="{devblocks_url}{/devblocks_url}#outgoing" method="post">
			<input type="hidden" name="c" value="config">
			<input type="hidden" name="a" value="saveOutgoingMailSettings">

			{if !$smtp_test && !empty($smtp_test_output)}
				<div class="error">
					{$smtp_test_output}
				</div>
				<br>
			{elseif $smtp_test===true}
				<div class="success">
					Outgoing mail settings were tested successfully.
				</div>
				<br>
			{/if}


			<b>SMTP Server:</b><br>
			<input type="text" name="smtp_host" value="{$settings->get('smtp_host','localhost')}" size="45"><br>
			<br>

			<b>SMTP Port:</b><br>
			<input type="text" name="smtp_port" value="{$settings->get('smtp_port',25)}" size="45"><br>
			<br>
			
			<b>SMTP Encryption:</b><br>
			<input type="radio" name="smtp_enc" value="None" {if $settings->get('smtp_enc') == 'None'}checked{/if}>None&nbsp;&nbsp;&nbsp;
			<input type="radio" name="smtp_enc" value="TLS" {if $settings->get('smtp_enc') == 'TLS'}checked{/if}>TLS&nbsp;&nbsp;&nbsp;
			<input type="radio" name="smtp_enc" value="SSL" {if $settings->get('smtp_enc') == 'SSL'}checked{/if}>SSL<br>
			<br>

			<b>SMTP Server Requires Login:</b> (optional)<br>
			<label><input type="checkbox" name="smtp_auth_enabled" value="1" onclick="toggleDiv('configGeneralSmtpAuth',(this.checked?'block':'none'));" {if $settings->get('smtp_auth_enabled')}checked{/if}> Enabled</label><br>
			<br>
			
			<div id="configGeneralSmtpAuth" style="margin-left:15px;display:{if $settings->get('smtp_auth_enabled')}block{else}none{/if};">
			<b>SMTP Auth Username:</b><br>
			<input type="text" name="smtp_auth_user" value="{$settings->get('smtp_auth_user')}" size="45"><br>
			<br>
			
			<b>SMTP Auth Password:</b><br>
			<input type="text" name="smtp_auth_pass" value="{$settings->get('smtp_auth_pass')}" size="45"><br>
			<br>
			</div>
			
			<b>By default, reply to mail as:</b> (E-mail Address)<br>
			<input type="text" name="sender_address" value="{$settings->get('default_reply_from')}" size="45"> (e.g., support@yourcompany.com)<br>
			<br>
			
			<b>By default, reply to mail as:</b> (Personal Name)<br>
			<input type="text" name="sender_personal" value="{$settings->get('default_reply_personal')}" size="45"> (e.g., Acme Widgets)<br>
			<br>
			
			<b>Default E-mail Signature:</b><br>
			<textarea name="default_signature" rows="4" cols="76">{$settings->get('default_signature')|escape:"html"}</textarea><br>
			<div style="padding-left:10px;">
				E-mail Signature Variables: 
				<select name="sig_vars" onchange="this.form.default_signature.value += this.options[this.selectedIndex].value;scrollElementToBottom(this.form.default_signature);this.selectedIndex=0;this.form.default_signature.focus();">
					<option value="">-- choose --</option>
					<optgroup label="Worker">
						<option value="#first_name#">#first_name#</option>
						<option value="#last_name#">#last_name#</option>
						<option value="#title#">#title#</option>
					</optgroup>
				</select>
				<br>
				<br>
				
				<b>Insert E-mail Signatures:</b><br> 
				<select name="default_signature_pos">
					<option value="0" {if $settings->get('default_signature_pos',0)==0}selected{/if}>Below quoted text in reply</option>
					<option value="1" {if $settings->get('default_signature_pos',0)==1}selected{/if}>Above quoted text in reply</option>
				</select>
				<br>
				<br>
			</div>
			
			<button type="submit"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/check.gif{/devblocks_url}" align="top"> {$translate->_('common.save_changes')|capitalize}</button>
			</form>
		</td>
	</tr>
</table>
</div>