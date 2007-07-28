<!-- // These don't need to affect spacing 
{assign var=results value=$view->getTickets()}
{assign var=total value=$results[1]}
{assign var=tickets value=$results[0]}
 --><div id="{$view->id}_output_container">
	{include file="file:$path/tickets/rpc/ticket_view_output.tpl.php"}
</div>
<table cellpadding="0" cellspacing="0" border="0" class="tableBlue" width="100%" class="tableBg">
	<tr>
		<td nowrap="nowrap" class="tableThBlue">{$view->name} {if $view->id == 'search'}<a href="#{$view->id}_actions" style="color:rgb(255,255,255);font-size:11px;">jump to actions</a>{/if}</td>
		<td nowrap="nowrap" class="tableThBlue" align="right">
			<a href="javascript:;" onclick="ajax.getRefresh('{$view->id}');" class="tableThLink">{$translate->_('common.refresh')|lower}</a>
			{if $view->id != 'contact_history'}<span style="font-size:12px"> | </span>{if $total >= 50}<img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/information.gif{/devblocks_url}" align="absmiddle">{/if}<a href="javascript:;" onclick="genericAjaxGet('{$view->id}_tips','c=tickets&a=showViewAutoAssist&view_id={$view->id}');toggleDiv('{$view->id}_tips','block');" class="tableThLink">{"super sort"|lower}</a>{/if}
			{if $view->id != 'search'}<span style="font-size:12px"> | </span><a href="{devblocks_url}c=tickets&a=searchview&id={$view->id}{/devblocks_url}" class="tableThLink">{$translate->_('common.search')|lower} list</a>{/if}
			<span style="font-size:12px"> | </span><a href="javascript:;" onclick="ajax.getCustomize('{$view->id}');" class="tableThLink">{$translate->_('common.customize')|lower}</a>
			<span style="font-size:12px"> | </span><a href="javascript:;" onclick="genericAjaxGet('{$view->id}_tips','c=tickets&a=showViewRss&view_id={$view->id}');toggleDiv('{$view->id}_tips','block');" class="tableThLink"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/feed-icon-16x16.gif{/devblocks_url}" border="0" align="absmiddle"></a>
		</td>
	</tr>
</table>

<div id="{$view->id}_tips" class="block" style="display:none;margin:10px;padding:5px;">Analyzing...</div>
<form id="customize{$view->id}" action="#" onsubmit="return false;" style="display:none;"></form>
<form id="viewForm{$view->id}" name="viewForm{$view->id}">
<!-- 
<input type="hidden" name="c" value="tickets">
<input type="hidden" name="a" value="runAction">
 -->
<input type="hidden" name="id" value="{$view->id}">
<table cellpadding="0" cellspacing="0" border="0" width="100%" class="tableRowBg">

	{* Column Headers *}
	<tr class="tableTh">
		<th style="text-align:center"><input type="checkbox" onclick="checkAll('view{$view->id}',this.checked);"></th>
		{foreach from=$view->view_columns item=header name=headers}
			{* start table header, insert column title and link *}
			<th>
			{if $header=="t_mask"}<a href="javascript:;" onclick="ajax.getSortBy('{$view->id}','t_mask');">{$translate->_('ticket.id')}</a>
			{elseif $header=="t_last_wrote"}<a href="javascript:;" onclick="ajax.getSortBy('{$view->id}','t_last_wrote');">{$translate->_('ticket.last_wrote')}</a>
			{elseif $header=="t_first_wrote"}<a href="javascript:;" onclick="ajax.getSortBy('{$view->id}','t_first_wrote');">{$translate->_('ticket.first_wrote')}</a>
			{elseif $header=="t_created_date"}<a href="javascript:;" onclick="ajax.getSortBy('{$view->id}','t_created_date');">{$translate->_('ticket.created')}</a>
			{elseif $header=="t_updated_date"}<a href="javascript:;" onclick="ajax.getSortBy('{$view->id}','t_updated_date');">{$translate->_('ticket.updated')}</a>
			{elseif $header=="t_due_date"}<a href="javascript:;" onclick="ajax.getSortBy('{$view->id}','t_due_date');">{$translate->_('ticket.due')}</a>
			{elseif $header=="m_name"}<a href="javascript:;" onclick="ajax.getSortBy('{$view->id}','m_name');">{$translate->_('ticket.mailbox')}</a>
			{elseif $header=="t_spam_score"}<a href="javascript:;" onclick="ajax.getSortBy('{$view->id}','t_spam_score');">{$translate->_('common.spam')}</a>
			{elseif $header=="t_next_action"}<a href="javascript:;" onclick="ajax.getSortBy('{$view->id}','t_next_action');">{$translate->_('ticket.next_action')}</a>
			{elseif $header=="t_last_action_code"}<a href="javascript:;" onclick="ajax.getSortBy('{$view->id}','t_last_action_code');">{$translate->_('ticket.last_action')}</a>
			{elseif $header=="t_last_worker_id"}<a href="javascript:;" onclick="ajax.getSortBy('{$view->id}','t_last_worker_id');">{$translate->_('ticket.last_worker')}</a>
			{elseif $header=="tm_name"}<a href="javascript:;" onclick="ajax.getSortBy('{$view->id}','tm_name');">{$translate->_('common.team')}</a>
			{elseif $header=="t_category_id"}<a href="javascript:;" onclick="ajax.getSortBy('{$view->id}','t_category_id');">{$translate->_('common.bucket')|capitalize}</a></a>
			{/if}
			
			{* add arrow if sorting by this column, finish table header tag *}
			{if $header==$view->renderSortBy}
				{if $view->renderSortAsc}
					<img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/arrow_up.gif{/devblocks_url}" align="absmiddle">
				{else}
					<img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/arrow_down.gif{/devblocks_url}" align="absmiddle">
				{/if}
			{/if}
			</th>
		{/foreach}
	</tr>

	{* Column Data *}
	{foreach from=$tickets item=result key=idx name=results}

	{assign var=rowIdPrefix value="row_"|cat:$view->id|cat:"_"|cat:$result.t_id}
	{if $smarty.foreach.results.iteration % 2}
		{assign var=tableRowBg value="tableRowBg"}
	{else}
		{assign var=tableRowBg value="tableRowAltBg"}
	{/if}
	
		<tr class="{$tableRowBg}" id="{$rowIdPrefix}_s" onmouseover="toggleClass(this.id,'tableRowHover');toggleClass('{$rowIdPrefix}','tableRowHover');" onmouseout="toggleClass(this.id,'{$tableRowBg}');toggleClass('{$rowIdPrefix}','{$tableRowBg}');" onclick="if(getEventTarget(event)=='TD') checkAll('{$rowIdPrefix}_s');">
			<td align="center" rowspan="2"><input type="checkbox" name="ticket_id[]" value="{$result.t_id}"></td>
			<td colspan="{math equation="x" x=$smarty.foreach.headers.total}"><a href="{devblocks_url}c=display&id={$result.t_mask}{/devblocks_url}" class="ticketLink" style="font-size:12px;"><b id="subject_{$result.t_id}_{$view->id}">{if $result.t_is_closed}<strike>{$result.t_subject}</strike>{else}{$result.t_subject}{/if}</b></a> <a href="javascript:;" onclick="ajax.scheduleTicketPreview('{$result.t_id}',this);" style="color:rgb(180,180,180);font-size:90%;">(peek)</a></td>
		</tr>
		<tr class="{$tableRowBg}" id="{$rowIdPrefix}" onmouseover="toggleClass(this.id,'tableRowHover');toggleClass('{$rowIdPrefix}_s','tableRowHover');" onmouseout="toggleClass(this.id,'{$tableRowBg}');toggleClass('{$rowIdPrefix}_s','{$tableRowBg}');" onclick="if(getEventTarget(event)=='TD') checkAll('{$rowIdPrefix}_s');">
		{foreach from=$view->view_columns item=column name=columns}
			{if $column=="t_mask"}
			<td><a href="{devblocks_url}c=display&id={$result.t_mask}{/devblocks_url}">{$result.t_mask}</a></td>
			{elseif $column=="t_last_wrote"}
			<td><a href="javascript:;" onclick="genericAjaxPanel('c=tickets&a=showContactPanel&address={$ticket->last_wrote}',this);" title="{$result.t_last_wrote}">{$result.t_last_wrote|truncate:45:'...':true:true}</a></td>
			{elseif $column=="t_first_wrote"}
			<td><a href="javascript:;" onclick="genericAjaxPanel('c=tickets&a=showContactPanel&address={$ticket->first_wrote}',this);" title="{$result.t_first_wrote}">{$result.t_first_wrote|truncate:45:'...':true:true}</a></td>
			{elseif $column=="t_created_date"}
			<td>{$result.t_created_date|date_format}</td>
			{elseif $column=="t_updated_date"}
			<td>{$result.t_updated_date|date_format}</td>
			{elseif $column=="t_due_date"}
			<td>{if $result.t_due_date}{$result.t_due_date|date_format}{/if}</td>
			{*{elseif $column=="t_tasks"}
			<td align='center'>{if !empty($result.t_tasks)}{$result.t_tasks}{/if}</td>*}
			{elseif $column=="tm_name"}
			<td>{$result.tm_name}</td>
			{elseif $column=="t_category_id"}
				{assign var=ticket_category_id value=$result.t_category_id}
			<td>{if 0 == $ticket_category_id}{else}{$buckets.$ticket_category_id->name}{/if}</td>
			{elseif $column=="t_next_action"}
			<td title="{$result.t_next_action}"><span style="color:rgb(130,130,130);">{$result.t_next_action|truncate:35:'...'|indent:2:"&nbsp;"}</span></td>
			{elseif $column=="t_last_action_code"}
			<td>
				{assign var=action_worker_id value=$result.t_last_worker_id}
				<span style="color:rgb(130,130,130);">
				{if $result.t_last_action_code=='O'}
					<span title="{$result.t_first_wrote}">{"<b>New</b> from "|cat:$result.t_first_wrote|truncate:45:'...':true:true}</span>
				{elseif $result.t_last_action_code=='R'}
					{if isset($workers.$action_worker_id)}
						<span title="{$result.t_last_wrote}"><b>Incoming for {$workers.$action_worker_id->getName()}</b></span>
					{else}
						<span title="{$result.t_last_wrote}"><b>Incoming for Helpdesk</b></span>
					{/if}
				{elseif $result.t_last_action_code=='W'}
					{if isset($workers.$action_worker_id)}
						<span title="{$result.t_last_wrote}">Outgoing from {$workers.$action_worker_id->getName()}</span>
					{else}
						<span title="{$result.t_last_wrote}">Outgoing from Helpdesk</span>
					{/if}
				{/if}
				</span>
			</td>
			{elseif $column=="t_last_worker_id"}
			<td>
				{assign var=action_worker_id value=$result.t_last_worker_id}
				{if isset($workers.$action_worker_id)}{$workers.$action_worker_id->getName()}{/if}
			</td>
			{elseif $column=="t_spam_score"}
			<td>
				{math assign=score equation="x*100" format="%0.2f%%" x=$result.t_spam_score}
				{if empty($result.t_spam_training)}
				<!---<a href="javascript:;"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/warning.gif{/devblocks_url}" align="top" border="0" title="Not Spam ({$score})"></a>--->
				<!---<a href="javascript:;"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/check_gray.gif{/devblocks_url}" align="top" border="0" title="Not Spam ({$score})"></a>--->
				<a href="javascript:;" onclick="toggleDiv('{$rowIdPrefix}_s','none');toggleDiv('{$rowIdPrefix}','none');genericAjaxGet('{$view->id}_output_container','c=tickets&a=reportSpam&id={$result.t_id}&viewId={$view->id}');"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/{if $result.t_spam_score >= .90}warning.gif{else}warning_gray.gif{/if}{/devblocks_url}" align="top" border="0" title="Report Spam ({$score})
				{if !empty($result.t_interesting_words)}{$result.t_interesting_words}{/if}"></a>
				{/if}
			</td>
			{/if}
		{/foreach}
		</tr>
	{/foreach}
	
</table>
<table cellpadding="2" cellspacing="0" border="0" width="100%" class="tableBg" id="{$view->id}_actions">
	{if $total}
	<tr>
		<td colspan="2">
			{if $view->id != 'contact_history'}<span id="tourDashboardBatch"><button type="button" onclick="ajax.showBatchPanel('{$view->id}','{$dashboard_team_id}');"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/folder_gear.gif{/devblocks_url}" align="top"> bulk update</button></span>{/if}
			<button type="button" onclick="ajax.viewCloseTickets('{$view->id}',0);"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/folder_ok.gif{/devblocks_url}" align="top"> close</button>
			<button type="button" onclick="ajax.viewCloseTickets('{$view->id}',1);"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/spam.gif{/devblocks_url}" align="top"> spam</button>
			{if $active_worker && ($active_worker->is_superuser || $active_worker->can_delete)}<button type="button" onclick="ajax.viewCloseTickets('{$view->id}',2);"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/delete.gif{/devblocks_url}" align="top"> delete</button>{/if}
			
			<input type="hidden" name="move_to" value="">
			{assign var=result_groups value=$view->getInvolvedGroups()}

			<select name="move_to_select" onchange="this.form.move_to.value=this.form.move_to_select[this.selectedIndex].value;ajax.viewMoveTickets('{$view->id}');">
				<option value="">-- move to --</option>
				{foreach from=$team_categories item=team_category_list key=teamId}
					{assign var=team value=$teams.$teamId}
					{if in_array($teamId, $result_groups) && sizeof($result_groups) == 1 && !empty($active_worker_memberships.$teamId)}
						<optgroup label="-- {$team->name} --">
						{foreach from=$team_category_list item=category}
							<option value="c{$category->id}">{$category->name}</option>
						{/foreach}
						</optgroup>
					{/if}
				{/foreach}
				<optgroup label="Group Inboxes" style="">
					{foreach from=$teams item=team}
						<option value="t{$team->id}">{$team->name}</option>
					{/foreach}
				</optgroup>
			</select>
			
			<a href="javascript:;" onclick="toggleDiv('view{$view_id}_more');">More &raquo;</a>

			<div id="view{$view_id}_more" style="display:none;padding-top:5px;padding-bottom:5px;">
				<button type="button" onclick="ajax.viewTicketsAction('{$view->id}','not_spam');">not spam</button>
				<button type="button" onclick="ajax.viewTicketsAction('{$view->id}','merge');">merge</button>
			</div>

		</td>
	</tr>
	{/if}
	<tr>
		<td align="left" valign="top">
			{if $total && !empty($move_to_counts) && $view->id != 'contact_history'}
			<span style="font-size:100%;">
			<b>Move to: </b>
				{foreach from=$move_to_counts item=move_count key=move_code name=move_links}
					{if substr($move_code,0,1)=='t'}
						{assign var=move_team_id value=$move_code|regex_replace:"/[t]/":""}
						{assign var=move_bucket_id value=0}
					{elseif substr($move_code,0,1)=='c'}
						{assign var=move_team_id value=0}
						{assign var=move_bucket_id value=$move_code|regex_replace:"/[c]/":""}
					{/if}
					<a href="javascript:;" onclick="document.viewForm{$view->id}.move_to.value='{$move_code}';ajax.viewMoveTickets('{$view->id}');" title="Used {$move_count} times." style="{if !empty($move_team_id)}color:rgb(0,150,0);font-weight:bold;font-style:normal;{else}{/if}">{if !empty($move_team_id)}{$teams.$move_team_id->name}{else}{$buckets.$move_bucket_id->name}{/if}</a>{if !$smarty.foreach.move_links.last}, {/if}
				{/foreach}
			</span>
			{/if}
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
				<a href="javascript:;" onclick="ajax.getPage('{$view->id}',0);">&lt;&lt;</a>
				<a href="javascript:;" onclick="ajax.getPage('{$view->id}','{$prevPage}');">&lt;{$translate->_('common.prev')|capitalize}</a>
			{/if}
			(Showing {$fromRow}-{$toRow} of {$total})
			{if $toRow < $total}
				<a href="javascript:;" onclick="ajax.getPage('{$view->id}','{$nextPage}');">{$translate->_('common.next')|capitalize}&gt;</a>
				<a href="javascript:;" onclick="ajax.getPage('{$view->id}','{$lastPage}');">&gt;&gt;</a>
			{/if}
		</td>
	</tr>
</table>
</form>
<br>