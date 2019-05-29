{$view_context = CerberusContexts::CONTEXT_TICKET}
{$view_fields = $view->getColumnsAvailable()}
{$total = $results[1]}
{$data = $results[0]}
{$are_rows_two_lines = !in_array('t_subject', $view->view_columns)}

<div id="{$view->id}_output_container">
	{include file="devblocks:cerberusweb.core::tickets/rpc/ticket_view_output.tpl"}
</div>

{include file="devblocks:cerberusweb.core::internal/views/view_marquee.tpl" view=$view}

<table cellpadding="0" cellspacing="0" border="0" class="worklist" width="100%" {if $view->options.header_color}style="background-color:{$view->options.header_color};"{/if}>
	<tr>
		<td nowrap="nowrap"><span class="title">{$view->name}</span></td>
		<td nowrap="nowrap" align="right" class="title-toolbar">
			{if $active_worker->hasPriv("contexts.{$view_context}.create")}<a href="javascript:;" title="{'common.add'|devblocks_translate|capitalize}" class="minimal" onclick="genericAjaxPopup('compose' + new Date().getTime(),'c=internal&a=showPeekPopup&context={$view_context}&context_id=0&view_id={$view->id}&bucket_id={$view->options.compose_bucket_id}',null,false,'80%');"><span class="glyphicons glyphicons-circle-plus"></span></a>{/if}
			<a href="javascript:;" title="{'common.search'|devblocks_translate|capitalize}" class="minimal" onclick="genericAjaxPopup('search','c=internal&a=viewShowQuickSearchPopup&view_id={$view->id}',null,false,'400');"><span class="glyphicons glyphicons-search"></span></a>
			<a href="javascript:;" title="{'common.customize'|devblocks_translate|capitalize}" class="minimal" onclick="genericAjaxGet('customize{$view->id}','c=internal&a=viewCustomize&id={$view->id}');toggleDiv('customize{$view->id}','block');"><span class="glyphicons glyphicons-cogwheel"></span></a>
			<a href="javascript:;" title="{'common.subtotals'|devblocks_translate|capitalize}" class="subtotals minimal"><span class="glyphicons glyphicons-signal"></span></a>
			{if $active_worker->hasPriv("contexts.{$view_context}.import")}<a href="javascript:;" title="{'common.import'|devblocks_translate|capitalize}" onclick="genericAjaxPopup('import','c=internal&a=showImportPopup&context={$view_context}&view_id={$view->id}',null,false,'50%');"><span class="glyphicons glyphicons-file-import"></span></a>{/if}
			{if $active_worker->hasPriv("contexts.{$view_context}.export")}<a href="javascript:;" title="{'common.export'|devblocks_translate|capitalize}" class="minimal" onclick="genericAjaxGet('{$view->id}_tips','c=internal&a=viewShowExport&id={$view->id}');toggleDiv('{$view->id}_tips','block');"><span class="glyphicons glyphicons-file-export"></span></a>{/if}
			<a href="javascript:;" title="{'common.copy'|devblocks_translate|capitalize}" onclick="genericAjaxGet('{$view->id}_tips','c=internal&a=viewShowCopy&view_id={$view->id}');toggleDiv('{$view->id}_tips','block');"><span class="glyphicons glyphicons-duplicate"></span></a>
			<a href="javascript:;" title="{'common.refresh'|devblocks_translate|capitalize}" class="minimal" onclick="genericAjaxGet('view{$view->id}','c=internal&a=viewRefresh&id={$view->id}');"><span class="glyphicons glyphicons-refresh"></span></a>
			<input type="checkbox" class="select-all">
		</td>
	</tr>
</table>

<div id="{$view->id}_tips" class="block" style="display:none;margin:10px;padding:5px;">Analyzing...</div>
<form id="customize{$view->id}" action="#" onsubmit="return false;" style="display:none;"></form>
<form id="viewForm{$view->id}" name="viewForm{$view->id}" action="{devblocks_url}{/devblocks_url}" method="post">
<input type="hidden" name="view_id" value="{$view->id}">
<input type="hidden" name="context_id" value="{$view_context}">
<input type="hidden" name="c" value="tickets">
<input type="hidden" name="a" value="">
<input type="hidden" name="id" value="{$view->id}">
<input type="hidden" name="explore_from" value="0">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<table cellpadding="1" cellspacing="0" border="0" width="100%" class="worklistBody">
	{* Column Headers *}
	<thead>
	<tr>
		{if !$view->options.disable_watchers}
		<th class="no-sort" style="text-align:center;width:40px;padding-left:0;padding-right:0;" title="{'common.watchers'|devblocks_translate|capitalize}">
			<span class="glyphicons glyphicons-eye-open" style="color:rgb(80,80,80);"></span>
		</th>
		{/if}
		
		{foreach from=$view->view_columns item=header name=headers}
			{* start table header, insert column title and link *}
			<th class="{if $view->options.disable_sorting}no-sort{/if}">
			{if !$view->options.disable_sorting && !empty($view_fields.$header->db_column)}
				<a href="javascript:;" onclick="genericAjaxGet('view{$view->id}','c=internal&a=viewSortBy&id={$view->id}&sortBy={$header}');">{$view_fields.$header->db_label|capitalize}</a>
			{else}
				<a href="javascript:;" style="text-decoration:none;">{$view_fields.$header->db_label|capitalize}</a>
			{/if}
			
			{* add arrow if sorting by this column, finish table header tag *}
			{if $header==$view->renderSortBy}
				<span class="glyphicons {if $view->renderSortAsc}glyphicons-sort-by-attributes{else}glyphicons-sort-by-attributes-alt{/if}" style="font-size:14px;{if $view->options.disable_sorting}color:rgb(80,80,80);{else}color:rgb(39,123,213);{/if}"></span>
			{/if}
			</th>
		{/foreach}
		
	</tr>
	</thead>

	{* Column Data *}
	{if !$view->options.disable_watchers}{$object_watchers = DAO_ContextLink::getContextLinks($view_context, array_keys($data), CerberusContexts::CONTEXT_WORKER)}{/if}
	
	{* Bulk load drafts *}
	{$ticket_drafts = DAO_MailQueue::getDraftsByTicketIds(array_keys($data))} 
	
	{* Bulk lazy email addresses *}
	{$object_addys = []}
	{if in_array(SearchFields_Ticket::TICKET_FIRST_WROTE_ID, $view->view_columns) || in_array(SearchFields_Ticket::TICKET_LAST_WROTE_ID, $view->view_columns)}
		{$addy_ids = array_unique(array_merge(DevblocksPlatform::extractArrayValues($results, 't_first_wrote_address_id'), DevblocksPlatform::extractArrayValues($results, 't_last_wrote_address_id')))}
		{$object_addys = DAO_Address::getIds($addy_ids)}
		{$addy_contact_ids = DevblocksPlatform::extractArrayValues($object_addys, 'contact_id', true, [0])}
		{$object_contacts = DAO_Contact::getIds($addy_contact_ids)}
	{/if}
	
	{* Bulk lazy load contacts *}
	
	{* Bulk lazy load orgs *}
	{$object_orgs = []}
	{if in_array(SearchFields_Ticket::TICKET_ORG_ID, $view->view_columns)}
		{$org_ids = DevblocksPlatform::extractArrayValues($results, 't_org_id')}
		{$object_orgs = DAO_ContactOrg::getIds($org_ids)}
	{/if}
	
	{foreach from=$data item=result key=idx name=results}

	{if $smarty.foreach.results.iteration % 2}
		{$tableRowClass = "even"}
	{else}
		{$tableRowClass = "odd"}
	{/if}
	
	{* This is used in two places depending on if the row is one or two lines *}
	{capture name="ticket_subject_content"}
		<input type="checkbox" name="ticket_id[]" value="{$result.t_id}" style="display:none;">
		{if isset($ticket_drafts.{$result.t_id})}
			{$ticket_draft = $ticket_drafts.{$result.t_id}}
			{$draft_worker = $workers.{$ticket_draft->worker_id}}
			{if $draft_worker}
			<img class="cerb-avatar" src="{devblocks_url}c=avatars&what=worker&id={$draft_worker->id}{/devblocks_url}?v={$draft_worker->updated}" title="({$ticket_draft->updated|devblocks_prettytime}) {'mail.worklist.draft_in_progress'|devblocks_translate:{$workers.{$ticket_draft->worker_id}->getName()}}">
			{/if}
		{/if}
		{if $result.t_status_id == Model_Ticket::STATUS_DELETED}<span class="glyphicons glyphicons-circle-remove" style="color:rgb(80,80,80);font-size:14px;"></span> {elseif $result.t_status_id == Model_Ticket::STATUS_CLOSED}<span class="glyphicons glyphicons-circle-ok" style="color:rgb(80,80,80);font-size:14px;"></span> {elseif $result.t_status_id == Model_Ticket::STATUS_WAITING}<span class="glyphicons glyphicons-clock" style="color:rgb(39,123,213);font-size:14px;"></span>{/if}
		<a href="{devblocks_url}c=profiles&type=ticket&id={$result.t_mask}&tab=conversation{/devblocks_url}" class="subject">{$result.t_subject|default:'(no subject)'}</a> 
		<button type="button" class="peek cerb-peek-trigger" data-context="{$view_context}" data-context-id="{$result.t_id}" data-width="55%"><span class="glyphicons glyphicons-new-window-alt"></span></button>
	{/capture}
	
	{$ticket_group_id = $result.t_group_id}
	{$ticket_group = $groups.$ticket_group_id}

	<tbody style="cursor:pointer;" data-status-id="{$result.t_status_id}" data-status="{if $result.t_status_id == Model_Ticket::STATUS_WAITING}waiting{elseif $result.t_status_id == Model_Ticket::STATUS_CLOSED}closed{elseif $result.t_status_id == Model_Ticket::STATUS_DELETED}deleted{else}open{/if}" data-num-messages="{$result.t_num_messages}">
	
	<tr class="{$tableRowClass}">
		{if !$view->options.disable_watchers}
		<td data-column="*_watchers" align="center" {if $are_rows_two_lines}rowspan="2"{/if} nowrap="nowrap" style="padding-right:0;">
			{include file="devblocks:cerberusweb.core::internal/watchers/context_follow_button.tpl" context=$view_context context_id=$result.t_id watchers_group_id=$result.t_group_id watchers_bucket_id=$result.t_bucket_id}
		</td>
		{/if}
		
		{if !in_array('t_subject',$view->view_columns)}
		<td data-column="label" colspan="{$smarty.foreach.headers.total}">
			{$smarty.capture.ticket_subject_content nofilter}
		</td>
		{/if}
		
	{if $are_rows_two_lines}
	</tr>
	<tr class="{$tableRowClass}">
	{/if}
	
	{foreach from=$view->view_columns item=column name=columns}
		{if substr($column,0,3)=="cf_"}
			{include file="devblocks:cerberusweb.core::internal/custom_fields/view/cell_renderer.tpl"}
		{elseif $column=="t_id"}
		<td data-column="{$column}"><a href="{devblocks_url}c=profiles&type=ticket&id={$result.t_id}{/devblocks_url}">{$result.t_id}</a></td>
		{elseif $column=="t_mask"}
		<td data-column="{$column}"><a href="{devblocks_url}c=profiles&type=ticket&id={$result.t_mask}{/devblocks_url}">{$result.t_mask}</a></td>
		{elseif $column=="t_subject"}
		<td data-column="{$column}" title="{$result.t_subject}">
			{$smarty.capture.ticket_subject_content nofilter}
		</td>
		{elseif $column=="t_status_id"}
			<td data-column="{$column}">
			{if $result.t_status_id == Model_Ticket::STATUS_WAITING}
			<span class="glyphicons glyphicons-clock" style="color:rgb(39,123,213);font-size:14px;"></span>
			{elseif $result.t_status_id == Model_Ticket::STATUS_CLOSED}
			<span class="glyphicons glyphicons-circle-ok" style="color:rgb(80,80,80);font-size:14px;"></span>
			{elseif $result.t_status_id == Model_Ticket::STATUS_DELETED}
			<span class="glyphicons glyphicons-circle-remove" style="color:rgb(80,80,80);font-size:14px;"></span>
			{else}
			{/if}
			</td>
		{elseif in_array($column,["t_first_wrote_address_id","t_last_wrote_address_id"])}
			{$wrote = $object_addys.{$result.$column}}
			{$wrote_label = ""}
			<td data-column="{$column}">
				{if $wrote}
					{$wrote_label = $wrote->email}
					{$wrote_contact = $object_contacts.{$wrote->contact_id}}
					
					{if $wrote_contact}
						{$wrote_contact_name = $wrote_contact->getName()}
						{if $wrote_contact_name}
							{$wrote_label = $wrote_contact_name|cat:" <"|cat:$wrote->email|cat:">"}
						{/if}
					{/if}
				{/if}
				<a href="javascript:;" class="cerb-peek-trigger no-underline" data-context="{CerberusContexts::CONTEXT_ADDRESS}" data-context-id="{$result.$column}" data-is-local="{if isset($sender_addresses.{$result.$column})}true{/if}" title="{$wrote_label}">
					{$wrote_label|truncate:45:'...':true:true}
				</a>
			</td>
		{elseif $column=="t_created_date" || $column=="t_updated_date" || $column=="t_reopen_at" || $column=="t_closed_at"}
		<td data-column="{$column}" data-timestamp="{$result.$column}"><abbr title="{$result.$column|devblocks_date}">{$result.$column|devblocks_prettytime}</abbr></td>
		{elseif $column=="t_elapsed_response_first" || $column=="t_elapsed_resolution_first"}
		<td data-column="{$column}">
			{if !empty($result.$column)}{$result.$column|devblocks_prettysecs:2}{/if}
		</td>
		{elseif $column=="t_owner_id"}
		<td data-column="{$column}">
			{$owner = $workers.{$result.t_owner_id}}
			{if $owner instanceof Model_Worker}
				<img src="{devblocks_url}c=avatars&context=worker&context_id={$owner->id}{/devblocks_url}?v={$owner->updated}" style="height:1.5em;width:1.5em;border-radius:0.75em;vertical-align:middle;"> 
				<a href="javascript:;" class="cerb-peek-trigger no-underline" data-context="{CerberusContexts::CONTEXT_WORKER}" data-context-id="{$owner->id}">{$owner->getName()}</a>
			{/if}
		</td>
		{elseif $column=="t_org_id"}
		<td data-column="{$column}">
			{$org_id = $result.t_org_id}
			{if $org_id && isset($object_orgs.$org_id)}
				{$org = $object_orgs.$org_id}
				<a href="javascript:;" class="cerb-peek-trigger no-underline" data-context="{CerberusContexts::CONTEXT_ORG}" data-context-id="{$org->id}">{$org->name|truncate:30:'...':true}</a>
			{/if}
		</td>
		{elseif $column=="t_group_id"}
		<td data-column="{$column}">
			{if $ticket_group instanceof Model_Group}
				<a href="javascript:;" class="cerb-peek-trigger no-underline" data-context="{CerberusContexts::CONTEXT_GROUP}" data-context-id="{$ticket_group->id}">{$ticket_group->name}</a>
			{/if}
		</td>
		{elseif $column=="t_bucket_id"}
			{$ticket_bucket_id = $result.t_bucket_id}
			{$ticket_bucket = $buckets.$ticket_bucket_id}
			<td data-column="{$column}">
				{if $ticket_bucket instanceof Model_Bucket}
				<a href="javascript:;" class="cerb-peek-trigger no-underline" data-context="{CerberusContexts::CONTEXT_BUCKET}" data-context-id="{$ticket_bucket->id}">{$ticket_bucket->name}</a>
				{/if}
			</td>
		{elseif $column=="t_spam_score" || $column=="t_spam_training"}
		<td data-column="{$column}">
			{math assign=score equation="x*100" format="%0.2f%%" x=$result.t_spam_score}
			{if empty($result.t_spam_training)}
			{if $active_worker->hasPriv('core.ticket.actions.spam')}<a href="javascript:;" onclick="$(this).closest('tbody').remove();genericAjaxGet('{$view->id}_output_container','c=tickets&a=reportSpam&id={$result.t_id}&view_id={$view->id}');">{/if}
			{if $result.t_spam_score >= 0.90}
			<span class="glyphicons glyphicons-ban" style="color:rgb(200,0,0);" title="Report Spam ({$score})"></span>
			{else}
			<span class="glyphicons glyphicons-ban" style="color:rgb(100,100,100);" title="Report Spam ({$score})"></span>
			{/if}
			{if $active_worker->hasPriv('core.ticket.actions.spam')}</a>{/if}
			{/if}
		</td>
		{elseif $column=="t_importance"}
		<td data-column="{$column}" title="{$result.$column}">
			<div style="display:inline-block;margin-left:5px;width:40px;height:8px;background-color:rgb(220,220,220);border-radius:8px;">
				<div style="position:relative;margin-left:-5px;top:-1px;left:{$result.$column}%;width:10px;height:10px;border-radius:10px;background-color:{if $result.$column < 50}rgb(0,200,0);{elseif $result.$column > 50}rgb(230,70,70);{else}rgb(175,175,175);{/if}"></div>
			</div>
		</td>
		{elseif $column=="wtb_responsibility"}
		<td data-column="{$column}">
			<div style="display:inline-block;margin-left:5px;width:40px;height:8px;background-color:rgb(220,220,220);border-radius:8px;">
				<div style="position:relative;margin-left:-5px;top:-1px;left:{$result.$column}%;width:10px;height:10px;border-radius:10px;background-color:{if $result.$column < 50}rgb(230,70,70);{elseif $result.$column > 50}rgb(0,200,0);{else}rgb(175,175,175);{/if}"></div>
			</div>
		</td>
		{elseif $column=="*_status"}
		<td data-column="{$column}">
			{if $result.t_status_id == Model_Ticket::STATUS_WAITING}
				{'status.waiting.abbr'|devblocks_translate|lower}
			{elseif $result.t_status_id == Model_Ticket::STATUS_CLOSED}
				{'status.closed'|devblocks_translate|lower}
			{elseif $result.t_status_id == Model_Ticket::STATUS_DELETED}
				{'status.deleted'|devblocks_translate|lower}
			{else}
				{'status.open'|devblocks_translate|lower}
			{/if}
		</td>
		{else}
		<td data-column="{$column}">{if $result.$column}{$result.$column}{/if}</td>
		{/if}
	{/foreach}
	</tr>
	</tbody>
{/foreach}
</table>

<div style="padding-top:5px;">
	<div style="float:right;">
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
			<a href="javascript:;" onclick="genericAjaxGet('view{$view->id}','c=internal&a=viewPage&id={$view->id}&page={$prevPage}');">&lt;{'common.previous_short'|devblocks_translate|capitalize}</a>
		{/if}
		({'views.showing_from_to'|devblocks_translate:$fromRow:$toRow:$total})
		{if $toRow < $total}
			<a href="javascript:;" onclick="genericAjaxGet('view{$view->id}','c=internal&a=viewPage&id={$view->id}&page={$nextPage}');">{'common.next'|devblocks_translate|capitalize}&gt;</a>
			<a href="javascript:;" onclick="genericAjaxGet('view{$view->id}','c=internal&a=viewPage&id={$view->id}&page={$lastPage}');">&gt;&gt;</a>
		{/if}
	</div>
	
	{if $total}
	<div style="float:left;" id="{$view->id}_actions">
		<button type="button" class="action-always-show action-explore" onclick="this.form.explore_from.value=$(this).closest('form').find('tbody input:checkbox:checked:first').val();this.form.a.value='viewTicketsExplore';this.form.submit();"><span class="glyphicons glyphicons-compass"></span> {'common.explore'|devblocks_translate|lower}</button>
		{if $active_worker->hasPriv("contexts.{$view_context}.update.bulk")}<button type="button" class="action-always-show action-bulkupdate" onclick="genericAjaxPopup('peek','c=profiles&a=handleSectionAction&section=ticket&action=showBulkPopup&view_id={$view->id}&ids=' + Devblocks.getFormEnabledCheckboxValues('viewForm{$view->id}','ticket_id[]'),null,false,'50%');"><span class="glyphicons glyphicons-folder-closed"></span> {'common.bulk_update'|devblocks_translate|lower}</button>{/if}
		{if $active_worker->hasPriv('core.ticket.actions.close')}<button type="button" class="action-close" onclick="ajax.viewCloseTickets('{$view->id}',0);" style="display:none;"><span class="glyphicons glyphicons-ok"></span> {'common.close'|devblocks_translate|lower}</button>{/if}
		{if $active_worker->hasPriv('core.ticket.actions.spam')}<button type="button" class="action-spam" onclick="ajax.viewCloseTickets('{$view->id}',1);" style="display:none;"><span class="glyphicons glyphicons-ban"></span> {'common.spam'|devblocks_translate|lower}</button>{/if}
		{if $active_worker->hasPriv("contexts.{$view_context}.delete")}<button type="button" class="action-delete" onclick="ajax.viewCloseTickets('{$view->id}',2);" style="display:none;"><span class="glyphicons glyphicons-remove"></span> {'common.delete'|devblocks_translate|lower}</button>{/if}
		
		<button type="button" class="action-move" style="display:none;">{'common.move'|devblocks_translate|lower} <span class="glyphicons glyphicons-chevron-down"></span></button>
		<div class="cerb-popupmenu cerb-float">
			<select class="cerb-moveto-group">
				<option></option>
				{foreach from=$groups item=group}
				<option value="{$group->id}">{$group->name}</option>
				{/foreach}
			</select>
			<select class="cerb-moveto-bucket-options" style="display:none;">
				{foreach from=$buckets item=bucket}
				<option value="{$bucket->id}" data-group-id="{$bucket->group_id}">{$bucket->name}</option>
				{/foreach}
			</select>
			<select class="cerb-moveto-bucket" style="display:none;">
			</select>
		</div>
		
		{if $active_worker->hasPriv("contexts.{$view_context}.merge")}<button type="button" onclick="genericAjaxPopup('peek','c=internal&a=showRecordsMergePopup&view_id={$view->id}&context={$view_context}&ids=' + Devblocks.getFormEnabledCheckboxValues('viewForm{$view->id}','ticket_id[]'),null,false,'50%');" style="display:none;"><span class="glyphicons glyphicons-git-merge"></span> {'common.merge'|devblocks_translate|lower}</button>{/if}
		<button type="button" onclick="ajax.viewTicketsAction('{$view->id}','waiting');" style="display:none;">{'mail.waiting'|devblocks_translate|lower}</button>
		<button type="button" onclick="ajax.viewTicketsAction('{$view->id}','not_waiting');" style="display:none;">{'mail.not_waiting'|devblocks_translate|lower}</button>
		<button type="button" onclick="ajax.viewTicketsAction('{$view->id}','not_spam');" style="display:none;">{'common.notspam'|devblocks_translate|lower}</button>
	
		{if $pref_keyboard_shortcuts}
		{if $view->isCustom() || substr($view->id,0,6)=='search'}
			<div class="action-on-select" style="display:none;">
			{'common.keyboard'|devblocks_translate|lower}: 
				(<b>a</b>) {'common.all'|devblocks_translate|lower} 
				(<b>e</b>) {'common.explore'|devblocks_translate|lower} 
				{if $active_worker->hasPriv('contexts.cerberusweb.contexts.ticket.update.bulk')}(<b>b</b>) {'common.bulk_update'|devblocks_translate|lower}{/if} 
				{if $active_worker->hasPriv('core.ticket.actions.close')}(<b>c</b>) {'common.close'|devblocks_translate|lower}{/if} 
				{if $active_worker->hasPriv('core.ticket.actions.spam')}(<b>s</b>) {'common.spam'|devblocks_translate|lower}{/if} 
				{if $active_worker->hasPriv("contexts.{$view_context}.delete")}(<b>x</b>) {'common.delete'|devblocks_translate|lower}{/if}
				(<b>m</b>) {'common.move'|devblocks_translate|lower}
				(<b>-</b>) undo last filter
				(<b>*</b>) reset filters
				(<b>~</b>) change subtotals
				(<b>`</b>) focus subtotals
			</div>
		{/if}
		{/if}
	</div>
	{/if}
</div>

<div style="clear:both;"></div>
</form>

{include file="devblocks:cerberusweb.core::internal/views/view_common_jquery_ui.tpl"}

<script type="text/javascript">
$(function() {
	var $view = $('#view{$view->id}');
	$view.data('total', {$total|default:0});
	
	var $frm = $('#viewForm{$view->id}');
	
	{if $pref_keyboard_shortcuts}
	$frm.bind('keyboard_shortcut',function(event) {
		//console.log("{$view->id} received " + (indirect ? 'indirect' : 'direct') + " keyboard event for: " + event.keypress_event.which);
		
		var $view_actions = $('#{$view->id}_actions');
		
		var hotkey_activated = true;
	
		switch(event.keypress_event.which) {
			case 43: // (+) bulk update
				break;
				
			case 98: // (b) bulk update
				$btn = $view_actions.find('button.action-bulkupdate');
			
				if(event.indirect) {
					$btn.select().focus();
					
				} else {
					$btn.click();
				}
				break;
			
			case 99: // (c) close
				$btn = $view_actions.find('button.action-close');
			
				if(!event.indirect) {
					$btn.click();
				}
				break;
			
			case 101: // (e) explore
				$btn = $view_actions.find('button.action-explore');
			
				if(event.indirect) {
					$btn.select().focus();
					
				} else {
					$btn.click();
				}
				break;
				
			case 109: // (m) move
				event.keypress_event.preventDefault();
			
				if(!event.indirect) {
					$btn = $view_actions.find('button.action-move');
					$btn.click();
				}
				break;
			
			case 115: // (s) spam
				$btn = $view_actions.find('button.action-spam');
			
				if(!event.indirect) {
					$btn.click();
				}
				break;
				
	// 		case 116: // (t) take
	// 			break;
				
	// 		case 117: // (u) surrender
	// 			break;
			
			case 120: // (x) delete
				var $btn = $view_actions.find('button.action-delete');
			
				if(!event.indirect) {
					$btn.click();
				}
				break;	
			
			default:
				hotkey_activated = false;
				break;
		}
	
		if(hotkey_activated)
			event.preventDefault();
	});
	{/if}
	
	// Quick move menu
	var $view_actions = $('#{$view->id}_actions');
	var $menu_trigger = $view_actions.find('button.action-move');
	var $menu = $menu_trigger.next('div.cerb-popupmenu');
	$menu_trigger.data('menu', $menu);
	
	var $select_moveto_group = $menu.find('select.cerb-moveto-group');
	var $select_moveto_bucket = $menu.find('select.cerb-moveto-bucket');
	var $select_moveto_bucket_options = $menu.find('select.cerb-moveto-bucket-options');
	
	$menu_trigger
		.click(
			function(e) {
				var $menu = $(this).data('menu');
	
				if($menu.is(':visible')) {
					$menu.hide();
					return;
				}
	
				$menu
					.css('position','absolute')
					.css('left',$(this).offset().left+'px')
					.show()
					;
			}
		)
	;
	
	$select_moveto_group.change(function() {
		var group_id = $(this).val();
		
		if(0 == group_id.length) {
			$select_moveto_bucket.fadeOut();
			return;
		}
		
		$select_moveto_bucket.find('> option').remove();
		
		$('<option/>').appendTo($select_moveto_bucket);
			
		$select_moveto_bucket_options.find('option').each(function() {
			var $opt = $(this);
			if($opt.attr('data-group-id') == group_id)
				$opt.clone().appendTo($select_moveto_bucket);
		});
		
		$select_moveto_bucket.fadeIn();
	});
	
	$select_moveto_bucket.change(function() {
		var bucket_id = $(this).val();
		
		if(0 == bucket_id.length)
			return;
		
		var $opt = $(this).find('option:selected');
		var group_id = $opt.attr('data-group-id');
		
		if(0 == group_id.length)
			return;
		
		genericAjaxPost('viewForm{$view->id}', 'view{$view->id}', 'c=tickets&a=viewMoveTickets&view_id={$view->id}&group_id=' + group_id + '&bucket_id=' + bucket_id);
	});
	
});
</script>