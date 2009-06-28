{include file="$path/crm/submenu.tpl"}

<table cellspacing="0" cellpadding="0" border="0" width="100%" style="padding-bottom:5px;">
<tr>
	<td valign="top" style="padding-right:5px;">
		<h1>{$opp->name}</h1> 
		<form action="{devblocks_url}{/devblocks_url}" onsubmit="return false;">
		{assign var=opp_worker_id value=$opp->worker_id}
		
		<b>Status:</b> {if $opp->is_closed}{if $opp->is_won}Closed/Won{else}Closed/Lost{/if}{else}Open{/if} &nbsp;
		<button id="btnOppAddyPeek" type="button" onclick="genericAjaxPanel('c=contacts&a=showAddressPeek&email={$address->email|escape:'javascript'}&view_id=',null,false,'500px',ajax.cbAddressPeek);" style="visibility:false;display:none;"></button>
		<b>E-mail:</b> {$address->first_name} {$address->last_name} &lt;<a href="javascript:;" onclick="document.getElementById('btnOppAddyPeek').click();">{$address->email}</a>&gt; &nbsp;
		<b>Created:</b> <abbr title="{$opp->created_date|devblocks_date}">{$opp->created_date|devblocks_prettytime}</abbr> &nbsp;
		{if !empty($opp_worker_id) && isset($workers.$opp_worker_id)}
			<b>Worker:</b> {$workers.$opp_worker_id->getName()} &nbsp;
		{/if}
		</form>
		<br>
	</td>
	<td align="right" valign="top">
		{if !empty($series_stats.next) || !empty($series_stats.prev)}
		<table cellpadding="0" cellspacing="0" border="0" style="margin:0px;">
			<tr>
				<td>	
				<div style="padding:10px;margin-top:0px;border:1px solid rgb(180,180,255);background-color:rgb(245,245,255);text-align:center;">
					Active list: <b>{$series_stats.title}</b><br>
					{if !empty($series_stats.prev)}<button style="display:none;visibility:hidden;" id="btnPagePrev" onclick="document.location='{devblocks_url}c=crm&a=opps&id={$series_stats.prev}{/devblocks_url}';">&laquo;Prev</button><a href="{devblocks_url}c=crm&a=opps&id={$series_stats.prev}{/devblocks_url}">&laquo;Prev</a>{/if} 
					 ({$series_stats.cur}-{$series_stats.count} of {$series_stats.total}) 
					{if !empty($series_stats.next)}<button style="display:none;visibility:hidden;" id="btnPageNext" onclick="document.location='{devblocks_url}c=crm&a=opps&id={$series_stats.next}{/devblocks_url}';">Next&raquo;</button><a href="{devblocks_url}c=crm&a=opps&id={$series_stats.next}{/devblocks_url}">Next&raquo;</a>{/if}
				</div>
				</td>
			</tr>
		</table>
		{/if}
	</td>
</tr>
</table>

<div id="displayCrmTabs"></div> 
<br>

<script type="text/javascript">
{literal}
var tabView = new YAHOO.widget.TabView();

tabView.addTab( new YAHOO.widget.Tab({
    label: 'Notes',
    dataSrc: '{/literal}{devblocks_url}ajax.php?c=crm&a=showOppNotesTab&id={$opp->id}{/devblocks_url}{literal}',
    cacheData: true,
    {/literal}active: {if 'notes'==$tab_selected || empty($tab_selected)}true{else}false{/if}{literal}
}));

tabView.addTab( new YAHOO.widget.Tab({
    label: 'Properties',
    dataSrc: '{/literal}{devblocks_url}ajax.php?c=crm&a=showOppPropertiesTab&id={$opp->id}{/devblocks_url}{literal}',
    cacheData: true,
    {/literal}active: {if 'properties'==$tab_selected}true{else}false{/if}{literal}
}));

tabView.addTab( new YAHOO.widget.Tab({
    label: 'Mail History',
    dataSrc: '{/literal}{devblocks_url}ajax.php?c=crm&a=showOppMailTab&id={$opp->id}{/devblocks_url}{literal}',
    cacheData: true,
    {/literal}active: {if 'mail'==$tab_selected}true{else}false{/if}{literal}
}));

tabView.addTab( new YAHOO.widget.Tab({
    label: 'Tasks ({/literal}{$tasks_total|string_format:'%d'}{literal})',
    dataSrc: '{/literal}{devblocks_url}ajax.php?c=crm&a=showOppTasksTab&id={$opp->id}{/devblocks_url}{literal}',
    cacheData: true,
    {/literal}active: {if 'tasks'==$tab_selected}true{else}false{/if}{literal}
}));

{/literal}

{*
{foreach from=$tab_manifests item=tab_manifest}
{literal}tabView.addTab( new YAHOO.widget.Tab({{/literal}
    label: '{$tab_manifest->params.title|escape:'quotes'}',
    dataSrc: '{devblocks_url}ajax.php?c=crm&a=showTab&ext_id={$tab_manifest->id}&id={$opp->id}{/devblocks_url}',
    {if $tab==$tab_manifest->params.uri}active: true,{/if}
    cacheData: false
{literal}}));{/literal}
{/foreach}
*}

tabView.appendTo('displayCrmTabs');
</script>

<script type="text/javascript">
{literal}
CreateKeyHandler(function doShortcuts(e) {

	var mykey = getKeyboardKey(e);
	
	switch(mykey) {
		case "a":  // E-mail Peek
		case "A":
			try {
				document.getElementById('btnOppAddyPeek').click();
			} catch(e){}
			break;
		case "q":  // quick compose
		case "Q":
			try {
				document.getElementById('btnQuickCompose').click();
			} catch(e){}
			break;
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
