<h2>SMTP Server</h2>

<fieldset>
	<legend>{'common.settings'|devblocks_translate|capitalize}</legend>
	
	<form action="{devblocks_url}{/devblocks_url}" method="post" id="frmSetupMailOutgoingSmtp" onsubmit="return false;">
	<input type="hidden" name="c" value="config">
	<input type="hidden" name="a" value="handleSectionAction">
	<input type="hidden" name="section" value="mail_smtp">
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
	
	<button type="button" class="submit"><span class="cerb-sprite2 sprite-tick-circle"></span> {$translate->_('common.save_changes')|capitalize}</button>
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
</script>