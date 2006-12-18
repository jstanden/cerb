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

<h2>Workflow</h2>

{include file="file:$path/configuration/pop3_accounts.tpl.php"}

{include file="file:$path/configuration/mailboxes.tpl.php"}

{include file="file:$path/configuration/agents.tpl.php"}

{include file="file:$path/configuration/teams.tpl.php"}

<script>
	var configAjax = new cConfigAjax();
</script>