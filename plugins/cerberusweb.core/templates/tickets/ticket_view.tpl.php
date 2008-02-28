<div id="{$view->id}_output_container">
	{include file="file:$view_path/rpc/ticket_view_output.tpl.php"}
</div>
{assign var=total value=$results[1]}
{assign var=tickets value=$results[0]}
<table cellpadding="0" cellspacing="0" border="0" class="tableBlue" width="100%">
	<tr>
		<td nowrap="nowrap" class="tableThBlue">{$view->name} {if $view->id == 'search'}<a href="#{$view->id}_actions" style="color:rgb(255,255,255);font-size:11px;">jump to actions</a>{/if}</td>
		<td nowrap="nowrap" class="tableThBlue" align="right">
			<a href="javascript:;" onclick="genericAjaxGet('{$view->id}_tips','c=tickets&a=showViewAutoAssign&view_id={$view->id}');toggleDiv('{$view->id}_tips','block');" class="tableThLink">{"assign"|lower}</a>
			{if $view->id != 'contact_history'}<span style="font-size:12px"> | </span><a href="javascript:;" onclick="genericAjaxGet('{$view->id}_tips','c=tickets&a=showViewAutoAssist&view_id={$view->id}');toggleDiv('{$view->id}_tips','block');" class="tableThLink">{"piles"|lower}</a>{/if}
			{if $view->id != 'search'}<span style="font-size:12px"> | </span><a href="{devblocks_url}c=tickets&a=searchview&id={$view->id}{/devblocks_url}" class="tableThLink">{$translate->_('common.search')|lower}</a>{/if}
			<span style="font-size:12px"> | </span><a href="javascript:;" onclick="genericAjaxGet('{$view->id}_tips','c=tickets&a=showViewCopy&view_id={$view->id}');toggleDiv('{$view->id}_tips','block');" class="tableThLink">{"copy"|lower}</a>
			<span style="font-size:12px"> | </span><a href="javascript:;" onclick="genericAjaxGet('customize{$view->id}','c=internal&a=viewCustomize&id={$view->id}');toggleDiv('customize{$view->id}','block');" class="tableThLink">{$translate->_('common.customize')|lower}</a>
			<span style="font-size:12px"> | </span><a href="javascript:;" onclick="genericAjaxGet('view{$view->id}','c=internal&a=viewRefresh&id={$view->id}');" class="tableThLink"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/refresh.gif{/devblocks_url}" border="0" align="absmiddle" title="{$translate->_('common.refresh')|lower}" alt="{$translate->_('common.refresh')|lower}"></a>
			<span style="font-size:12px"> | </span><a href="javascript:;" onclick="genericAjaxGet('{$view->id}_tips','c=tickets&a=showViewRss&view_id={$view->id}');toggleDiv('{$view->id}_tips','block');" class="tableThLink"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/feed-icon-16x16.gif{/devblocks_url}" border="0" align="absmiddle"></a>
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
	
	{assign var=ticket_group_id value=$result.tm_id}
	{if !isset($active_worker_memberships.$ticket_group_id)}{*censor*}
		<tr class="{$tableRowBg}">
			<td>&nbsp;</td>
			<td rowspan="2" colspan="{math equation="x" x=$smarty.foreach.headers.total}" style="color:rgb(140,140,140);font-size:10px;text-align:left;vertical-align:middle;">[Access Denied: {$result.tm_name} #{$result.t_mask}]</td>
		</tr>
		<tr class="{$tableRowBg}">
			<td>&nbsp;</td>
		</tr>
	
	{else}
	<tr class="{$tableRowBg}" id="{$rowIdPrefix}_s" onmouseover="toggleClass(this.id,'tableRowHover');toggleClass('{$rowIdPrefix}','tableRowHover');" onmouseout="toggleClass(this.id,'{$tableRowBg}');toggleClass('{$rowIdPrefix}','{$tableRowBg}');" onclick="if(getEventTarget(event)=='TD') checkAll('{$rowIdPrefix}_s');">
		<td align="center" rowspan="2"><input type="checkbox" name="ticket_id[]" value="{$result.t_id}"></td>
		<td colspan="{math equation="x" x=$smarty.foreach.headers.total}"><a href="{devblocks_url}c=display&a=browse&id={$result.t_mask}&view={$view->id}{/devblocks_url}" class="ticketLink" style="font-size:12px;"><b id="subject_{$result.t_id}_{$view->id}">{if $result.t_is_deleted}<img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/delete2_gray.gif{/devblocks_url}" width="16" height="16" align="top" border="0" title="{$translate->_('status.deleted')}"> {elseif $result.t_is_closed}<img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/check_gray.gif{/devblocks_url}" width="16" height="16" align="top" border="0" title="{$translate->_('status.closed')}"> {elseif $result.t_is_waiting}<img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/clock.gif{/devblocks_url}" width="16" height="16" align="top" border="0" title="{$translate->_('status.waiting')}"> {/if}{$result.t_subject|escape:"htmlall"}</b></a> <a href="javascript:;" onclick="genericAjaxPanel('c=tickets&a=showPreview&view_id={$view->id}&tid={$result.t_id}', this, false, '500px');" style="color:rgb(180,180,180);font-size:90%;">(peek)</a></td>
	</tr>
	<tr class="{$tableRowBg}" id="{$rowIdPrefix}" onmouseover="toggleClass(this.id,'tableRowHover');toggleClass('{$rowIdPrefix}_s','tableRowHover');" onmouseout="toggleClass(this.id,'{$tableRowBg}');toggleClass('{$rowIdPrefix}_s','{$tableRowBg}');" onclick="if(getEventTarget(event)=='TD') checkAll('{$rowIdPrefix}_s');">
	{foreach from=$view->view_columns item=column name=columns}
		{if substr($column,0,3)=="cf_"}
			{assign var=col value=$column|explode:'_'}
			{assign var=col_id value=$col.1}
			{assign var=col value=$ticket_fields.$col_id}
			
			{if $col->type=='S'}
			<td>{$result.$column}</td>
			{elseif $col->type=='T'}
			<td title="{$result.$column|escape}">{$result.$column|truncate:32}</td>
			{elseif $col->type=='D'}
			<td>{$result.$column}</td>
			{elseif $col->type=='E'}
			<td>{$result.$column|date_format}</td>
			{elseif $col->type=='C'}
			<td>{if '1'==$result.$column}Yes{elseif '0'==$result.$column}No{/if}</td>
			{/if}
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
		<td><a href="javascript:;" onclick="genericAjaxPanel('c=contacts&a=showAddressPeek&email={$result.t_last_wrote}&view_id={$view->id}',this,false,'500px',ajax.cbAddressPeek);" title="{$result.t_last_wrote}">{$result.t_last_wrote|truncate:45:'...':true:true}</a></td>
		{elseif $column=="t_first_wrote"}
		<td><a href="javascript:;" onclick="genericAjaxPanel('c=contacts&a=showAddressPeek&email={$result.t_first_wrote}&view_id={$view->id}',this,false,'500px',ajax.cbAddressPeek);" title="{$result.t_first_wrote}">{$result.t_first_wrote|truncate:45:'...':true:true}</a></td>
		{elseif $column=="t_created_date"}
		<td title="{$result.t_created_date|date_format:'%b %e, %Y  %H:%M:%S'}">{$result.t_created_date|prettytime}</td>
		{elseif $column=="t_updated_date"}
			{assign var=overdue value=0}
			{if $result.t_category_id}
				{assign var=ticket_category_id value=$result.t_category_id}
				{assign var=bucket value=$buckets.$ticket_category_id}
				{if $bucket->response_hrs}
					{math assign=overdue equation="(t-x)/3600" t=$timestamp_now x=$result.t_updated_date h=$bucket->response_hrs format="%d"}
				{/if}
			{/if}
			<td title="{$result.t_updated_date|date_format:'%b %e, %Y  %H:%M:%S'}" style="{if $overdue && $overdue>=$bucket->response_hrs}color:rgb(220,0,0);font-weight:bold;{/if}">{$result.t_updated_date|prettytime}</td>
		{elseif $column=="t_due_date"}
		<td title="{if $result.t_due_date}{$result.t_due_date|date_format:'%b %e, %Y  %H:%M:%S'}{/if}">{if $result.t_due_date}{$result.t_due_date|prettytime}{/if}</td>
		{*{elseif $column=="t_tasks"}
		<td align='center'>{if !empty($result.t_tasks)}{$result.t_tasks}{/if}</td>*}
		{elseif $column=="tm_name"}
		<td>
			{assign var=ticket_team_id value=$result.tm_id}
			{if (isset($active_worker_memberships.$ticket_team_id)) && $active_worker_memberships.$ticket_team_id->is_manager || $active_worker->is_superuser}
				<a href="javascript:;" onclick="genericAjaxPanel('c=groups&a=showGroupPanel&id={$ticket_team_id}&view_id=',this,false,'500px');">{$result.tm_name}</a>
			{else}
				{$result.tm_name}
			{/if}
		</td>
		{elseif $column=="t_interesting_words"}
		<td>{$result.t_interesting_words|replace:',':', '}</td>
		{elseif $column=="t_category_id"}
			{assign var=ticket_team_id value=$result.tm_id}
			{assign var=ticket_category_id value=$result.t_category_id}
			<td>
				{if 0 == $ticket_category_id}
					{if (isset($active_worker_memberships.$ticket_team_id)) && $active_worker_memberships.$ticket_team_id->is_manager || $active_worker->is_superuser}
						<a href="javascript:;" onclick="genericAjaxPanel('c=tickets&a=showAddInboxRulePanel&view_id={$view->id}&id={$result.t_id}',this,false,'400px');">-add filter-</a>
					{/if}
				{else}
					{$buckets.$ticket_category_id->name}
				{/if}
			</td>
		{elseif $column=="t_sla_id"}
		<td>
			{assign var=sla_id value=$result.t_sla_id}
			{if !empty($sla_id) && isset($slas.$sla_id)}
				{$slas.$sla_id->name}
			{/if}
		</td>
		{elseif $column=="t_sla_priority"}
		<td>
			{assign var=sla_id value=$result.t_sla_id}
			{if !empty($sla_id) && isset($slas.$sla_id)}
				{$slas.$sla_id->name} ({$result.t_sla_priority})
			{/if}
		</td>
		{elseif $column=="t_next_action"}
		<td title="{$result.t_next_action}"><span style="color:rgb(130,130,130);">{$result.t_next_action|truncate:35:'...'|indent:2:"&nbsp;"}</span></td>
		{elseif $column=="t_last_action_code"}
		<td>
			<span style="color:rgb(130,130,130);">
			{if $result.t_last_action_code=='O'}
				{assign var=action_worker_id value=$result.t_next_worker_id}
				<span title="{$result.t_first_wrote}">New 
				{if isset($workers.$action_worker_id)}for {$workers.$action_worker_id->getName()}{else}from <a href="javascript:;" onclick="genericAjaxPanel('c=contacts&a=showAddressPeek&email={$result.t_first_wrote}&view_id={$view->id}',this,false,'500px',ajax.cbAddressPeek);">{$result.t_first_wrote|truncate:45:'...':true:true}</a>{/if}</span>
			{elseif $result.t_last_action_code=='R'}
				{assign var=action_worker_id value=$result.t_next_worker_id}
				{if isset($workers.$action_worker_id)}
					<span title="{$result.t_last_wrote}"><b>Incoming for {$workers.$action_worker_id->getName()}</b></span>
				{else}
					<span title="{$result.t_last_wrote}"><b>Incoming for Helpdesk</b></span>
				{/if}
			{elseif $result.t_last_action_code=='W'}
				{assign var=action_worker_id value=$result.t_last_worker_id}
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
			<a href="javascript:;" onclick="toggleDiv('{$rowIdPrefix}_s','none');toggleDiv('{$rowIdPrefix}','none');genericAjaxGet('{$view->id}_output_container','c=tickets&a=reportSpam&id={$result.t_id}&viewId={$view->id}');"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/{if $result.t_spam_score >= .90}warning.gif{else}warning_gray.gif{/if}{/devblocks_url}" align="top" border="0" title="Report Spam ({$score})
			{if !empty($result.t_interesting_words)}{$result.t_interesting_words}{/if}"></a>
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
			{if $view->id != 'contact_history'}<button type="button"  id="btn{$view->id}BulkUpdate" onclick="ajax.showBatchPanel('{$view->id}','{$dashboard_team_id}',this);"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/folder_gear.gif{/devblocks_url}" align="top"> bulk update</button>{/if}
			<button type="button" id="btn{$view->id}Close" onclick="ajax.viewCloseTickets('{$view->id}',0);"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/folder_ok.gif{/devblocks_url}" align="top"> close</button>
			<button type="button"  id="btn{$view->id}Spam" onclick="ajax.viewCloseTickets('{$view->id}',1);"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/spam.gif{/devblocks_url}" align="top"> spam</button>
			{if $active_worker && ($active_worker->is_superuser || $active_worker->can_delete)}<button type="button"  id="btn{$view->id}Delete" onclick="ajax.viewCloseTickets('{$view->id}',2);"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/delete.gif{/devblocks_url}" align="top"> delete</button>{/if}
			
			<input type="hidden" name="move_to" value="">

			<select name="move_to_select" onchange="this.form.move_to.value=this.form.move_to_select[this.selectedIndex].value;ajax.viewMoveTickets('{$view->id}');">
				<option value="">-- move to --</option>
				<optgroup label="Inboxes" style="">
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
			
			<a href="javascript:;" onclick="toggleDiv('view{$view->id}_more');">more options &raquo;</a><br>

			<div id="view{$view->id}_more" style="display:none;padding-top:5px;padding-bottom:5px;">
				<button type="button" onclick="ajax.viewTicketsAction('{$view->id}','not_spam');">not spam</button>
				<button type="button" onclick="ajax.viewTicketsAction('{$view->id}','merge');">merge</button>
				<button type="button" id="btn{$view->id}Take" onclick="ajax.viewTicketsAction('{$view->id}','take');">take</button>
				<button type="button" id="btn{$view->id}Surrender" onclick="ajax.viewTicketsAction('{$view->id}','surrender');">surrender</button>
				<button type="button" onclick="ajax.viewTicketsAction('{$view->id}','waiting');">waiting</button>
				<button type="button" onclick="ajax.viewTicketsAction('{$view->id}','not_waiting');">not waiting</button>
			</div>

			{if $view->id=='overview_all'}{*Only on Overview*}
				keyboard: (<b>b</b>) bulk update, (<b>c</b>) close, (<b>s</b>) spam, (<b>t</b>) take, (<b>u</b>) surrender, {if $active_worker && ($active_worker->is_superuser || $active_worker->can_delete)}(<b>x</b>) delete{/if}<br>
			{/if}

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
						{assign var=move_bucket_team_id value=0}
					{elseif substr($move_code,0,1)=='c'}
						{assign var=move_team_id value=0}
						{assign var=move_bucket_id value=$move_code|regex_replace:"/[c]/":""}
						{assign var=move_bucket_team_id value=$buckets.$move_bucket_id->team_id}
					{/if}
					<a href="javascript:;" onclick="document.viewForm{$view->id}.move_to.value='{$move_code}';ajax.viewMoveTickets('{$view->id}');" title="{if !empty($move_bucket_team_id)}({$teams.$move_bucket_team_id->name}){/if} Used {$move_count} times." style="{if !empty($move_team_id)}color:rgb(0,150,0);font-weight:bold;font-style:normal;{else}{/if}">{if !empty($move_team_id)}{$teams.$move_team_id->name}{else}{$buckets.$move_bucket_id->name}{/if}</a>{if !$smarty.foreach.move_links.last}, {/if}
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
				<a href="javascript:;" onclick="genericAjaxGet('view{$view->id}','c=internal&a=viewPage&id={$view->id}&page=0');">&lt;&lt;</a>
				<a href="javascript:;" onclick="genericAjaxGet('view{$view->id}','c=internal&a=viewPage&id={$view->id}&page={$prevPage}');">&lt;{$translate->_('common.prev')|capitalize}</a>
			{/if}
			(Showing {$fromRow}-{$toRow} of {$total})
			{if $toRow < $total}
				<a href="javascript:;" onclick="genericAjaxGet('view{$view->id}','c=internal&a=viewPage&id={$view->id}&page={$nextPage}');">{$translate->_('common.next')|capitalize}&gt;</a>
				<a href="javascript:;" onclick="genericAjaxGet('view{$view->id}','c=internal&a=viewPage&id={$view->id}&page={$lastPage}');">&gt;&gt;</a>
			{/if}
		</td>
	</tr>
</table>
</form>
<br>