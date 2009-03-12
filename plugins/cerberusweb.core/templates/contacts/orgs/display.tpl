<div id="headerSubMenu">
	<div style="padding:5px;">
		<a href="{devblocks_url}c=contacts{/devblocks_url}">address book</a>
		 &raquo; 
		<a href="{devblocks_url}c=contacts&a=orgs{/devblocks_url}">organizations</a>
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
			Phone: {$contact->phone}
			<br>
		{/if}
		{if !empty($contact->website)}<a href="{$contact->website}" target="_blank">{$contact->website}</a>{/if}
		<br>
		<br>
	</td>
	<td align="right" valign="top">
		{if !empty($series_stats.next) || !empty($series_stats.prev)}
		<table cellpadding="0" cellspacing="0" border="0" style="margin:0px;">
			<tr>
				<td>	
				<div style="padding:10px;margin-top:0px;border:1px solid rgb(180,180,255);background-color:rgb(245,245,255);text-align:center;">
					Active list: <b>{$series_stats.title}</b><br>
					{if !empty($series_stats.prev)}<button style="display:none;visibility:hidden;" id="btnPagePrev" onclick="document.location='{devblocks_url}c=contacts&a=orgs&d=display&id={$series_stats.prev}{/devblocks_url}';">&laquo;Prev</button><a href="{devblocks_url}c=contacts&a=orgs&d=display&id={$series_stats.prev}{/devblocks_url}">&laquo;Prev</a>{/if} 
					 ({$series_stats.cur}-{$series_stats.count} of {$series_stats.total}) 
					{if !empty($series_stats.next)}<button style="display:none;visibility:hidden;" id="btnPageNext" onclick="document.location='{devblocks_url}c=contacts&a=orgs&d=display&id={$series_stats.next}{/devblocks_url}';">Next&raquo;</button><a href="{devblocks_url}c=contacts&a=orgs&d=display&id={$series_stats.next}{/devblocks_url}">Next&raquo;</a>{/if}
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
    label: 'Notes',
    dataSrc: '{/literal}{devblocks_url}ajax.php?c=contacts&a=showTabNotes&org={$contact->id}{/devblocks_url}{literal}',
    cacheData: true,
    active: true
}));

tabView.addTab( new YAHOO.widget.Tab({
    label: 'Properties',
    dataSrc: '{/literal}{devblocks_url}ajax.php?c=contacts&a=showTabProperties&org={$contact->id}{/devblocks_url}{literal}',
    cacheData: false
}));

tabView.addTab( new YAHOO.widget.Tab({
    label: 'Mail History',
    dataSrc: '{/literal}{devblocks_url}ajax.php?c=contacts&a=showTabHistory&org={$contact->id}{/devblocks_url}{literal}',
    cacheData: true
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
    label: '{$tab_manifest->params.title|escape:'quotes'}',
    dataSrc: '{devblocks_url}ajax.php?c=contacts&a=showTab&ext_id={$tab_manifest->id}&org_id={$contact->id}{/devblocks_url}',
    {if $tab==$tab_manifest->params.uri}active: true,{/if}
    cacheData: false
{literal}}));{/literal}
{/foreach}

tabView.appendTo('contactOptions');
</script>

<script type="text/javascript">
{literal}
CreateKeyHandler(function doShortcuts(e) {

	var mykey = getKeyboardKey(e);
	
	switch(mykey) {
//		case "q":  // quick compose
//		case "Q":
//			try {
//				document.getElementById('btnQuickCompose').click();
//			} catch(e){}
//			break;
		default:
			// We didn't find any obvious keys, try other codes
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
			}
			break;
	}
});
{/literal}
</script>

