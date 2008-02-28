{include file="file:$path/community/submenu.tpl.php"}

<h1>Communities</h1>
<br>

<div id="toolTabs"></div> 
<br>

<script>
{literal}
var tabView = new YAHOO.widget.TabView();

tabView.addTab( new YAHOO.widget.Tab({
    label: 'Configuration',
    dataSrc: '{/literal}{devblocks_url}ajax.php?c=community&a=showToolConfig&portal={$portal}{/devblocks_url}{literal}',
    cacheData: true,
    active: true
}));

tabView.addTab( new YAHOO.widget.Tab({
    label: 'Installation',
    dataSrc: '{/literal}{devblocks_url}ajax.php?c=community&a=showToolInstall&portal={$portal}{/devblocks_url}{literal}',
    cacheData: true
}));

tabView.appendTo('toolTabs');
{/literal}
</script>
