{$page_context = 'cerberusweb.contexts.datacenter.server'}
{$page_context_id = $server->id}

<div style="float:left;">
	<h1>{$server->name}</h1>
</div>

<div style="float:right;">
	{$ctx = Extension_DevblocksContext::get($page_context)}
	{include file="devblocks:cerberusweb.core::search/quick_search.tpl" view=$ctx->getSearchView() return_url="{devblocks_url}c=search&context={$ctx->manifest->params.alias}{/devblocks_url}" reset=true}
</div>

<div style="clear:both;"></div>

<fieldset class="properties">
	<legend>{'cerberusweb.datacenter.common.server'|devblocks_translate|capitalize}</legend>
	
	{$owners = $server->getOwners()}
	{if !empty($owners)}
		<div class="property">
			<b>{$translate->_('common.contact_person')|capitalize}:</b>
			{foreach from=$owners item=v name=ownrs}
				{if $smarty.foreach.ownrs.index < 3}
					{$contact = DAO_ContactPerson::get($v->id)}
					{$contact->getPrimaryAddress()->getName()}
					{if $smarty.foreach.ownrs.index != 2 && ($smarty.foreach.ownrs.index+1) != $smarty.foreach.ownrs.last}, {/if}
				{elseif $smarty.foreach.ownrs.index == 3}
					...
				{/if}
			{/foreach}
		</div>
		{$cnt = 1}
	{else}
		{$cnt = 0}
	{/if}
	
	{foreach from=$properties item=v key=k name=props}
		<div class="property">
			{if $k == '...'}
				<b>{$translate->_('...')|capitalize}:</b>
				...
			{else}
				{include file="devblocks:cerberusweb.core::internal/custom_fields/profile_cell_renderer.tpl"}
			{/if}
		</div>
		{if ($smarty.foreach.props.iteration + $cnt) % 3 == 0 && !$smarty.foreach.props.last}
			<br clear="all">
		{/if}
	{/foreach}
	
	{if !empty($properties)}
	<br clear="all">
	{/if}
	
	<form class="toolbar" action="{devblocks_url}{/devblocks_url}" method="post" style="margin-bottom:5px;">
		<!-- Toolbar -->
		<span>
		{$object_watchers = DAO_ContextLink::getContextLinks($page_context, array($page_context_id), CerberusContexts::CONTEXT_WORKER)}
		{include file="devblocks:cerberusweb.core::internal/watchers/context_follow_button.tpl" context=$page_context context_id=$page_context_id full=true}
		</span>
		
		<!-- Macros -->
		{devblocks_url assign=return_url full=true}c=profiles&type=server&id={$page_context_id}-{$server->name|devblocks_permalink}{/devblocks_url}
		{include file="devblocks:cerberusweb.core::internal/macros/display/button.tpl" context=$page_context context_id=$page_context_id macros=$macros return_url=$return_url}		
		
		<!-- Edit -->
		<button type="button" id="btnDatacenterServerEdit" title="{'common.edit'|devblocks_translate|capitalize}">&nbsp;<span class="cerb-sprite2 sprite-gear"></span>&nbsp;</button>
	</form>
	
	{if $pref_keyboard_shortcuts}
	<small>
		{$translate->_('common.keyboard')|lower}:
		(<b>e</b>) {'common.edit'|devblocks_translate|lower}
		{if !empty($macros)}(<b>m</b>) {'common.macros'|devblocks_translate|lower} {/if}
		(<b>1-9</b>) change tab
	</small> 
	{/if}
</fieldset>

<div>
{include file="devblocks:cerberusweb.core::internal/notifications/context_profile.tpl" context=$page_context context_id=$page_context_id}
</div>

<div>
{include file="devblocks:cerberusweb.core::internal/macros/behavior/scheduled_behavior_profile.tpl" context=$page_context context_id=$page_context_id}
</div>

<div id="datacenterServerTabs">
	<ul>
		{$tabs = [activity,comments,links,journal]}
		
		<li><a href="{devblocks_url}ajax.php?c=internal&a=showTabActivityLog&scope=target&point={$point}&context=cerberusweb.contexts.datacenter.server&context_id={$page_context_id}{/devblocks_url}">{'common.activity_log'|devblocks_translate|capitalize}</a></li>   
		<li><a href="{devblocks_url}ajax.php?c=internal&a=showTabContextComments&point={$point}&context=cerberusweb.contexts.datacenter.server&point={$point}&id={$page_context_id}{/devblocks_url}">{'common.comments'|devblocks_translate|capitalize} <div class="tab-badge">{DAO_Comment::count($page_context, $page_context_id)|default:0}</div></a></li>
		<li><a href="{devblocks_url}ajax.php?c=internal&a=showTabContextLinks&point={$point}&context=cerberusweb.contexts.datacenter.server&point={$point}&id={$page_context_id}{/devblocks_url}">{'common.links'|devblocks_translate} <div class="tab-badge">{DAO_ContextLink::count($page_context, $page_context_id)|default:0}</div></a></li>
		<li><a href="{devblocks_url}ajax.php?c=internal&a=showTabContextJournal&point={$point}&context=cerberusweb.contexts.datacenter.server&point={$point}&id={$page_context_id}{/devblocks_url}">{'common.journal'|devblocks_translate|capitalize} <div class="tab-badge">{DAO_Journal::count($page_context, $page_context_id)|default:0}</div></a></li>
		
		{foreach from=$tab_manifests item=tab_manifest}
			{$tabs[] = $tab_manifest->params.uri}
			<li><a href="{devblocks_url}ajax.php?c=profiles&a=showTab&ext_id={$tab_manifest->id}&point={$point}&context={$page_context}&context_id={$page_context_id}{/devblocks_url}"><i>{$tab_manifest->params.title|devblocks_translate}</i></a></li>
		{/foreach}
	</ul>
</div> 
<br>

{$selected_tab_idx=0}
{foreach from=$tabs item=tab_label name=tabs}
	{if $tab_label==$selected_tab}{$selected_tab_idx = $smarty.foreach.tabs.index}{/if}
{/foreach}

<script type="text/javascript">
	$(function() {
		var tabs = $("#datacenterServerTabs").tabs( { selected:{$selected_tab_idx} } );
		
		$('#btnDatacenterServerEdit').bind('click', function() {
			$popup = genericAjaxPopup('peek','c=internal&a=showPeekPopup&context={$page_context}&context_id={$page_context_id}',null,false,'550');
			$popup.one('datacenter_server_save', function(event) {
				event.stopPropagation();
				document.location.href = '{devblocks_url}c=profiles&type=server&id={$page_context_id}{/devblocks_url}';
			});
		})
	});
	
	{include file="devblocks:cerberusweb.core::internal/macros/display/menu_script.tpl"}
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
				$tabs = $("#datacenterServerTabs").tabs();
				$tabs.tabs('select', idx);
			} catch(ex) { } 
			break;
		case 101:  // (E) edit
			try {
				$('#btnDatacenterServerEdit').click();
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

{$profile_scripts = Extension_ContextProfileScript::getExtensions(true, $page_context)}
{if !empty($profile_scripts)}
{foreach from=$profile_scripts item=renderer}
	{if method_exists($renderer,'renderScript')}
		{$renderer->renderScript($page_context, $page_context_id)}
	{/if}
{/foreach}
{/if}
