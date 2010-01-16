{include file="$path/tasks/display/submenu.tpl"}

<table cellspacing="0" cellpadding="0" border="0" width="100%" style="padding-bottom:5px;">
<tr>
	<td valign="top" style="padding-right:5px;">
		<h1>{$task->title|escape}</h1> 
		<form action="{devblocks_url}{/devblocks_url}" onsubmit="return false;">
		<b>{'task.is_completed'|devblocks_translate|capitalize}:</b> {if $task->is_completed}{'common.yes'|devblocks_translate|capitalize}{else}{'common.no'|devblocks_translate|capitalize}{/if} &nbsp;
		{if !empty($task->updated_date)}
		<b>{'task.updated_date'|devblocks_translate|capitalize}:</b> <abbr title="{$task->updated_date|devblocks_date}">{$task->updated_date|devblocks_prettytime}</abbr> &nbsp;
		{/if}
		{if !empty($task->due_date)}
		<b>{'task.due_date'|devblocks_translate|capitalize}:</b> <abbr title="{$task->due_date|devblocks_date}">{$task->due_date|devblocks_prettytime}</abbr> &nbsp;
		{/if}
		{assign var=task_worker_id value=$task->worker_id}
		{if !empty($task_worker_id) && isset($workers.$task_worker_id)}
			<b>{'common.worker'|devblocks_translate|capitalize}:</b> {$workers.$task_worker_id->getName()} &nbsp;
		{/if}
		</form>
		<br>
	</td>
	<td align="right" valign="top">
		{*
		<form action="{devblocks_url}{/devblocks_url}" method="post">
		<input type="hidden" name="c" value="contacts">
		<input type="hidden" name="a" value="doOrgQuickSearch">
		<span><b>{$translate->_('common.quick_search')|capitalize}:</b></span> <select name="type">
			<option value="name">{$translate->_('contact_org.name')|capitalize}</option>
			<option value="phone">{$translate->_('contact_org.phone')|capitalize}</option>
		</select><input type="text" name="query" size="24"><button type="submit">{$translate->_('common.search_go')|lower}</button>
		</form>
		*}
		
		{if !empty($series_stats.next) || !empty($series_stats.prev)}
		<table cellpadding="0" cellspacing="0" border="0" style="margin:0px;">
			<tr>
				<td>	
				<div style="padding:10px;margin-top:0px;border:1px solid rgb(180,180,255);background-color:rgb(245,245,255);text-align:center;">
					{$translate->_('display.listnav.active_list')} <b>{$series_stats.title}</b><br>
					{if !empty($series_stats.prev)}<button style="display:none;visibility:hidden;" id="btnPagePrev" onclick="document.location='{devblocks_url}c=tasks&a=display&id={$series_stats.prev}{/devblocks_url}';"></button><a href="{devblocks_url}c=tasks&a=display&id={$series_stats.prev}{/devblocks_url}" title="[">&laquo;{$translate->_('common.previous_short')|capitalize}</a>{/if}
					{'display.listnav.showing_of_total'|devblocks_translate:$series_stats.cur:$series_stats.count} 
					{if !empty($series_stats.next)}<button style="display:none;visibility:hidden;" id="btnPageNext" onclick="document.location='{devblocks_url}c=tasks&a=display&id={$series_stats.next}{/devblocks_url}';"></button><a href="{devblocks_url}c=tasks&a=display&id={$series_stats.next}{/devblocks_url}" title="]">{$translate->_('common.next')|capitalize}&raquo;</a>{/if}
				</div>
				</td>
			</tr>
		</table>
		{/if}
	</td>
</tr>
</table>

<div id="displayTaskTabs"></div> 
<br>

<script type="text/javascript">
{literal}
var tabView = new YAHOO.widget.TabView();

tabView.addTab( new YAHOO.widget.Tab({
    label: '{/literal}{'activity.tasks.tab.notes'|devblocks_translate|escape}{literal}',
    dataSrc: '{/literal}{devblocks_url}ajax.php?c=tasks&a=showTaskNotesTab&id={$task->id}{/devblocks_url}{literal}',
    cacheData: true,
    {/literal}active: {if 'notes'==$tab_selected || empty($tab_selected)}true{else}false{/if}{literal}
}));

{/literal}
{if ($active_worker->hasPriv('core.tasks.actions.create') && (empty($task) || $active_worker->id==$task->worker_id))
	|| ($active_worker->hasPriv('core.tasks.actions.update_nobody') && empty($task->worker_id)) 
	|| $active_worker->hasPriv('core.tasks.actions.update_all')}
{literal}
tabView.addTab( new YAHOO.widget.Tab({
    label: '{/literal}{'activity.tasks.tab.properties'|devblocks_translate|escape}{literal}',
    dataSrc: '{/literal}{devblocks_url}ajax.php?c=tasks&a=showTasksPropertiesTab&id={$task->id}{/devblocks_url}{literal}',
    cacheData: true,
    {/literal}active: {if 'properties'==$tab_selected}true{else}false{/if}{literal}
}));
{/literal}
{/if}

{literal}
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

tabView.appendTo('displayTaskTabs');
</script>

<script type="text/javascript">
{if $pref_keyboard_shortcuts}
{literal}
CreateKeyHandler(function doShortcuts(e) {

	var mycode = getKeyboardKey(e, true);
	
	switch(mycode) {
//		case 65:  // (A) E-mail Peek
//			try {
//				document.getElementById('btnOppAddyPeek').click();
//			} catch(e){}
//			break;
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
			break;
	}
});
{/literal}
{/if}
</script>