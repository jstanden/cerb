{include file="devblocks:cerberusweb.timetracking::timetracking/display/submenu.tpl"}

<table cellspacing="0" cellpadding="0" border="0" width="100%" style="padding-bottom:5px;">
<tr>
	<td valign="top" style="padding-right:5px;">
		<h1>{$time_entry->getSummary()}</h1>
		<b>{'timetracking_entry.log_date'|devblocks_translate}:</b>
		{$time_entry->log_date|devblocks_prettytime}
		 &nbsp; 
		<b>{'common.status'|devblocks_translate|capitalize}:</b>
		{if !empty($time_entry->is_closed)}{'status.closed'|devblocks_translate|capitalize}{else}{'status.open'|devblocks_translate|capitalize}{/if}
		
		<form action="{devblocks_url}{/devblocks_url}" onsubmit="return false;">
		
		<!-- Toolbar -->
		<button type="button" id="btnDisplayTimeEdit"><span class="cerb-sprite sprite-document_edit"></span> Edit</button>
		
		</form>
	</td>
</tr>
</table>

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
