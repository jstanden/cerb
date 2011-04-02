{$view_fields = $view->getColumnsAvailable()}
{assign var=total value=$results[1]}
{assign var=data value=$results[0]}
<table cellpadding="0" cellspacing="0" border="0" class="worklist" width="100%">
	<tr>
		<td nowrap="nowrap"><span class="title">{$view->name}</span> {if $view->id == 'search'}<a href="#{$view->id}_actions">{$translate->_('views.jump_to_actions')}</a>{/if}</td>
		<td nowrap="nowrap" align="right">
			<a href="javascript:;" onclick="genericAjaxGet('customize{$view->id}','c=internal&a=viewCustomize&id={$view->id}');toggleDiv('customize{$view->id}','block');">{$translate->_('common.customize')|lower}</a>
			{if $active_worker->hasPriv('core.home.workspaces')} | <a href="javascript:;" onclick="genericAjaxGet('{$view->id}_tips','c=internal&a=viewShowCopy&view_id={$view->id}');toggleDiv('{$view->id}_tips','block');">{$translate->_('common.copy')|lower}</a>{/if}
			{if $active_worker->hasPriv('core.tasks.view.actions.export')} | <a href="javascript:;" onclick="genericAjaxGet('{$view->id}_tips','c=internal&a=viewShowExport&id={$view->id}');toggleDiv('{$view->id}_tips','block');">{$translate->_('common.export')|lower}</a>{/if}
			 | <a href="javascript:;" onclick="genericAjaxGet('view{$view->id}','c=internal&a=viewRefresh&id={$view->id}');"><span class="cerb-sprite sprite-refresh"></span></a>
			{if $active_worker->hasPriv('core.rss')} | <a href="javascript:;" onclick="genericAjaxGet('{$view->id}_tips','c=tickets&a=showViewRss&view_id={$view->id}&source=core.rss.source.task');toggleDiv('{$view->id}_tips','block');"><span class="cerb-sprite sprite-rss"></span></a>{/if}
			 | <input type="checkbox" onclick="checkAll('view{$view->id}',this.checked);this.blur();$rows=$('#viewForm{$view->id}').find('table.worklistBody').find('tbody > tr');if($(this).is(':checked')) { $rows.addClass('selected'); } else { $rows.removeClass('selected'); }">
		</td>
	</tr>
</table>

<div id="{$view->id}_tips" class="block" style="display:none;margin:10px;padding:5px;">Loading...</div>
<form id="customize{$view->id}" name="customize{$view->id}" action="#" onsubmit="return false;" style="display:none;"></form>
<form id="viewForm{$view->id}" name="viewForm{$view->id}" action="{devblocks_url}{/devblocks_url}" method="post">
<input type="hidden" name="view_id" value="{$view->id}">
<input type="hidden" name="context_id" value="cerberusweb.contexts.task">
<input type="hidden" name="c" value="tasks">
<input type="hidden" name="a" value="">
<input type="hidden" name="explore_from" value="0">
<table cellpadding="1" cellspacing="0" border="0" width="100%" class="worklistBody">

	{* Column Headers *}
	<thead>
	<tr>
		<th style="text-align:center;width:100px;">
			<a href="javascript:;">{'common.watchers'|devblocks_translate|capitalize}</a>
		</th>
		
		{foreach from=$view->view_columns item=header name=headers}
			{* start table header, insert column title and link *}
			<th nowrap="nowrap">
			<a href="javascript:;" onclick="genericAjaxGet('view{$view->id}','c=internal&a=viewSortBy&id={$view->id}&sortBy={$header}');">{$view_fields.$header->db_label|capitalize}</a>
			
			{* add arrow if sorting by this column, finish table header tag *}
			{if $header==$view->renderSortBy}
				{if $view->renderSortAsc}
					<span class="cerb-sprite sprite-sort_ascending"></span>
				{else}
					<span class="cerb-sprite sprite-sort_descending"></span>
				{/if}
			{/if}
			</th>
		{/foreach}
	</tr>
	</thead>

	{* Column Data *}
	{$object_watchers = DAO_ContextLink::getContextLinks(CerberusContexts::CONTEXT_TASK, array_keys($data), CerberusContexts::CONTEXT_WORKER)}
	{foreach from=$data item=result key=idx name=results}

	{if $smarty.foreach.results.iteration % 2}
		{assign var=tableRowClass value="even"}
	{else}
		{assign var=tableRowClass value="odd"}
	{/if}
	<tbody style="cursor:pointer;">
		<tr class="{$tableRowClass}">
			<td align="center" rowspan="2" nowrap="nowrap" style="padding:5px;">
				{include file="devblocks:cerberusweb.core::internal/watchers/context_follow_button.tpl" context=CerberusContexts::CONTEXT_TASK context_id=$result.t_id}
			</td>
			<td colspan="{$smarty.foreach.headers.total}">
				<input type="checkbox" name="row_id[]" value="{$result.t_id}" style="display:none;">
				{if $result.t_is_completed}
					<span class="cerb-sprite2 sprite-tick-circle-frame-gray" title="{$result.t_completed_date|devblocks_date}"></span>
				{/if}
				<a href="{devblocks_url}c=tasks&d=display&id={$result.t_id}{/devblocks_url}" class="subject">{if !empty($result.t_title)}{$result.t_title}{else}New Task{/if}</a> 
				
				<button type="button" class="peek" style="visibility:hidden;padding:1px;margin:0px 5px;" onclick="genericAjaxPopup('peek','c=tasks&a=showTaskPeek&id={$result.t_id}&view_id={$view->id}',null,false,'550');"><span class="cerb-sprite2 sprite-document-search-result" style="margin-left:2px" title="{$translate->_('views.peek')}"></span></button>
			</td>
		</tr>
		<tr class="{$tableRowClass}">
		{foreach from=$view->view_columns item=column name=columns}
			{if substr($column,0,3)=="cf_"}
				{include file="devblocks:cerberusweb.core::internal/custom_fields/view/cell_renderer.tpl"}
			{elseif $column=="t_id"}
				<td>{$result.t_id}&nbsp;</td>
			{elseif $column=="t_completed_date" || $column=="t_updated_date"}
				<td title="{$result.$column|devblocks_date}">
					{if !empty($result.$column)}
						{$result.$column|devblocks_prettytime}&nbsp;
					{/if}
				</td>
			{elseif $column=="t_due_date"}
				{assign var=overdue value=0}
				{if $result.t_due_date}
					{math assign=overdue equation="(t-x)" t=$timestamp_now x=$result.t_due_date format="%d"}
				{/if}
				<td title="{$result.t_due_date|devblocks_date}" style="{if $overdue > 0}color:rgb(220,0,0);font-weight:bold;{/if}">{$result.t_due_date|devblocks_prettytime}</td>
			{elseif $column=="t_url"}
				<td>
					{if empty($result.t_url)}
					<a href="{$result.t_url}" target="_blank">{$result.t_url|truncate:64:'...':true}</a>
					{/if}
				</td>
			{elseif $column=="t_is_completed"}
				<td>
					{if $result.t_is_completed}
					<span class="cerb-sprite2 sprite-tick-circle-frame-gray"></span>
					{/if}
				</td>
			{else}
				<td>{$result.$column}</td>
			{/if}
		{/foreach}
		</tr>
	</tbody>
	{/foreach}
	
</table>
<table cellpadding="2" cellspacing="0" border="0" width="100%" id="{$view->id}_actions">
	{if $total}
	<tr>
		<td colspan="2" valign="top">
			{if 'context'==$view->renderTemplate}<button type="button" onclick="removeSelectedContextLinks('{$view->id}');">Unlink</button>{/if}
			<button id="btnExplore{$view->id}" type="button" onclick="this.form.explore_from.value=$(this).closest('form').find('tbody input:checkbox:checked:first').val();this.form.a.value='viewTasksExplore';this.form.submit();"><span class="cerb-sprite sprite-media_play_green"></span> {'common.explore'|devblocks_translate|lower}</button>
			{if $active_worker->hasPriv('core.tasks.actions.update_all')}<button type="button" onclick="genericAjaxPopup('peek','c=tasks&a=showTaskBulkPanel&view_id={$view->id}&ids=' + Devblocks.getFormEnabledCheckboxValues('viewForm{$view->id}','row_id[]'),null,false,'500');"><span class="cerb-sprite2 sprite-folder-gear"></span> {'common.bulk_update'|devblocks_translate|lower}</button>{/if}
		</td>
	</tr>
	{/if}
	<tr>
		<td align="right" valign="top" nowrap="nowrap">
			{math assign=fromRow equation="(x*y)+1" x=$view->renderPage y=$view->renderLimit}
			{math assign=toRow equation="(x-1)+y" x=$fromRow y=$view->renderLimit}
			{math assign=nextPage equation="x+1" x=$view->renderPage}
			{math assign=prevPage equation="x-1" x=$view->renderPage}
			{math assign=lastPage equation="ceil(x/y)-1" x=$total y=$view->renderLimit}
			
			{* Sanity checks *}
			{if $toRow > $total}{assign var=toRow value=$total}{/if}
			{if $fromRow > $toRow}{assign var=fromRow value=$toRow}{/if}
			
			{if $view->renderPage > 0}
				<a href="javascript:;" onclick="genericAjaxGet('view{$view->id}','c=internal&a=viewPage&id={$view->id}&page=0');">&lt;&lt;</a>
				<a href="javascript:;" onclick="genericAjaxGet('view{$view->id}','c=internal&a=viewPage&id={$view->id}&page={$prevPage}');">&lt;{$translate->_('common.previous_short')|capitalize}</a>
			{/if}
			({'views.showing_from_to'|devblocks_translate:$fromRow:$toRow:$total})
			{if $toRow < $total}
				<a href="javascript:;" onclick="genericAjaxGet('view{$view->id}','c=internal&a=viewPage&id={$view->id}&page={$nextPage}');">{$translate->_('common.next')|capitalize}&gt;</a>
				<a href="javascript:;" onclick="genericAjaxGet('view{$view->id}','c=internal&a=viewPage&id={$view->id}&page={$lastPage}');">&gt;&gt;</a>
			{/if}
		</td>
	</tr>
</table>
</form>

{include file="devblocks:cerberusweb.core::internal/views/view_common_jquery_ui.tpl"}