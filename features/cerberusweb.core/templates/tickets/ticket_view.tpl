<div id="{$view->id}_output_container">
	{include file="file:$view_path/rpc/ticket_view_output.tpl"}
</div>
{assign var=total value=$results[1]}
{assign var=tickets value=$results[0]}
<table cellpadding="0" cellspacing="0" border="0" class="tableBlue" width="100%">
	<tr>
		<td nowrap="nowrap" class="tableThBlue">{$view->name} {if $view->id == 'search'}<a href="#{$view->id}_actions" style="color:rgb(255,255,255);font-size:11px;">{$translate->_('views.jump_to_actions')}</a>{/if}</td>
		<td nowrap="nowrap" class="tableThBlue" align="right">
			<a href="javascript:;" onclick="genericAjaxGet('customize{$view->id}','c=internal&a=viewCustomize&id={$view->id}');toggleDiv('customize{$view->id}','block');" class="tableThLink">{$translate->_('common.customize')|lower}</a>
			{if $active_worker->hasPriv('core.ticket.view.actions.pile_sort')}<span style="font-size:12px"> | </span><a href="javascript:;" onclick="genericAjaxGet('{$view->id}_tips','c=tickets&a=showViewAutoAssist&view_id={$view->id}');toggleDiv('{$view->id}_tips','block');" class="tableThLink">{$translate->_('mail.piles')|lower}</a>{/if}
			{if $active_worker->hasPriv('core.mail.search')}<span style="font-size:12px"> | </span><a href="{devblocks_url}c=tickets&a=searchview&id={$view->id}{/devblocks_url}" class="tableThLink">{$translate->_('common.search')|lower}</a>{/if}
			{if $active_worker->hasPriv('core.home.workspaces')}<span style="font-size:12px"> | </span><a href="javascript:;" onclick="genericAjaxGet('{$view->id}_tips','c=internal&a=viewShowCopy&view_id={$view->id}');toggleDiv('{$view->id}_tips','block');" class="tableThLink">{$translate->_('common.copy')|lower}</a>{/if}
			{if $active_worker->hasPriv('core.ticket.view.actions.export')}<span style="font-size:12px"> | </span><a href="javascript:;" onclick="genericAjaxGet('{$view->id}_tips','c=internal&a=viewShowExport&id={$view->id}');toggleDiv('{$view->id}_tips','block');" class="tableThLink">{$translate->_('common.export')|lower}</a>{/if}
			<span style="font-size:12px"> | </span><a href="javascript:;" onclick="genericAjaxGet('view{$view->id}','c=internal&a=viewRefresh&id={$view->id}');" class="tableThLink"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/refresh.gif{/devblocks_url}" border="0" align="absmiddle" title="{$translate->_('common.refresh')|lower}" alt="{$translate->_('common.refresh')|lower}"></a>
			{if $active_worker->hasPriv('core.rss')}<span style="font-size:12px"> | </span><a href="javascript:;" onclick="genericAjaxGet('{$view->id}_tips','c=tickets&a=showViewRss&view_id={$view->id}&source=core.rss.source.ticket');toggleDiv('{$view->id}_tips','block');" class="tableThLink"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/feed-icon-16x16.gif{/devblocks_url}" border="0" align="absmiddle"></a>{/if}
		</td>
	</tr>
</table>

<div id="{$view->id}_tips" class="block" style="display:none;margin:10px;padding:5px;">Analyzing...</div>
<form id="customize{$view->id}" action="#" onsubmit="return false;" style="display:none;"></form>
<form id="viewForm{$view->id}" name="viewForm{$view->id}" action="#">
<input type="hidden" name="id" value="{$view->id}">
<table cellpadding="1" cellspacing="0" border="0" width="100%" class="tableRowBg">

	{* Column Headers *}
	<tr class="tableTh">
		<th style="text-align:center"><input type="checkbox" onclick="checkAll('viewForm{$view->id}',this.checked);this.blur();"></th>
		{foreach from=$view->view_columns item=header name=headers}
			{* start table header, insert column title and link *}
			<th>
			{if !empty($view_fields.$header->db_column)}
				<a href="javascript:;" onclick="genericAjaxGet('view{$view->id}','c=internal&a=viewSortBy&id={$view->id}&sortBy={$header}');">{$view_fields.$header->db_label|capitalize}</a>
			{else}
				<a href="javascript:;" style="text-decoration:none;">{$view_fields.$header->db_label|capitalize}</a>
			{/if}
			
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
	{foreach from=$tickets item=result key=idx name=results}

	{assign var=rowIdPrefix value="row_"|cat:$view->id|cat:"_"|cat:$result.t_id}
	{if $smarty.foreach.results.iteration % 2}
		{assign var=tableRowBg value="tableRowBg"}
	{else}
		{assign var=tableRowBg value="tableRowAltBg"}
	{/if}
	
	{assign var=ticket_group_id value=$result.t_team_id}
	{if !isset($active_worker_memberships.$ticket_group_id)}{*censor*}
		<tr class="{$tableRowBg}">
			<td>&nbsp;</td>
			<td rowspan="2" colspan="{math equation="x" x=$smarty.foreach.headers.total}" style="color:rgb(140,140,140);font-size:10px;text-align:left;vertical-align:middle;">[Access Denied: {$teams.$ticket_group_id->name} #{$result.t_mask}]</td>
		</tr>
		<tr class="{$tableRowBg}">
			<td>&nbsp;</td>
		</tr>
	
	{else}
	<tr class="{$tableRowBg}" id="{$rowIdPrefix}_s" onmouseover="$(this).addClass('tableRowHover');$('#{$rowIdPrefix}').addClass('tableRowHover');" onmouseout="$(this).removeClass('tableRowHover');$('#{$rowIdPrefix}').removeClass('tableRowHover');" onclick="if(getEventTarget(event)=='TD') checkAll('{$rowIdPrefix}_s');">
		<td align="center" rowspan="2"><input type="checkbox" name="ticket_id[]" value="{$result.t_id}"></td>
		<td colspan="{math equation="x" x=$smarty.foreach.headers.total}"><a href="{devblocks_url}c=display&a=browse&id={$result.t_mask}&view={$view->id}{/devblocks_url}" style="color:rgb(75,75,75);font-size:12px;"><b id="subject_{$result.t_id}_{$view->id}">{if $result.t_is_deleted}<img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/delete2_gray.gif{/devblocks_url}" width="16" height="16" align="top" border="0" title="{$translate->_('status.deleted')}"> {elseif $result.t_is_closed}<img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/check_gray.gif{/devblocks_url}" width="16" height="16" align="top" border="0" title="{$translate->_('status.closed')}"> {elseif $result.t_is_waiting}<img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/clock.gif{/devblocks_url}" width="16" height="16" align="top" border="0" title="{$translate->_('status.waiting')}"> {/if}{$result.t_subject|escape}</b></a> <a href="javascript:;" onclick="genericAjaxPanel('c=tickets&a=showPreview&view_id={$view->id}&tid={$result.t_id}', null, false, '500');"><span class="ui-icon ui-icon-newwin" style="display:inline-block;" title="{$translate->_('views.peek')}"></span></a></td>
	</tr>
	<tr class="{$tableRowBg}" id="{$rowIdPrefix}" onmouseover="$(this).addClass('tableRowHover');$('#{$rowIdPrefix}_s').addClass('tableRowHover');" onmouseout="$(this).removeClass('tableRowHover');$('#{$rowIdPrefix}_s').removeClass('tableRowHover');" onclick="if(getEventTarget(event)=='TD') checkAll('{$rowIdPrefix}_s');">
	{foreach from=$view->view_columns item=column name=columns}
		{if substr($column,0,3)=="cf_"}
			{include file="file:$core_tpl/internal/custom_fields/view/cell_renderer.tpl"}
		{elseif $column=="t_id"}
		<td><a href="{devblocks_url}c=display&id={$result.t_id}{/devblocks_url}">{$result.t_id}</a></td>
		{elseif $column=="t_mask"}
		<td><a href="{devblocks_url}c=display&id={$result.t_mask}{/devblocks_url}">{$result.t_mask}</a></td>
		{elseif $column=="t_subject"}
		<td title="{$result.t_subject}">{$result.t_subject|truncate:35:'...'}</td>
		{elseif $column=="t_is_waiting"}
		<td>{if $result.t_is_waiting}<img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/clock.gif{/devblocks_url}" width="16" height="16" border="0" title="{$translate->_('status.waiting')}">{else}{/if}</td>
		{elseif $column=="t_is_closed"}
		<td>{if $result.t_is_closed}<img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/check_gray.gif{/devblocks_url}" width="16" height="16" border="0" title="{$translate->_('status.closed')}">{else}{/if}</td>
		{elseif $column=="t_is_deleted"}
		<td>{if $result.t_is_deleted}<img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/delete2_gray.gif{/devblocks_url}" width="16" height="16" border="0" title="{$translate->_('status.deleted')}">{else}{/if}</td>
		{elseif $column=="t_last_wrote"}
		<td><a href="javascript:;" onclick="genericAjaxPanel('c=contacts&a=showAddressPeek&email={$result.t_last_wrote|escape:'url'}&view_id={$view->id}',null,false,'500');" title="{$result.t_last_wrote}">{$result.t_last_wrote|truncate:45:'...':true:true}</a></td>
		{elseif $column=="t_first_wrote"}
		<td><a href="javascript:;" onclick="genericAjaxPanel('c=contacts&a=showAddressPeek&email={$result.t_first_wrote|escape:'url'}&view_id={$view->id}',null,false,'500');" title="{$result.t_first_wrote}">{$result.t_first_wrote|truncate:45:'...':true:true}</a></td>
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
		{*{elseif $column=="t_tasks"}
		<td align='center'>{if !empty($result.t_tasks)}{$result.t_tasks}{/if}</td>*}
		{elseif $column=="t_team_id"}
		<td>
			{assign var=ticket_team_id value=$result.t_team_id}
			{$teams.$ticket_team_id->name}
		</td>
		{elseif $column=="t_interesting_words"}
		<td>{$result.t_interesting_words|replace:',':', '}</td>
		{elseif $column=="t_category_id"}
			{assign var=ticket_team_id value=$result.t_team_id}
			{assign var=ticket_category_id value=$result.t_category_id}
			<td>
				{if 0 == $ticket_category_id}
					{if (isset($active_worker_memberships.$ticket_team_id)) && $active_worker_memberships.$ticket_team_id->is_manager || $active_worker->is_superuser}
						<a href="javascript:;" onclick="genericAjaxPanel('c=groups&a=showInboxFilterPanel&id=0&group_id={$ticket_team_id}&ticket_id={$result.t_id}&view_id={$view->id}',null,false,'600');">{$translate->_('mail.view.add_filter')}</a>
					{/if}
				{else}
					{$buckets.$ticket_category_id->name}
				{/if}
			</td>
		{elseif $column=="t_last_action_code"}
		<td>
			<span style="color:rgb(130,130,130);">
			{if $result.t_last_action_code=='O'}
				{assign var=action_worker_id value=$result.t_next_worker_id}
				<span title="{$result.t_first_wrote}">New 
				{if isset($workers.$action_worker_id)}for {$workers.$action_worker_id->getName()}{else}from <a href="javascript:;" onclick="genericAjaxPanel('c=contacts&a=showAddressPeek&email={$result.t_first_wrote|escape:'url'}&view_id={$view->id}',null,false,'500');">{$result.t_first_wrote|truncate:45:'...':true:true}</a>{/if}</span>
			{elseif $result.t_last_action_code=='R'}
				{assign var=action_worker_id value=$result.t_next_worker_id}
				{if isset($workers.$action_worker_id)}
					<span title="{$result.t_last_wrote}"><span style="color:rgb(255,50,50);background-color:rgb(255,213,213);font-weight:bold;">{'mail.inbound'|devblocks_translate}</span> for {$workers.$action_worker_id->getName()}</span>
				{else}
					<span title="{$result.t_last_wrote}"><span style="color:rgb(255,50,50);background-color:rgb(255,213,213);font-weight:bold;">{'mail.inbound'|devblocks_translate}</span> from <a href="javascript:;" onclick="genericAjaxPanel('c=contacts&a=showAddressPeek&email={$result.t_last_wrote|escape:'url'}&view_id={$view->id}',null,false,'500');">{$result.t_last_wrote|truncate:45:'...':true:true}</a></span>
				{/if}
			{elseif $result.t_last_action_code=='W'}
				{assign var=action_worker_id value=$result.t_last_worker_id}
				{if isset($workers.$action_worker_id)}
					<span title="{$result.t_last_wrote}"><span style="color:rgb(50,120,50);background-color:rgb(219,255,190);font-weight:bold;">{'mail.outbound'|devblocks_translate}</span> from {$workers.$action_worker_id->getName()}</span>
				{else}
					<span title="{$result.t_last_wrote}"><span style="color:rgb(50,120,50);background-color:rgb(219,255,190);font-weight:bold;">{'mail.outbound'|devblocks_translate}</span> from Helpdesk</span>
				{/if}
			{/if}
			</span>
		</td>
		{elseif $column=="t_last_worker_id"}
		<td>
			{assign var=action_worker_id value=$result.t_last_worker_id}
			{if isset($workers.$action_worker_id)}{$workers.$action_worker_id->getName()}{/if}
		</td>
		{elseif $column=="t_next_worker_id"}
		<td>
			{assign var=action_worker_id value=$result.t_next_worker_id}
			{if isset($workers.$action_worker_id)}{$workers.$action_worker_id->getName()}{/if}
		</td>
		{elseif $column=="t_first_wrote_spam"}
		<td>{$result.t_first_wrote_spam}</td>
		{elseif $column=="t_first_wrote_nonspam"}
		<td>{$result.t_first_wrote_nonspam}</td>
		{elseif $column=="t_spam_score" || $column=="t_spam_training"}
		<td>
			{math assign=score equation="x*100" format="%0.2f%%" x=$result.t_spam_score}
			{if empty($result.t_spam_training)}
			<!--<a href="javascript:;"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/warning.gif{/devblocks_url}" align="top" border="0" title="Not Spam ({$score})"></a>-->
			<!--<a href="javascript:;"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/check_gray.gif{/devblocks_url}" align="top" border="0" title="Not Spam ({$score})"></a>-->
			{if $active_worker->hasPriv('core.ticket.actions.spam')}<a href="javascript:;" onclick="toggleDiv('{$rowIdPrefix}_s','none');toggleDiv('{$rowIdPrefix}','none');genericAjaxGet('{$view->id}_output_container','c=tickets&a=reportSpam&id={$result.t_id}&viewId={$view->id}');">{/if}
			<img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/{if $result.t_spam_score >= 0.90}warning.gif{else}warning_gray.gif{/if}{/devblocks_url}" align="top" border="0" title="Report Spam ({$score})">
			{if $active_worker->hasPriv('core.ticket.actions.spam')}</a>{/if}
			{/if}
		</td>
		{else}
		<td>{if $result.$column}{$result.$column}{/if}</td>
		{/if}
	{/foreach}
	</tr>
	{/if}{*!censor*}
	
{/foreach}
	
</table>
<table cellpadding="2" cellspacing="0" border="0" width="100%" class="tableBg" id="{$view->id}_actions">
	{if $total}
	<tr>
		<td colspan="2">
			{assign var=show_more value=0}
			{if $active_worker->hasPriv('core.ticket.view.actions.bulk_update')}{assign var=show_more value=1}<button type="button"  id="btn{$view->id}BulkUpdate" onclick="ajax.showBatchPanel('{$view->id}',null);"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/folder_gear.gif{/devblocks_url}" align="top"> {$translate->_('common.bulk_update')|lower}</button>{/if}
			{if $active_worker->hasPriv('core.ticket.actions.close')}{assign var=show_more value=1}<button type="button" id="btn{$view->id}Close" onclick="ajax.viewCloseTickets('{$view->id}',0);"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/folder_ok.gif{/devblocks_url}" align="top"> {$translate->_('common.close')|lower}</button>{/if}
			{if $active_worker->hasPriv('core.ticket.actions.spam')}{assign var=show_more value=1}<button type="button"  id="btn{$view->id}Spam" onclick="ajax.viewCloseTickets('{$view->id}',1);"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/spam.gif{/devblocks_url}" align="top"> {$translate->_('common.spam')|lower}</button>{/if}
			{if $active_worker->hasPriv('core.ticket.actions.delete')}{assign var=show_more value=1}<button type="button"  id="btn{$view->id}Delete" onclick="ajax.viewCloseTickets('{$view->id}',2);"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/delete.gif{/devblocks_url}" align="top"> {$translate->_('common.delete')|lower}</button>{/if}
			
			{if $active_worker->hasPriv('core.ticket.actions.move')}
			{assign var=show_more value=1}
			<input type="hidden" name="move_to" value="">
			<select name="move_to_select" onchange="this.form.move_to.value=this.form.move_to_select[this.selectedIndex].value;ajax.viewMoveTickets('{$view->id}');">
				<option value="">-- {$translate->_('common.move_to')} --</option>
				<optgroup label="{$translate->_('common.inboxes')|capitalize}" style="">
					{foreach from=$teams item=team}
						<option value="t{$team->id}">{$team->name}</option>
					{/foreach}
				</optgroup>
				{foreach from=$team_categories item=team_category_list key=teamId}
					{assign var=team value=$teams.$teamId}
					{if !empty($active_worker_memberships.$teamId)}
						<optgroup label="-- {$team->name} --">
						{foreach from=$team_category_list item=category}
							<option value="c{$category->id}">{$category->name}</option>
						{/foreach}
						</optgroup>
					{/if}
				{/foreach}
			</select>
			{/if}
			
			{if $show_more}
			<button type="button" onclick="toggleDiv('view{$view->id}_more');">{$translate->_('common.more')|lower} &raquo;</button><br>
			{/if}

			<div id="view{$view->id}_more" style="display:{if $show_more}none{else}block{/if};padding-top:5px;padding-bottom:5px;">
				<button type="button" onclick="ajax.viewTicketsAction('{$view->id}','not_spam');">{$translate->_('common.notspam')|lower}</button>
				{if $active_worker->hasPriv('core.ticket.view.actions.merge')}<button type="button" onclick="ajax.viewTicketsAction('{$view->id}','merge');">{$translate->_('mail.merge')|lower}</button>{/if}
				<button type="button" id="btn{$view->id}Take" onclick="ajax.viewTicketsAction('{$view->id}','take');">{$translate->_('mail.take')|lower}</button>
				<button type="button" id="btn{$view->id}Surrender" onclick="ajax.viewTicketsAction('{$view->id}','surrender');">{$translate->_('mail.surrender')|lower}</button>
				<button type="button" onclick="ajax.viewTicketsAction('{$view->id}','waiting');">{$translate->_('mail.waiting')|lower}</button>
				<button type="button" onclick="ajax.viewTicketsAction('{$view->id}','not_waiting');">{$translate->_('mail.not_waiting')|lower}</button>
			</div>

			{if $pref_keyboard_shortcuts}
			{if $view->id=='overview_all' || $view->id=='mail_workflow' || $view->id=='search'}{*Only on Workflow/Overview*}
				{$translate->_('common.keyboard')|lower}: 
					{if $active_worker->hasPriv('core.ticket.view.actions.bulk_update')}(<b>b</b>) {$translate->_('common.bulk_update')|lower}{/if} 
					{if $active_worker->hasPriv('core.ticket.actions.close')}(<b>c</b>) {$translate->_('common.close')|lower}{/if} 
					{if $active_worker->hasPriv('core.ticket.actions.spam')}(<b>s</b>) {$translate->_('common.spam')|lower}{/if} 
					(<b>t</b>) {$translate->_('mail.take')|lower} 
					(<b>u</b>) {$translate->_('mail.surrender')|lower} 
					{if $active_worker->hasPriv('core.ticket.actions.delete')}(<b>x</b>) {$translate->_('common.delete')|lower}{/if}
					<br>
			{/if}
			{/if}
		</td>
	</tr>
	{/if}
	<tr>
		<td align="left" valign="top">
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