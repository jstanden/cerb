{*include file="file:$path/contacts/menu.tpl.php"*}

<h1>Organization: {$contact->name}</h1>
<br>

<div id="contactOptions"></div> 
<br>

<script>
{literal}
var tabView = new YAHOO.widget.TabView();

tabView.addTab( new YAHOO.widget.Tab({
    label: 'Details',
    dataSrc: '{/literal}{devblocks_url}ajax.php?c=contacts&a=showTabDetails&org={$contact->id}{/devblocks_url}{literal}',
    cacheData: true,
    active: true
}));

tabView.addTab( new YAHOO.widget.Tab({
    label: 'People',
    dataSrc: '{/literal}{devblocks_url}ajax.php?c=contacts&a=showTabPeople&org={$contact->id}{/devblocks_url}{literal}',
    cacheData: true
}));

tabView.addTab( new YAHOO.widget.Tab({
    label: 'History',
    dataSrc: '{/literal}{devblocks_url}ajax.php?c=contacts&a=showTabHistory&org={$contact->id}{/devblocks_url}{literal}',
    cacheData: true
}));

tabView.appendTo('contactOptions');
{/literal}
</script>

