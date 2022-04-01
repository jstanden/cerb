{$view_fields = $view->getColumnsAvailable()}
{$results = $view->getData()}
{$total = $results[1]}
{$data = $results[0]}
<table cellpadding="0" cellspacing="0" border="0" width="100%" class="worklist">
	<tr>
		<td nowrap="nowrap"><h2>{$view->name}</h2></td>
	</tr>
</table>

<form id="viewForm{$view->id}" name="viewForm{$view->id}" action="{devblocks_url}{/devblocks_url}" method="post">
<input type="hidden" name="id" value="{$view->id}">
<input type="hidden" name="c" value="kb">
<input type="hidden" name="a" value="">
<input type="hidden" name="_csrf_token" value="{$session->csrf_token}">

{if !empty($total)}
<table cellpadding="3" cellspacing="0" border="0" width="100%" class="worklistBody">
	{* Column Headers *}
	<thead>
	<tr>
		{foreach from=$view->view_columns item=header name=headers}
			{* start table header, insert column title and link *}
			<th nowrap="nowrap" onclick="ajaxHtmlGet('#view{$view->id}','{devblocks_url}c=ajax&a=viewSortBy{/devblocks_url}?id={$view->id}&sort_by={$header}');">
			<a href="javascript:;" style="font-weight:bold;">{$view_fields.$header->db_label|capitalize}</a>
			
			{* add arrow if sorting by this column, finish table header tag *}
			{if $header==$view->renderSortBy}
				{if $view->renderSortAsc}
					<span class="glyphicons glyphicons-sort-by-attributes" style="color:rgb(30,143,234);"></span>
				{else}
					<span class="glyphicons glyphicons-sort-by-attributes-alt" style="color:rgb(30,143,234);"></span>
				{/if}
			{/if}
			</th>
		{/foreach}
	</tr>
	</thead>

	{* Column Data *}
	{foreach from=$data item=result key=idx name=results}

	{capture name=kb_title_block}
		{if !empty($result.kb_title)}
		<a href="{devblocks_url}c=kb&a=article&id={$result.kb_id}-{$result.kb_title|devblocks_permalink}{/devblocks_url}" class="record-link"><span id="subject_{$result.kb_id}_{$view->id}">{$result.kb_title}</span></a>				
		{/if}
	{/capture}

	{$tableRowClass = ($smarty.foreach.results.iteration % 2) ? "tableRowBg" : "tableRowAltBg"}
	<tbody style="cursor:pointer;">
		{if !in_array('kb_title', $view->view_columns)}
		<tr class="{$tableRowClass}">
			<td data-column="label" colspan="{$view->view_columns|count}">
				{$smarty.capture.kb_title_block nofilter}
			</td>
		</tr>
		{/if}
	
		<tr class="{$tableRowClass}">
		{foreach from=$view->view_columns item=column name=columns}
			{if substr($column,0,3)=="cf_"}
				{include file="devblocks:cerberusweb.support_center::support_center/internal/view/cell_renderer.tpl"}

			{elseif $column=="kb_title"}
				<td data-column="{$column}">
					{$smarty.capture.kb_title_block nofilter}
				</td>
				
			{elseif $column=="kb_updated"}
				<td data-column="{$column}"><abbr title="{$result.kb_updated|devblocks_date}">{$result.kb_updated|devblocks_prettytime}</abbr>&nbsp;</td>
				
			{elseif $column=="kb_format"}
				<td data-column="{$column}">
					{if 0==$result.$column}
						Plaintext
					{elseif 1==$result.$column}
						HTML
					{elseif 2==$result.$column}
						Markdown
					{/if}
					&nbsp;
				</td>
				
			{else}
				<td data-column="{$column}">{$result.$column}</td>
				
			{/if}
		{/foreach}
		</tr>
	</tbody>
	{/foreach}
</table>
{/if}

{if $total >= 0}
<table cellpadding="2" cellspacing="0" border="0" width="100%" id="{$view->id}_actions">
	<tr>
		<td align="right" valign="top" nowrap="nowrap">
			{$fromRow = ($view->renderPage * $view->renderLimit) + 1}
			{$toRow = ($fromRow-1) + $view->renderLimit}
			{$nextPage = $view->renderPage + 1}
			{$prevPage = $view->renderPage - 1}
			{$lastPage = ceil($total/$view->renderLimit)-1}
			
			{* Sanity checks *}
			{if $toRow > $total}{assign var=toRow value=$total}{/if}
			{if $fromRow > $toRow}{assign var=fromRow value=$toRow}{/if}
			
			{if $view->renderPage > 0}
				<a href="javascript:;" onclick="ajaxHtmlGet('#view{$view->id}','{devblocks_url}c=ajax&a=viewPage{/devblocks_url}?id={$view->id}&page=0');">&lt;&lt;</a>
				<a href="javascript:;" onclick="ajaxHtmlGet('#view{$view->id}','{devblocks_url}c=ajax&a=viewPage{/devblocks_url}?id={$view->id}&page={$prevPage}');">&lt;{'common.previous_short'|devblocks_translate|capitalize}</a>
			{/if}
			({'views.showing_from_to'|devblocks_translate:$fromRow:$toRow:$total})
			{if $toRow < $total}
				<a href="javascript:;" onclick="ajaxHtmlGet('#view{$view->id}','{devblocks_url}c=ajax&a=viewPage{/devblocks_url}?id={$view->id}&page={$nextPage}');">{'common.next'|devblocks_translate|capitalize}&gt;</a>
				<a href="javascript:;" onclick="ajaxHtmlGet('#view{$view->id}','{devblocks_url}c=ajax&a=viewPage{/devblocks_url}?id={$view->id}&page={$lastPage}');">&gt;&gt;</a>
			{/if}
		</td>
	</tr>
</table>
{/if}

</form>
