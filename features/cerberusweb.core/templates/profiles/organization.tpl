{$page_context = CerberusContexts::CONTEXT_ORG}
{$page_context_id = $contact->id}

<div style="float:left;margin-right:10px;">
	<img src="{devblocks_url}c=avatars&context=org&context_id={$contact->id}{/devblocks_url}?v={$contact->updated}" style="height:75px;width:75px;border-radius:5px;">
</div>

<div style="float:left;">
	<h1>{$contact->name}</h1>
	
	<div class="cerb-profile-toolbar">
		<form class="toolbar" action="{devblocks_url}{/devblocks_url}" method="post" style="margin-bottom:5px;">
			<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">
			
			<!-- Toolbar -->
			<span>
			{$object_watchers = DAO_ContextLink::getContextLinks($page_context, array($page_context_id), CerberusContexts::CONTEXT_WORKER)}
			{include file="devblocks:cerberusweb.core::internal/watchers/context_follow_button.tpl" context=$page_context context_id=$page_context_id full=true}
			</span>
			
			<!-- Macros -->
			{devblocks_url assign=return_url full=true}c=profiles&type=org&id={$page_context_id}-{$contact->name|devblocks_permalink}{/devblocks_url}
			{include file="devblocks:cerberusweb.core::internal/macros/display/button.tpl" context=$page_context context_id=$page_context_id macros=$macros return_url=$return_url}		
			
			<!-- Edit -->
			<button type="button" id="btnDisplayOrgEdit" title="{'common.edit'|devblocks_translate|capitalize} (E)" class="cerb-peek-trigger" data-context="{$page_context}" data-context-id="{$page_context_id}" data-edit="true"><span class="glyphicons glyphicons-cogwheel"></span></button>
		</form>
		
		{if $pref_keyboard_shortcuts}
		<small>
			{'common.keyboard'|devblocks_translate|lower}:
			(<b>e</b>) {'common.edit'|devblocks_translate|lower}
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

<div style="clear:both;padding-top:5px;"></div>

<fieldset class="properties">
	<legend>{'common.organization'|devblocks_translate|capitalize}</legend>
	
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
	
	<br clear="all">
	</div>
	
	<div style="margin-left:15px;margin-top:5px;">
		<button type="button" class="cerb-search-trigger" data-context="{CerberusContexts::CONTEXT_CONTACT}" data-query="org.id:{$page_context_id}"><div class="badge-count">{$activity_counts.contacts|default:0}</div> {'common.contacts'|devblocks_translate|capitalize}</button>
		<button type="button" class="cerb-search-trigger" data-context="{CerberusContexts::CONTEXT_ADDRESS}" data-query="org.id:{$page_context_id}"><div class="badge-count">{$activity_counts.emails|default:0}</div> {'common.email_addresses'|devblocks_translate|capitalize}</button>
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

<div style="clear:both;" id="profileOrgTabs">
	<ul>
		{$tabs = [activity,history,comments]}
		
		<li><a href="{devblocks_url}ajax.php?c=internal&a=showTabActivityLog&scope=target&point={$point}&context={$page_context}&context_id={$page_context_id}{/devblocks_url}">{'common.log'|devblocks_translate|capitalize}</a></li>
		<li><a href="{devblocks_url}ajax.php?c=contacts&a=showTabMailHistory&point={$point}&org_id={$page_context_id}{/devblocks_url}">{'addy_book.org.tabs.mail_history'|devblocks_translate}</a></li>
		<li><a href="{devblocks_url}ajax.php?c=internal&a=showTabContextComments&point={$point}&context=cerberusweb.contexts.org&id={$page_context_id}{/devblocks_url}">{'common.comments'|devblocks_translate|capitalize} <div class="tab-badge">{DAO_Comment::count($page_context, $page_context_id)|default:0}</div></a></li>

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
	tabOptions.active = Devblocks.getjQueryUiTabSelected('profileOrgTabs');
	
	var tabs = $("#profileOrgTabs").tabs(tabOptions);

	// Edit
	
	$('#btnDisplayOrgEdit')
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
});

{include file="devblocks:cerberusweb.core::internal/macros/display/menu_script.tpl" selector_button=null selector_menu=null}
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
				$tabs = $("#profileOrgTabs").tabs();
				$tabs.tabs('option', 'active', idx);
			} catch(ex) { } 
			break;
		case 101:  // (E) edit
			try {
				$('#btnDisplayOrgEdit').click();
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
{/if}
</script>

{include file="devblocks:cerberusweb.core::internal/profiles/profile_common_scripts.tpl"}