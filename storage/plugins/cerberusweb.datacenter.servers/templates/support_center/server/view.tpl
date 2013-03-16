{$view_fields = $view->getColumnsAvailable()}
{assign var=results value=$view->getData()}
{assign var=total value=$results[1]}
{assign var=data value=$results[0]}
	
<form id="viewForm{$view->id}" name="viewForm{$view->id}" action="{devblocks_url}{/devblocks_url}" method="post">
<input type="hidden" name="id" value="{$view->id}">
<input type="hidden" name="c" value="server">
<input type="hidden" name="a" value="">
<table cellpadding="1" cellspacing="0" border="0" width="100%">
	{* Column Headers *}
	<tr>
		{foreach from=$view->view_columns item=header name=headers}
			{* start table header, insert column title and link *}
			<td nowrap="nowrap">
				<a href="javascript:;" style="font-weight:bold;" onclick="ajaxHtmlGet('#view{$view->id}','{devblocks_url}c=ajax&a=viewSortBy{/devblocks_url}?id={$view->id}&sort_by={$header}');">
				{if $header=="j_context_id"}
					{'portal.sc.public.server.server'|devblocks_translate}
				{else}
					{$view_fields.$header->db_label|capitalize}
				{/if}
				</a>
			
			{* add arrow if sorting by this column, finish table header tag *}
			{if $header==$view->renderSortBy}
				{if $view->renderSortAsc}
					<img src="{devblocks_url}c=resource&p=cerberusweb.support_center&f=images/sort_ascending.png{/devblocks_url}" align="absmiddle">
				{else}
					<img src="{devblocks_url}c=resource&p=cerberusweb.support_center&f=images/sort_descending.png{/devblocks_url}" align="absmiddle">
				{/if}
			{/if}
			</td>
		{/foreach}
	</tr>

	{* Column Data *}
	<tbody>
	{foreach from=$data item=result key=idx name=results}

	{$tableRowClass = ($smarty.foreach.results.iteration % 2) ? "tableRowBg" : "tableRowAltBg"}
		<tr class="{$tableRowClass}" style="cursor:pointer;">
		{foreach from=$view->view_columns item=column name=columns}
			{if $column=="j_context_id"}
			<td>
				{$server = DAO_Server::get($result.$column)}
				{$server->name}
			</td>
			{elseif $column=="j_journal"}
			<td>
				{if !empty($result.$column)}
				<img src="{devblocks_url}c=resource&p=cerberusweb.support_center&f=images/document.gif{/devblocks_url}" align="absmiddle">
				<a href="{devblocks_url}c=server&a=detail&id={$result.j_id}-{$result.$column|devblocks_permalink}{/devblocks_url}"><span id="subject_{$result.j_id}_{$view->id}">{$result.$column}</span></a>				
				{/if}
			</td>
			{elseif $column=="j_created"}
			<td><abbr title="{$result.$column|devblocks_date}">{$result.$column|devblocks_prettytime}</abbr>&nbsp;</td>
			{elseif $column=="j_state"}
			<td><img src="{devblocks_url}c=resource&p=cerberusweb.support_center&f=images/traffic_light_small_{$result.$column}.png{/devblocks_url}" /></td>
			{else}
			<td>{$result.$column}</td>
			{/if}
		{/foreach}
		</tr>
	{/foreach}
	</tbody>
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
				<a href="javascript:;" onclick="ajaxHtmlGet('#view{$view->id}','{devblocks_url}c=ajax&a=viewPage{/devblocks_url}?id={$view->id}&page=0');">&lt;&lt;</a>
				<a href="javascript:;" onclick="ajaxHtmlGet('#view{$view->id}','{devblocks_url}c=ajax&a=viewPage{/devblocks_url}?id={$view->id}&page={$prevPage}');">&lt;{$translate->_('common.previous_short')|capitalize}</a>
			{/if}
			({'views.showing_from_to'|devblocks_translate:$fromRow:$toRow:$total})
			{if $toRow < $total}
				<a href="javascript:;" onclick="ajaxHtmlGet('#view{$view->id}','{devblocks_url}c=ajax&a=viewPage{/devblocks_url}?id={$view->id}&page={$nextPage}');">{$translate->_('common.next')|capitalize}&gt;</a>
				<a href="javascript:;" onclick="ajaxHtmlGet('#view{$view->id}','{devblocks_url}c=ajax&a=viewPage{/devblocks_url}?id={$view->id}&page={$lastPage}');">&gt;&gt;</a>
			{/if}
		</td>
	</tr>
</table>
</form>
