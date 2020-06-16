{$view_context = CerberusContexts::CONTEXT_MESSAGE}
{$view_fields = $view->getColumnsAvailable()}
{$total = $results[1]}
{$data = $results[0]}

{include file="devblocks:cerberusweb.core::internal/views/view_marquee.tpl" view=$view}

<table cellpadding="0" cellspacing="0" border="0" class="worklist" width="100%" {if $view->options.header_color}style="background-color:{$view->options.header_color};"{/if}>
	<tr>
		<td nowrap="nowrap"><span class="title">{$view->name}</span></td>
		<td nowrap="nowrap" align="right" class="title-toolbar">
			<a href="javascript:;" title="{'common.search'|devblocks_translate|capitalize}" class="minimal" onclick="genericAjaxPopup('search','c=internal&a=invoke&module=worklists&action=showQuickSearchPopup&view_id={$view->id}',null,false,'400');"><span class="glyphicons glyphicons-search"></span></a>
			<a href="javascript:;" title="{'common.customize'|devblocks_translate|capitalize}" class="minimal" onclick="genericAjaxGet('customize{$view->id}','c=internal&a=invoke&module=worklists&action=customize&id={$view->id}');toggleDiv('customize{$view->id}','block');"><span class="glyphicons glyphicons-cogwheel"></span></a>
			<a href="javascript:;" title="{'common.subtotals'|devblocks_translate|capitalize}" class="subtotals minimal"><span class="glyphicons glyphicons-signal"></span></a>
			{if $active_worker->hasPriv("contexts.{$view_context}.export")}<a href="javascript:;" title="{'common.export'|devblocks_translate|capitalize}" class="minimal" onclick="genericAjaxGet('{$view->id}_tips','c=internal&a=invoke&module=worklists&action=renderExport&id={$view->id}');toggleDiv('{$view->id}_tips','block');"><span class="glyphicons glyphicons-file-export"></span></a>{/if}
			<a href="javascript:;" title="{'common.copy'|devblocks_translate|capitalize}" onclick="genericAjaxGet('{$view->id}_tips','c=internal&a=invoke&module=worklists&action=renderCopy&view_id={$view->id}');toggleDiv('{$view->id}_tips','block');"><span class="glyphicons glyphicons-duplicate"></span></a>
			<a href="javascript:;" title="{'common.refresh'|devblocks_translate|capitalize}" class="minimal" onclick="genericAjaxGet('view{$view->id}','c=internal&a=invoke&module=worklists&action=refresh&id={$view->id}');"><span class="glyphicons glyphicons-refresh"></span></a>
			<input type="checkbox" class="select-all">
		</td>
	</tr>
</table>

<div id="{$view->id}_tips" class="block" style="display:none;margin:10px;padding:5px;">Loading...</div>
<form id="customize{$view->id}" name="customize{$view->id}" action="#" onsubmit="return false;" style="display:none;"></form>
<form id="viewForm{$view->id}" name="viewForm{$view->id}" action="{devblocks_url}{/devblocks_url}" method="post">
<input type="hidden" name="view_id" value="{$view->id}">
<input type="hidden" name="context_id" value="{$view_context}">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="message">
<input type="hidden" name="action" value="viewExplore">
<input type="hidden" name="explore_from" value="0">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<table cellpadding="1" cellspacing="0" border="0" width="100%" class="worklistBody">

	{* Column Headers *}
	<thead>
	<tr>
		{foreach from=$view->view_columns item=header name=headers}
			{* start table header, insert column title and link *}
			{$view_field = $view_fields.$header}
			<th class="{if $view->options.disable_sorting}no-sort{/if}">
			{if !$view->options.disable_sorting && $view_field->db_column && $view_field->is_sortable}
				<a href="javascript:;" onclick="genericAjaxGet('view{$view->id}','c=internal&a=invoke&module=worklists&action=sort&id={$view->id}&sortBy={$header}');">{$view_fields.$header->db_label|capitalize}</a>
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

	{* Bulk lazy load sender address *}
	{$object_senders = []}
	{if in_array(SearchFields_Message::ADDRESS_EMAIL, $view->view_columns)}
		{$sender_ids = DevblocksPlatform::extractArrayValues($results, 'm_address_id')}
		{$object_senders = DAO_Address::getIds($sender_ids)}

		{$contact_ids = DevblocksPlatform::extractArrayValues($object_senders, 'contact_id')}
		{$object_contacts = DAO_Contact::getIds($contact_ids)}
	{/if}

	{* Bulk lazy load tickets *}
	{$ticket_ids = DevblocksPlatform::extractArrayValues($results, 'm_ticket_id')}
	{$object_tickets = DAO_Ticket::getIds($ticket_ids)}

	{* Column Data *}
	{foreach from=$data item=result key=idx name=results}

	{if $smarty.foreach.results.iteration % 2}
		{$tableRowClass = "even"}
	{else}
		{$tableRowClass = "odd"}
	{/if}
	<tbody style="cursor:pointer;">
		<tr class="{$tableRowClass}">
			<td data-column="label" colspan="{$smarty.foreach.headers.total}">
				{$ticket = $object_tickets.{$result.m_ticket_id}}
				{if $ticket}
				<input type="checkbox" name="row_id[]" value="{$result.m_id}" style="display:none;">
				{if $ticket->status_id == Model_Ticket::STATUS_DELETED}<span class="glyphicons glyphicons-circle-remove" style="color:rgb(80,80,80);font-size:14px;"></span> {elseif $ticket->status_id == Model_Ticket::STATUS_CLOSED}<span class="glyphicons glyphicons-circle-ok" style="color:rgb(80,80,80);font-size:14px;"></span> {elseif $ticket->status_id == Model_Ticket::STATUS_WAITING}<span class="glyphicons glyphicons-clock" style="color:rgb(39,123,213);font-size:14px;"></span>{/if}
				{if $result.m_was_encrypted}
					<span class="glyphicons glyphicons-lock" style="color:rgb(80,80,80);font-size:14px;" title="{'common.encrypted'|devblocks_translate|capitalize}"></span>
				{/if}
				<a href="{devblocks_url}c=profiles&type=ticket&id={$ticket->mask}&focus=message&focusid={$result.m_id}{/devblocks_url}" class="subject">{if !empty($ticket->subject)}{$ticket->subject}{else}(no subject){/if}</a>
				<button type="button" class="peek cerb-peek-trigger" data-context="{CerberusContexts::CONTEXT_MESSAGE}" data-context-id="{$result.m_id}"><span class="glyphicons glyphicons-new-window-alt"></span></button>
				{/if} 
			</td>
		</tr>
		<tr class="{$tableRowClass}">
		{foreach from=$view->view_columns item=column name=columns}
			{if substr($column,0,3)=="cf_"}
				{include file="devblocks:cerberusweb.core::internal/custom_fields/view/cell_renderer.tpl"}
			{elseif $column=="a_email"}
				{$sender = $object_senders.{$result.m_address_id}}
				<td data-column="{$column}">
					{if $sender}
						{$contact = $object_contacts.{$sender->contact_id}}
						{if $contact}
							<a href="javascript:;" class="cerb-peek-trigger no-underline" data-context="{CerberusContexts::CONTEXT_ADDRESS}" data-context-id="{$result.m_address_id}" title="{$contact->getName()} &lt;{$sender->email}&gt;">{$contact->getName()} &lt;{$sender->email|truncate:45:'...':true:true}&gt;</a>
						{else}
							<a href="javascript:;" class="cerb-peek-trigger no-underline" data-context="{CerberusContexts::CONTEXT_ADDRESS}" data-context-id="{$result.m_address_id}" title="{$sender->email}">{$sender->email|truncate:45:'...':true:true}</a>
						{/if}
					{/if}
				</td>
			{elseif $column=="t_bucket_id"}
				{$ticket = $object_tickets.{$result.m_ticket_id}}
				<td data-column="{$column}">
					{if $ticket}
						{$bucket = $ticket->getBucket()}
						{if $bucket}
						<a href="javascript:;" class="cerb-peek-trigger no-underline" data-context="{CerberusContexts::CONTEXT_BUCKET}" data-context-id="{$ticket->bucket_id}">{$bucket->name}</a>
						{/if}
					{/if}
				</td>
			{elseif $column=="t_group_id"}
				{$ticket = $object_tickets.{$result.m_ticket_id}}
				<td data-column="{$column}">
					{if $ticket}
						{$group = $ticket->getGroup()}
						{if $group}
						<a href="javascript:;" class="cerb-peek-trigger no-underline" data-context="{CerberusContexts::CONTEXT_GROUP}" data-context-id="{$ticket->group_id}">{$group->name}</a>
						{/if}
					{/if}
				</td>
			{elseif $column=="t_subject"}
				{$ticket = $object_tickets.{$result.m_ticket_id}}
				<td data-column="{$column}">
					{if $ticket}
					<a href="javascript:;" class="cerb-peek-trigger no-underline" data-context="{CerberusContexts::CONTEXT_TICKET}" data-context-id="{$ticket->id}">{$ticket->subject|truncate:45:'...':true:true}</a>
					{/if}
				</td>
			{elseif $column=="t_mask"}
				{$ticket = $object_tickets.{$result.m_ticket_id}}
				<td data-column="{$column}">
					{if $ticket}
					<a href="javascript:;" class="cerb-peek-trigger no-underline" data-context="{CerberusContexts::CONTEXT_TICKET}" data-context-id="{$ticket->id}">{$ticket->mask}</a>
					{/if}
				</td>
			{elseif $column=="m_worker_id"}
				<td data-column="{$column}">
				{if empty($workers)}{$workers = DAO_Worker::getAll()}{/if}
				{$worker_id = $result.$column}
				{if isset($workers.$worker_id)}
					{$worker = $workers.$worker_id}
					<img src="{devblocks_url}c=avatars&context=worker&context_id={$worker->id}{/devblocks_url}?v={$worker->updated}" style="height:1.5em;width:1.5em;border-radius:0.75em;vertical-align:middle;">
					<a href="javascript:;" class="cerb-peek-trigger" data-context="{CerberusContexts::CONTEXT_WORKER}" data-context-id="{$worker_id}">{$workers.{$worker_id}->getName()}</a>
				{/if}
				</td>
			{elseif in_array($column, ["m_is_outgoing", "m_is_broadcast", "m_is_not_sent", "m_was_encrypted"])}
				<td data-column="{$column}">
					{if !empty($result.$column)}{'common.yes'|devblocks_translate|lower}{else}{'common.no'|devblocks_translate|lower}{/if}
				</td>
			{elseif $column=="m_created_date"}
				<td data-column="{$column}"><abbr title="{$result.$column|devblocks_date}">{$result.$column|devblocks_prettytime}</abbr>&nbsp;</td>
			{elseif $column=="m_response_time"}
				<td data-column="{$column}">{if !empty($result.$column)}{$result.$column|devblocks_prettysecs:2}{/if}</td>
			{elseif $column=="*_ticket_status"}
				<td data-column="{$column}">
					{if $result.status_id == Model_Ticket::STATUS_DELETED}
						{'status.deleted'|devblocks_translate|lower}
					{elseif $result.status_id == Model_Ticket::STATUS_CLOSED}
						{'status.closed'|devblocks_translate|lower}
					{elseif $result.status_id == Model_Ticket::STATUS_WAITING}
						{'status.waiting'|devblocks_translate|lower}
					{else}
						{'status.open'|devblocks_translate|lower}
					{/if}
				</td>
			{else}
				<td data-column="{$column}">{$result.$column}</td>
			{/if}
		{/foreach}
		</tr>
	</tbody>
	{/foreach}
</table>

{if $total >= 0}
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
			<a href="javascript:;" onclick="genericAjaxGet('view{$view->id}','c=internal&a=invoke&module=worklists&action=page&id={$view->id}&page=0');">&lt;&lt;</a>
			<a href="javascript:;" onclick="genericAjaxGet('view{$view->id}','c=internal&a=invoke&module=worklists&action=page&id={$view->id}&page={$prevPage}');">&lt;{'common.previous_short'|devblocks_translate|capitalize}</a>
		{/if}
		({'views.showing_from_to'|devblocks_translate:$fromRow:$toRow:$total})
		{if $toRow < $total}
			<a href="javascript:;" onclick="genericAjaxGet('view{$view->id}','c=internal&a=invoke&module=worklists&action=page&id={$view->id}&page={$nextPage}');">{'common.next'|devblocks_translate|capitalize}&gt;</a>
			<a href="javascript:;" onclick="genericAjaxGet('view{$view->id}','c=internal&a=invoke&module=worklists&action=page&id={$view->id}&page={$lastPage}');">&gt;&gt;</a>
		{/if}
	</div>
	
	<div style="float:left;" id="{$view->id}_actions">
		<button type="button" class="action-always-show action-explore" onclick="this.form.explore_from.value=$(this).closest('form').find('tbody input:checkbox:checked:first').val();this.form.submit();"><span class="glyphicons glyphicons-play-button"></span> {'common.explore'|devblocks_translate|lower}</button>
	</div>
</div>
{/if}

<div style="clear:both;"></div>

</form>

{include file="devblocks:cerberusweb.core::internal/views/view_common_jquery_ui.tpl"}

<script type="text/javascript">
$(function() {
var $frm = $('#viewForm{$view->id}');
var $view = $('#view{$view->id}');

{if $pref_keyboard_shortcuts}
$frm.bind('keyboard_shortcut',function(event) {
	//console.log("{$view->id} received " + (indirect ? 'indirect' : 'direct') + " keyboard event for: " + event.keypress_event.which);
	
	$view_actions = $('#{$view->id}_actions');
	
	hotkey_activated = true;

	switch(event.keypress_event.which) {
		case 101: // (e) explore
			$btn = $view_actions.find('button.action-explore');
		
			if(event.indirect) {
				$btn.select().focus();
				
			} else {
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
});
</script>