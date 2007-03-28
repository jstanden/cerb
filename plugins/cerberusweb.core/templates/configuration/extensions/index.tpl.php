{include file="file:$path/configuration/menu.tpl.php"}
<br>

<h2>Synchronization</h2>
<br>
<form action="{devblocks_url}{/devblocks_url}" method="post">
<input type="hidden" name="c" value="config">
<input type="hidden" name="a" value="refreshPlugins">
<input type="submit" value="Scan for plugin changes">
</form>
<br>

<h2>Plugins</h2>
<form action="{devblocks_url}{/devblocks_url}" method="post">
<input type="hidden" name="c" value="config">
<input type="hidden" name="a" value="savePlugins">

<ul style="list-style:none;margin-left:0;padding-left:1em;text-indent:1em;">
{foreach from=$plugins item=plugin}
		<li style='padding-bottom:5px;'>
			<div style="border:1px solid rgb(0,120,0);background-color:rgb(240,240,255);width:750px;" id="config_plugin_{$plugin->id}">
			<table cellpadding="5" cellspacing="0" border="0" width="100%">
				<tr>
					<td nowrap="nowrap" width="0%" valign="top">
						<input type="checkbox" name="plugins_enabled[]" value="{$plugin->id}" {if $plugin->enabled}checked{/if}>
					</td>
					<td width="100%" onclick="checkAll('config_plugin_{$plugin->id}');">
						<span style="font-weight:bold;color:rgb(0,120,0);">{$plugin->name}</span> (Revision: {$plugin->revision})<br>
						{$plugin->description}
					</td>
				</tr>
			</table>
			</div>
		</li>
{foreachelse}
	<li>No extensions installed.</li>
{/foreach}
</ul>

<input type="submit" value="{$translate->say('common.save_changes')}">
</form>
<br>

<h2>Extensions</h2>
{if !empty($points)}
<ul style="list-style:none;margin-left:0;padding-left:1em;text-indent:1em;">
	{foreach from=$points key=point item=p}
		<li><b>{$point}</b></li>
		{if !empty($p->extensions)}
		<ul>
		{foreach from=$p->extensions item=extension}
			<li><a href="javascript:;">{$extension->name}</a> (<i>{$extension->plugin_id}</i>)</li>
		{/foreach}
		</ul>
		<br>
		{/if}
	{/foreach}
</ul>
<br>
{/if}

<script>
	var configAjax = new cConfigAjax();
</script>