{include file="file:$path/configuration/menu.tpl.php"}
<br>

<h2>Mail</h2>
<br>

<a name="routing"></a>
<div id="tourConfigMailRouting"></div>
<span id="configMailboxRouting">{include file="file:$path/configuration/mail/mail_routing.tpl.php"}</span>
<br>

<a name="incoming"></a>
<span id="configMailboxIncoming">{include file="file:$path/configuration/mail/incoming_settings.tpl.php"}</span>
<br>

<a name="outgoing"></a>
<span id="configMailboxOutgoing">{include file="file:$path/configuration/mail/outgoing_settings.tpl.php"}</span>
<br>

<script>
	var configAjax = new cConfigAjax();
</script>