{$page_context = CerberusContexts::CONTEXT_GROUP}
{$page_context_id = $group->id}

{$members = $group->getMembers()}
{$buckets = $group->getBuckets()}

<div style="float:left;margin-right:10px;">
	<img src="{devblocks_url}c=avatars&context=group&context_id={$group->id}{/devblocks_url}?v={$group->updated}" style="height:75px;width:75px;border-radius:5px;">
</div>

<div style="float:left;">
	<h1>{$group->name}</h1>
	
	<div class="cerb-profile-toolbar">
		<form class="toolbar" action="javascript:;" method="POST" onsubmit="return false;">
			<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">
		
			<!-- Macros -->
			{if $active_worker->isGroupManager($group->id) || $active_worker->is_superuser}
				{if !empty($page_context) && !empty($page_context_id) && !empty($macros)}
					{devblocks_url assign=return_url full=true}c=profiles&tab=group&id={$page_context_id}-{$group->name|devblocks_permalink}{/devblocks_url}
					{include file="devblocks:cerberusweb.core::internal/macros/display/button.tpl" context=$page_context context_id=$page_context_id macros=$macros return_url=$return_url}
				{/if}
			{/if}
		
			{if $active_worker->is_superuser}
				<button type="button" id="btnProfileGroupEdit" title="{'common.edit'|devblocks_translate|capitalize}"><span class="glyphicons glyphicons-cogwheel"></span></button>
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
</div>

<div style="float:right;">
	{$ctx = Extension_DevblocksContext::get($page_context)}
	{include file="devblocks:cerberusweb.core::search/quick_search.tpl" view=$ctx->getSearchView() return_url="{devblocks_url}c=search&context={$ctx->manifest->params.alias}{/devblocks_url}"}
</div>

<div style="clear:both;"></div>

<fieldset class="properties" style="margin-top:5px;">
	<legend>Group</legend>
	
	<div style="margin-left:15px;">
	{if !empty($properties)}
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
	<br clear="all">
	{/if}
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

<div id="profileGroupTabs">
	<ul>
		{$tabs = []}
		{$point = "cerberusweb.profiles.group.{$group->id}"}
		
		{$tabs[] = 'members'}
		<li><a href="{devblocks_url}ajax.php?c=profiles&a=handleSectionAction&section=group&action=showMembersTab&point={$point}&id={$page_context_id}{/devblocks_url}">{'common.members'|devblocks_translate|capitalize} <div class="tab-badge">{$members|count}</div></a></li>

		{$tabs[] = 'buckets'}
		<li><a href="{devblocks_url}ajax.php?c=profiles&a=handleSectionAction&section=group&action=showBucketsTab&point={$point}&id={$page_context_id}{/devblocks_url}">{'common.buckets'|devblocks_translate|capitalize} <div class="tab-badge">{$buckets|count}</div></a></li>
		
		{$tabs[] = 'responsibilities'}
		<li data-alias="responsibilities"><a href="{devblocks_url}ajax.php?c=internal&a=handleSectionAction&section=responsibilities&action=showResponsibilitiesTab&point={$point}&context={$page_context}&context_id={$page_context_id}{/devblocks_url}">{'common.responsibilities'|devblocks_translate|capitalize}</a></li>

		{$tabs[] = 'activity'}
		<li><a href="{devblocks_url}ajax.php?c=internal&a=showTabActivityLog&scope=both&point={$point}&context={$page_context}&context_id={$page_context_id}{/devblocks_url}">{'common.activity_log'|devblocks_translate|capitalize}</a></li>
		
		{$tabs[] = 'comments'}
		<li><a href="{devblocks_url}ajax.php?c=internal&a=showTabContextComments&context={$page_context}&id={$page_context_id}{/devblocks_url}">{'common.comments'|devblocks_translate|capitalize}</a></li>
		
		{$tabs[] = 'links'}
		<li><a href="{devblocks_url}ajax.php?c=internal&a=showTabContextLinks&point={$point}&context={$page_context}&id={$page_context_id}{/devblocks_url}">{'common.links'|devblocks_translate|capitalize} <div class="tab-badge">{DAO_ContextLink::count($page_context, $page_context_id)|default:0}</div></a></li>
		
		{if $active_worker->is_superuser || $active_worker->isGroupManager($group->id)}
		{$tabs[] = 'attendants'}
		<li><a href="{devblocks_url}ajax.php?c=internal&a=showAttendantsTab&point={$point}&context={$page_context}&context_id={$page_context_id}{/devblocks_url}">Virtual Attendants</a></li>
		{/if}

		{if $active_worker->is_superuser || $active_worker->isGroupManager($group->id)}
		{$tabs[] = 'custom_fieldsets'}
		<li><a href="{devblocks_url}ajax.php?c=internal&a=handleSectionAction&section=custom_fieldsets&action=showTabCustomFieldsets&context={$page_context}&context_id={$page_context_id}&point={$point}{/devblocks_url}">{'common.custom_fieldsets'|devblocks_translate|capitalize}</a></li>
		{/if}

		{if $active_worker->is_superuser || $active_worker->isGroupManager($group->id)}
		{$tabs[] = 'snippets'}
		<li><a href="{devblocks_url}ajax.php?c=internal&a=showTabSnippets&point={$point}&context={$page_context}&context_id={$page_context_id}{/devblocks_url}">{'common.snippets'|devblocks_translate|capitalize}</a></li>
		{/if}

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
	tabOptions.active = Devblocks.getjQueryUiTabSelected('profileGroupTabs');
	
	var tabs = $("#profileGroupTabs").tabs(tabOptions);

	{if $active_worker->is_superuser || $active_worker->isGroupManager($group->id)}
	$('#btnProfileGroupEdit')
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
	{/if}
	
	{include file="devblocks:cerberusweb.core::internal/macros/display/menu_script.tpl" selector_button=null selector_menu=null}
});
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
					var $tabs = $("#profileGroupTabs").tabs();
					$tabs.tabs('option', 'active', idx);
				} catch(ex) { }
				break;
			case 101:  // (E) edit
				try {
					$('#btnProfileGroupEdit').click();
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

{include file="devblocks:cerberusweb.core::internal/profiles/profile_common_scripts.tpl"}