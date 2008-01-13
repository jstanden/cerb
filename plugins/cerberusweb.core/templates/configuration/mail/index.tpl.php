{include file="file:$path/configuration/menu.tpl.php"}
<br>

<h2>Mail</h2>
<br>

<a name="routing"></a>
<div id="tourConfigMailRouting"></div>
{include file="file:$path/configuration/mail/mail_routing.tpl.php"}
<br>

<a name="incoming"></a>
{include file="file:$path/configuration/mail/incoming_settings.tpl.php"}
<br>

<a name="outgoing"></a>
{include file="file:$path/configuration/mail/outgoing_settings.tpl.php"}
<br>

<script type="text/javascript">
	var configAjax = new cConfigAjax();
</script>