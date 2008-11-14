<div id="headerSubMenu">
	<div style="padding-bottom:5px;">
	{*
	<div style="padding:5px;">
	<a href="{devblocks_url}c=tickets&a=overview{/devblocks_url}">overview</a>
	*} 
	</div>
</div> 

<h1>Setup</h1>
{if $smarty.const.DEMO_MODE}
<div style="color:red;padding:2px;font-weight:bold;">NOTE: This helpdesk is in Demo Mode and changes will not be saved.</div>
{/if}

{if $install_dir_warning}
<div class="error">
	Warning: The 'install' directory still exists.  This is a potential security risk.  Please delete it.
</div>
{/if}

<div id="tourConfigMenu"></div>
<div id="configTabs"></div>

<script type="text/javascript">
{literal}
var tabView = new YAHOO.widget.TabView();

tabView.addTab( new YAHOO.widget.Tab({
    label: 'System',
    dataSrc: '{/literal}{devblocks_url}ajax.php?c=config&a=showTabSettings{/devblocks_url}{literal}',
    cacheData: false,
    {/literal}active: {if empty($tab_selected) || $tab_selected=="settings"}true{else}false{/if}{literal}
}));

tabView.addTab( new YAHOO.widget.Tab({
    label: 'Features &amp; Plugins',
    dataSrc: '{/literal}{devblocks_url}ajax.php?c=config&a=showTabPlugins{/devblocks_url}{literal}',
    cacheData: false,
    {/literal}active: {if $tab_selected=="plugins"}true{else}false{/if}{literal}
}));

tabView.addTab( new YAHOO.widget.Tab({
    label: 'Mail Setup',
    dataSrc: '{/literal}{devblocks_url}ajax.php?c=config&a=showTabMail{/devblocks_url}{literal}',
    cacheData: false,
    {/literal}active: {if $tab_selected=="mail"}true{else}false{/if}{literal}
}));

tabView.addTab( new YAHOO.widget.Tab({
    label: 'Pre-Parser',
    dataSrc: '{/literal}{devblocks_url}ajax.php?c=config&a=showTabPreParser{/devblocks_url}{literal}',
    cacheData: false,
    {/literal}active: {if $tab_selected=="preparser"}true{else}false{/if}{literal}
}));

tabView.addTab( new YAHOO.widget.Tab({
    label: 'Mail Routing',
    dataSrc: '{/literal}{devblocks_url}ajax.php?c=config&a=showTabParser{/devblocks_url}{literal}',
    cacheData: false,
    {/literal}active: {if $tab_selected=="parser"}true{else}false{/if}{literal}
}));

tabView.addTab( new YAHOO.widget.Tab({
    label: 'Attachments',
    dataSrc: '{/literal}{devblocks_url}ajax.php?c=config&a=showTabAttachments{/devblocks_url}{literal}',
    cacheData: false,
    {/literal}active: {if $tab_selected=="attachments"}true{else}false{/if}{literal}
}));

tabView.addTab( new YAHOO.widget.Tab({
    label: 'Scheduler',
    dataSrc: '{/literal}{devblocks_url}ajax.php?c=config&a=showTabScheduler{/devblocks_url}{literal}',
    cacheData: false,
    {/literal}active: {if $tab_selected=="scheduler"}true{else}false{/if}{literal}
}));

tabView.addTab( new YAHOO.widget.Tab({
    label: 'Workers',
    dataSrc: '{/literal}{devblocks_url}ajax.php?c=config&a=showTabWorkers{/devblocks_url}{literal}',
    cacheData: false,
    {/literal}active: {if $tab_selected=="workers"}true{else}false{/if}{literal}
}));

tabView.addTab( new YAHOO.widget.Tab({
    label: 'Groups',
    dataSrc: '{/literal}{devblocks_url}ajax.php?c=config&a=showTabGroups{/devblocks_url}{literal}',
    cacheData: false,
    {/literal}active: {if $tab_selected=="groups"}true{else}false{/if}{literal}
}));

tabView.addTab( new YAHOO.widget.Tab({
    label: 'Knowledgebase',
    dataSrc: '{/literal}{devblocks_url}ajax.php?c=config&a=showTabKb{/devblocks_url}{literal}',
    cacheData: false,
    {/literal}active: {if $tab_selected=="kb"}true{else}false{/if}{literal}
}));

tabView.addTab( new YAHOO.widget.Tab({
    label: 'Service Levels',
    dataSrc: '{/literal}{devblocks_url}ajax.php?c=config&a=showTabSla{/devblocks_url}{literal}',
    cacheData: false,
    {/literal}active: {if $tab_selected=="sla"}true{else}false{/if}{literal}
}));

tabView.addTab( new YAHOO.widget.Tab({
    label: 'Custom Fields',
    dataSrc: '{/literal}{devblocks_url}ajax.php?c=config&a=showTabFields{/devblocks_url}{literal}',
    cacheData: false,
    {/literal}active: {if $tab_selected=="fields"}true{else}false{/if}{literal}
}));

tabView.addTab( new YAHOO.widget.Tab({
    label: 'Fetch &amp; Retrieve',
    dataSrc: '{/literal}{devblocks_url}ajax.php?c=config&a=showTabFnr{/devblocks_url}{literal}',
    cacheData: false,
    {/literal}active: {if $tab_selected=="fnr"}true{else}false{/if}{literal}
}));

{/literal}

{foreach from=$tab_manifests item=tab_manifest}
{literal}tabView.addTab( new YAHOO.widget.Tab({{/literal}
    label: '{$tab_manifest->params.title}',
    dataSrc: '{devblocks_url}ajax.php?c=config&a=showTab&ext_id={$tab_manifest->id}{/devblocks_url}',
    {if $tab_selected==$tab_manifest->params.uri}active: true,{/if}
    cacheData: false
{literal}}));{/literal}
{/foreach}

tabView.appendTo('configTabs');
</script>

<br>

<script type="text/javascript">
	var configAjax = new cConfigAjax();
</script>
