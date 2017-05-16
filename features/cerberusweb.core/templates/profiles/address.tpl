{$page_context = CerberusContexts::CONTEXT_ADDRESS}
{$page_context_id = $address->id}
{$is_writeable = Context_Address::isWriteableByActor($address, $active_worker)}

<div style="float:left;margin-right:10px;">
	<img src="{devblocks_url}c=avatars&context=address&context_id={$address->id}{/devblocks_url}?v={$address->updated}" style="height:75px;width:75px;border-radius:5px;">
</div>

<div style="float:left;">
	<h1>
	{$addy_name = $address->getName()} 
	{if !empty($addy_name)}
		{$addy_name} &lt;{$address->email}&gt;
	{else}
		{$address->email}
	{/if}
	</h1>
	
	<div class="cerb-profile-toolbar" style="margin-top:5px;">
		<form class="toolbar" action="{devblocks_url}{/devblocks_url}" method="post" style="margin-bottom:5px;">
			<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">
			
			<span>
			{$object_watchers = DAO_ContextLink::getContextLinks($page_context, array($page_context_id), CerberusContexts::CONTEXT_WORKER)}
			{include file="devblocks:cerberusweb.core::internal/watchers/context_follow_button.tpl" context=$page_context context_id=$page_context_id full=true}
			</span>
		
			<!-- Macros -->
			{if $is_writeable}
			{devblocks_url assign=return_url full=true}c=profiles&type=address&id={$page_context_id}-{$address->email|devblocks_permalink}{/devblocks_url}
			{include file="devblocks:cerberusweb.core::internal/macros/display/button.tpl" context=$page_context context_id=$page_context_id macro_event="event.macro.address" return_url=$return_url}
			{/if}
		<span id="spanInteractions" style="position:relative;">
		{include file="devblocks:cerberusweb.core::events/interaction/interactions_menu.tpl"}
		</span>
		
			<!-- Toolbar -->
			{if $is_writeable}
			<button type="button" id="btnDisplayAddyEdit" title="{'common.edit'|devblocks_translate|capitalize}"><span class="glyphicons glyphicons-cogwheel"></span></button>
			{/if}
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

<br clear="all">

<fieldset class="properties" style="margin-top:5px;">
	<legend>{'common.email_address'|devblocks_translate|capitalize}</legend>
	
	<div style="margin-left:15px;">
	{foreach from=$properties item=v key=k name=props}
		<div class="property">
			{if $k == '_'}
				<b>Key</b>
				Value
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
</fieldset>

{include file="devblocks:cerberusweb.core::internal/custom_fieldsets/profile_fieldsets.tpl" properties=$properties_custom_fieldsets}

{include file="devblocks:cerberusweb.core::internal/profiles/profile_record_links.tpl" properties=$properties_links}

<div>
{include file="devblocks:cerberusweb.core::internal/notifications/context_profile.tpl" context=$page_context context_id=$page_context_id}
</div>

<div>
{include file="devblocks:cerberusweb.core::internal/macros/behavior/scheduled_behavior_profile.tpl" context=$page_context context_id=$page_context_id}
</div>

<div style="clear:both;" id="profileAddressTabs">
	<ul>
		{$tabs = [activity,mail,comments]}
		
		<li><a href="{devblocks_url}ajax.php?c=internal&a=showTabActivityLog&scope=both&point={$point}&context={$page_context}&context_id={$page_context_id}{/devblocks_url}">{'common.log'|devblocks_translate|capitalize}</a></li>
		<li><a href="{devblocks_url}ajax.php?c=contacts&a=showTabMailHistory&point={$point}&address_ids={$page_context_id}{/devblocks_url}">{'addy_book.org.tabs.mail_history'|devblocks_translate}</a></li>
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
	tabOptions.active = Devblocks.getjQueryUiTabSelected('profileAddressTabs');
	
	var tabs = $("#profileAddressTabs").tabs(tabOptions);

	$('#btnDisplayAddyEdit').bind('click', function() {
		$popup = genericAjaxPopup('peek','c=internal&a=showPeekPopup&context={CerberusContexts::CONTEXT_ADDRESS}&context_id={$page_context_id}',null,false,'50%');
		$popup.one('address_save', function(event) {
			event.stopPropagation();
			document.location.href = '{devblocks_url}c=profiles&type=address&id={$page_context_id}-{$address->email|devblocks_permalink}{/devblocks_url}';
		});
	});
	// Interactions
	var $interaction_container = $('#spanInteractions');
	{include file="devblocks:cerberusweb.core::events/interaction/interactions_menu.js.tpl"}
});
</script>

<script type="text/javascript">
$(function() {
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
				var idx = event.which-49;
				var $tabs = $("#profileAddressTabs").tabs();
				$tabs.tabs('option', 'active', idx);
			} catch(ex) { } 
			break;
		case 101:  // (E) edit
			try {
				$('#btnDisplayAddyEdit').click();
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
});
</script>

{include file="devblocks:cerberusweb.core::internal/profiles/profile_common_scripts.tpl"}