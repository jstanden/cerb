{$view_fields = $view->getColumnsAvailable()}
{assign var=results value=$view->getData()}
{assign var=total value=$results[1]}
{assign var=data value=$results[0]}
<table cellpadding="0" cellspacing="0" border="0" width="100%" class="worklist">
	<tr>
		<td nowrap="nowrap"><h2>{$view->name}</h2></td>
	</tr>
</table>

<form id="customize{$view->id}" name="customize{$view->id}" action="#" onsubmit="return false;" style="display:none;"></form>
<form id="viewForm{$view->id}" name="viewForm{$view->id}" action="{devblocks_url}{/devblocks_url}" method="post">
<input type="hidden" name="id" value="{$view->id}">
<input type="hidden" name="c" value="history">
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
					<img src="{devblocks_url}c=resource&p=cerberusweb.support_center&f=images/sort_ascending.png{/devblocks_url}" align="absmiddle">
				{else}
					<img src="{devblocks_url}c=resource&p=cerberusweb.support_center&f=images/sort_descending.png{/devblocks_url}" align="absmiddle">
				{/if}
			{/if}
			</th>
		{/foreach}
	</tr>
	</thead>

	{* Column Data *}
	{foreach from=$data item=result key=idx name=results}

	{capture name=subject_block}
		{if $result.t_is_closed == 0}{* Active *}
			{if $result.t_is_waiting == 0}{* Open *}
				<img src="{devblocks_url}c=resource&p=cerberusweb.support_center&f=images/clock_gray.png{/devblocks_url}" border="0" align="top">
			{else}{* Waiting *}
				<img src="{devblocks_url}c=resource&p=cerberusweb.support_center&f=images/information.png{/devblocks_url}" border="0" align="top">
			{/if}
		{else}{* Closed *}
			<img src="{devblocks_url}c=resource&p=cerberusweb.support_center&f=images/check_gray.png{/devblocks_url}" border="0" align="top">
		{/if}
		
		{if !empty($result.t_subject)}
		<a href="{devblocks_url}c=history&mask={$result.t_mask}{/devblocks_url}" class="record-link">{$result.t_subject}</a>
		{/if}
	{/capture}

	{$tableRowClass = ($smarty.foreach.results.iteration % 2) ? "tableRowBg" : "tableRowAltBg"}
	<tbody style="cursor:pointer;">
		{if !in_array('t_subject', $view->view_columns)}
		<tr class="{$tableRowClass}">
			<td colspan="{$view->view_columns|count}">
				{$smarty.capture.subject_block nofilter}
			</td>
		</tr>
		{/if}
		
		<tr class="{$tableRowClass}">
		{foreach from=$view->view_columns item=column name=columns}
			{if substr($column,0,3)=="cf_"}
				{include file="devblocks:cerberusweb.support_center::support_center/internal/view/cell_renderer.tpl"}

			{elseif $column=="t_subject"}
				<td>{$smarty.capture.subject_block nofilter}</td>
			
			{elseif $column=="t_updated_date" || $column=="t_created_date" || $column=="t_closed_at" || $column=="t_reopen_at"}
				<td><abbr title="{$result.$column|devblocks_date}">{$result.$column|devblocks_prettytime}</abbr>&nbsp;</td>
			
			{elseif $column=="t_is_closed" || $column=="t_is_deleted" || $column=="t_is_waiting"}
				<td>{if $result.$column}{'common.yes'|devblocks_translate}{else}{'common.no'|devblocks_translate}{/if}</td>
			
			{elseif $column=="t_mask"}
				<td><a href="{devblocks_url}c=history&mask={$result.t_mask}{/devblocks_url}">{$result.$column}</a></td>
				
			{elseif $column=="t_owner_id"}
				{if empty($workers) && !empty($result.t_owner_id)}
					{$workers = DAO_Worker::getAll()}
				{/if}
				<td>
					{if isset($workers.{$result.t_owner_id})}
						{$workers.{$result.t_owner_id}->getName()}
					{/if}
				</td>
				
			{elseif $column=="t_elapsed_response_first" || $column=="t_elapsed_resolution_first"}
				<td>
					{if !empty($result.$column)}{$result.$column|devblocks_prettysecs:2}{/if}
				</td>
				
			{elseif $column=="t_last_action_code"}
				<td>
				{if $result.t_last_action_code=='O'}
					<span title="{$result.t_first_wrote}">New from {$result.t_last_wrote|truncate:45:'...':true:true}</span>
				{elseif $result.t_last_action_code=='R'}
					<span title="{$result.t_last_wrote}">{'mail.received'|devblocks_translate} from {$result.t_last_wrote|truncate:45:'...':true:true}</span>
				{elseif $result.t_last_action_code=='W'}
					<span title="{$result.t_last_wrote}">{'mail.sent'|devblocks_translate} from {$result.t_last_wrote|truncate:45:'...':true:true}</span>
				{/if}
				</td>
			
			{elseif $column=="t_group_id"}
				<td>
				{if $groups.{$result.$column}}
					{$groups.{$result.$column}->name}
				{/if}
				</td>
				
			{elseif $column=="t_bucket_id"}
				<td>
					{$buckets.{$result.$column}->name}
				</td>
				
			{else}
				<td>
					{$result.$column}
				</td>
				
			{/if}
		{/foreach}
		</tr>
	</tbody>
	{/foreach}
</table>
{/if}

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
</form>
