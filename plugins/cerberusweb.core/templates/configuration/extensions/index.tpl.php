{include file="file:$path/configuration/menu.tpl.php"}
<br>

<div id="tourConfigExtensionsRefresh"></div>
<div class="block">
<h2>Synchronization</h2>
<form action="{devblocks_url}{/devblocks_url}" method="post">
<input type="hidden" name="c" value="config">
<input type="hidden" name="a" value="refreshPlugins">
<button onclick="this.form.submit();"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/data_find.gif{/devblocks_url}" align="top"> Scan for plugin changes</button>
</form>
</div>
<br>

<h2>Active Plugins</h2>
<form action="{devblocks_url}{/devblocks_url}" method="post">
<input type="hidden" name="c" value="config">
<input type="hidden" name="a" value="savePlugins">

<ul style="list-style:none;margin-left:0;padding-left:1em;text-indent:1em;">
{foreach from=$plugins item=plugin}
		<li style='padding-bottom:5px;'>
			<div style="{if $plugin->enabled}border:1px solid rgb(0,0,120);background-color:rgb(240,240,255);{else}border:1px solid rgb(200,200,200);{/if}width:75%;" id="config_plugin_{$plugin->id}">
			<table cellpadding="2" cellspacing="0" border="0" width="100%">
				<tr>
					<td nowrap="nowrap" width="0%" valign="middle" rowspan="2" style="background-color:{if $plugin->enabled}rgb(200,200,255){else}rgb(200,200,200){/if};border-right:1px solid rgb(180,180,255);">
						<input type="checkbox" name="plugins_enabled[]" value="{$plugin->id}" {if $plugin->enabled}checked{/if}>
					</td>
					<td width="100%" onclick="checkAll('config_plugin_{$plugin->id}');" style="padding-left:5px;">
						<!-- 
						<img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/wgm/icon_plus.gif{/devblocks_url}" align="absmiddle" border="0"> 
						&nbsp;
						 -->
						<span style="font-weight:bold;color:rgb(0,120,0);">{$plugin->name}</span> (Revision: {$plugin->revision})
						<br> 
						by <a href="#" style="font-weight:normal;color:rgb(120,120,120);">{$plugin->author}</a>
					</td>
				</tr>
				<tr>
					<td>
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

<button onclick="this.form.submit();"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/check.gif{/devblocks_url}" align="top"> {$translate->_('common.save_changes')|capitalize}</button>
</form>
<br>

<div class="block">
<h2>Extension Points</h2>
{if !empty($points)}
<ul style="list-style:none;margin-left:0;padding-left:1em;text-indent:1em;">
	{foreach from=$points key=point item=p}
		<li>
			<a href="javascript:;" onclick="toggleDiv('divChConfig_{$point}');" style="text-decoration:none;"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/wgm/icon_plus.gif{/devblocks_url}" align="absmiddle" border="0"></a>
			&nbsp; 
			<a href="javascript:;" onclick="toggleDiv('divChConfig_{$point}');" style="text-decoration:none;"><b>{$point}</b></a>
		</li>
		{if !empty($p->extensions)}
		<ul style="display:none;" id='divChConfig_{$point}'>
		{foreach from=$p->extensions item=extension}
			<li><a href="javascript:;">{$extension->name}</a> (<i>{$extension->plugin_id}</i>)</li>
		{/foreach}
		</ul>
		<br>
		{/if}
	{/foreach}
</ul>
{/if}
</div>

<script>
	var configAjax = new cConfigAjax();
</script>