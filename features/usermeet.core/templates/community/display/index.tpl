{include file="$path/community/display/submenu.tpl"}

{if !empty($tool->name)}
	<h1>{$tool->name|escape}</h1>
{else}
	{$tool_extid = $tool->extension_id}
	{if isset($tool_manifests.$tool_extid)}
		<h1>{$tool_manifests.$tool_extid->name}</h1>
	{else}
		<h1>Community Portal</h1>
	{/if}
{/if}

{$translate->_('usermeet.ui.community.cfg.profile_id')} <b>{$tool->code}</b><br>
<br>

<div id="communityToolTabs"></div> 
<br>

<script type="text/javascript">
{literal}
var tabView = new YAHOO.widget.TabView();

tabView.addTab( new YAHOO.widget.Tab({
    label: '{/literal}{'Settings'|devblocks_translate|escape}{literal}',
    dataSrc: '{/literal}{devblocks_url}ajax.php?c=community&a=showTabSettings&id={$tool->id}{/devblocks_url}{literal}',
    cacheData: false,
    {/literal}active: {if 'settings'==$tab_selected || empty($tab_selected)}true{else}false{/if}{literal}
}));

tabView.addTab( new YAHOO.widget.Tab({
    label: '{/literal}{'Custom Templates'|devblocks_translate|escape}{literal}',
    dataSrc: '{/literal}{devblocks_url}ajax.php?c=community&a=showTabTemplates&id={$tool->id}{/devblocks_url}{literal}',
    cacheData: false,
    {/literal}active: {if 'templates'==$tab_selected}true{else}false{/if}{literal}
}));

tabView.addTab( new YAHOO.widget.Tab({
    label: '{/literal}{'Installation'|devblocks_translate|escape}{literal}',
    dataSrc: '{/literal}{devblocks_url}ajax.php?c=community&a=showTabInstallation&id={$tool->id}{/devblocks_url}{literal}',
    cacheData: false,
    {/literal}active: {if 'installation'==$tab_selected}true{else}false{/if}{literal}
}));

{/literal}

{*
{foreach from=$tab_manifests item=tab_manifest}
{literal}tabView.addTab( new YAHOO.widget.Tab({{/literal}
    label: '{$tab_manifest->params.title|escape:'quotes'}',
    dataSrc: '{devblocks_url}ajax.php?c=community&a=showTab&ext_id={$tab_manifest->id}&id={$tool->id}{/devblocks_url}',
    {if $tab==$tab_manifest->params.uri}active: true,{/if}
    cacheData: false
{literal}}));{/literal}
{/foreach}
*}

tabView.appendTo('communityToolTabs');
</script>
