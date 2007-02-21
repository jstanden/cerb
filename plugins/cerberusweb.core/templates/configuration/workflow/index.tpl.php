{include file="file:$path/configuration/menu.tpl.php"}
<br>

<h2>Workflow</h2>

<a name="workers"></a>
{include file="file:$path/configuration/workflow/agents.tpl.php"}

<a name="teams"></a>
{include file="file:$path/configuration/workflow/teams.tpl.php"}

<script>
	var configAjax = new cConfigAjax();
</script>