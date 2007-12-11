<!-- ************** -->

<form action="{devblocks_url}{/devblocks_url}" method="post">
<input type="hidden" name="c" value="config">
<input type="hidden" name="a" value="saveSettings">

<div class="block">
<h2>System Settings</h2>
<br>

<b>Helpdesk Title:</b><br>
<input type="text" name="title" value="{$settings->get('helpdesk_title')|escape:"html"}" size="64"><br>
<br>

<b>Logo URL:</b> (leave blank for default)<br>
<input type="text" name="logo" value="{$settings->get('helpdesk_logo_url')|escape:"html"}" size="64"><br>
<br>

<!-- 
<b>Timezone:</b><br>
<select name="timezone">
</select><br>
 -->

</div>
<br>

<!-- ************** -->

<div class="block">
<h2>Attachments</h2>
<br>

<b>Enabled:</b><br>
<label><input type="checkbox" name="attachments_enabled" value="1" {if $settings->get('attachments_enabled')}checked{/if}> Allow Incoming Attachments</label><br>
<br>

<b>Max. Attachment Size:</b><br>
<input type="text" name="attachments_max_size" value="{$settings->get('attachments_max_size')|escape:"html"}" size="5"> MB<br>
</div>

<br>

<div class="block">
<h2>IP Security</h2>
<br>
<b>Allow remote administration tools (upgrade, cron) from these IPs:</b> (one IP per line)
<br>
<textarea name="authorized_ips" rows="5" cols="24" style="width: 400;">{$settings->get('authorized_ips')|escape:"html"}</textarea>	
<br>
(Partial IP matches OK. For example: 192.168.1.)<br>
</div>

<br>
<button type="submit"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/check.gif{/devblocks_url}" align="top"> {$translate->_('common.save_changes')|capitalize}</button>
</form>
