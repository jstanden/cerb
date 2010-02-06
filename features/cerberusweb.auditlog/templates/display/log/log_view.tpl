{assign var=results value=$view->getData()}
{assign var=total value=$results[1]}
{assign var=data value=$results[0]}
<table cellpadding="0" cellspacing="0" border="0" class="tableBlue" width="100%" class="tableBg">
	<tr>
		<td nowrap="nowrap" class="tableThBlue">{$view->name}</td>
		<td nowrap="nowrap" class="tableThBlue" align="right">
			<a href="javascript:;" onclick="genericAjaxGet('view{$view->id}','c=internal&a=viewRefresh&id={$view->id}');" class="tableThLink">{$translate->_('common.refresh')|lower}</a>
			<!-- {if $view->id != 'search'}<span style="font-size:12px"> | </span><a href="{devblocks_url}c=internal&a=searchview&id={$view->id}{/devblocks_url}" class="tableThLink">{$translate->_('common.search')|lower} list</a>{/if} -->
			<!-- <span style="font-size:12px"> | </span><a href="javascript:;" onclick="genericAjaxGet('customize{$view->id}','c=internal&a=viewCustomize&id={$view->id}');toggleDiv('customize{$view->id}','block');" class="tableThLink">{$translate->_('common.customize')|lower}</a> -->
		</td>
	</tr>
</table>

<form id="customize{$view->id}" name="customize{$view->id}" action="#" onsubmit="return false;" style="display:none;"></form>
<form id="viewForm{$view->id}" name="viewForm{$view->id}">
<input type="hidden" name="id" value="{$view->id}">
<table cellpadding="0" cellspacing="0" border="0" width="100%" class="tableRowBg">

	{* Column Headers *}
	<tr class="tableTh">
		<th style="text-align:center"><input type="checkbox" onclick="checkAll('view{$view->id}',this.checked);"></th>
		{foreach from=$view->view_columns item=header name=headers}
			{* start table header, insert column title and link *}
			<th nowrap="nowrap">
				<a href="javascript:;" onclick="genericAjaxGet('view{$view->id}','c=internal&a=viewSortBy&id={$view->id}&sortBy={$header}');">{$view_fields.$header->db_label|capitalize}</a>
			
			{* add arrow if sorting by this column, finish table header tag *}
			{if $header==$view->renderSortBy}
				{if $view->renderSortAsc}
					<img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/sort_ascending.png{/devblocks_url}" align="absmiddle">
				{else}
					<img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/sort_descending.png{/devblocks_url}" align="absmiddle">
				{/if}
			{/if}
			</th>
		{/foreach}
	</tr>

	{* Column Data *}
	{foreach from=$data item=result key=idx name=results}

	{assign var=rowIdPrefix value="row_"|cat:$view->id|cat:"_"|cat:$result.l_id}
	{if $smarty.foreach.results.iteration % 2}
		{assign var=tableRowBg value="tableRowBg"}
	{else}
		{assign var=tableRowBg value="tableRowAltBg"}
	{/if}
	
		<tr class="{$tableRowBg}" id="{$rowIdPrefix}_s" onmouseover="$(this).addClass('tableRowHover');" onmouseout="$(this).removeClass('tableRowHover');" onclick="if(getEventTarget(event)=='TD') checkAll('{$rowIdPrefix}_s');">
			<td align="center" rowspan="1"><input type="checkbox" name="row_id[]" value="{$result.l_id}"></td>
		{foreach from=$view->view_columns item=column name=columns}
			{if $column=="l_id"}
			<td>{$result.l_id}&nbsp;</td>
			{elseif $column=="l_worker_id"}
			<td>
				{assign var=log_worker_id value=$result.l_worker_id}
				{if isset($workers.$log_worker_id)}{$workers.$log_worker_id->getName()}{else}(auto){/if}&nbsp;
			</td>
			{elseif $column=="l_change_date"}
			<td><abbr title="{$result.l_change_date|devblocks_date}">{$result.l_change_date|devblocks_prettytime}</abbr>&nbsp;</td>
			{elseif $column=="l_change_field"}
				<td>
					{assign var=change_field value='t_'|cat:$result.l_change_field}
					{if isset($ticket_fields.$change_field)}
						{$ticket_fields.$change_field->db_label|capitalize}
					{else}
						{$change_field}&nbsp;
					{/if}
				</td>
			{elseif $column=="l_change_value"}
				<td>
					{assign var=change_field value=$result.l_change_field}
					{if $change_field=="updated_date"}
						{$result.l_change_value|devblocks_date}
					{elseif $change_field=="created_date"}
						{$result.l_change_value|devblocks_date}
					{elseif $change_field=="due_date"}
						{$result.l_change_value|devblocks_date}
					{elseif $change_field=="unlock_date"}
						{$result.l_change_value|devblocks_date}
					{elseif $change_field=="next_worker_id" || $change_field=="last_worker_id"}
						{assign var=change_worker_id value=$result.l_change_value}
						{if isset($workers.$change_worker_id)}{$workers.$change_worker_id->getName()}{else}Anybody{/if}&nbsp;
					{elseif $change_field=="is_deleted" || $change_field=="is_closed"}
						{if $result.l_change_value==1}{$translate->_('common.yes')}{else}{$translate->_('common.no')}{/if}
					{elseif $change_field=="spam_training"}
						{if $result.l_change_value=='S'}{$translate->_('training.report_spam')}{else}{$translate->_('training.not_spam')}{/if}
					{elseif $change_field=="team_id"}
						{assign var=change_team_id value=$result.l_change_value}
						{if isset($groups.$change_team_id)}{$groups.$change_team_id->name}{else}{/if}&nbsp;
					{elseif $change_field=="category_id"}
						{assign var=change_category_id value=$result.l_change_value}
						{if isset($buckets.$change_category_id)}{$buckets.$change_category_id->name}{else}Inbox{/if}&nbsp;
					{else}
						{$result.l_change_value}
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
<table cellpadding="2" cellspacing="0" border="0" width="100%" class="tableBg" id="{$view->id}_actions">
	{if $total}
	<tr>
		<td colspan="2">
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
