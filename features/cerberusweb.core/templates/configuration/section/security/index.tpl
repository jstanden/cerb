<h2>Security</h2>

<form id="frmSetupSecurity" action="{devblocks_url}{/devblocks_url}" method="post" onsubmit="return false;">
<input type="hidden" name="c" value="config">
<input type="hidden" name="a" value="handleSectionAction">
<input type="hidden" name="section" value="security">
<input type="hidden" name="action" value="saveJson">

<fieldset>
	<legend>Settings</legend>
	
	<b>Allow remote administration tools (upgrade, cron) from these IPs:</b> (one IP per line)
	<br>
	<textarea name="authorized_ips" rows="5" cols="24" style="width:400px;">{$settings->get('cerberusweb.core','authorized_ips',CerberusSettingsDefaults::AUTHORIZED_IPS)}</textarea>	
	<br>
	(Partial IP matches OK. For example: 192.168.1.)<br>
	<br>
	
	<div class="status"></div>
	
	<button type="button" class="submit"><span class="cerb-sprite2 sprite-tick-circle"></span> {$translate->_('common.save_changes')|capitalize}</button>
</fieldset>

</form>

<script type="text/javascript">
	$('#frmSetupSecurity BUTTON.submit')
		.click(function(e) {
			genericAjaxPost('frmSetupSecurity','',null,function(json) {
				$o = $.parseJSON(json);
				if(false == $o || false == $o.status) {
					Devblocks.showError('#frmSetupSecurity div.status',$o.error);
				} else {
					Devblocks.showSuccess('#frmSetupSecurity div.status','Your changes have been saved.');
				}
			});
		})
	;
</script>
