<h2>Outgoing Mail</h2>

<fieldset>
	<legend>SMTP Server</legend>
	
	<form action="{devblocks_url}{/devblocks_url}" method="post" id="frmSetupMailOutgoingSmtp" onsubmit="return false;">
	<input type="hidden" name="c" value="config">
	<input type="hidden" name="a" value="handleSectionAction">
	<input type="hidden" name="section" value="mail_outgoing">
	<input type="hidden" name="action" value="saveSmtpJson">

	<b>SMTP Server:</b><br>
	<input type="text" name="smtp_host" value="{$settings->get('cerberusweb.core','smtp_host','localhost')}" size="45">
	<i>(e.g. localhost)</i>
	<br>
	<br>

	<b>SMTP Port:</b><br>
	<input type="text" name="smtp_port" value="{$settings->get('cerberusweb.core','smtp_port',25)}" size="5">
	<i>(usually '25')</i>
	<br>
	<br>
	
	<b>SMTP Encryption:</b> (optional)<br>
	<label><input type="radio" name="smtp_enc" value="None" {if $settings->get('cerberusweb.core','smtp_enc') == 'None'}checked{/if}>None</label>&nbsp;&nbsp;&nbsp;
	<label><input type="radio" name="smtp_enc" value="TLS" {if $settings->get('cerberusweb.core','smtp_enc') == 'TLS'}checked{/if}>TLS</label>&nbsp;&nbsp;&nbsp;
	<label><input type="radio" name="smtp_enc" value="SSL" {if $settings->get('cerberusweb.core','smtp_enc') == 'SSL'}checked{/if}>SSL</label><br>
	<br>

	<b>SMTP Authentication:</b> (optional)<br>
	<label><input type="checkbox" name="smtp_auth_enabled" value="1" onclick="toggleDiv('configGeneralSmtpAuth',(this.checked?'block':'none'));if(!this.checked){literal}{{/literal}this.form.smtp_auth_user.value='';this.form.smtp_auth_pass.value='';{literal}}{/literal}" {if $settings->get('cerberusweb.core','smtp_auth_enabled')}checked{/if}> Enabled</label><br>
	<br>
	
	<div id="configGeneralSmtpAuth" style="margin-left:15px;display:{if $settings->get('cerberusweb.core','smtp_auth_enabled')}block{else}none{/if};">
		<b>Username:</b><br>
		<input type="text" name="smtp_auth_user" value="{$settings->get('cerberusweb.core','smtp_auth_user')}" size="45"><br>
		<br>
		
		<b>Password:</b><br>
		<input type="text" name="smtp_auth_pass" value="{$settings->get('cerberusweb.core','smtp_auth_pass')}" size="45"><br>
		<br>
	</div>
	
	<b>SMTP Timeout:</b><br>
	<input type="text" name="smtp_timeout" value="{$settings->get('cerberusweb.core','smtp_timeout',30)}" size="4">
	seconds
	<br>
	<br>
	
	<b>Maximum Deliveries Per SMTP Connection:</b><br>
	<input type="text" name="smtp_max_sends" value="{$settings->get('cerberusweb.core','smtp_max_sends',20)}" size="5">
	<i>(tuning this depends on your mail server; default is 20)</i>
	<br>
	<br>
	
	<div class="status"></div>
	
	<button type="button" class="submit"><span class="cerb-sprite sprite-check"></span> {$translate->_('common.save_changes')|capitalize}</button>
	</form>				
</fieldset>

<fieldset>
	<legend>{'common.settings'|devblocks_translate|capitalize}</legend>
	
	<form action="{devblocks_url}{/devblocks_url}" method="post" id="frmSetupMailOutgoing" onsubmit="return false;">
	<input type="hidden" name="c" value="config">
	<input type="hidden" name="a" value="handleSectionAction">
	<input type="hidden" name="section" value="mail_outgoing">
	<input type="hidden" name="action" value="saveJson">
	
	<b>By default, reply to mail as:</b> (E-mail Address)<br>
	<input type="text" name="sender_address" value="{$settings->get('cerberusweb.core','default_reply_from')}" size="45"> (e.g. support@yourcompany.com)<br>
	<br>
	
	<b>By default, reply to mail as:</b> (Personal Name)<br>
	<input type="text" name="sender_personal" value="{$settings->get('cerberusweb.core','default_reply_personal')}" size="45"> (e.g. Acme Widgets)<br>
	<br>
	
	<b>Default E-mail Signature:</b><br>
	<textarea name="default_signature" rows="10" cols="76" style="width:100%;" wrap="off">{$settings->get('cerberusweb.core','default_signature')}</textarea><br>
	<div style="padding-left:10px;">
		<button type="button" onclick="genericAjaxPost('frmSetupMailOutgoing','divTemplateTester','c=internal&a=snippetTest&snippet_context=cerberusweb.contexts.worker&snippet_field=default_signature');"><span class="cerb-sprite sprite-gear"></span> Test</button>
		<select name="sig_vars" onchange="insertAtCursor(this.form.default_signature,this.options[this.selectedIndex].value);this.selectedIndex=0;this.form.default_signature.focus();">
			<option value="">-- insert at cursor --</option>
			{foreach from=$token_labels key=k item=v}
			<option value="{literal}{{{/literal}{$k}{literal}}}{/literal}">{$v}</option>
			{/foreach}
		</select>
		<br>
		<div id="divTemplateTester"></div>
		<br>
		
		<b>Insert E-mail Signatures:</b><br> 
		<select name="default_signature_pos">
			<option value="0" {if $settings->get('cerberusweb.core','default_signature_pos',0)==0}selected{/if}>Below quoted text in reply</option>
			<option value="1" {if $settings->get('cerberusweb.core','default_signature_pos',0)==1}selected{/if}>Above quoted text in reply</option>
		</select>
		<br>
		<br>
	</div>	
	
	<div class="status"></div>
	
	<button type="button" class="submit"><span class="cerb-sprite sprite-check"></span> {$translate->_('common.save_changes')|capitalize}</button>
	</form>
</fieldset>

<script type="text/javascript">
	$('#frmSetupMailOutgoingSmtp BUTTON.submit')
		.click(function(e) {
			genericAjaxPost('frmSetupMailOutgoingSmtp','',null,function(json) {
				$o = $.parseJSON(json);
				if(false == $o || false == $o.status) {
					Devblocks.showError('#frmSetupMailOutgoingSmtp div.status',$o.error);
				} else {
					Devblocks.showSuccess('#frmSetupMailOutgoingSmtp div.status','Settings saved!');
				}
			});
		})
	;
	$('#frmSetupMailOutgoing BUTTON.submit')
		.click(function(e) {
			genericAjaxPost('frmSetupMailOutgoing','',null,function(json) {
				$o = $.parseJSON(json);
				if(false == $o || false == $o.status) {
					Devblocks.showError('#frmSetupMailOutgoing div.status',$o.error);
				} else {
					Devblocks.showSuccess('#frmSetupMailOutgoing div.status','Settings saved!');
				}
			});
		})
	;
</script>