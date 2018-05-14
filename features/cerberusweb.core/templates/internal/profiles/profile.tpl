{$page_context = $dict->_context}
{$page_context_id = $dict->id}
{$is_writeable = CerberusContexts::isWriteableByActor($page_context, $record, $active_worker)}

{if $context_ext->hasOption('avatars')}
<div style="float:left;margin-right:10px;">
	<img src="{devblocks_url}c=avatars&context={$context_ext->manifest->params.alias}&context_id={$page_context_id}{/devblocks_url}?v={$dict->updated_at|default:$dict->updated|default:$dict->updated_date}" style="height:75px;width:75px;border-radius:5px;">
</div>
{/if}

{if $smarty.const.APP_OPT_DEPRECATED_PROFILE_QUICK_SEARCH}
<div style="float:right">
	{$ctx = Extension_DevblocksContext::get($page_context)}
	{include file="devblocks:cerberusweb.core::search/quick_search.tpl" view=$ctx->getSearchView() return_url="{devblocks_url}c=search&context={$ctx->manifest->params.alias}{/devblocks_url}"}
</div>
{/if}

<div style="float:left;">
	<h1>{$dict->_label}</h1>
	
	<div class="cerb-profile-toolbar">
		<form class="toolbar" action="{devblocks_url}{/devblocks_url}" method="post" style="margin-bottom:5px;">
			<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">
			
			<span id="spanInteractions">
			{include file="devblocks:cerberusweb.core::events/interaction/interactions_menu.tpl"}
			</span>
			
			<!-- Card -->
			<button type="button" id="btnProfileCard" title="{'common.card'|devblocks_translate|capitalize}" data-context="{$page_context}" data-context-id="{$page_context_id}"><span class="glyphicons glyphicons-nameplate"></span></button>
			
			<!-- Edit -->
			{if $is_writeable && $active_worker->hasPriv("contexts.{$page_context}.update")}
			<button type="button" id="btnProfileCardEdit" title="{'common.edit'|devblocks_translate|capitalize} (E)" class="cerb-peek-trigger" data-context="{$page_context}" data-context-id="{$page_context_id}" data-edit="true"><span class="glyphicons glyphicons-cogwheel"></span></button>
			{/if}
			
			{if $context_ext->hasOption('watchers')}
				<span>
				{$object_watchers = DAO_ContextLink::getContextLinks($page_context, array($page_context_id), CerberusContexts::CONTEXT_WORKER)}
				{include file="devblocks:cerberusweb.core::internal/watchers/context_follow_button.tpl" context=$page_context context_id=$page_context_id full=true}
				</span>
			{/if}
			
			{* <!-- Refresh --> *}
			<button type="button" title="{'common.refresh'|devblocks_translate|capitalize}" onclick="document.location.reload();"><span class="glyphicons glyphicons-refresh"></span></button>
		</form>
		
		{if $pref_keyboard_shortcuts}
		<small>
			{'common.keyboard'|devblocks_translate|lower}:
			(<b>e</b>) {'common.edit'|devblocks_translate|lower}
			(<b>i</b>) {'common.interactions'|devblocks_translate|lower}
			{if $context_ext->hasOption('watchers')}(<b>w</b>) {'common.watch'|devblocks_translate|lower}{/if} 
			(<b>1-9</b>) change tab
		</small> 
		{/if}
	</div>
</div>

<div style="clear:both;padding-top:5px;"></div>

<div>
{include file="devblocks:cerberusweb.core::internal/notifications/context_profile.tpl" context=$page_context context_id=$page_context_id}
</div>

<div>
{include file="devblocks:cerberusweb.core::internal/macros/behavior/scheduled_behavior_profile.tpl" context=$page_context context_id=$page_context_id}
</div>

<div style="clear:both;" id="profileTabs">
	<ul>
		{$tabs = []}
		
		{$profile_tabs = DAO_ProfileTab::getByProfile($page_context)}
		
		{foreach from=$profile_tabs item=profile_tab}
			{$tabs[] = "tab_{$profile_tab->id}"}
			<li><a href="{devblocks_url}ajax.php?c=profiles&a=showProfileTab&tab_id={$profile_tab->id}&context={$page_context}&context_id={$page_context_id}{/devblocks_url}">{$profile_tab->name}</a></li>
		{/foreach}
		
		{*
		{foreach from=$tab_manifests item=tab_manifest}
			{$tabs[] = $tab_manifest->params.uri}
			<li><a href="{devblocks_url}ajax.php?c=profiles&a=showTab&ext_id={$tab_manifest->id}&context={$page_context}&context_id={$page_context_id}{/devblocks_url}">{$tab_manifest->params.title|devblocks_translate}</a></li>
		{/foreach}
		*}
		
		{if $active_worker->is_superuser}
		<li><a href="{devblocks_url}ajax.php?c=profiles&a=configTabs&context={$page_context}{/devblocks_url}">+</a></li>
		{/if}
	</ul>
</div> 
<br>

<script type="text/javascript">
$(function() {
	var tabOptions = Devblocks.getDefaultjQueryUiTabOptions();
	tabOptions.active = Devblocks.getjQueryUiTabSelected('profileTabs');
	
	// Tabs
	var tabs = $("#profileTabs").tabs(tabOptions);
	
	// Set the browser tab label to the record label
	{*$context_ext->manifest->name|capitalize|escape:'javascript' nofilter*}
	document.title = "{$dict->_label|escape:'javascript' nofilter} - Profile - {$settings->get('cerberusweb.core','helpdesk_title')|escape:'javascript' nofilter}";
	
	// Peeks
	$('#btnProfileCard').cerbPeekTrigger();
	
	// Edit
	
	$('#btnProfileCardEdit')
		.cerbPeekTrigger()
		.on('cerb-peek-opened', function(e) {
		})
		.on('cerb-peek-saved', function(e) {
			// [TODO] Don't refresh the page, just send an evnet to the current tab
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
				$tabs = $("#profileTabs").tabs();
				$tabs.tabs('option', 'active', idx);
			} catch(ex) { } 
			break;
		case 101:  // (E) edit
			try {
				$('#btnProfileCardEdit').click();
			} catch(ex) { } 
			break;
		case 105:  // (I) interactions
			try {
				$('#spanInteractions').find('> button').click();
			} catch(ex) { } 
			break;
		case 119:  // (W) watch
			try {
				$('#spanWatcherToolbar button:first').click();
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