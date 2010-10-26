{$view_fields = $view->getColumnsAvailable()}
{assign var=total value=$results[1]}
{assign var=data value=$results[0]}
<div id="{$view->id}_output_container">
	{include file="file:$view_path/rpc/ticket_view_output.tpl"}
</div>

<table cellpadding="0" cellspacing="0" border="0" width="100%">
	<tr>
		{if !empty($view->renderSubtotals)}
		<td valign="top" width="0%" nowrap="nowrap" id="view{$view->id}_sidebar" style="padding-right:5px;">{$view->renderSubtotals()}</td>
		{else}
		<td valign="top" width="0%" nowrap="nowrap" id="view{$view->id}_sidebar"></td>
		{/if}
		
		<td valign="top" width="100%">
			
			<table cellpadding="0" cellspacing="0" border="0" class="worklist" width="100%">
				<tr>
					<td nowrap="nowrap"><span class="title">{$view->name}</span> {if $view->id == 'search'}<a href="#{$view->id}_actions">{$translate->_('views.jump_to_actions')}</a>{/if}</td>
					<td nowrap="nowrap" align="right">
						<a href="javascript:;" onclick="$('#btnExplore{$view->id}').click();">explore</a>
						 | <a href="javascript:;" id="view{$view->id}_sidebartoggle">subtotals</a>
						 | <a href="javascript:;" onclick="genericAjaxGet('customize{$view->id}','c=internal&a=viewCustomize&id={$view->id}');toggleDiv('customize{$view->id}','block');">{$translate->_('common.customize')|lower}</a>
						{if $active_worker->hasPriv('core.ticket.view.actions.pile_sort')} | <a href="javascript:;" onclick="genericAjaxGet('{$view->id}_tips','c=tickets&a=showViewAutoAssist&view_id={$view->id}');toggleDiv('{$view->id}_tips','block');">{$translate->_('mail.piles')|lower}</a>{/if}
						{if $active_worker->hasPriv('core.mail.search')} | <a href="{devblocks_url}c=tickets&a=searchview&id={$view->id}{/devblocks_url}">{$translate->_('common.search')|lower}</a>{/if}
						{if $active_worker->hasPriv('core.home.workspaces')} | <a href="javascript:;" onclick="genericAjaxGet('{$view->id}_tips','c=internal&a=viewShowCopy&view_id={$view->id}');toggleDiv('{$view->id}_tips','block');">{$translate->_('common.copy')|lower}</a>{/if}
						{if $active_worker->hasPriv('core.ticket.view.actions.export')} | <a href="javascript:;" onclick="genericAjaxGet('{$view->id}_tips','c=internal&a=viewShowExport&id={$view->id}');toggleDiv('{$view->id}_tips','block');">{$translate->_('common.export')|lower}</a>{/if}
						 | <a href="javascript:;" onclick="genericAjaxGet('view{$view->id}','c=internal&a=viewRefresh&id={$view->id}');"><span class="cerb-sprite sprite-refresh"></span></a>
						{if $active_worker->hasPriv('core.rss')} | <a href="javascript:;" onclick="genericAjaxGet('{$view->id}_tips','c=tickets&a=showViewRss&view_id={$view->id}&source=core.rss.source.ticket');toggleDiv('{$view->id}_tips','block');"><span class="cerb-sprite sprite-rss"></span></a>{/if}
					</td>
				</tr>
			</table>
			
			<div id="{$view->id}_tips" class="block" style="display:none;margin:10px;padding:5px;">Analyzing...</div>
			<form id="customize{$view->id}" action="#" onsubmit="return false;" style="display:none;"></form>
			<form id="viewForm{$view->id}" name="viewForm{$view->id}" action="{devblocks_url}{/devblocks_url}" method="post">
			<button id="btnExplore{$view->id}" type="button" style="display:none;" onclick="this.form.explore_from.value=$(this).closest('form').find('tbody input:checkbox:checked:first').val();this.form.a.value='viewTicketsExplore';this.form.submit();"></button>
			<input type="hidden" name="view_id" value="{$view->id}">
			<input type="hidden" name="context_id" value="cerberusweb.contexts.ticket">
			<input type="hidden" name="c" value="tickets">
			<input type="hidden" name="a" value="">
			<input type="hidden" name="id" value="{$view->id}">
			<input type="hidden" name="explore_from" value="0">
			<table cellpadding="1" cellspacing="0" border="0" width="100%" class="worklistBody">
				{* Column Headers *}
				<thead>
				<tr>
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
								<span class="cerb-sprite sprite-sort_ascending"></span>
							{else}
								<span class="cerb-sprite sprite-sort_descending"></span>
							{/if}
						{/if}
						</th>
					{/foreach}
				</tr>
				</thead>
			
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
				<tbody onmouseover="$(this).find('tr').addClass('hover');" onmouseout="$(this).find('tr').removeClass('hover');" onclick="if(getEventTarget(event)=='TD') { var $chk=$(this).find('input:checkbox:first');if(!$chk) return;$chk.attr('checked', !$chk.is(':checked')); } ">
				<tr class="{$tableRowClass}">
					<td align="center" rowspan="2"><input type="checkbox" name="ticket_id[]" value="{$result.t_id}"></td>
					<td colspan="{math equation="x" x=$smarty.foreach.headers.total}">
						<a href="{devblocks_url}c=display&id={$result.t_mask}{/devblocks_url}" class="subject">{if $result.t_is_deleted}<span class="cerb-sprite sprite-delete2_gray"></span> {elseif $result.t_is_closed}<span class="cerb-sprite sprite-check_gray" title="{$translate->_('status.closed')}"></span> {elseif $result.t_is_waiting}<span class="cerb-sprite sprite-clock"></span> {/if}{$result.t_subject|escape}</a> 
						<a href="javascript:;" onclick="genericAjaxPopup('peek','c=tickets&a=showPreview&view_id={$view->id}&tid={$result.t_id}', null, false, '650');"><span class="ui-icon ui-icon-newwin" style="display:inline-block;vertical-align:middle;" title="{$translate->_('views.peek')}"></span></a>
						
						{$object_workers = DAO_ContextLink::getContextLinks(CerberusContexts::CONTEXT_TICKET, array_keys($data), CerberusContexts::CONTEXT_WORKER)}
						{if isset($object_workers.{$result.t_id})}
						<div style="display:inline;padding-left:5px;">
						{foreach from=$object_workers.{$result.t_id} key=worker_id item=worker name=workers}
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
					{elseif $column=="t_id"}
					<td><a href="{devblocks_url}c=display&id={$result.t_id}{/devblocks_url}">{$result.t_id}</a></td>
					{elseif $column=="t_mask"}
					<td><a href="{devblocks_url}c=display&id={$result.t_mask}{/devblocks_url}">{$result.t_mask|escape}</a></td>
					{elseif $column=="t_subject"}
					<td title="{$result.t_subject}">{$result.t_subject|escape}</td>
					{elseif $column=="t_is_waiting"}
					<td>{if $result.t_is_waiting}<span class="cerb-sprite sprite-clock"></span>{else}{/if}</td>
					{elseif $column=="t_is_closed"}
					<td>{if $result.t_is_closed}<span class="cerb-sprite sprite-check_gray" title="{$translate->_('status.closed')}"></span>{else}{/if}</td>
					{elseif $column=="t_is_deleted"}
					<td>{if $result.t_is_deleted}<span class="cerb-sprite sprite-delete2_gray"></span>{else}{/if}</td>
					{elseif $column=="t_last_wrote"}
					<td><a href="javascript:;" onclick="genericAjaxPopup('peek','c=contacts&a=showAddressPeek&email={$result.t_last_wrote|escape:'url'}&view_id={$view->id}',null,false,'500');" title="{$result.t_last_wrote}">{$result.t_last_wrote|truncate:45:'...':true:true}</a></td>
					{elseif $column=="t_first_wrote"}
					<td><a href="javascript:;" onclick="genericAjaxPopup('peek','c=contacts&a=showAddressPeek&email={$result.t_first_wrote|escape:'url'}&view_id={$view->id}',null,false,'500');" title="{$result.t_first_wrote}">{$result.t_first_wrote|truncate:45:'...':true:true}</a></td>
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
						{$teams.$ticket_team_id->name|escape}
					</td>
					{elseif $column=="t_category_id"}
						{assign var=ticket_team_id value=$result.t_team_id}
						{assign var=ticket_category_id value=$result.t_category_id}
						<td>
							{if 0 == $ticket_category_id}
								{if (isset($active_worker_memberships.$ticket_team_id)) && $active_worker_memberships.$ticket_team_id->is_manager || $active_worker->is_superuser}
									<a href="javascript:;" onclick="genericAjaxPopup('peek','c=groups&a=showInboxFilterPanel&id=0&group_id={$ticket_team_id}&ticket_id={$result.t_id}&view_id={$view->id}',null,false,'600');">{$translate->_('mail.view.add_filter')}</a>
								{/if}
							{else}
								{$buckets.$ticket_category_id->name|escape}
							{/if}
						</td>
					{elseif $column=="t_last_action_code"}
					<td>
						{if $result.t_last_action_code=='O'}
							<span title="{$result.t_first_wrote}">New from <a href="javascript:;" onclick="genericAjaxPopup('peek','c=contacts&a=showAddressPeek&email={$result.t_last_wrote|escape:'url'}&view_id={$view->id}',null,false,'500');">{$result.t_last_wrote|truncate:45:'...':true:true}</a></span>
						{elseif $result.t_last_action_code=='R'}
							<span title="{$result.t_last_wrote}">{'mail.received'|devblocks_translate} from <a href="javascript:;" onclick="genericAjaxPopup('peek','c=contacts&a=showAddressPeek&email={$result.t_last_wrote|escape:'url'}&view_id={$view->id}',null,false,'500');">{$result.t_last_wrote|truncate:45:'...':true:true}</a></span>
						{elseif $result.t_last_action_code=='W'}
							<span title="{$result.t_last_wrote}">{'mail.sent'|devblocks_translate} from <a href="javascript:;" onclick="genericAjaxPopup('peek','c=contacts&a=showAddressPeek&email={$result.t_last_wrote|escape:'url'}&view_id={$view->id}',null,false,'500');">{$result.t_last_wrote|truncate:45:'...':true:true}</a></span>
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
						{if $active_worker->hasPriv('core.ticket.actions.spam')}<a href="javascript:;" onclick="$(this).closest('tbody').remove();genericAjaxGet('{$view->id}_output_container','c=tickets&a=reportSpam&id={$result.t_id}&viewId={$view->id}');">{/if}
						<span class="cerb-sprite sprite-{if $result.t_spam_score >= 0.90}warning{else}warning_gray{/if}" title="Report Spam ({$score})"></span>
						{if $active_worker->hasPriv('core.ticket.actions.spam')}</a>{/if}
						{/if}
					</td>
					{else}
					<td>{if $result.$column}{$result.$column|escape}{/if}</td>
					{/if}
				{/foreach}
				</tr>
				</tbody>
				{/if}{*!censor*}
				
			{/foreach}
				
			</table>
			<table cellpadding="2" cellspacing="0" border="0" width="100%" id="{$view->id}_actions">
				{if $total}
				<tr>
					<td colspan="2">
						{if 'context'==$view->renderTemplate}<button type="button" onclick="removeSelectedContextLinks('{$view->id}');">Unlink</button>{/if}
						{assign var=show_more value=0}
						{if $active_worker->hasPriv('core.ticket.view.actions.bulk_update')}{assign var=show_more value=1}<button type="button"  id="btn{$view->id}BulkUpdate" onclick="ajax.showBatchPanel('{$view->id}',null);"><span class="cerb-sprite sprite-folder_gear"></span> {$translate->_('common.bulk_update')|lower}</button>{/if}
						{if $active_worker->hasPriv('core.ticket.actions.close')}{assign var=show_more value=1}<button type="button" id="btn{$view->id}Close" onclick="ajax.viewCloseTickets('{$view->id}',0);"><span class="cerb-sprite sprite-folder_ok"></span> {$translate->_('common.close')|lower}</button>{/if}
						{if $active_worker->hasPriv('core.ticket.actions.spam')}{assign var=show_more value=1}<button type="button"  id="btn{$view->id}Spam" onclick="ajax.viewCloseTickets('{$view->id}',1);"><span class="cerb-sprite sprite-spam"></span> {$translate->_('common.spam')|lower}</button>{/if}
						{if $active_worker->hasPriv('core.ticket.actions.delete')}{assign var=show_more value=1}<button type="button"  id="btn{$view->id}Delete" onclick="ajax.viewCloseTickets('{$view->id}',2);"><span class="cerb-sprite sprite-delete"></span> {$translate->_('common.delete')|lower}</button>{/if}
						
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
						{if $view->id=='mail_workflow' || $view->id=='search'}{*Only on Workflow/Search*}
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

		</td>
	</tr>
</table>

<script type="text/javascript">
	$('#view{$view->id}_sidebartoggle').click(function(event) {
		$sidebar = $('#view{$view->id}_sidebar');
		
		if(0 == $sidebar.html().length) {
			genericAjaxGet('view{$view->id}_sidebar','c=internal&a=viewSubtotal&view_id={$view->id}&category=group');
			$sidebar.css('padding-right','5px');
		} else {
			genericAjaxGet('view{$view->id}_sidebar','c=internal&a=viewSubtotal&view_id={$view->id}&category=');
			$sidebar.css('padding-right','0px');
		}
	});
</script>

<br>