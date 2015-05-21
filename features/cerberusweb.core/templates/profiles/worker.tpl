{$page_context = CerberusContexts::CONTEXT_WORKER}
{$page_context_id = $worker->id}

{$gravatar_plugin = DevblocksPlatform::getPlugin('cerberusweb.gravatar')}
{$gravatar_enabled = $gravatar_plugin && $gravatar_plugin->enabled}

{$memberships = $worker->getMemberships()}

<div style="float:left;">
	<h1>{$worker->getName()}</h1>
</div>

<div style="float:right;">
	{$ctx = Extension_DevblocksContext::get($page_context)}
	{include file="devblocks:cerberusweb.core::search/quick_search.tpl" view=$ctx->getSearchView() return_url="{devblocks_url}c=search&context={$ctx->manifest->params.alias}{/devblocks_url}"}
</div>

<div style="clear:both;"></div>

<div class="cerb-profile-toolbar">
	<form class="toolbar" action="javascript:;" method="POST" style="margin:0px 0px 5px 5px;" onsubmit="return false;">
		<!-- Macros -->
		{if $worker->id == $active_worker->id || $active_worker->is_superuser}
			{if !empty($page_context) && !empty($page_context_id) && !empty($macros)}
				{devblocks_url assign=return_url full=true}c=profiles&tab=worker&id={$page_context_id}-{$worker->getName()|devblocks_permalink}{/devblocks_url}
				{include file="devblocks:cerberusweb.core::internal/macros/display/button.tpl" context=$page_context context_id=$page_context_id macros=$macros return_url=$return_url}
			{/if}
		{/if}
	
		{if $active_worker->is_superuser}
			{if $worker->id != $active_worker->id}<button type="button" id="btnProfileWorkerPossess"><span class="glyphicons glyphicons-user"></span> Impersonate</button>{/if}
			<button type="button" id="btnProfileWorkerEdit" title="{'common.edit'|devblocks_translate|capitalize}"><span class="glyphicons glyphicons-cogwheel"></span></button>
			<button type="button" title="{'common.refresh'|devblocks_translate|capitalize}" onclick="document.location='{devblocks_url}c=profiles&type=worker&id={$worker->id}{/devblocks_url}-{$worker->getName()|devblocks_permalink}';"><span class="glyphicons glyphicons-refresh"></span></button>
		{/if}
	</form>
	
	{if $pref_keyboard_shortcuts}
		<small>
		{$translate->_('common.keyboard')|lower}:
		{if $active_worker->is_superuser}(<b>e</b>) {'common.edit'|devblocks_translate|lower}{/if}
		{if !empty($macros)}(<b>m</b>) {'common.macros'|devblocks_translate|lower} {/if}
		(<b>1-9</b>) change tab
		</small>
	{/if}
</div>

<fieldset class="properties">
	<legend>Worker</legend>
	
	<div style="margin-left:15px;">
	
	{foreach from=$properties item=v key=k name=props}
		<div class="property">
			{if $k == '...'}
				<b>{'...'|devblocks_translate|capitalize}:</b>
				...
			{else}
				{include file="devblocks:cerberusweb.core::internal/custom_fields/profile_cell_renderer.tpl"}
			{/if}
		</div>
		{if $smarty.foreach.props.iteration % 3 == 0 && !$smarty.foreach.props.last}
			<br clear="all">
		{/if}
	{/foreach}
	</div>
	
	<br clear="all">
</fieldset>

{include file="devblocks:cerberusweb.core::internal/custom_fieldsets/profile_fieldsets.tpl" properties=$properties_custom_fieldsets}

{include file="devblocks:cerberusweb.core::internal/profiles/profile_record_links.tpl" properties=$properties_links}

<div>
{include file="devblocks:cerberusweb.core::internal/notifications/context_profile.tpl" context=$page_context context_id=$page_context_id}
</div>

<div>
{include file="devblocks:cerberusweb.core::internal/macros/behavior/scheduled_behavior_profile.tpl" context=$page_context context_id=$page_context_id}
</div>

<div id="profileWorkerTabs">
	<ul>
		{$tabs = []}
		{$point = "cerberusweb.profiles.worker.{$worker->id}"}
		
		{if $worker->id == $active_worker->id}
		{$tabs[] = 'notifications'}
		<li data-alias="notifications"><a href="{devblocks_url}ajax.php?c=preferences&a=showMyNotificationsTab{/devblocks_url}">{'home.tab.my_notifications'|devblocks_translate}</a></li>
		{/if}
		
		{$tabs[] = 'responsibilities'}
		<li data-alias="responsibilities"><a href="{devblocks_url}ajax.php?c=internal&a=handleSectionAction&section=responsibilities&action=showResponsibilitiesTab&point={$point}&context={$page_context}&context_id={$page_context_id}{/devblocks_url}">{'common.responsibilities'|devblocks_translate|capitalize}</a></li>
		
		{$tabs[] = 'skills'}
		<li data-alias="skills"><a href="{devblocks_url}ajax.php?c=internal&a=handleSectionAction&section=skills&action=showSkillsTab&point={$point}&context={$page_context}&context_id={$page_context_id}{/devblocks_url}">{'common.skills'|devblocks_translate|capitalize}</a></li>
		
		{$tabs[] = 'calendar'}
		<li data-alias="calendar"><a href="{devblocks_url}ajax.php?c=internal&a=handleSectionAction&section=calendars&action=showCalendarTab&point={$point}&context={$page_context}&context_id={$page_context_id}&id={$worker->calendar_id}{/devblocks_url}">{'common.calendar'|devblocks_translate|capitalize}</a></li>

		{$tabs[] = 'availability'}
		<li data-alias="availability"><a href="{devblocks_url}ajax.php?c=internal&a=handleSectionAction&section=calendars&action=showCalendarAvailabilityTab&point={$point}&context={$page_context}&context_id={$page_context_id}&id={$worker->calendar_id}{/devblocks_url}">{'common.availability'|devblocks_translate|capitalize}</a></li>
		
		{$tabs[] = 'activity'}
		<li data-alias="activity"><a href="{devblocks_url}ajax.php?c=internal&a=showTabActivityLog&scope=both&point={$point}&context={$page_context}&context_id={$page_context_id}{/devblocks_url}">{'common.activity_log'|devblocks_translate|capitalize}</a></li>
		
		{$tabs[] = 'links'}
		<li data-alias="links"><a href="{devblocks_url}ajax.php?c=internal&a=showTabContextLinks&context={$page_context}&point={$point}&id={$page_context_id}{/devblocks_url}">Watchlist <div class="tab-badge">{DAO_ContextLink::count($page_context, $page_context_id)|default:0}</div></a></li>
		
		{$tabs[] = 'comments'}
		<li data-alias="comments"><a href="{devblocks_url}ajax.php?c=internal&a=showTabContextComments&context={$page_context}&id={$page_context_id}{/devblocks_url}">{'common.comments'|devblocks_translate|capitalize} <div class="tab-badge">{DAO_Comment::count($page_context, $page_context_id)|default:0}</div></a></li>

		{if $active_worker->is_superuser || $worker->id == $active_worker->id}
		{$tabs[] = 'attendants'}
		<li data-alias="attendants"><a href="{devblocks_url}ajax.php?c=internal&a=showAttendantsTab&point={$point}&context={$page_context}&context_id={$page_context_id}{/devblocks_url}">{'common.virtual_attendants'|devblocks_translate|capitalize}</a></li>
		{/if}
		
		{if $active_worker->is_superuser || $worker->id == $active_worker->id}
		{$tabs[] = 'snippets'}
		<li data-alias="snippets"><a href="{devblocks_url}ajax.php?c=internal&a=showTabSnippets&point={$point}&context={$page_context}&context_id={$page_context_id}{/devblocks_url}">{'common.snippets'|devblocks_translate|capitalize}</a></li>
		{/if}
		
		{foreach from=$tab_manifests item=tab_manifest}
			{$tabs[] = $tab_manifest->params.uri}
			<li data-alias="{$tab_manifest->params.uri}"><a href="{devblocks_url}ajax.php?c=profiles&a=showTab&ext_id={$tab_manifest->id}&point={$point}&context={$page_context}&context_id={$page_context_id}{/devblocks_url}"><i>{$tab_manifest->params.title|devblocks_translate}</i></a></li>
		{/foreach}
	</ul>
</div> 
<br>

<script type="text/javascript">
$(function() {
	var tabOptions = Devblocks.getDefaultjQueryUiTabOptions();
	tabOptions.active = Devblocks.getjQueryUiTabSelected('profileWorkerTabs', '{$tab}');
	
	var tabs = $("#profileWorkerTabs").tabs(tabOptions);
	
	{if $active_worker->is_superuser}
	$('#btnProfileWorkerEdit').bind('click', function() {
		$popup = genericAjaxPopup('peek','c=config&a=handleSectionAction&section=workers&action=showWorkerPeek&id={$worker->id}',null,false,'550');
		$popup.one('worker_save', function(event) {
			event.stopPropagation();
			window.location.reload();
		});
	});
	$('#btnProfileWorkerPossess').bind('click', function() {
		genericAjaxGet('','c=internal&a=su&worker_id={$worker->id}',function(o) {
			window.location.reload();
		});
	});
	{/if}
});

{include file="devblocks:cerberusweb.core::internal/macros/display/menu_script.tpl" selector_button=null selector_menu=null}
</script>

<script type="text/javascript">
{if $pref_keyboard_shortcuts}
$(function() {
	$(document).keypress(function(event) {
		if(event.altKey || event.ctrlKey || event.shiftKey || event.metaKey)
			return;
		
		if($(event.target).is(':input'))
			return;
	
		var hotkey_activated = true;
		
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
					var idx = event.which-49;
					var $tabs = $("#profileWorkerTabs").tabs();
					$tabs.tabs('option', 'active', idx);
				} catch(ex) { }
				break;
			case 101:  // (E) edit
				try {
					$('#btnProfileWorkerEdit').click();
				} catch(ex) { }
				break;
			case 109:  // (M) macros
				try {
					$('#btnDisplayMacros').click();
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
});
{/if}
</script>

{$profile_scripts = Extension_ContextProfileScript::getExtensions(true, $page_context)}
{if !empty($profile_scripts)}
{foreach from=$profile_scripts item=renderer}
	{if method_exists($renderer,'renderScript')}
		{$renderer->renderScript($page_context, $page_context_id)}
	{/if}
{/foreach}
{/if}
