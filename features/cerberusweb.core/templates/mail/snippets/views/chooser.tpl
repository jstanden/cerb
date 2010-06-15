{$view_fields = $view->getColumnsAvailable()}
{assign var=results value=$view->getData()}
{assign var=total value=$results[1]}
{assign var=data value=$results[0]}

<form id="viewForm{$view->id}" name="viewForm{$view->id}" action="{devblocks_url}{/devblocks_url}" method="post" onsubmit="return false;">
<input type="hidden" name="view_id" value="{$view->id}">
<input type="hidden" name="c" value="tickets">
<input type="hidden" name="a" value="">
<table cellpadding="1" cellspacing="0" border="0" width="100%" class="worklistBody">

	{* Column Headers *}
	<tr>
		{foreach from=$view->view_columns item=header name=headers}
			{* start table header, insert column title and link *}
			<th nowrap="nowrap" style="background-color:rgb(232,242,254);border-color:rgb(121,183,231);">
			<a href="javascript:;" style="color:rgb(74,110,158);" onclick="genericAjaxGet('view{$view->id}','c=internal&a=viewSortBy&id={$view->id}&sortBy={$header}');">{$view_fields.$header->db_label|capitalize}</a>
			
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
		{*<td align="center"><input type="checkbox" name="row_id[]" value="{$result.s_id}"></td>*}
		{foreach from=$view->view_columns item=column name=columns}
			{if substr($column,0,3)=="cf_"}
				{include file="file:$core_tpl/internal/custom_fields/view/cell_renderer.tpl"}
			{elseif $column=="s_title"}
			<td>
				<a href="javascript:;" onclick="var contextId=$('#view{$view->id}').data('context_id');if(null==contextId)return;genericAjaxGet('','c=display&a=getSnippet&context_id='+contextId+'&id={$result.s_id|escape}',function(text) { var divname=$('#view{$view->id}').data('text_element');insertAtCursor($('#'+divname)[0], text);$('#'+divname).focus(); } );" class="subject">{if empty($result.$column)}(no title){else}{$result.$column}{/if}</a>
				<a href="javascript:;" onclick="$('#divSnippetChooserPreview').html('<pre class=\'emailBody\'></pre>').insertAfter($(this));genericAjaxGet('','c=internal&a=snippetPaste&snippet_id={$result.s_id|escape}',function(text) { $('#divSnippetChooserPreview pre').append(text); } );"><span class="ui-icon ui-icon-newwin" style="display:inline-block;vertical-align:middle;" title="{$translate->_('views.peek')}"></span></a>
				{* [TODO] I can just insert this from $result.s_content if we're not rendering it *}
			</td>
			{elseif $column=="s_last_updated"}
			<td>
				<abbr title="{$result.$column|devblocks_date}">{$result.$column|devblocks_prettytime}</abbr>
			</td>
			{elseif $column=="s_last_updated_by" || $column=="s_created_by"}
			<td>
				{if empty($workers)}
					{$workers = DAO_Worker::getAll()}
				{/if}
				{$worker_id = $result.$column}
				{if $workers.$worker_id}
					{$workers.{$worker_id}->getName()}
				{/if}
			</td>
			{elseif $column=="su_hits"}
			<td>{if empty($result.$column)}0{else}{$result.$column}{/if}&nbsp;</td>
			{else}
			<td>{$result.$column}&nbsp;</td>
			{/if}
		{/foreach}
		</tr>
	</tbody>
	{/foreach}
	
</table>
<table cellpadding="2" cellspacing="0" border="0" width="100%" id="{$view->id}_actions">
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
<div id="divSnippetChooserPreview"></div>
<br>
