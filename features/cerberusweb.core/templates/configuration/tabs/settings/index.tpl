<!-- ************** -->

<form action="{devblocks_url}{/devblocks_url}" method="post">
<input type="hidden" name="c" value="config">
<input type="hidden" name="a" value="saveSettings">

<div class="block">
<h2>System Settings</h2>
<br>

<b>Helpdesk Title:</b><br>
<input type="text" name="title" value="{$settings->get('cerberusweb.core','helpdesk_title')|escape:"html"}" size="64"><br>
<br>

<b>Logo URL:</b> (leave blank for default)<br>
<input type="text" name="logo" value="{$settings->get('cerberusweb.core','helpdesk_logo_url')|escape:"html"}" size="64"><br>
<br>

<!-- 
<b>Timezone:</b><br>
<select name="timezone">
</select><br>
 -->

<!-- ************** -->

{if !$smarty.const.ONDEMAND_MODE}
	<h2>IP Security</h2>
	<br>
	<b>Allow remote administration tools (upgrade, cron) from these IPs:</b> (one IP per line)
	<br>
	<textarea name="authorized_ips" rows="5" cols="24" style="width: 400;">{$settings->get('cerberusweb.core','authorized_ips')|escape:"html"}</textarea>	
	<br>
	(Partial IP matches OK. For example: 192.168.1.)<br>
	<br>
{/if}

<button type="submit"><span class="cerb-sprite sprite-check"></span> {$translate->_('common.save_changes')|capitalize}</button>
</div>
</form>

<br>

<div id="divLicenseInfo">
	{include file="{$core_tpl}configuration/tabs/settings/license.tpl"}
</div>
