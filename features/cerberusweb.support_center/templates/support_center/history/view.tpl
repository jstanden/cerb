{$view_fields = $view->getColumnsAvailable()}
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

	{capture name=subject_block}
		{if $result.t_status_id == Model_Ticket::STATUS_WAITING}
		<span class="glyphicons glyphicons-asterisk" style="color:rgb(200,0,0);"></span>
		{elseif $result.t_status_id == Model_Ticket::STATUS_CLOSED}
		<span class="glyphicons glyphicons-circle-ok" style="color:rgb(120,120,120);"></span>
		{elseif $result.t_status_id == Model_Ticket::STATUS_DELETED}
		{else}
		<span class="glyphicons glyphicons-clock"></span>
		{/if}
		
		{if !empty($result.t_subject)}
		<a href="{devblocks_url}c=history&mask={$result.t_mask}{/devblocks_url}" class="record-link">{$result.t_subject}</a>
		{/if}
	{/capture}
	
	{$tableRowClass = ($smarty.foreach.results.iteration % 2) ? "tableRowBg" : "tableRowAltBg"}
	<tbody style="cursor:pointer;">
		{if !in_array('t_subject', $view->view_columns)}
		<tr class="{$tableRowClass}">
			<td data-column="label" colspan="{$view->view_columns|count}">
				{$smarty.capture.subject_block nofilter}
			</td>
		</tr>
		{/if}
		
		<tr class="{$tableRowClass}">
		{foreach from=$view->view_columns item=column name=columns}
			{if substr($column,0,3)=="cf_"}
				{include file="devblocks:cerberusweb.support_center::support_center/internal/view/cell_renderer.tpl"}

			{elseif $column=="t_subject"}
				<td data-column="{$column}">{$smarty.capture.subject_block nofilter}</td>
			
			{elseif $column=="t_updated_date" || $column=="t_created_date" || $column=="t_closed_at" || $column=="t_reopen_at"}
				<td data-column="{$column}"><abbr title="{$result.$column|devblocks_date}">{$result.$column|devblocks_prettytime}</abbr>&nbsp;</td>
			
			{elseif $column=="t_status_id" || $column == "*_status"}
				<td data-column="{$column}">
					{$column = "t_status_id"}
					{if $result.$column == Model_Ticket::STATUS_OPEN}
						{'status.open'|devblocks_translate|lower}
					{elseif $result.$column == Model_Ticket::STATUS_CLOSED}
						{'status.closed'|devblocks_translate|lower}
					{elseif $result.$column == Model_Ticket::STATUS_DELETED}
						{'status.deleted'|devblocks_translate|lower}
					{else}
						{'status.waiting.client'|devblocks_translate|lower}
					{/if}
				</td>
			
			{elseif $column=="t_mask"}
				<td data-column="{$column}"><a href="{devblocks_url}c=history&mask={$result.t_mask}{/devblocks_url}">{$result.$column}</a></td>
				
			{elseif $column=="t_org_id"}
				<td data-column="{$column}">
					{$org_id = $result.t_org_id}
					{if $org_id && isset($object_orgs.$org_id)}
						{$org = $object_orgs.$org_id}
						{$org->name}
					{/if}
				</td>
				
			{elseif $column=="t_owner_id"}
				<td data-column="{$column}">
					{if isset($workers.{$result.t_owner_id})}
						{$workers.{$result.t_owner_id}->getName()}
					{/if}
				</td>
			
			{elseif $column=="t_first_wrote_address_id"}
				{$first_wrote = $object_first_wrotes.{$result.$column}}
				<td data-column="{$column}">
					{if $first_wrote}
					{$first_wrote->getNameWithEmail()|truncate:45:'...':true:true}
					{/if}
				</td>
			
			{elseif $column=="t_last_wrote_address_id"}
				{$last_wrote = $object_last_wrotes.{$result.$column}}
				<td data-column="{$column}">
					{if $last_wrote}
					{$last_wrote->getNameWithEmail()|truncate:45:'...':true:true}
					{/if}
				</td>
				
			{elseif $column=="t_elapsed_response_first" || $column=="t_elapsed_resolution_first"}
				<td data-column="{$column}">
					{if !empty($result.$column)}{$result.$column|devblocks_prettysecs:2}{/if}
				</td>
				
			{elseif $column=="t_group_id"}
				<td data-column="{$column}">
				{if $groups.{$result.$column}}
					{$groups.{$result.$column}->name}
				{/if}
				</td>
				
			{elseif $column=="t_bucket_id"}
				<td data-column="{$column}">
					{$buckets.{$result.$column}->name}
				</td>
				
			{else}
				<td data-column="{$column}">
					{$result.$column}
				</td>
				
			{/if}
		{/foreach}
		</tr>
	</tbody>
	{/foreach}
</table>

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
