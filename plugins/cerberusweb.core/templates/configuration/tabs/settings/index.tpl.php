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

<!-- ************** -->

<h2>IP Security</h2>
<br>
<b>Allow remote administration tools (upgrade, cron) from these IPs:</b> (one IP per line)
<br>
<textarea name="authorized_ips" rows="5" cols="24" style="width: 400;">{$settings->get('authorized_ips')|escape:"html"}</textarea>	
<br>
(Partial IP matches OK. For example: 192.168.1.)<br>

<br>
<button type="submit"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/check.gif{/devblocks_url}" align="top"> {$translate->_('common.save_changes')|capitalize}</button>
</div>
</form>

<br>

<div id="tourConfigLicenses"></div>

<div class="block">
<h2>License Info</h2>
<br>

{if empty($license.key)}
	<span style="color:rgb(200,0,0);">No License (Free Mode)</span><br>
	<ul style="margin-top:0px;">
		<li>Cerberus Helpdesk Tagline on Outgoing E-mail</li>
		<li>Limited to 3 Users</li>
		<li>Limited to 3 Groups</li>
	</ul> 
{else}
	<b>Licensed to:</b><br>
	{$license.name}<br>
	<br>
	<b># Licensed Workers:</b><br>
	{if !$license.users}unlimited{else}{$license.users}{/if}<br>
	<br>
{/if} 

{*
{if !empty($license.features) && !empty($license.key)}
<b>Enabled Products:</b><br>
<ul style="margin-top:0px;">
{foreach from=$license.features key=feature item=en}
	<li>{$feature}</li>
{/foreach}
</ul>
{/if}
*}

<h2>Add License</h2>

<form action="{devblocks_url}{/devblocks_url}" method="post">
<input type="hidden" name="c" value="config">
<input type="hidden" name="a" value="saveLicenses">

<b>Paste the product key you received with your order:</b><br>
<textarea rows="5" cols="80" name="key"></textarea><br>
<br>
<button type="submit"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/check.gif{/devblocks_url}" align="top"> {$translate->_('common.save_changes')|capitalize}</button>

</form>

</div>