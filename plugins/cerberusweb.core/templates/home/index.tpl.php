<div id="headerSubMenu">
	<div style="padding-bottom:5px;">
	</div>
</div>

<div id="homeOptions"></div> 
<br>

<script type="text/javascript">
{literal}
var tabView = new YAHOO.widget.TabView();

tabView.addTab( new YAHOO.widget.Tab({
    label: '{/literal}{'home.tab.my_notifications'|devblocks_translate}{literal}',
    dataSrc: '{/literal}{devblocks_url}ajax.php?c=home&a=showMyEvents{/devblocks_url}{literal}',
    cacheData: false,
    {/literal}active: {if empty($tab_selected) || 'events'==$tab_selected}true{else}false{/if}{literal}
}));

tabView.addTab( new YAHOO.widget.Tab({
    label: '{/literal}{'home.tab.my_mail'|devblocks_translate}{literal}',
    dataSrc: '{/literal}{devblocks_url}ajax.php?c=home&a=showMyTickets{/devblocks_url}{literal}',
    cacheData: false,
    {/literal}active: {if 'tickets'==$tab_selected}true{else}false{/if}{literal}
}));

tabView.addTab( new YAHOO.widget.Tab({
    label: '{/literal}{'home.tab.my_tasks'|devblocks_translate}{literal}',
    dataSrc: '{/literal}{devblocks_url}ajax.php?c=home&a=showMyTasks{/devblocks_url}{literal}',
    cacheData: false,
    {/literal}active: {if 'tasks'==$tab_selected}true{else}false{/if}{literal}
}));

{/literal}

{foreach from=$tab_manifests item=tab_manifest}
{literal}tabView.addTab( new YAHOO.widget.Tab({{/literal}
    label: '{$tab_manifest->params.title|devblocks_translate}',
    dataSrc: '{devblocks_url}ajax.php?c=home&a=showTab&ext_id={$tab_manifest->id}{/devblocks_url}',
    {if $tab_selected==$tab_manifest->params.uri}active: true,{/if}
    cacheData: false
{literal}}));{/literal}
{/foreach}

tabView.appendTo('homeOptions');
</script>


