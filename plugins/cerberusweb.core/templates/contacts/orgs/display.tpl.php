{include file="file:$path/contacts/submenu.tpl.php"}

<table cellpadding="2" cellspacing="0" border="0">
<tr>
	<td><h1>{$contact->name}</h1></td>
	<td>(<a href="javascript:;" onclick="genericAjaxPanel('c=contacts&a=showOrgPeek&id={$contact->id}=&view_id=',null,false,'500px',ajax.cbOrgCountryPeek);">edit</a>)</td>
</tr>
</table>

<div id="contactOptions"></div> 
<br>

<script type="text/javascript">
{literal}
var tabView = new YAHOO.widget.TabView();

tabView.addTab( new YAHOO.widget.Tab({
    label: 'Mail History',
    dataSrc: '{/literal}{devblocks_url}ajax.php?c=contacts&a=showTabHistory&org={$contact->id}{/devblocks_url}{literal}',
    cacheData: true,
    active: true
}));

tabView.addTab( new YAHOO.widget.Tab({
    label: 'People ({/literal}{$people_total}{literal})',
    dataSrc: '{/literal}{devblocks_url}ajax.php?c=contacts&a=showTabPeople&org={$contact->id}{/devblocks_url}{literal}',
    cacheData: true
}));

tabView.addTab( new YAHOO.widget.Tab({
    label: 'Tasks ({/literal}{$tasks_total}{literal})',
    dataSrc: '{/literal}{devblocks_url}ajax.php?c=contacts&a=showTabTasks&org={$contact->id}{/devblocks_url}{literal}',
    cacheData: true
}));

var tabDetails = tabView.getTab(0);

{/literal}

{* Add any plugin-contributed tabs to the addresses view *}
{foreach from=$tab_manifests item=tab_manifest}
{literal}tabView.addTab( new YAHOO.widget.Tab({{/literal}
    label: '{$tab_manifest->params.title}',
    dataSrc: '{devblocks_url}ajax.php?c=contacts&a=showTab&ext_id={$tab_manifest->id}&org_id={$contact->id}{/devblocks_url}',
    {if $tab==$tab_manifest->params.uri}active: true,{/if}
    cacheData: false
{literal}}));{/literal}
{/foreach}

tabView.appendTo('contactOptions');
</script>
