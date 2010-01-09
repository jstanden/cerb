<div id="headerSubMenu">
	<div style="padding:5px;">
		<a href="{devblocks_url}c=contacts{/devblocks_url}">{$translate->_('core.menu.address_book')|lower}</a>
		 &raquo; 
		<a href="{devblocks_url}c=contacts&a=orgs{/devblocks_url}">{$translate->_('addy_book.tab.organizations')|lower}</a>
	</div>
</div>

<table cellpadding="2" cellspacing="0" border="0" width="100%">
<tr>
	<td valign="top">
		<h1>{$contact->name}</h1>
		{if !empty($contact->street) || !empty($contact->country)}
			{if !empty($contact->street)}{$contact->street}, {/if}
			{if !empty($contact->city)}{$contact->city}, {/if}
			{if !empty($contact->province)}{$contact->province}, {/if}
			{if !empty($contact->postal)}{$contact->postal} {/if}
			{if !empty($contact->country)}{$contact->country}{/if}
			<br>
		{/if}
		{if !empty($contact->phone)}
			{$translate->_('contact_org.phone')|capitalize}: {$contact->phone}
			<br>
		{/if}
		{if !empty($contact->website)}<a href="{$contact->website}" target="_blank">{$contact->website}</a>{/if}
		<br>
		<br>
	</td>
	<td align="right" valign="top">
		<form action="{devblocks_url}{/devblocks_url}" method="post">
		<input type="hidden" name="c" value="contacts">
		<input type="hidden" name="a" value="doOrgQuickSearch">
		<span><b>{$translate->_('common.quick_search')|capitalize}:</b></span> <select name="type">
			<option value="name">{$translate->_('contact_org.name')|capitalize}</option>
			<option value="phone">{$translate->_('contact_org.phone')|capitalize}</option>
		</select><input type="text" name="query" size="24"><button type="submit">{$translate->_('common.search_go')|lower}</button>
		</form>
		
		{if !empty($series_stats.next) || !empty($series_stats.prev)}
		<table cellpadding="0" cellspacing="0" border="0" style="margin:0px;">
			<tr>
				<td>	
				<div style="padding:10px;margin-top:0px;border:1px solid rgb(180,180,255);background-color:rgb(245,245,255);text-align:center;">
					{$translate->_('display.listnav.active_list')} <b>{$series_stats.title}</b><br>
					{if !empty($series_stats.prev)}<button style="display:none;visibility:hidden;" id="btnPagePrev" onclick="document.location='{devblocks_url}c=contacts&a=orgs&d=display&id={$series_stats.prev}{/devblocks_url}';"></button><a href="{devblocks_url}c=contacts&a=orgs&d=display&id={$series_stats.prev}{/devblocks_url}">&laquo;{$translate->_('common.previous_short')|capitalize}</a>{/if}
					{'display.listnav.showing_of_total'|devblocks_translate:$series_stats.cur:$series_stats.count} 
					{if !empty($series_stats.next)}<button style="display:none;visibility:hidden;" id="btnPageNext" onclick="document.location='{devblocks_url}c=contacts&a=orgs&d=display&id={$series_stats.next}{/devblocks_url}';"></button><a href="{devblocks_url}c=contacts&a=orgs&d=display&id={$series_stats.next}{/devblocks_url}">{$translate->_('common.next')|capitalize}&raquo;</a>{/if}
				</div>
				</td>
			</tr>
		</table>
		{/if}
	</td>
</tr>
</table>

<div id="contactOptions"></div> 
<br>

<script type="text/javascript">
{literal}
var tabView = new YAHOO.widget.TabView();

tabView.addTab( new YAHOO.widget.Tab({
    label: '{/literal}{$translate->_('addy_book.org.tabs.notes')|escape:'quotes'}{literal}',
    dataSrc: '{/literal}{devblocks_url}ajax.php?c=contacts&a=showTabNotes&org={$contact->id}{/devblocks_url}{literal}',
    cacheData: true,
    active: true
}));

tabView.addTab( new YAHOO.widget.Tab({
    label: '{/literal}{$translate->_('addy_book.org.tabs.properties')|escape:'quotes'}{literal}',
    dataSrc: '{/literal}{devblocks_url}ajax.php?c=contacts&a=showTabProperties&org={$contact->id}{/devblocks_url}{literal}',
    cacheData: false
}));

tabView.addTab( new YAHOO.widget.Tab({
    label: '{/literal}{$translate->_('addy_book.org.tabs.mail_history')|escape:'quotes'}{literal}',
    dataSrc: '{/literal}{devblocks_url}ajax.php?c=contacts&a=showTabHistory&org={$contact->id}{/devblocks_url}{literal}',
    cacheData: true
}));

tabView.addTab( new YAHOO.widget.Tab({
    label: '{/literal}{'addy_book.org.tabs.people'|devblocks_translate:$people_total|escape:'quotes'}{literal}',
    dataSrc: '{/literal}{devblocks_url}ajax.php?c=contacts&a=showTabPeople&org={$contact->id}{/devblocks_url}{literal}',
    cacheData: true
}));

tabView.addTab( new YAHOO.widget.Tab({
    label: '{/literal}{'addy_book.org.tabs.tasks'|devblocks_translate:$tasks_total|escape:'quotes'}{literal}',
    dataSrc: '{/literal}{devblocks_url}ajax.php?c=contacts&a=showTabTasks&org={$contact->id}{/devblocks_url}{literal}',
    cacheData: true
}));

var tabDetails = tabView.getTab(0);

{/literal}

{* Add any plugin-contributed tabs to the addresses view *}
{foreach from=$tab_manifests item=tab_manifest}
{literal}tabView.addTab( new YAHOO.widget.Tab({{/literal}
    label: '{$tab_manifest->params.title|devblocks_translate|escape:'quotes'}',
    dataSrc: '{devblocks_url}ajax.php?c=contacts&a=showTab&ext_id={$tab_manifest->id}&org_id={$contact->id}{/devblocks_url}',
    {if $tab==$tab_manifest->params.uri}active: true,{/if}
    cacheData: false
{literal}}));{/literal}
{/foreach}

tabView.appendTo('contactOptions');
</script>

<script type="text/javascript">
{if $pref_keyboard_shortcuts}
{literal}
CreateKeyHandler(function doShortcuts(e) {

	var mycode = getKeyboardKey(e,true);
	
	switch(mycode) {
		case 219:  // [ - prev page
			try {
				document.getElementById('btnPagePrev').click();
			} catch(e){}
			break;
		case 221:  // ] - next page
			try {
				document.getElementById('btnPageNext').click();
			} catch(e){}
			break;
		default:
			// We didn't find any obvious keys, try other codes
	}
});
{/literal}
{/if}
</script>

