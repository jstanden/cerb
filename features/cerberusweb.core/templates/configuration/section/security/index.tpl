<h2>Security</h2>

<form id="frmSetupSecurity" action="{devblocks_url}{/devblocks_url}" method="post" onsubmit="return false;">
<input type="hidden" name="c" value="config">
<input type="hidden" name="a" value="handleSectionAction">
<input type="hidden" name="section" value="security">
<input type="hidden" name="action" value="saveJson">

<fieldset>
	<legend>Remote Administration</legend>
	
	<b>Allow remote administration tools (upgrade, cron) from these IPs:</b> (one IP per line)
	<br>
	<textarea name="authorized_ips" rows="5" cols="24" style="width:400px;">{$settings->get('cerberusweb.core','authorized_ips',CerberusSettingsDefaults::AUTHORIZED_IPS)}</textarea>	
	<br>
	(Partial IP matches OK. For example: 192.168.1.)<br>
</fieldset>

<fieldset>
	<legend>Sessions</legend>
	
	<b>Expire session cookies:</b>
	<br>
	{$opts = [["When the browser is closed",0],["After 1 day",86400],["After 1 week",604800],["After 2 weeks",1209600],["After 1 month",2592000]]}
	<select name="session_lifespan">
		{foreach from=$opts item=opt}
		<option value="{$opt[1]}" {if $opt[1]==$settings->get('cerberusweb.core','session_lifespan',CerberusSettingsDefaults::SESSION_LIFESPAN)}selected="selected"{/if}>{$opt[0]}</option>
		{/foreach}
	</select>
</fieldset>

<div class="status"></div>
<button type="button" class="submit"><span class="cerb-sprite2 sprite-tick-circle"></span> {$translate->_('common.save_changes')|capitalize}</button>

</form>

<script type="text/javascript">
	$('#frmSetupSecurity BUTTON.submit')
		.click(function(e) {
			genericAjaxPost('frmSetupSecurity','',null,function(json) {
				$o = $.parseJSON(json);
				if(false == $o || false == $o.status) {
					Devblocks.showError('#frmSetupSecurity div.status', $o.error);
				} else {
					Devblocks.showSuccess('#frmSetupSecurity div.status','Your changes have been saved.');
				}
			});
		})
	;
</script>
