<h2>Security</h2>

<form id="frmSetupSecurity" action="{devblocks_url}{/devblocks_url}" method="post" onsubmit="return false;">
<input type="hidden" name="c" value="config">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="security">
<input type="hidden" name="action" value="saveJson">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<fieldset>
	<legend>Remote Administration</legend>
	
	<b>Allow remote administration tools (upgrade, cron) from these IPs:</b> (one IP per line)
	<br>
	<textarea name="authorized_ips" rows="5" cols="24" style="width:400px;">{$settings->get('cerberusweb.core','authorized_ips',CerberusSettingsDefaults::AUTHORIZED_IPS)}</textarea>	
	<br>
	(Partial IP matches OK. For example: 192.168.1.)<br>
</fieldset>

<fieldset>
	<legend>Session Expiration</legend>
	
	<b>Expire session cookies:</b>
	<br>
	{$opts = [
		["When the browser is closed",0],
		["After 15 minutes of inactivity",900],
		["After 1 hour of inactivity",3600],
		["After 2 hours of inactivity",7200],
		["After 6 hours of inactivity",21600],
		["After 12 hours of inactivity",43200],
		["After 1 day of inactivity",86400],
		["After 1 week of inactivity",604800],
		["After 2 weeks of inactivity",1209600],
		["After 1 month of inactivity",2592000]
	]}
	<select name="session_lifespan">
		{foreach from=$opts item=opt}
		<option value="{$opt[1]}" {if $opt[1]==$settings->get('cerberusweb.core','session_lifespan',CerberusSettingsDefaults::SESSION_LIFESPAN)}selected="selected"{/if}>{$opt[0]}</option>
		{/foreach}
	</select>
</fieldset>

<button type="button" class="submit"><span class="glyphicons glyphicons-circle-ok" style="color:rgb(0,180,0);"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>

</form>

<script type="text/javascript">
$(function() {
	var $frm = $('#frmSetupSecurity');
	
	$frm.find('BUTTON.submit')
		.click(function(e) {
			genericAjaxPost('frmSetupSecurity','',null,function(json) {
				Devblocks.saveAjaxForm($frm);
			});
		})
	;
});
</script>
