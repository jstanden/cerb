{include file="file:$path/configuration/menu.tpl.php"}
<br>

<div id="tourConfigMaintPurge"></div>
<h2>Purge Deleted Tickets</h2>
<form action="{devblocks_url}{/devblocks_url}" method="post">
<input type="hidden" name="c" value="config">
<input type="hidden" name="a" value="doPurge">
<b>{$purge_count}</b> deleted tickets pending purge.<br>
<input type="submit" value="Purge">
</form>

<!-- 
<a name="routing"></a>
<span id="configMailboxRouting">{include file="file:$path/configuration/mail/mail_routing.tpl.php"}</span>
-->

<br>

<script>
	var configAjax = new cConfigAjax();
</script>