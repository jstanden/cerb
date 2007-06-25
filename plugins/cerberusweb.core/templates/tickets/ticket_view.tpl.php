{if !empty($last_action)}
<div id="{$view->id}_output" style="margin:10px;padding:5px;border:1px solid rgb(200,200,200);background-color:rgb(250,250,150);">
<table cellspacing="0" cellpadding="0" border="0" width="100%">
<tr>
	<td>
		<img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/information.gif{/devblocks_url}" align="absmiddle"> 
	
		{$last_action_count} ticket{if $last_action_count!=1}s{/if} 
	
		{if $last_action->action == 'spam'}
			marked spam.
		{elseif $last_action->action == 'delete'}
			deleted.
		{elseif $last_action->action == 'close'}
			closed.
		{elseif $last_action->action == 'move'}
			{assign var=moved_to_team_id value=$last_action->action_params.team_id}
			{assign var=moved_to_category_id value=$last_action->action_params.category_id}
	
			moved to 
			{if empty($moved_to_category_id)}
				'{$teams.$moved_to_team_id->name}'.
			{else}
				{assign var=moved_team_category value=$team_categories.$moved_to_team_id}
				'{$teams.$moved_to_team_id->name}: {$moved_team_category.$moved_to_category_id->name}'.
			{/if}
		{/if}
		
		( <a href="javascript:;" onclick="ajax.viewUndo('{$view->id}');" style="font-weight:bold;">Undo</a> )
	</td>
	
	<td align="right">
		<a href="javascript:;" onclick="toggleDiv('{$view->id}_output','none');genericAjaxGet('','c=tickets&a=viewUndo&view_id={$view->id}&clear=1');" style="">close</a> 
	</td>
</tr>
</table>
</div>
{/if}

<table cellpadding="0" cellspacing="0" border="0" class="tableBlue" width="100%" class="tableBg">
	<tr>
		<td nowrap="nowrap" class="tableThBlue">{$view->name}</td>
		<td nowrap="nowrap" class="tableThBlue" align="right">
			<a href="javascript:;" onclick="ajax.getRefresh('{$view->id}');" class="tableThLink">{$translate->_('common.refresh')|lower}</a><span style="font-size:12px"> | </span>
			{*if !empty($view->tips)*}<img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/information.gif{/devblocks_url}" align="absmiddle"><a href="javascript:;" onclick="genericAjaxGet('{$view->id}_tips','c=tickets&a=showViewAutoAssist&view_id={$view->id}');toggleDiv('{$view->id}_tips','block');" class="tableThLink">{"auto-assist"|lower}</a><span style="font-size:12px"> | </span>{*/if*}
			<!-- <a href="javascript:;" onclick="" class="tableThLink">read all</a><span style="font-size:12px"> | </span> -->
			{if $view->id != 'search'}<a href="{devblocks_url}c=tickets&a=searchview&id={$view->id}{/devblocks_url}" class="tableThLink">{$translate->_('common.search')|lower} list</a><span style="font-size:12px"> | </span>{/if}
			<a href="javascript:;" onclick="ajax.getCustomize('{$view->id}');" class="tableThLink">{$translate->_('common.customize')|lower}</a>
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
			{if $header=="t_mask"}
			<th><a href="javascript:;" onclick="ajax.getSortBy('{$view->id}','t_mask');">{$translate->_('ticket.id')}</a></th>
			{*
			{elseif $header=="t_priority"}
			<th><a href="javascript:;" onclick="ajax.getSortBy('{$view->id}','t_priority');">{$translate->_('ticket.priority')}</a></th>
			*}
			{elseif $header=="t_last_wrote"}
			<th><a href="javascript:;" onclick="ajax.getSortBy('{$view->id}','t_last_wrote');">{$translate->_('ticket.last_wrote')}</a></th>
			{elseif $header=="t_first_wrote"}
			<th><a href="javascript:;" onclick="ajax.getSortBy('{$view->id}','t_first_wrote');">{$translate->_('ticket.first_wrote')}</a></th>
			{elseif $header=="t_created_date"}
			<th><a href="javascript:;" onclick="ajax.getSortBy('{$view->id}','t_created_date');">{$translate->_('ticket.created')}</a></th>
			{elseif $header=="t_updated_date"}
			<th><a href="javascript:;" onclick="ajax.getSortBy('{$view->id}','t_updated_date');">{$translate->_('ticket.updated')}</a></th>
			{elseif $header=="t_due_date"}
			<th><a href="javascript:;" onclick="ajax.getSortBy('{$view->id}','t_due_date');">{$translate->_('ticket.due')}</a></th>
{*			{elseif $header=="t_tasks"}
			<th><a href="javascript:;" onclick="ajax.getSortBy('{$view->id}','t_tasks');">{$translate->_('common.tasks')}</a></th> *}
			{elseif $header=="m_name"}
			<th><a href="javascript:;" onclick="ajax.getSortBy('{$view->id}','m_name');">{$translate->_('ticket.mailbox')}</a></th>
			{elseif $header=="t_spam_score"}
			<th><a href="javascript:;" onclick="ajax.getSortBy('{$view->id}','t_spam_score');">{$translate->_('common.spam')}</a></th>
			{elseif $header=="t_next_action"}
			<th><a href="javascript:;" onclick="ajax.getSortBy('{$view->id}','t_next_action');">{$translate->_('ticket.next_action')}</a></th>
			{elseif $header=="tm_name"}
			<th><a href="javascript:;" onclick="ajax.getSortBy('{$view->id}','tm_name');">{$translate->_('common.team')}</a></th>
			{elseif $header=="cat_name"}
			<th><a href="javascript:;" onclick="ajax.getSortBy('{$view->id}','cat_name');">{$translate->_('common.bucket')|capitalize}</a></th>
			{elseif $header=="t_owner_id"}
			<th><a href="javascript:;" onclick="ajax.getSortBy('{$view->id}','t_owner_id');">{$translate->_('ticket.owner')|capitalize}</a></th>
			{/if}
		{/foreach}
	</tr>

	{* Column Data *}
	{assign var=results value=$view->getTickets()}
	{assign var=total value=$results[1]}
	{assign var=tickets value=$results[0]}
	{foreach from=$tickets item=result key=idx name=results}

	{assign var=rowIdPrefix value="row_"|cat:$view->id|cat:"_"|cat:$result.t_id}
	{if $smarty.foreach.results.iteration % 2}
		{assign var=tableRowBg value="tableRowBg"}
	{else}
		{assign var=tableRowBg value="tableRowAltBg"}
	{/if}
	
		<tr class="{$tableRowBg}" id="{$rowIdPrefix}_s" onmouseover="toggleClass(this.id,'tableRowHover');toggleClass('{$rowIdPrefix}','tableRowHover');" onmouseout="toggleClass(this.id,'{$tableRowBg}');toggleClass('{$rowIdPrefix}','{$tableRowBg}');" onclick="if(getEventTarget(event)=='TD') checkAll('{$rowIdPrefix}_s');">
			<td align="center" rowspan="2"><input type="checkbox" name="ticket_id[]" value="{$result.t_id}"></td>
			<td colspan="{math equation="x" x=$smarty.foreach.headers.total}"><a href="{devblocks_url}c=display&id={$result.t_mask}#latest{/devblocks_url}" class="ticketLink" style="font-size:12px;"><b id="subject_{$result.t_id}_{$view->id}">{if $result.t_is_closed}<strike>{$result.t_subject}</strike>{else}{$result.t_subject}{/if}</b></a> <a href="javascript:;" onclick="ajax.scheduleTicketPreview('{$result.t_id}',this);" title="Preview">&raquo;</a></td>
		</tr>
		<tr class="{$tableRowBg}" id="{$rowIdPrefix}" onmouseover="toggleClass(this.id,'tableRowHover');toggleClass('{$rowIdPrefix}_s','tableRowHover');" onmouseout="toggleClass(this.id,'{$tableRowBg}');toggleClass('{$rowIdPrefix}_s','{$tableRowBg}');" onclick="if(getEventTarget(event)=='TD') checkAll('{$rowIdPrefix}_s');">
		{foreach from=$view->view_columns item=column name=columns}
			{if $column=="t_mask"}
			<td><a href="{devblocks_url}c=display&id={$result.t_mask}{/devblocks_url}">{$result.t_mask}</a></td>
			{*
			{elseif $column=="t_priority"}
			<td>
				{if $result.t_priority >= 75}
					<img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/star_red.gif{/devblocks_url}" title="{$result.t_priority}">
				{elseif $result.t_priority >= 50}
					<img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/star_yellow.gif{/devblocks_url}" title="{$result.t_priority}">
				{elseif $result.t_priority >= 25}
					<img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/star_green.gif{/devblocks_url}" title="{$result.t_priority}">
				{else}
					<img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/star_alpha.gif{/devblocks_url}" title="{$result.t_priority}">
				{/if}
			</td>
			*}
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
			<td><a href="{devblocks_url}c=tickets&a=dashboards&m=team&id={$result.tm_id}{/devblocks_url}">{$result.tm_name}</a></td>
			{elseif $column=="cat_name"}
			<td>{$result.cat_name}</td>
			{elseif $column=="t_next_action"}
			<td title="{$result.t_next_action}">{$result.t_next_action|truncate:35:'...'}</td>
			{elseif $column=="t_owner_id"}
			<td>
				{assign var=owner value=$visit->getWorker()}
				{if !empty($result.t_owner_id)}
					{if !empty($owner) && $result.t_owner_id==$owner->id}
						<a href="javascript:;" onclick="ajax.viewAssignTicket('{$view->id}','{$result.t_id}','0');"><b>Release</b></a>
					{else}
						{assign var=ticket_worker_id value=$result.t_owner_id}
						{if isset($workers.$ticket_worker_id)}{$workers.$ticket_worker_id->getName()}{/if}
					{/if}
				{else}
					<a href="javascript:;" onclick="ajax.viewAssignTicket('{$view->id}','{$result.t_id}','{$owner->id}');">Take</a>
				{/if}
			</td>
			{elseif $column=="t_spam_score"}
			<td>
				{math assign=score equation="x*100" format="%0.2f%%" x=$result.t_spam_score}
				{if empty($result.t_spam_training)}
				<!---<a href="javascript:;"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/warning.gif{/devblocks_url}" align="top" border="0" title="Not Spam ({$score})"></a>--->
				<!---<a href="javascript:;"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/check_gray.gif{/devblocks_url}" align="top" border="0" title="Not Spam ({$score})"></a>--->
				<a href="javascript:;" onclick="toggleDiv('{$rowIdPrefix}_s','none');toggleDiv('{$rowIdPrefix}','none');genericAjaxGet(null,'c=tickets&a=reportSpam&id={$result.t_id}');"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/{if $result.t_spam_score >= .90}warning.gif{else}warning_gray.gif{/if}{/devblocks_url}" align="top" border="0" title="Report Spam ({$score})
				{if !empty($result.t_interesting_words)}{$result.t_interesting_words}{/if}"></a>
				{/if}
			</td>
			{/if}
		{/foreach}
		</tr>
	{/foreach}
	
</table>
<table cellpadding="2" cellspacing="0" border="0" width="100%" class="tableBg">
	{if $total}
	<tr>
		<td colspan="2">
			<span id="tourDashboardBatch"><button type="button" onclick="ajax.showBatchPanel('{$view->id}','{$dashboard_team_id}');">bulk update</button></span> <!-- genericAjaxPanel('c=tickets&a=showBatchPanel&view_id={$view->id}',this,true,'500px'); -->
			<button type="button" onclick="ajax.viewCloseTickets('{$view->id}',0);">close</button>
			<button type="button" onclick="ajax.viewCloseTickets('{$view->id}',1);">report spam</button>
			<button type="button" onclick="ajax.viewCloseTickets('{$view->id}',2);">delete</button>
			
			<input type="hidden" name="move_to" value="">
			<select name="move_to_select" onchange="this.form.move_to.value=this.form.move_to_select[this.selectedIndex].value;ajax.viewMoveTickets('{$view->id}');">
				<option value="">-- move to --</option>
				{foreach from=$team_categories item=team_category_list key=teamId}
					{assign var=team value=$teams.$teamId}
					{if $dashboard_team_id == $teamId}
						<optgroup label="-- {$team->name} --">
						{foreach from=$team_category_list item=category}
							<option value="c{$category->id}">{$category->name}</option>
						{/foreach}
						</optgroup>
					{/if}
				{/foreach}
				<optgroup label="Team Inboxes" style="">
					{foreach from=$teams item=team}
						<option value="t{$team->id}">{$team->name}</option>
					{/foreach}
				</optgroup>
			</select>
			
		</td>
	</tr>
	{/if}
	<tr>
		<td align="left" valign="top">
			{if $total && !empty($move_to_counts)}
			<span style="font-size:100%;">
			<b>Move to: </b>
				{foreach from=$move_to_counts item=move_count key=move_code name=move_links}
					{if substr($move_code,0,1)=='t'}
						{assign var=move_team_id value=$move_code|regex_replace:"/[t]/":""}
					{elseif substr($move_code,0,1)=='c'}
						{assign var=move_bucket_id value=$move_code|regex_replace:"/[c]/":""}
					{/if}
					<a href="javascript:;" onclick="document.viewForm{$view->id}.move_to.value='{$move_code}';ajax.viewMoveTickets('{$view->id}');" title="Used {$move_count} times." style="{if !empty($move_team_id)}color:rgb(0,150,0);font-weight:bold;font-style:normal;{else}{/if}">{if !empty($move_team_id)}{$teams.$move_team_id->name}{else}{$categories.$move_bucket_id->name}{/if}</a>{if !$smarty.foreach.move_links.last}, {/if}
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