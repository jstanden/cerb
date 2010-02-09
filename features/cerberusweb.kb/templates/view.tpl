{assign var=results value=$view->getData()}
{assign var=total value=$results[1]}
{assign var=data value=$results[0]}
<table cellpadding="0" cellspacing="0" border="0" class="worklist" width="100%">
	<tr>
		<td nowrap="nowrap"><span class="title">{$view->name}</span></td>
		<td nowrap="nowrap" align="right">
			<a href="javascript:;" onclick="genericAjaxGet('view{$view->id}','c=internal&a=viewRefresh&id={$view->id}');">{$translate->_('common.refresh')|lower}</a>
			<!-- {if $view->id != 'search'} | <a href="{devblocks_url}c=internal&a=searchview&id={$view->id}{/devblocks_url}">{$translate->_('common.search')|lower} list</a>{/if} -->
			 | <a href="javascript:;" onclick="genericAjaxGet('customize{$view->id}','c=internal&a=viewCustomize&id={$view->id}');toggleDiv('customize{$view->id}','block');">{$translate->_('common.customize')|lower}</a>
		</td>
	</tr>
</table>

<form id="customize{$view->id}" name="customize{$view->id}" action="#" onsubmit="return false;" style="display:none;"></form>
<form id="viewForm{$view->id}" name="viewForm{$view->id}" action="{devblocks_url}{/devblocks_url}" method="post">
<input type="hidden" name="id" value="{$view->id}">
<input type="hidden" name="c" value="kb">
<input type="hidden" name="a" value="">
<input type="hidden" name="return" value="{$response_uri}">
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

	{assign var=rowIdPrefix value="row_"|cat:$view->id|cat:"_"|cat:$result.kb_id}
	{if $smarty.foreach.results.iteration % 2}
		{assign var=tableRowClass value="even"}
	{else}
		{assign var=tableRowClass value="odd"}
	{/if}
	
		<tr class="{$tableRowClass}" id="{$rowIdPrefix}_s" onmouseover="$(this).addClass('hover');" onmouseout="$(this).removeClass('hover');" onclick="if(getEventTarget(event)=='TD') checkAll('{$rowIdPrefix}_s');">
			<td align="center"><input type="checkbox" name="row_id[]" value="{$result.kb_id}"></td>
		{foreach from=$view->view_columns item=column name=columns}
			{if $column=="kb_id"}
			<td>{$result.kb_id}&nbsp;</td>
			{elseif $column=="kb_title"}
			<td>
				{if !empty($result.kb_title)}
				<span class="cerb-sprite sprite-document"></span> <a href="javascript:;" onclick="genericAjaxPanel('c=kb.ajax&a=showArticlePeekPanel&id={$result.kb_id}&view_id={$view->id}',null,false,'700');" class="subject">{$result.kb_title|escape}</a>				
				{/if}
			</td>
			{elseif $column=="kb_updated"}
			<td><abbr title="{$result.kb_updated|devblocks_date}">{$result.kb_updated|devblocks_prettytime}</abbr>&nbsp;</td>
			{elseif $column=="kb_format"}
			<td>
				{if 0==$result.$column}
					Plaintext
				{elseif 1==$result.$column}
					HTML
				{/if}
				&nbsp;
			</td>
			{elseif $column=="katc_top_category_id"}
			<td>
				{if !empty($result.$column)}
					{assign var=topic_id value=$result.$column}
					{if isset($categories.$topic_id)}
						{$categories.$topic_id->name}
					{/if}
				{/if}
				&nbsp;
			</td>
			{else}
			<td>{$result.$column}&nbsp;</td>
			{/if}
		{/foreach}
		</tr>
	{/foreach}
	
</table>
<table cellpadding="2" cellspacing="0" border="0" width="100%" id="{$view->id}_actions">
	{if $total}
	<tr>
		<td colspan="2">
			{if $active_worker->hasPriv('kb.articles.actions.update_all')}<button type="button" onclick="genericAjaxPanel('c=kb.ajax&a=showArticlesBulkPanel&view_id={$view->id}&ids=' + Devblocks.getFormEnabledCheckboxValues('viewForm{$view->id}','row_id[]'),null,false,'500');"><span class="cerb-sprite sprite-folder_gear"></span> {'common.bulk_update'|devblocks_translate|lower}</button>{/if}
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
