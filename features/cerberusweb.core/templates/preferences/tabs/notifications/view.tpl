{$view_fields = $view->getColumnsAvailable()}
{assign var=results value=$view->getData()}
{assign var=total value=$results[1]}
{assign var=data value=$results[0]}
<table cellpadding="0" cellspacing="0" border="0" class="worklist" width="100%">
	<tr>
		<td nowrap="nowrap"><span class="title">{$view->name}</span> {if $view->id == 'search'}<a href="#{$view->id}_actions">{$translate->_('views.jump_to_actions')}</a>{/if}</td>
		<td nowrap="nowrap" align="right">
			<a href="javascript:;" onclick="genericAjaxGet('customize{$view->id}','c=internal&a=viewCustomize&id={$view->id}');toggleDiv('customize{$view->id}','block');">{$translate->_('common.customize')|lower}</a>
			{if $active_worker->hasPriv('core.home.workspaces')} | <a href="javascript:;" onclick="genericAjaxGet('{$view->id}_tips','c=internal&a=viewShowCopy&view_id={$view->id}');toggleDiv('{$view->id}_tips','block');">{$translate->_('common.copy')|lower}</a>{/if}
			 | <a href="javascript:;" onclick="genericAjaxGet('view{$view->id}','c=internal&a=viewRefresh&id={$view->id}');"><span class="cerb-sprite sprite-refresh"></span></a>
			{if $active_worker->hasPriv('core.rss')} | <a href="javascript:;" onclick="genericAjaxGet('{$view->id}_tips','c=tickets&a=showViewRss&view_id={$view->id}&source=core.rss.source.notification');toggleDiv('{$view->id}_tips','block');"><span class="cerb-sprite sprite-rss"></span></a>{/if}
			 | <input type="checkbox" onclick="checkAll('view{$view->id}',this.checked);this.blur();$rows=$('#viewForm{$view->id}').find('table.worklistBody').find('tbody > tr');if($(this).is(':checked')) { $rows.addClass('selected'); } else { $rows.removeClass('selected'); }">
		</td>
	</tr>
</table>

<div id="{$view->id}_tips" class="block" style="display:none;margin:10px;padding:5px;">Loading...</div>
<form id="customize{$view->id}" name="customize{$view->id}" action="#" onsubmit="return false;" style="display:none;"></form>
<form id="viewForm{$view->id}" name="viewForm{$view->id}" action="{devblocks_url}{/devblocks_url}" method="post">
<input type="hidden" name="view_id" value="{$view->id}">
<input type="hidden" name="c" value="preferences">
<input type="hidden" name="a" value="">
<input type="hidden" name="explore_from" value="0">
<table cellpadding="5" cellspacing="0" border="0" width="100%" class="worklistBody">

	{* Column Headers *}
	<tr>
		<th style="text-align:center"></th>
		{foreach from=$view->view_columns item=header name=headers}
			{* start table header, insert column title and link *}
			<th nowrap="nowrap">
			{if $header=="x"}<a href="javascript:;" onclick="genericAjaxGet('view{$view->id}','c=internal&a=viewSortBy&id={$view->id}&sortBy=we_id');">{$translate->_('contact_org.id')|capitalize}</a>
			{else}<a href="javascript:;" onclick="genericAjaxGet('view{$view->id}','c=internal&a=viewSortBy&id={$view->id}&sortBy={$header}');">{$view_fields.$header->db_label}</a>
			{/if}
			
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

	{* Column Data *}
	{foreach from=$data item=result key=idx name=results}

	{if $smarty.foreach.results.iteration % 2}
		{assign var=tableRowClass value="even"}
	{else}
		{assign var=tableRowClass value="odd"}
	{/if}
	<tbody style="cursor:pointer;">
		<tr class="{$tableRowClass}">
			<td align="center">		
				<input type="checkbox" name="row_id[]" value="{$result.we_id}" style="display:none;">
			</td>
		{foreach from=$view->view_columns item=column name=columns}
			{if $column=="we_id"}
				<td valign="top">{$result.we_id}&nbsp;</td>
			{elseif $column=="we_message"}
				<td valign="top">
					{if $result.we_is_read}<span class="cerb-sprite2 sprite-tick-circle-frame-gray"></span>{/if} 
					<a href="{devblocks_url}c=preferences&a=redirectRead&id={$result.we_id}{/devblocks_url}" class="subject">{$result.we_message}</a>			
				</td>
			{elseif $column=="we_created_date"}
				<td valign="top"><abbr title="{$result.we_created_date|devblocks_date}">{$result.we_created_date|devblocks_prettytime}</abbr>&nbsp;</td>
			{elseif $column=="we_worker_id"}
				{assign var=worker_id value=$result.$column}
				<td>
					{if !empty($worker_id)}
						{$workers.$worker_id->getName()}
					{else}
						(auto)
					{/if}
					&nbsp;
				</td>
			{elseif $column=="we_url"}
				<td valign="top"><a href="{devblocks_url}c=preferences&a=redirectRead&id={$result.we_id}{/devblocks_url}">{$result.$column}</a>&nbsp;</td>
			{elseif $column=="we_is_read"}
				<td valign="top">{if $result.$column}<span class="cerb-sprite2 sprite-tick-circle-frame-gray"></span>{/if}&nbsp;</td>
			{else}
				<td valign="top">{$result.$column}&nbsp;</td>
			{/if}
		{/foreach}
		</tr>
	</tbody>
	{/foreach}
	
</table>
<table cellpadding="2" cellspacing="0" border="0" width="100%" id="{$view->id}_actions">
	{if $total}
	<tr>
		<td colspan="2">
			<button id="btnExplore{$view->id}" type="button" onclick="this.form.explore_from.value=$(this).closest('form').find('tbody input:checkbox:checked:first').val();this.form.a.value='viewNotificationsExplore';this.form.submit();"><span class="cerb-sprite sprite-media_play_green"></span> {'common.explore'|devblocks_translate|lower}</button>
			<button type="button" onclick="genericAjaxPopup('peek','c=preferences&a=showNotificationsBulkPanel&view_id={$view->id}&ids=' + Devblocks.getFormEnabledCheckboxValues('viewForm{$view->id}','row_id[]'),null,false,'500');"><span class="cerb-sprite2 sprite-folder-gear"></span> {'common.bulk_update'|devblocks_translate|lower}</button>
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