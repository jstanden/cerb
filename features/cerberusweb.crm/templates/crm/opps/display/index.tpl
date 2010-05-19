{include file="$path/crm/submenu.tpl"}

<table cellspacing="0" cellpadding="0" border="0" width="100%" style="padding-bottom:5px;">
<tr>
	<td valign="top" style="padding-right:5px;">
		<h1>{$opp->name}</h1> 
		<form action="{devblocks_url}{/devblocks_url}" onsubmit="return false;">
		{assign var=opp_worker_id value=$opp->worker_id}
		
		<b>{'common.status'|devblocks_translate|capitalize}:</b> {if $opp->is_closed}{if $opp->is_won}{'crm.opp.status.closed.won'|devblocks_translate|capitalize}{else}{'crm.opp.status.closed.lost'|devblocks_translate|capitalize}{/if}{else}{'crm.opp.status.open'|devblocks_translate|capitalize}{/if} &nbsp;
		<button id="btnOppAddyPeek" type="button" onclick="genericAjaxPanel('c=contacts&a=showAddressPeek&email={$address->email|escape:'url'}&view_id=',null,false,'500');" style="visibility:false;display:none;"></button>
		<b>{'common.email'|devblocks_translate|capitalize}:</b> {$address->first_name} {$address->last_name} &lt;<a href="javascript:;" onclick="document.getElementById('btnOppAddyPeek').click();">{$address->email}</a>&gt; &nbsp;
		<b>{'crm.opportunity.created_date'|devblocks_translate|capitalize}:</b> <abbr title="{$opp->created_date|devblocks_date}">{$opp->created_date|devblocks_prettytime}</abbr> &nbsp;
		{if !empty($opp_worker_id) && isset($workers.$opp_worker_id)}
			<b>{'common.worker'|devblocks_translate|capitalize}:</b> {$workers.$opp_worker_id->getName()} &nbsp;
		{/if}
		</form>
		<br>
	</td>
</tr>
</table>

<div id="oppTabs">
	<ul>
		<li><a href="{devblocks_url}ajax.php?c=crm&a=showOppNotesTab&id={$opp->id}{/devblocks_url}">{'crm.opp.tab.notes'|devblocks_translate|escape}</a></li>
		<li><a href="{devblocks_url}ajax.php?c=crm&a=showOppPropertiesTab&id={$opp->id}{/devblocks_url}">{'crm.opp.tab.properties'|devblocks_translate|escape}</a></li>
		<li><a href="{devblocks_url}ajax.php?c=crm&a=showOppMailTab&id={$opp->id}{/devblocks_url}">{'crm.opp.tab.mail_history'|devblocks_translate|escape}</a></li>
		<li><a href="{devblocks_url}ajax.php?c=crm&a=showOppTasksTab&id={$opp->id}{/devblocks_url}">{'crm.opp.tab.tasks'|devblocks_translate:$tasks_total|escape}</a></li>

		{$tabs = [notes,properties,mail,tasks]}

		{foreach from=$tab_manifests item=tab_manifest}
			{$tabs[] = $tab_manifest->params.uri}
			<li><a href="{devblocks_url}ajax.php?c=config&a=showTab&ext_id={$tab_manifest->id}{/devblocks_url}"><i>{$tab_manifest->params.title|devblocks_translate|escape:'quotes'}</i></a></li>
		{/foreach}
	</ul>
</div> 
<br>

{$tab_selected_idx=0}
{foreach from=$tabs item=tab_label name=tabs}
	{if $tab_label==$tab_selected}{$tab_selected_idx = $smarty.foreach.tabs.index}{/if}
{/foreach}

<script type="text/javascript">
	$(function() {
		var tabs = $("#oppTabs").tabs( { selected:{$tab_selected_idx} } );
	});
</script>

<script type="text/javascript">
{if $pref_keyboard_shortcuts}
CreateKeyHandler(function doShortcuts(e) {

	var mycode = getKeyboardKey(e, true);
	
	switch(mycode) {
		case 65:  // (A) E-mail Peek
			try {
				document.getElementById('btnOppAddyPeek').click();
			} catch(e) { } 
			break;
		case 81:  // (Q) quick compose
			try {
				document.getElementById('btnQuickCompose').click();
			} catch(e) { } 
			break;
		case 219:  // [ - prev page
			try {
				document.getElementById('btnPagePrev').click();
			} catch(e) { } 
			break;
		case 221:  // ] - next page
			try {
				document.getElementById('btnPageNext').click();
			} catch(e) { } 
			break;
		default:
			// We didn't find any obvious keys, try other codes
			break;
	}
});
{/if}
</script>
