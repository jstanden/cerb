{$view_fields = $view->getColumnsAvailable()}
{assign var=results value=$view->getData()}
{assign var=total value=$results[1]}
{assign var=data value=$results[0]}

<form id="viewForm{$view->id}" name="viewForm{$view->id}" action="{devblocks_url}{/devblocks_url}" method="post" onsubmit="return false;">
<input type="hidden" name="view_id" value="{$view->id}">
<input type="hidden" name="c" value="config">
<input type="hidden" name="a" value="">

<table cellpadding="1" cellspacing="0" border="0" width="100%" class="worklistBody">

	{* Column Headers *}
	<tr>
		<th style="text-align:center;background-color:rgb(232,242,254);border-color:rgb(121,183,231);"><input type="checkbox" onclick="checkAll('view{$view->id}',this.checked);"></th>
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
	{assign var=ticket_group_id value=$result.t_team_id}
	{if !isset($active_worker_memberships.$ticket_group_id)}{*censor*}
	<tbody>
	<tr class="{$tableRowClass}">
		<td>&nbsp;</td>
		<td rowspan="2" colspan="{math equation="x" x=$smarty.foreach.headers.total}" style="color:rgb(140,140,140);font-size:10px;text-align:left;vertical-align:middle;">[Access Denied: {$teams.$ticket_group_id->name} #{$result.t_mask}]</td>
	</tr>
	<tr class="{$tableRowClass}">
		<td>&nbsp;</td>
	</tr>
	</tbody>
	
	{else}
	<tbody onmouseover="$(this).find('tr').addClass('hover');" onmouseout="$(this).find('tr').removeClass('hover');">
	<tr class="{$tableRowClass}">
		<td align="center" rowspan="2"><input type="checkbox" name="ticket_id[]" title="[#{$result.t_mask}] {$result.t_subject}" value="{$result.t_id}"></td>
		<td colspan="{math equation="x" x=$smarty.foreach.headers.total}">
			<a href="{devblocks_url}c=display&id={$result.t_mask}{/devblocks_url}" class="subject" target="_blank">{if $result.t_is_deleted}<span class="cerb-sprite sprite-delete2_gray"></span> {elseif $result.t_is_closed}<span class="cerb-sprite sprite-check_gray" title="{$translate->_('status.closed')}"></span> {elseif $result.t_is_waiting}<span class="cerb-sprite sprite-clock"></span> {/if}{$result.t_subject}</a>
			
			{$object_watchers = DAO_ContextLink::getContextLinks(CerberusContexts::CONTEXT_TICKET, array_keys($data), CerberusContexts::CONTEXT_WORKER)}
			{if isset($object_watchers.{$result.t_id})}
			<div style="display:inline;padding-left:5px;">
			{foreach from=$object_watchers.{$result.t_id} key=worker_id item=worker name=workers}
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
			{include file="devblocks:cerberusweb.core::internal/custom_fields/view/cell_renderer.tpl"}
		{elseif $column=="t_subject"}
		<td title="{$result.t_subject}">{$result.t_subject}</td>
		{elseif $column=="t_is_waiting"}
		<td>{if $result.t_is_waiting}<span class="cerb-sprite sprite-clock"></span>{else}{/if}</td>
		{elseif $column=="t_is_closed"}
		<td>{if $result.t_is_closed}<span class="cerb-sprite sprite-check_gray" title="{$translate->_('status.closed')}"></span>{else}{/if}</td>
		{elseif $column=="t_is_deleted"}
		<td>{if $result.t_is_deleted}<span class="cerb-sprite sprite-delete2_gray"></span>{else}{/if}</td>
		{elseif $column=="t_last_wrote"}
		<td>{$result.t_last_wrote|truncate:45:'...':true:true}</td>
		{elseif $column=="t_first_wrote"}
		<td>{$result.t_first_wrote|truncate:45:'...':true:true}</td>
		{elseif $column=="t_created_date"}
		<td title="{$result.t_created_date|devblocks_date}">{$result.t_created_date|devblocks_prettytime}</td>
		{elseif $column=="t_updated_date"}
			{if $result.t_category_id}
				{assign var=ticket_category_id value=$result.t_category_id}
				{assign var=bucket value=$buckets.$ticket_category_id}
			{/if}
			<td title="{$result.t_updated_date|devblocks_date}">{$result.t_updated_date|devblocks_prettytime}</td>
		{elseif $column=="t_due_date"}
		<td title="{if $result.t_due_date}{$result.t_due_date|devblocks_date}{/if}">{if $result.t_due_date}{$result.t_due_date|devblocks_prettytime}{/if}</td>
		{elseif $column=="t_team_id"}
		<td>
			{assign var=ticket_team_id value=$result.t_team_id}
			{$teams.$ticket_team_id->name}
		</td>
		{elseif $column=="t_category_id"}
			{assign var=ticket_team_id value=$result.t_team_id}
			{assign var=ticket_category_id value=$result.t_category_id}
			<td>
				{if 0 == $ticket_category_id}
				{else}
					{$buckets.$ticket_category_id->name}
				{/if}
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
		{elseif $column=="t_first_wrote_spam"}
		<td>{$result.t_first_wrote_spam}</td>
		{elseif $column=="t_first_wrote_nonspam"}
		<td>{$result.t_first_wrote_nonspam}</td>
		{elseif $column=="t_spam_score" || $column=="t_spam_training"}
		<td>
			{math assign=score equation="x*100" format="%0.2f%%" x=$result.t_spam_score}
			{if empty($result.t_spam_training)}
			<span class="cerb-sprite sprite-{if $result.t_spam_score >= 0.90}warning{else}warning_gray{/if}" title="Report Spam ({$score})"></span>
			{/if}
		</td>
		{else}
		<td>{if $result.$column}{$result.$column}{/if}</td>
		{/if}
	{/foreach}
	</tr>
	</tbody>
	{/if}{*!censor*}
	{/foreach}
</table>
<table cellpadding="2" cellspacing="0" border="0" width="100%">
	<tr>
		<td align="left" valign="top" id="{$view->id}_actions">
			<button type="button" class="devblocks-chooser-add-selected"><span class="cerb-sprite sprite-add"></span> Add Selected</button>
		</td>
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

{include file="devblocks:cerberusweb.core::internal/views/view_common_jquery_ui.tpl"}