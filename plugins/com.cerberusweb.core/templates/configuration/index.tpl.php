<h1>Configuration</h1>
[ <a href="#">general settings</a> ] 
[ <a href="#">workflow</a> ] 
[ <a href="#">mail</a> ] 
[ <a href="#">extensions</a> ] 
<br>
<br>
<div id="configContent">
	{include file="file:$path/configuration/system_info.tpl.php"}
</div>

<h2>Mail</h2>

<a name="routing"></a>
<span id="configMailboxRouting">{include file="file:$path/configuration/mail_routing.tpl.php"}</span>

<br>

<h2>Workflow</h2>

<a name="pop3"></a>
{include file="file:$path/configuration/pop3_accounts.tpl.php"}

<a name="mailboxes"></a>
{include file="file:$path/configuration/mailboxes.tpl.php"}

<a name="workers"></a>
{include file="file:$path/configuration/agents.tpl.php"}

<a name="teams"></a>
{include file="file:$path/configuration/teams.tpl.php"}

<script>
	var configAjax = new cConfigAjax();
</script>