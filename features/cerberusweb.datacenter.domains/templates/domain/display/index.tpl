{$page_context = 'cerberusweb.contexts.datacenter.domain'}
{$page_context_id = $domain->id}

{include file="devblocks:cerberusweb.datacenter.domains::domain/display/submenu.tpl"}

<h2>{'cerberusweb.datacenter.domain'|devblocks_translate|capitalize}</h2>

<fieldset class="properties">
	<legend>{$domain->name}</legend>
	
	<form action="{devblocks_url}{/devblocks_url}" method="post" style="margin-bottom:5px;">

		{foreach from=$properties item=v key=k name=props}
			<div class="property">
				{if $k == 'server'}
					<b>{$v.label|capitalize}:</b>
					<a href="javascript:;" onclick="genericAjaxPopup('peek','c=datacenter&a=showServerPeek&view_id=&id={$domain->server_id}', null, false, '500');">{$v.server->name}</a>
				{else}
					{include file="devblocks:cerberusweb.core::internal/custom_fields/profile_cell_renderer.tpl"}
				{/if}
			</div>
			{if $smarty.foreach.props.iteration % 3 == 0 && !$smarty.foreach.props.last}
				<br clear="all">
			{/if}
		{/foreach}
		<br clear="all">
	
		<!-- Toolbar -->
		<div>
			<span>
			{$object_watchers = DAO_ContextLink::getContextLinks($page_context, array($page_context_id), CerberusContexts::CONTEXT_WORKER)}
			{include file="devblocks:cerberusweb.core::internal/watchers/context_follow_button.tpl" context=$page_context context_id=$page_context_id full=true}
			</span>		

			<!-- Macros -->
			{devblocks_url assign=return_url full=true}c=datacenter.domains&tab=domain&id={$page_context_id}-{$domain->name|devblocks_permalink}{/devblocks_url}
			{include file="devblocks:cerberusweb.core::internal/macros/display/button.tpl" context=$page_context context_id=$page_context_id macros=$macros return_url=$return_url}		
		
			<!-- Edit -->
			<button type="button" id="btnDatacenterDomainEdit"><span class="cerb-sprite sprite-document_edit"></span> Edit</button>
		</div>
	
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

<div id="datacenterDomainTabs">
	<ul>
		{$tabs = [activity, comments, links]}
		<li><a href="{devblocks_url}ajax.php?c=internal&a=showTabActivityLog&scope=target&point={$point}&context=cerberusweb.contexts.datacenter.domain&context_id={$page_context_id}{/devblocks_url}">{'common.activity_log'|devblocks_translate|capitalize}</a></li>		
		<li><a href="{devblocks_url}ajax.php?c=internal&a=showTabContextComments&context=cerberusweb.contexts.datacenter.domain&id={$page_context_id}{/devblocks_url}">{'common.comments'|devblocks_translate|capitalize}</a></li>
		<li><a href="{devblocks_url}ajax.php?c=internal&a=showTabContextLinks&context=cerberusweb.contexts.datacenter.domain&id={$page_context_id}{/devblocks_url}">{'common.links'|devblocks_translate}</a></li>
	</ul>
</div> 
<br>

{$tab_selected_idx=0}
{foreach from=$tabs item=tab_label name=tabs}
	{if $tab_label==$tab_selected}{$tab_selected_idx = $smarty.foreach.tabs.index}{/if}
{/foreach}

<script type="text/javascript">
	$(function() {
		var tabs = $("#datacenterDomainTabs").tabs( { selected:{$tab_selected_idx} } );
		
		$('#btnDatacenterDomainEdit').bind('click', function() {
			$popup = genericAjaxPopup('peek','c=datacenter.domains&a=showDomainPeek&id={$page_context_id}',null,false,'550');
			$popup.one('datacenter_domain_save', function(event) {
				event.stopPropagation();
				document.location.href = '{devblocks_url}c=datacenter.domains&a=domain&id={$page_context_id}{/devblocks_url}';
			});
		});
		
		{include file="devblocks:cerberusweb.core::internal/macros/display/menu_script.tpl"}
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
				$tabs = $("#datacenterDomainTabs").tabs();
				$tabs.tabs('select', idx);
			} catch(ex) { } 
			break;
		case 101:  // (E) edit
			try {
				$('#btnDatacenterDomainEdit').click();
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
