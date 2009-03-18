<div id="headerSubMenu">
	<div style="padding-bottom:5px;"></div>
</div>

<div id="researchTabs"></div> 
<br>

<script type="text/javascript">
{literal}
var tabView = new YAHOO.widget.TabView();
{/literal}

{*
tabView.addTab( new YAHOO.widget.Tab({
    label: '{/literal}{$translate->_('research.tab.links')|escape:'quotes'}{literal}',
    dataSrc: '{/literal}{devblocks_url}ajax.php?c=research&a=showLinksTab&request={$request_path|escape:'url'}{/devblocks_url}{literal}',
    cacheData: false,
    {/literal}active: {if empty($selected_tab) || 'links'==$selected_tab}true{else}false{/if}{literal}
}));
*}

{foreach from=$tab_manifests item=tab_manifest}
{if !isset($tab_manifest->params.acl) || $worker->hasPriv($tab_manifest->params.acl)}
{literal}tabView.addTab(new YAHOO.widget.Tab({{/literal}
    label: '{$tab_manifest->params.title|devblocks_translate|escape:'quotes'}',
    dataSrc: '{devblocks_url}ajax.php?c=research&a=showTab&ext_id={$tab_manifest->id}&request={$request_path|escape:'url'}{/devblocks_url}',
    {if $tab_selected==$tab_manifest->params.uri}active: true,{/if}
    cacheData: false
{literal}}));{/literal}
{/if}
{/foreach}

tabView.appendTo('researchTabs');
</script>
