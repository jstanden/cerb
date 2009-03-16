<div id="headerSubMenu">
	<div style="padding-bottom:5px;"></div>
</div>

<div id="addyBookTabs"></div> 
<br>

<script type="text/javascript">
{literal}
var tabView = new YAHOO.widget.TabView();

tabView.addTab( new YAHOO.widget.Tab({
    label: '{/literal}{$translate->_('addy_book.tab.organizations')|escape:'quotes'}{literal}',
    dataSrc: '{/literal}{devblocks_url}ajax.php?c=contacts&a=showOrgsTab{/devblocks_url}{literal}',
    cacheData: false,
    {/literal}active: {if empty($selected_tab) || 'orgs'==$selected_tab}true{else}false{/if}{literal}
}));

tabView.addTab( new YAHOO.widget.Tab({
    label: '{/literal}{$translate->_('addy_book.tab.addresses')|escape:'quotes'}{literal}',
    dataSrc: '{/literal}{devblocks_url}ajax.php?c=contacts&a=showAddysTab{/devblocks_url}{literal}',
    cacheData: false,
    {/literal}active: {if 'addresses'==$selected_tab}true{else}false{/if}{literal}
}));

{/literal}{if $active_worker->hasPriv('core.addybook.import')}{literal}
tabView.addTab( new YAHOO.widget.Tab({
    label: '{/literal}{$translate->_('addy_book.tab.import')|escape:'quotes'}{literal}',
    dataSrc: '{/literal}{devblocks_url}ajax.php?c=contacts&a=showImportTab{/devblocks_url}{literal}',
    cacheData: false,
    {/literal}active: {if 'import'==$selected_tab}true{else}false{/if}{literal}
}));
{/literal}{/if}{literal}

{/literal}

tabView.appendTo('addyBookTabs');
</script>
