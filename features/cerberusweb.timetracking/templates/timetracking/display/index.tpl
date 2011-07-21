{include file="devblocks:cerberusweb.timetracking::timetracking/display/submenu.tpl"}

<h2>{'timetracking.activity.tab'|devblocks_translate|capitalize}</h2>

<fieldset class="properties">
	<legend>{$time_entry->getSummary()}</legend>
	
	<form action="{devblocks_url}{/devblocks_url}" method="post">
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
		
		<button type="button" id="btnDisplayTimeEdit"><span class="cerb-sprite sprite-document_edit"></span> Edit</button>
		
	</form>	
</fieldset>

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
		})
	});
</script>
