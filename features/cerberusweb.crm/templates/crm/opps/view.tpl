{$view_fields = $view->getColumnsAvailable()}
{assign var=results value=$view->getData()}
{assign var=total value=$results[1]}
{assign var=data value=$results[0]}
<table cellpadding="0" cellspacing="0" border="0" class="worklist" width="100%">
	<tr>
		<td nowrap="nowrap"><span class="title">{$view->name}</span> {if $view->id == 'search'}<a href="#{$view->id}_actions">{$translate->_('views.jump_to_actions')}</a>{/if}</td>
		<td nowrap="nowrap" align="right">
			<a href="javascript:;" onclick="$('#btnExplore{$view->id}').click();">explore</a>
			 | <a href="javascript:;" onclick="genericAjaxGet('customize{$view->id}','c=internal&a=viewCustomize&id={$view->id}');toggleDiv('customize{$view->id}','block');">{$translate->_('common.customize')|lower}</a>
			{if $active_worker->hasPriv('core.home.workspaces')} | <a href="javascript:;" onclick="genericAjaxGet('{$view->id}_tips','c=internal&a=viewShowCopy&view_id={$view->id}');toggleDiv('{$view->id}_tips','block');">{$translate->_('common.copy')|lower}</a>{/if}
			{if $active_worker->hasPriv('crm.opp.view.actions.export')} | <a href="javascript:;" onclick="genericAjaxGet('{$view->id}_tips','c=internal&a=viewShowExport&id={$view->id}');toggleDiv('{$view->id}_tips','block');">{$translate->_('common.export')|lower}</a>{/if}
			 | <a href="javascript:;" onclick="genericAjaxGet('view{$view->id}','c=internal&a=viewRefresh&id={$view->id}');"><span class="cerb-sprite sprite-refresh"></span></a>
		</td>
	</tr>
</table>

<div id="{$view->id}_tips" class="block" style="display:none;margin:10px;padding:5px;">Analyzing...</div>
<form id="customize{$view->id}" name="customize{$view->id}" action="#" onsubmit="return false;" style="display:none;"></form>
<form id="viewForm{$view->id}" name="viewForm{$view->id}" action="{devblocks_url}{/devblocks_url}" method="post">
<button id="btnExplore{$view->id}" type="button" style="display:none;" onclick="this.form.explore_from.value=$(this).closest('form').find('tbody input:checkbox:checked:first').val();this.form.a.value='viewOppsExplore';this.form.submit();"></button>
<input type="hidden" name="view_id" value="{$view->id}">
<input type="hidden" name="id" value="{$view->id}">
<input type="hidden" name="c" value="crm">
<input type="hidden" name="a" value="">
<input type="hidden" name="explore_from" value="0">
<table cellpadding="1" cellspacing="0" border="0" width="100%" class="worklistBody">

	{* Column Headers *}
	<tr>
		<th style="text-align:center"><input type="checkbox" onclick="checkAll('view{$view->id}',this.checked);"></th>
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

	{* Column Data *}
	{foreach from=$data item=result key=idx name=results}

	{if $smarty.foreach.results.iteration % 2}
		{assign var=tableRowClass value="even"}
	{else}
		{assign var=tableRowClass value="odd"}
	{/if}
	<tbody onmouseover="$(this).find('tr').addClass('hover');" onmouseout="$(this).find('tr').removeClass('hover');" onclick="if(getEventTarget(event)=='TD') { var $chk=$(this).find('input:checkbox:first');if(!$chk) return;$chk.attr('checked', !$chk.is(':checked')); } ">
		<tr class="{$tableRowClass}">
			<td align="center" rowspan="2"><input type="checkbox" name="row_id[]" value="{$result.o_id}"></td>
			<td colspan="{math equation="x" x=$smarty.foreach.headers.total}">
				{if $result.o_is_closed && $result.o_is_won}<img src="{devblocks_url}c=resource&p=cerberusweb.crm&f=images/up_plus_gray.gif{/devblocks_url}" align="top" title="Won"> 
				{elseif $result.o_is_closed && !$result.o_is_won}<img src="{devblocks_url}c=resource&p=cerberusweb.crm&f=images/down_minus_gray.gif{/devblocks_url}" align="top" title="Lost"> {/if}
				<a href="{devblocks_url}c=crm&d=opps&id={$result.o_id}{/devblocks_url}" class="subject">{if !empty($result.o_name)}{$result.o_name|escape}{else}{'common.no_title'|devblocks_translate}{/if}</a> <a href="javascript:;" onclick="genericAjaxPopup('peek','c=crm&a=showOppPanel&view_id={$view->id}&id={$result.o_id}', null, false, '500');"><span class="ui-icon ui-icon-newwin" style="display:inline-block;vertical-align:middle;" title="{$translate->_('views.peek')}"></span></a>
				
				{$object_workers = DAO_ContextLink::getContextLinks(CerberusContexts::CONTEXT_OPPORTUNITY, array_keys($data), CerberusContexts::CONTEXT_WORKER)}
				{if isset($object_workers.{$result.o_id})}
				<div style="display:inline;padding-left:5px;">
				{foreach from=$object_workers.{$result.o_id} key=worker_id item=worker name=workers}
					{if isset($workers.{$worker_id})}
						<span style="color:rgb(150,150,150);">
						{$workers.{$worker_id}->getName()}{if !$smarty.foreach.workers.last}, {/if}
						</span>
					{/if}
				{/foreach}
				</div>
				{/if}
			</td>
		</tr>
		<tr class="{$tableRowClass}">
		{foreach from=$view->view_columns item=column name=columns}
			{if substr($column,0,3)=="cf_"}
				{include file="file:$core_tpl/internal/custom_fields/view/cell_renderer.tpl"}
			{elseif $column=="o_id"}
				<td>{$result.o_id}&nbsp;</td>
			{elseif $column=="org_name"}
				<td>
					{if !empty($result.org_id)}
						<a href="javascript:;" onclick="genericAjaxPopup('peek','c=contacts&a=showOrgPeek&id={$result.org_id}&view_id={$view->id}',null,false,'500');">{$result.org_name}</a>&nbsp;
					{/if}
				</td>
			{elseif $column=="o_amount"}
				<td>{$result.o_amount|number_format:2}&nbsp;</td>
			{elseif $column=="a_email"}
				<td>
					{if !empty($result.a_email)}
						<a href="javascript:;" onclick="genericAjaxPopup('peek','c=contacts&a=showAddressPeek&email={$result.a_email|escape:'url'}&view_id={$view->id}',null,false,'500');" title="{$result.a_email}">{$result.a_email}</a>&nbsp;
					{else}
						<!-- [<a href="javascript:;">assign</a>]  -->
					{/if}
				</td>
			{elseif $column=="o_created_date"}
				<td><abbr title="{$result.$column|devblocks_date}">{$result.o_created_date|devblocks_prettytime}</abbr>&nbsp;</td>
			{elseif $column=="o_updated_date"}
				<td><abbr title="{$result.$column|devblocks_date}">{$result.o_updated_date|devblocks_prettytime}</abbr>&nbsp;</td>
			{elseif $column=="o_closed_date"}
				<td><abbr title="{$result.$column|devblocks_date}">{$result.o_closed_date|devblocks_prettytime}</abbr>&nbsp;</td>
			{elseif $column=="o_worker_id"}
				<td>
					{assign var=o_worker_id value=$result.o_worker_id}
					{if isset($workers.$o_worker_id)}
						{$workers.$o_worker_id->getName()}&nbsp;
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
		<td colspan="2">
			{if 'context'==$view->renderTemplate}<button type="button" onclick="removeSelectedContextLinks('{$view->id}');">Unlink</button>{/if}
			{if $active_worker->hasPriv('crm.opp.actions.update_all')}<button type="button" onclick="genericAjaxPopup('peek','c=crm&a=showOppBulkPanel&view_id={$view->id}&ids=' + Devblocks.getFormEnabledCheckboxValues('viewForm{$view->id}','row_id[]'),null,false,'500');"><span class="cerb-sprite sprite-folder_gear"></span> {'common.bulk_update'|devblocks_translate|lower}</button>{/if}
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
<br>