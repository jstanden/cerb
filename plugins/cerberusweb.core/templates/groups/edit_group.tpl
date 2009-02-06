<h2>{$team->name|escape}</h2>
<div id="groupTabs"></div>

<script type="text/javascript">
{literal}
var tabView = new YAHOO.widget.TabView();

tabView.addTab( new YAHOO.widget.Tab({
    label: 'Workflow',
    dataSrc: '{/literal}{devblocks_url}ajax.php?c=groups&a=showTabBuckets&id={$team->id}{/devblocks_url}{literal}',
    cacheData: false,
    {/literal}active: {if empty($tab_selected) || $tab_selected=="buckets"}true{else}false{/if}{literal}
}));

tabView.addTab( new YAHOO.widget.Tab({
    label: 'Mail Preferences',
    dataSrc: '{/literal}{devblocks_url}ajax.php?c=groups&a=showTabMail&id={$team->id}{/devblocks_url}{literal}',
    cacheData: false,
    {/literal}active: {if $tab_selected=="settings"}true{else}false{/if}{literal}
}));

tabView.addTab( new YAHOO.widget.Tab({
    label: 'Inbox Filtering',
    dataSrc: '{/literal}{devblocks_url}ajax.php?c=groups&a=showTabInbox&id={$team->id}{/devblocks_url}{literal}',
    cacheData: false,
    {/literal}active: {if $tab_selected=="inbox"}true{else}false{/if}{literal}
}));

tabView.addTab( new YAHOO.widget.Tab({
    label: 'Members',
    dataSrc: '{/literal}{devblocks_url}ajax.php?c=groups&a=showTabMembers&id={$team->id}{/devblocks_url}{literal}',
    cacheData: false,
    {/literal}active: {if $tab_selected=="members"}true{else}false{/if}{literal}
}));

tabView.addTab( new YAHOO.widget.Tab({
    label: 'Ticket Fields',
    dataSrc: '{/literal}{devblocks_url}ajax.php?c=groups&a=showTabFields&id={$team->id}{/devblocks_url}{literal}',
    cacheData: false,
    {/literal}active: {if $tab_selected=="fields"}true{else}false{/if}{literal}
}));

{/literal}

tabView.appendTo('groupTabs');
</script>

<br>
