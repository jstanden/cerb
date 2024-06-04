<h2>Security</h2>

<form id="frmSetupSecurity" action="{devblocks_url}{/devblocks_url}" method="post">
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
	<legend>Session Cookies</legend>
	
	<b>Expire sessions after </b>
	{$opts = [
		["15 minutes",900],
		["30 minutes",1800],
		["1 hour",3600],
		["2 hours",7200],
		["4 hours",14400],
		["6 hours",21600],
		["8 hours",28800],
		["12 hours",43200],
		["1 day",86400],
		["3 days",259200],
		["1 week",604800],
		["2 weeks",1209600],
		["1 month",2592000]
	]}
	<select name="session_lifespan">
		{foreach from=$opts item=opt}
		<option value="{$opt[1]}" {if $opt[1]==$session_lifespan}selected="selected"{/if}>{$opt[0]}</option>
		{/foreach}
	</select>

	<b>of inactivity</b>
</fieldset>

<button type="button" class="submit"><span class="glyphicons glyphicons-circle-ok"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>

</form>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	var $frm = $('#frmSetupSecurity');

	Devblocks.formDisableSubmit($frm);
	
	$frm.find('BUTTON.submit')
		.click(function(e) {
			genericAjaxPost('frmSetupSecurity','',null,function(json) {
				Devblocks.saveAjaxForm($frm);
			});
		})
	;
});
</script>
