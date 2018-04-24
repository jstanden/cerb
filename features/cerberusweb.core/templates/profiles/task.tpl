{$page_context = CerberusContexts::CONTEXT_TASK}
{$page_context_id = $task->id}
{$is_writeable = Context_Task::isWriteableByActor($task, $active_worker)}

<h1>{$task->title}</h1>

<div class="cerb-profile-toolbar">
	<form class="toolbar" action="{devblocks_url}{/devblocks_url}" method="post" style="margin-bottom:5px;">
		<input type="hidden" name="c" value="tasks">
		<input type="hidden" name="a" value="">
		<input type="hidden" name="id" value="{$page_context_id}">
		<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">
		
		<span id="spanInteractions">
		{include file="devblocks:cerberusweb.core::events/interaction/interactions_menu.tpl"}
		</span>
		
		<!-- Card -->
		<button type="button" id="btnProfileCard" title="{'common.card'|devblocks_translate|capitalize}" data-context="{$page_context}" data-context-id="{$page_context_id}"><span class="glyphicons glyphicons-nameplate"></span></button>
		
		<!-- Edit -->
		{if $is_writeable && $active_worker->hasPriv("contexts.{$page_context}.update")}
		<button type="button" id="btnDisplayTaskEdit" title="{'common.edit'|devblocks_translate|capitalize} (E)" class="cerb-peek-trigger" data-context="{CerberusContexts::CONTEXT_TASK}" data-context-id="{$page_context_id}" data-edit="true"><span class="glyphicons glyphicons-cogwheel"></span></button>
		{/if}
		
		<!-- Toolbar -->
		<span>
		{$object_watchers = DAO_ContextLink::getContextLinks($page_context, array($page_context_id), CerberusContexts::CONTEXT_WORKER)}
		{include file="devblocks:cerberusweb.core::internal/watchers/context_follow_button.tpl" context=$page_context context_id=$page_context_id full=true}
		</span>

		<button type="button" title="{'common.refresh'|devblocks_translate|capitalize}" onclick="document.location='{devblocks_url}c=profiles&type=task&id={$page_context_id}-{$task->title|devblocks_permalink}{/devblocks_url}';">&nbsp;<span class="glyphicons glyphicons-refresh"></span></a>&nbsp;</button>
	</form>
	
	{if $pref_keyboard_shortcuts}
	<small>
		{'common.keyboard'|devblocks_translate|lower}:
		(<b>e</b>) {'common.edit'|devblocks_translate|lower}
		(<b>i</b>) {'common.interactions'|devblocks_translate|lower}
		(<b>1-9</b>) change tab
	</small> 
	{/if}
</div>

<fieldset class="properties">
	<legend>Task</legend>
	
	<div style="margin-left:15px;">
	{foreach from=$properties item=v key=k name=props}
		<div class="property">
			{if $k == 'status'}
				<b>{'common.status'|devblocks_translate|capitalize}:</b>
				{if 1 == $task->status_id}
					<span style="font-weight:bold;color:rgb(50,115,185);">{'status.closed'|devblocks_translate}</span>
				{elseif 2 == $task->status_id}
					<span style="font-weight:bold;color:rgb(50,115,185);">{'status.waiting.abbr'|devblocks_translate}</span>
					{if !empty($task->reopen_at)}
						(opens {if $task->reopen_at > time()}in {/if}<abbr title="{$task->reopen_at|devblocks_date}">{$task->reopen_at|devblocks_prettytime}</abbr>)
					{/if}
				{else}
					{'status.open'|devblocks_translate}
				{/if} 
			{elseif $k == 'due_date'}
				<b>{'task.due_date'|devblocks_translate|capitalize}:</b>
				<abbr title="{$task->due_date|devblocks_date}" style="{if 1 != $task->status_id && $task->due_date < time()}font-weight:bold;color:rgb(150,0,0);{/if}">{$task->due_date|devblocks_prettytime}</abbr>
			{elseif $k == 'importance'}
				<b>{'common.importance'|devblocks_translate|capitalize}:</b>
				<div style="display:inline-block;margin-left:5px;width:40px;height:8px;background-color:rgb(220,220,220);border-radius:8px;">
					<div style="position:relative;margin-left:-5px;top:-1px;left:{$task->importance}%;width:10px;height:10px;border-radius:10px;background-color:{if $task->importance < 50}rgb(0,200,0);{elseif $task->importance > 50}rgb(230,70,70);{else}rgb(175,175,175);{/if}"></div>
				</div>
			{else}
				{include file="devblocks:cerberusweb.core::internal/custom_fields/profile_cell_renderer.tpl"}
			{/if}
		</div>
		{if $smarty.foreach.props.iteration % 3 == 0 && !$smarty.foreach.props.last}
			<br clear="all">
		{/if}
	{/foreach}
	<br clear="all">

	{include file="devblocks:cerberusweb.core::internal/peek/peek_search_buttons.tpl"}
	</div>
</fieldset>

{include file="devblocks:cerberusweb.core::internal/custom_fieldsets/profile_fieldsets.tpl" properties=$properties_custom_fieldsets}

{include file="devblocks:cerberusweb.core::internal/profiles/profile_record_links.tpl" properties=$properties_links}

<div>
{include file="devblocks:cerberusweb.core::internal/notifications/context_profile.tpl" context=$page_context context_id=$page_context_id}
</div>

<div>
{include file="devblocks:cerberusweb.core::internal/macros/behavior/scheduled_behavior_profile.tpl" context=$page_context context_id=$page_context_id}
</div>

<div id="profileTaskTabs">
	<ul>
		{$tabs = [activity,comments]}

		<li><a href="{devblocks_url}ajax.php?c=internal&a=showTabActivityLog&scope=target&point={$point}&context={$page_context}&context_id={$page_context_id}{/devblocks_url}">{'common.log'|devblocks_translate|capitalize}</a></li>
		<li><a href="{devblocks_url}ajax.php?c=internal&a=showTabContextComments&point={$point}&context={$page_context}&id={$page_context_id}{/devblocks_url}">{'common.comments'|devblocks_translate|capitalize} <div class="tab-badge">{DAO_Comment::count($page_context, $page_context_id)|default:0}</div></a></li>
		
		{foreach from=$tab_manifests item=tab_manifest}
			{$tabs[] = $tab_manifest->params.uri}
			<li><a href="{devblocks_url}ajax.php?c=profiles&a=showTab&ext_id={$tab_manifest->id}&point={$point}&context={$page_context}&context_id={$page_context_id}{/devblocks_url}"><i>{$tab_manifest->params.title|devblocks_translate}</i></a></li>
		{/foreach}
	</ul>
</div> 
<br>

<script type="text/javascript">
$(function() {
	var tabOptions = Devblocks.getDefaultjQueryUiTabOptions();
	tabOptions.active = Devblocks.getjQueryUiTabSelected('profileTaskTabs');
	
	var tabs = $("#profileTaskTabs").tabs(tabOptions);

	$('#btnProfileCard').cerbPeekTrigger();
	
	// Edit
	
	$('#btnDisplayTaskEdit')
		.cerbPeekTrigger()
		.on('cerb-peek-opened', function(e) {
		})
		.on('cerb-peek-saved', function(e) {
			e.stopPropagation();
			document.location.reload();
		})
		.on('cerb-peek-deleted', function(e) {
			document.location.href = '{devblocks_url}{/devblocks_url}';
			
		})
		.on('cerb-peek-closed', function(e) {
		})
	;
	
	// Interactions
	var $interaction_container = $('#spanInteractions');
	{include file="devblocks:cerberusweb.core::events/interaction/interactions_menu.js.tpl"}
});
</script>

<script type="text/javascript">
{if $pref_keyboard_shortcuts}
$(document).keypress(function(event) {
	if(event.altKey || event.ctrlKey || event.shiftKey || event.metaKey)
		return;
	
	if($(event.target).is(':input'))
		return;

	hotkey_activated = true;
	
	switch(event.which) {
		case 49:  // (1) tab cycle
		case 50:  // (2) tab cycle
		case 51:  // (3) tab cycle
		case 52:  // (4) tab cycle
		case 53:  // (5) tab cycle
		case 54:  // (6) tab cycle
		case 55:  // (7) tab cycle
		case 56:  // (8) tab cycle
		case 57:  // (9) tab cycle
		case 58:  // (0) tab cycle
			try {
				idx = event.which-49;
				$tabs = $("#profileTaskTabs").tabs();
				$tabs.tabs('option', 'active', idx);
			} catch(ex) { } 
			break;
		case 101:  // (E) edit
			try {
				$('#btnDisplayTaskEdit').click();
			} catch(ex) { } 
			break;
		case 105:  // (I) interactions
			try {
				$('#spanInteractions').find('> button').click();
			} catch(ex) { } 
			break;
		default:
			// We didn't find any obvious keys, try other codes
			hotkey_activated = false;
			break;
	}
	
	if(hotkey_activated)
		event.preventDefault();
});
{/if}
</script>

{include file="devblocks:cerberusweb.core::internal/profiles/profile_common_scripts.tpl"}