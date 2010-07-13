{include file="$path/timetracking/display/submenu.tpl"}

<table cellspacing="0" cellpadding="0" border="0" width="100%" style="padding-bottom:5px;">
<tr>
	<td valign="top" style="padding-right:5px;">
		{$activities = DAO_TimeTrackingActivity::getWhere()}
		{$worker = DAO_Worker::get({$time_entry->worker_id})}
	
		{if isset($activities.{$time_entry->activity_id}->name)}
		{$title = 'timetracking.ui.tracked_desc'|devblocks_translate:$worker->getName():$time_entry->time_actual_mins:$activities.{$time_entry->activity_id}->name}
		{else}
		{$title = '%s tracked %s mins'|devblocks_translate:{$worker->getName()}:{$time_entry->time_actual_mins}}
		{/if}
	
		<h1>{$title}</h1>
		<b>{'timetracking_entry.log_date'|devblocks_translate}:</b>
		{$time_entry->log_date|devblocks_prettytime}
		
		<form action="{devblocks_url}{/devblocks_url}" onsubmit="return false;">
		
		<!-- Toolbar -->
		<button type="button" id="btnDisplayTimeEdit"><span class="cerb-sprite sprite-document_edit"></span> Edit</button>
		
		</form>
	</td>
</tr>
</table>

<div id="timeTabs">
	<ul>
		<li><a href="{devblocks_url}ajax.php?c=internal&a=showTabContextComments&context=cerberusweb.contexts.timetracking&id={$time_entry->id}{/devblocks_url}">{$translate->_('common.comments')|capitalize|escape:'quotes'}</a></li>		
		<li><a href="{devblocks_url}ajax.php?c=internal&a=showTabContextLinks&context=cerberusweb.contexts.timetracking&id={$time_entry->id}{/devblocks_url}">{$translate->_('common.links')|escape:'quotes'}</a></li>		

		{$tabs = [comments,links]}

		{foreach from=$tab_manifests item=tab_manifest}
			{$tabs[] = $tab_manifest->params.uri}
			<li><a href="{devblocks_url}ajax.php?c=config&a=showTab&ext_id={$tab_manifest->id}{/devblocks_url}"><i>{$tab_manifest->params.title|devblocks_translate|escape:'quotes'}</i></a></li>
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
