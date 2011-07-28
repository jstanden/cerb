{include file="devblocks:cerberusweb.timetracking::timetracking/display/submenu.tpl"}

<h2>{'timetracking.activity.tab'|devblocks_translate|capitalize}</h2>

<fieldset class="properties">
	<legend>{$time_entry->getSummary()}</legend>
	
	<form action="{devblocks_url}{/devblocks_url}" method="post" style="margin-bottom:5px;">
		{foreach from=$properties item=v key=k name=props}
			<div class="property">
				{if $k == 'status'}
					<b>{$v.label}:</b>
					{$v.value}
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
		<span>
		{$object_watchers = DAO_ContextLink::getContextLinks(CerberusContexts::CONTEXT_TIMETRACKING, array($time_entry->id), CerberusContexts::CONTEXT_WORKER)}
		{include file="devblocks:cerberusweb.core::internal/watchers/context_follow_button.tpl" context=CerberusContexts::CONTEXT_TIMETRACKING context_id=$time_entry->id full=true}
		</span>		
		
		<!-- Macros -->
		{devblocks_url assign=return_url full=true}c=timetracking&d=display&id={$time_entry->id}-{$time_entry->getSummary()|devblocks_permalink}{/devblocks_url}
		{include file="devblocks:cerberusweb.core::internal/macros/display/button.tpl" context=CerberusContexts::CONTEXT_TIMETRACKING context_id=$time_entry->id macros=$macros return_url=$return_url}		
		
		<!-- Edit -->
		<button type="button" id="btnDisplayTimeEdit"><span class="cerb-sprite sprite-document_edit"></span> Edit</button>
		
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

{include file="devblocks:cerberusweb.core::internal/notifications/context_profile.tpl" context=CerberusContexts::CONTEXT_TIMETRACKING context_id=$time_entry->id}

<div id="timeTabs">
	<ul>
		{$tabs = [activity,comments,links]}
		
		<li><a href="{devblocks_url}ajax.php?c=internal&a=showTabActivityLog&scope=target&point={$point}&context={CerberusContexts::CONTEXT_TIMETRACKING}&context_id={$time_entry->id}{/devblocks_url}">{'common.activity_log'|devblocks_translate|capitalize}</a></li>
		<li><a href="{devblocks_url}ajax.php?c=internal&a=showTabContextComments&context=cerberusweb.contexts.timetracking&id={$time_entry->id}{/devblocks_url}">{$translate->_('common.comments')|capitalize}</a></li>		
		<li><a href="{devblocks_url}ajax.php?c=internal&a=showTabContextLinks&context=cerberusweb.contexts.timetracking&id={$time_entry->id}{/devblocks_url}">{$translate->_('common.links')}</a></li>		

		{foreach from=$tab_manifests item=tab_manifest}
			{$tabs[] = $tab_manifest->params.uri}
			<li><a href="{devblocks_url}ajax.php?c=config&a=showTab&ext_id={$tab_manifest->id}{/devblocks_url}"><i>{$tab_manifest->params.title|devblocks_translate}</i></a></li>
		{/foreach}
	</ul>
</div> 
<br>

{$tab_selected_idx=0}
{foreach from=$tabs item=tab_label name=tabs}
	{if $tab_label==$tab_selected}{$tab_selected_idx = $smarty.foreach.tabs.index}{/if}
{/foreach}

<script type="text/javascript">
	$(function() {
		var tabs = $("#timeTabs").tabs( { selected:{$tab_selected_idx} } );
		
		$('#btnDisplayTimeEdit').bind('click', function() {
			$popup = genericAjaxPopup('peek','c=timetracking&a=showEntry&id={$time_entry->id}',null,false,'550');
			$popup.one('timetracking_save', function(event) {
				event.stopPropagation();
				document.location.href = '{devblocks_url}c=timetracking&a=display&id={$time_entry->id}{/devblocks_url}';
			});
			$popup.one('timetracking_delete', function(event) {
				event.stopPropagation();
				document.location.href = '{devblocks_url}c=activity&a=timetracking{/devblocks_url}';
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
				$tabs = $("#timeTabs").tabs();
				$tabs.tabs('select', idx);
			} catch(ex) { } 
			break;
		case 101:  // (E) edit
			try {
				$('#btnDisplayTimeEdit').click();
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