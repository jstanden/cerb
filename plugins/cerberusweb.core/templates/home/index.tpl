<div id="headerSubMenu">
	<div style="padding-bottom:5px;">
	</div>
</div>

<div id="homeOptions"></div> 
<br>

<script type="text/javascript">
var tabView = new YAHOO.widget.TabView();

{literal}
tabView.addTab( new YAHOO.widget.Tab({
    label: '{/literal}{'home.tab.my_notifications'|devblocks_translate|escape:'quotes'}{literal}',
    dataSrc: '{/literal}{devblocks_url}ajax.php?c=home&a=showMyEvents{/devblocks_url}{literal}',
    cacheData: false,
    {/literal}active: {if empty($selected_tab) || 'events'==$selected_tab}true{else}false{/if}{literal}
}));
{/literal}

{if empty($workspaces)}
{literal}
tabView.addTab( new YAHOO.widget.Tab({
    label: '{/literal}{'home.tab.workspaces_intro'|devblocks_translate|escape:'quotes'}{literal}',
    dataSrc: '{/literal}{devblocks_url}ajax.php?c=home&a=showWorkspacesIntroTab{/devblocks_url}{literal}',
    cacheData: false,
    active: false
}));
{/literal}
{/if}

{foreach from=$tab_manifests item=tab_manifest}
{literal}tabView.addTab( new YAHOO.widget.Tab({{/literal}
    label: '{$tab_manifest->params.title|devblocks_translate|escape:'quotes'}',
    dataSrc: '{devblocks_url}ajax.php?c=home&a=showTab&ext_id={$tab_manifest->id}{/devblocks_url}',
    {if $selected_tab==$tab_manifest->params.uri}active: true,{/if}
    cacheData: false
{literal}}));{/literal}
{/foreach}

{foreach from=$workspaces item=workspace}
{literal}tabView.addTab( new YAHOO.widget.Tab({{/literal}
    label: '<i>{$workspace|escape}</i>',
    dataSrc: '{devblocks_url}ajax.php?c=home&a=showWorkspaceTab&workspace={$workspace|escape:'url'}{/devblocks_url}',
    cacheData: false,
    active:{if substr($selected_tab,2)==$workspace}true{else}false{/if}
{literal}}));{/literal}
{/foreach}

tabView.appendTo('homeOptions');
</script>


