{include file="file:$path/configuration/menu.tpl.php"}
<br>

<h2>Plugins</h2>

<ul>
{foreach from=$plugins item=plugin name=plugins}
	<li><a href="javascript:;"><img src="{devblocks_url}images/checkbox_on.gif{/devblocks_url}" align="top" border="0"></a> <b>{$plugin->name}</b>
	<ul>
	{foreach from=$plugin->extensions item=e}
	<li>{$e->name}</li>
	{/foreach}
	</ul>
	</li>
	<br>
{/foreach}
</ul>

<script>
	var configAjax = new cConfigAjax();
</script>