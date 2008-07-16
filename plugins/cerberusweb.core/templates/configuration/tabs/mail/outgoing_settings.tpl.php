<div class="block" id="configMailboxOutgoing">
<table cellpadding="2" cellspacing="0" border="0">
	<tr>
		<td><h2>Outgoing Mail Preferences</h2></td>
	</tr>
	<tr>
		<td>
			<form action="{devblocks_url}{/devblocks_url}" method="post">
			<input type="hidden" name="c" value="config">
			<input type="hidden" name="a" value="saveOutgoingMailSettings">

			<h3>Outgoing Server (SMTP)</h3>

			<blockquote style="margin-left:20px;">
				<b>SMTP Server:</b><br>
				<input type="text" name="smtp_host" value="{$settings->get('smtp_host','localhost')}" size="45">
				<i>(e.g. localhost)</i>
				<br>
				<br>
	
				<b>SMTP Port:</b><br>
				<input type="text" name="smtp_port" value="{$settings->get('smtp_port',25)}" size="5">
				<i>(usually '25')</i>
				<br>
				<br>
				
				<b>SMTP Encryption:</b> (optional)<br>
				<label><input type="radio" name="smtp_enc" value="None" {if $settings->get('smtp_enc') == 'None'}checked{/if}>None</label>&nbsp;&nbsp;&nbsp;
				<label><input type="radio" name="smtp_enc" value="TLS" {if $settings->get('smtp_enc') == 'TLS'}checked{/if}>TLS</label>&nbsp;&nbsp;&nbsp;
				<label><input type="radio" name="smtp_enc" value="SSL" {if $settings->get('smtp_enc') == 'SSL'}checked{/if}>SSL</label><br>
				<br>
	
				<b>SMTP Authentication:</b> (optional)<br>
				<label><input type="checkbox" name="smtp_auth_enabled" value="1" onclick="toggleDiv('configGeneralSmtpAuth',(this.checked?'block':'none'));" {if $settings->get('smtp_auth_enabled')}checked{/if}> Enabled</label><br>
				<br>
				
				<div id="configGeneralSmtpAuth" style="margin-left:15px;display:{if $settings->get('smtp_auth_enabled')}block{else}none{/if};">
					<b>Username:</b><br>
					<input type="text" name="smtp_auth_user" value="{$settings->get('smtp_auth_user')}" size="45"><br>
					<br>
					
					<b>Password:</b><br>
					<input type="text" name="smtp_auth_pass" value="{$settings->get('smtp_auth_pass')}" size="45"><br>
					<br>
				</div>
				
				<b>SMTP Timeout:</b><br>
				<input type="text" name="smtp_timeout" value="{$settings->get('smtp_timeout',30)}" size="4">
				seconds
				<br>
				<br>
				
				<b>Maximum Deliveries Per SMTP Connection:</b><br>
				<input type="text" name="smtp_max_sends" value="{$settings->get('smtp_max_sends',20)}" size="5">
				<i>(tuning this depends on your mail server; default is 20)</i>
				<br>
				<br>
				
				<div id="configSmtpTest"></div>	
				<button type="button" onclick="genericAjaxGet('configSmtpTest','c=config&a=getSmtpTest&host='+this.form.smtp_host.value+'&port='+this.form.smtp_port.value+'&enc='+radioValue(this.form.smtp_enc)+'&smtp_user='+this.form.smtp_auth_user.value+'&smtp_pass='+this.form.smtp_auth_pass.value);"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/gear.gif{/devblocks_url}" align="top"> Test SMTP</button>				
			</blockquote>
			
			<h3>Default Preferences</h3>
			
			<blockquote style="margin-left:20px;">
				<b>By default, reply to mail as:</b> (E-mail Address)<br>
				<input type="text" name="sender_address" value="{$settings->get('default_reply_from')}" size="45"> (e.g. support@yourcompany.com)<br>
				<br>
				
				<b>By default, reply to mail as:</b> (Personal Name)<br>
				<input type="text" name="sender_personal" value="{$settings->get('default_reply_personal')}" size="45"> (e.g. Acme Widgets)<br>
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
			</blockquote>
			
			<button type="submit"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/check.gif{/devblocks_url}" align="top"> {$translate->_('common.save_changes')|capitalize}</button>
			</form>
		</td>
	</tr>
</table>
</div>