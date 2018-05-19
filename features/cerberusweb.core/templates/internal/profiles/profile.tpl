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
	<h1 style="font-size:2em;">{$dict->_label}</h1>
	
	<div class="cerb-profile-toolbar">
		<form class="toolbar" action="{devblocks_url}{/devblocks_url}" method="post" style="margin-bottom:5px;">
			<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">
			
			<span id="spanInteractions" title="{'common.interactions'|devblocks_translate|capitalize}{if $pref_keyboard_shortcuts} (I){/if}">
			{include file="devblocks:cerberusweb.core::events/interaction/interactions_menu.tpl"}
			</span>
			
			<!-- Card -->
			<button type="button" id="btnProfileCard" title="{'common.card'|devblocks_translate|capitalize}{if $pref_keyboard_shortcuts} (V){/if}" data-context="{$page_context}" data-context-id="{$page_context_id}"><span class="glyphicons glyphicons-nameplate"></span></button>
			
			<!-- Edit -->
			{if $is_writeable && $active_worker->hasPriv("contexts.{$page_context}.update")}
			<button type="button" id="btnProfileCardEdit" title="{'common.edit'|devblocks_translate|capitalize}{if $pref_keyboard_shortcuts} (E){/if}" class="cerb-peek-trigger" data-context="{$page_context}" data-context-id="{$page_context_id}" data-edit="true"><span class="glyphicons glyphicons-cogwheel"></span></button>
			{/if}
			
			{if $context_ext->hasOption('watchers')}
				<span id="spanProfileWatchers" title="{'common.watchers'|devblocks_translate|capitalize}{if $pref_keyboard_shortcuts} (W){/if}">
				{$object_watchers = DAO_ContextLink::getContextLinks($page_context, array($page_context_id), CerberusContexts::CONTEXT_WORKER)}
				{include file="devblocks:cerberusweb.core::internal/watchers/context_follow_button.tpl" context=$page_context context_id=$page_context_id full=true}
				</span>
			{/if}
			
			<!-- Refresh -->
			<button type="button" title="{'common.refresh'|devblocks_translate|capitalize}" onclick="document.location.reload();"><span class="glyphicons glyphicons-refresh"></span></button>
			
		</form>
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
		
		{if $active_worker->is_superuser}
		<li><a href="{devblocks_url}ajax.php?c=profiles&a=configTabs&context={$page_context}{/devblocks_url}">&nbsp;<span class="glyphicons glyphicons-cogwheel"></span>&nbsp;</a></li>
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
	document.title = "{$dict->_label|escape:'javascript' nofilter} - {$settings->get('cerberusweb.core','helpdesk_title')|escape:'javascript' nofilter}";
	
	// Peeks
	$('#btnProfileCard').cerbPeekTrigger();
	
	// Edit
	
	$('#btnProfileCardEdit')
		.cerbPeekTrigger()
		.on('cerb-peek-opened', function(e) {
		})
		.on('cerb-peek-saved', function(e) {
			// [TODO] Don't refresh the page, just send an event to the current tab
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
$(function() {
	var $document = $(document);
	var $body = $document.find('body');
	
	$body.bind('keypress', 'E', function(e) {
		e.preventDefault();
		e.stopPropagation();
		$('#btnProfileCardEdit').click();
	});
	
	$body.bind('keypress', 'I', function(e) {
		e.preventDefault();
		e.stopPropagation();
		$('#spanInteractions').find('> button').click();
	});
	
	$body.bind('keypress', 'V', function(e) {
		e.preventDefault();
		e.stopPropagation();
		$('#btnProfileCard').click();
	});
	
	$body.bind('keypress', 'W', function(e) {
		e.preventDefault();
		e.stopPropagation();
		$('#spanProfileWatchers button:first').click();
	});
	
	$body.bind('keypress', '1 2 3 4 5 6 7 8 9 0', function(e) {
		e.preventDefault();
		e.stopPropagation();
		
		try {
			var idx = event.which-49;
			$tabs = $("#profileTabs").tabs();
			$tabs.tabs('option', 'active', idx);
		} catch(ex) { }
	});
	
	$document.bind('keydown', function(e) {
		if($(e.target).is(':input'))
			return;
		
		var $tabs = $("#profileTabs");
			
		var $tab_content = $('#' + $tabs.find('li.ui-tabs-active').attr('aria-controls'));
		
		var $widgets = $tab_content.find('div.cerb-profile-widget');
		
		$widgets.each(function() {
			if(!e.isPropagationStopped()) {
				var $widget = $(this);
				$widget.triggerHandler(e);
			}
		});
	});
});
{/if}
</script>

{include file="devblocks:cerberusweb.core::internal/profiles/profile_common_scripts.tpl"}