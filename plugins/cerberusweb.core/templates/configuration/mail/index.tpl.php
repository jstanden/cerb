{include file="file:$path/configuration/menu.tpl.php"}
<br>

<h2>Mail</h2>

<a name="routing"></a>
<span id="configMailboxRouting">{include file="file:$path/configuration/mail/mail_routing.tpl.php"}</span>

<br>

<a name="pop3"></a>
{include file="file:$path/configuration/mail/pop3_accounts.tpl.php"}

<script>
	var configAjax = new cConfigAjax();
</script>