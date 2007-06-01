{include file="file:$path/configuration/menu.tpl.php"}
<br>

<h2>Mail</h2>

<a name="routing"></a>
<div id="tourConfigMailRouting"></div>
<span id="configMailboxRouting">{include file="file:$path/configuration/mail/mail_routing.tpl.php"}</span>

<br>

<a name="outgoing"></a>
<span id="configMailboxOutgoing">{include file="file:$path/configuration/mail/outgoing_settings.tpl.php"}</span>

<br>

<a name="manual"></a>
<span id="configManualParse">{include file="file:$path/configuration/mail/manual_parse.tpl.php"}</span>

<br>

<script>
	var configAjax = new cConfigAjax();
</script>