{$page_context = CerberusContexts::CONTEXT_BOT}
{$page_context_id = $model->id}
{$is_writeable = Context_Bot::isWriteableByActor($model, $active_worker)}

<div style="float:left;margin-right:10px;">
	<img src="{devblocks_url}c=avatars&context=bot&context_id={$model->id}{/devblocks_url}?v={$model->updated_at}" style="height:75px;width:75px;border-radius:5px;">
</div>

<div style="float:left">
	<h1>{$model->name}</h1>
	
	<div class="cerb-profile-toolbar">
		<form class="toolbar" action="{devblocks_url}{/devblocks_url}" onsubmit="return false;" style="margin-bottom:5px;">
			<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">
		
			<!-- Toolbar -->
			
			<span>
			{$object_watchers = DAO_ContextLink::getContextLinks($page_context, array($page_context_id), CerberusContexts::CONTEXT_WORKER)}
			{include file="devblocks:cerberusweb.core::internal/watchers/context_follow_button.tpl" context=$page_context context_id=$page_context_id full=true}
			</span>
			
			<!-- Macros -->
			{devblocks_url assign=return_url full=true}c=profiles&type=bot&id={$page_context_id}-{$model->name|devblocks_permalink}{/devblocks_url}
			{include file="devblocks:cerberusweb.core::internal/macros/display/button.tpl" context=$page_context context_id=$page_context_id macros=$macros return_url=$return_url}
			
			<!-- Edit -->
			{if $active_worker->is_superuser}
				<button type="button" id="btnDisplayBotEdit" title="{'common.edit'|devblocks_translate|capitalize} (E)" class="cerb-peek-trigger" data-context="{$page_context}" data-context-id="{$page_context_id}" data-edit="true"><span class="glyphicons glyphicons-cogwheel"></span></button>
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

<div style="clear:both;"></div>

<fieldset class="properties" style="margin-top:5px;">
	<legend>{'common.bot'|devblocks_translate|capitalize}</legend>

	<div style="margin-left:15px;">
		{foreach from=$properties item=v key=k name=props}
			<div class="property">
				{if $k == '__'}
				{else}
					{include file="devblocks:cerberusweb.core::internal/custom_fields/profile_cell_renderer.tpl"}
				{/if}
			</div>
			{if $smarty.foreach.props.iteration % 3 == 0 && !$smarty.foreach.props.last}
				<br clear="all">
			{/if}
		{/foreach}
		<br clear="all">
		
		<div style="margin-top:5px;">
			<button type="button" class="cerb-search-trigger" data-context="{CerberusContexts::CONTEXT_BEHAVIOR}" data-query="bot.id:{$page_context_id}"><div class="badge-count">{$owner_counts.behaviors|default:0}</div> {'common.behaviors'|devblocks_translate|capitalize}</button>
			<button type="button" class="cerb-search-trigger" data-context="{CerberusContexts::CONTEXT_CALENDAR}" data-query="owner.bot.id:{$page_context_id}"><div class="badge-count">{$owner_counts.calendars|default:0}</div> {'common.calendars'|devblocks_translate|capitalize}</button>
			<button type="button" class="cerb-search-trigger" data-context="{CerberusContexts::CONTEXT_CLASSIFIER}" data-query="owner.bot.id:{$page_context_id}"><div class="badge-count">{$owner_counts.classifiers|default:0}</div> {'common.classifiers'|devblocks_translate|capitalize}</button>
			{*<button type="button" class="cerb-search-trigger" data-context="{CerberusContexts::CONTEXT_COMMENT}" data-query="owner.bot.id:{$page_context_id}"><div class="badge-count">{$owner_counts.comments|default:0}</div> {'common.comments'|devblocks_translate|capitalize}</button>*}
			{if $is_writeable}<button type="button" class="cerb-search-trigger" data-context="{CerberusContexts::CONTEXT_CUSTOM_FIELDSET}" data-query="owner.bot.id:{$page_context_id}"><div class="badge-count">{$owner_counts.custom_fieldsets|default:0}</div> {'common.custom_fieldsets'|devblocks_translate|capitalize}</button>{/if}
		</div>
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

<div id="profileVaTabs">
	<ul>
		{$tabs = []}

		{$tabs[] = 'activity'}
		<li><a href="{devblocks_url}ajax.php?c=internal&a=showTabActivityLog&scope=both&point={$point}&context={$page_context}&context_id={$page_context_id}{/devblocks_url}">{'common.log'|devblocks_translate|capitalize}</a></li>
		
		{$tabs[] = 'behavior'}
		<li><a href="{devblocks_url}ajax.php?c=profiles&a=handleSectionAction&section=bot&action=showScheduledBehaviorsTab&point={$point}&va_id={$page_context_id}{/devblocks_url}">Scheduled Behaviors</a></li>
		
		{$tabs[] = 'comments'}
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
	tabOptions.active = Devblocks.getjQueryUiTabSelected('profileVaTabs');
	
	var tabs = $("#profileVaTabs").tabs(tabOptions);
	
	// Edit
	
	{if $is_writeable}
	$('#btnDisplayBotEdit')
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
				$tabs = $("#profileVaTabs").tabs();
				$tabs.tabs('option', 'active', idx);
			} catch(ex) { }
			break;
		case 101:  // (E) edit
			try {
				$('#btnDisplayBotEdit').click();
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