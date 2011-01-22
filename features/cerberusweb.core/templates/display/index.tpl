{include file="devblocks:cerberusweb.core::tickets/submenu.tpl"}

<table cellpadding="0" cellspacing="0" width="100%" border="0">
<tr>
	<td valign="top">
		<table cellpadding="0" cellspacing="0" width="100%">
			<tr>
				<td valign="top">
					<div style="float:right">
					{include file="devblocks:cerberusweb.core::tickets/quick_search_box.tpl"}
					</div>
					
					<h1>{$ticket->subject}</h1>
					{assign var=ticket_team_id value=$ticket->team_id}
					{assign var=ticket_team value=$teams.$ticket_team_id}
					{assign var=ticket_category_id value=$ticket->category_id}
					{assign var=ticket_team_category_set value=$team_categories.$ticket_team_id}
					{assign var=ticket_category value=$ticket_team_category_set.$ticket_category_id}
					
					<b>{$translate->_('ticket.status')|capitalize}:</b> 
					{if $ticket->is_deleted}
						{$translate->_('status.deleted')}
					{elseif $ticket->is_closed}
						{$translate->_('status.closed')}
					{elseif $ticket->is_waiting}
						{$translate->_('status.waiting')}
						{if !empty($ticket->due_date)}
							(<abbr title="{$ticket->due_date|devblocks_date}">{$ticket->due_date|devblocks_prettytime}</abbr>)
						{/if}
					{else}
						{$translate->_('status.open')}
					{/if} &nbsp; 
					<b>{$translate->_('common.group')|capitalize}:</b> {$teams.$ticket_team_id->name} &nbsp; 
					<b>{$translate->_('common.bucket')|capitalize}:</b> {if !empty($ticket_category_id)}{$ticket_category->name}{else}{$translate->_('common.inbox')|capitalize}{/if} &nbsp; 
					<b>{$translate->_('ticket.mask')|capitalize}:</b> {$ticket->mask} &nbsp; 
					<b>{$translate->_('ticket.id')}:</b> {$ticket->id} &nbsp; 
					<br>
					
					{if !empty($context_workers)}
						<b>{'common.owners'|devblocks_translate|capitalize}:</b> 
						{foreach from=$context_workers item=context_worker name=context_workers}
						{$context_worker->getName()}{if !$smarty.foreach.context_workers.last}, {/if}
						{/foreach}	
					<br>
					{/if}
					
					<b>{$translate->_('ticket.requesters')|capitalize}:</b>
					<span id="displayTicketRequesterBubbles">
						{include file="devblocks:cerberusweb.core::display/rpc/requester_list.tpl" ticket_id=$ticket->id}
					</span>
					(<a href="javascript:;" onclick="genericAjaxPopup('peek','c=display&a=showRequestersPanel&ticket_id={$ticket->id}',null,true,'500');">{$translate->_('common.edit')|lower}</a>)
				</td>
			</tr>
		</table>
	</td>
</tr>
<tr>
	<td valign="top">
		<div id="tourDisplayProperties"></div>
		<form action="{devblocks_url}{/devblocks_url}" method="post" style="margin-bottom:10px;margin-top:5px;">
			<input type="hidden" name="c" value="display">
			<input type="hidden" name="a" value="updateProperties">
			<input type="hidden" name="id" value="{$ticket->id}">
			<input type="hidden" name="closed" value="{if $ticket->is_closed}1{else}0{/if}">
			<input type="hidden" name="deleted" value="{if $ticket->is_deleted}1{else}0{/if}">
			<input type="hidden" name="spam" value="0">
			<input type="hidden" name="do_take" value="0">
			<input type="hidden" name="do_surrender" value="0">
			
			<div style="padding-bottom:5px;">
			<button type="button" id="btnDisplayTicketEdit"><span class="cerb-sprite sprite-document_edit"></span> Edit</button>
			
			{if !$ticket->is_deleted}
				{if $ticket->is_closed}
					<button type="button" onclick="this.form.closed.value='0';this.form.submit();"><span class="cerb-sprite sprite-folder_out"></span> {$translate->_('common.reopen')|capitalize}</button>
				{else}
					{if $active_worker->hasPriv('core.ticket.actions.close')}<button title="{$translate->_('display.shortcut.close')}" id="btnClose" type="button" onclick="this.form.closed.value=1;this.form.submit();"><span class="cerb-sprite sprite-folder_ok"></span> {$translate->_('common.close')|capitalize}</button>{/if}
				{/if}
				
				{if empty($ticket->spam_training)}
					{if $active_worker->hasPriv('core.ticket.actions.spam')}<button title="{$translate->_('display.shortcut.spam')}" id="btnSpam" type="button" onclick="this.form.spam.value='1';this.form.submit();"><span class="cerb-sprite sprite-spam"></span> {$translate->_('common.spam')|capitalize}</button>{/if}
				{/if}
			{/if}
			
			{if $ticket->is_deleted}
				<button type="button" onclick="this.form.deleted.value='0';this.form.closed.value=0;this.form.submit();"><span class="cerb-sprite sprite-delete_gray"></span> {$translate->_('common.undelete')|capitalize}</button>
			{else}
				{if $active_worker->hasPriv('core.ticket.actions.delete')}<button title="{$translate->_('display.shortcut.delete')}" id="btnDelete" type="button" onclick="this.form.deleted.value=1;this.form.closed.value=1;this.form.submit();"><span class="cerb-sprite sprite-delete"></span> {$translate->_('common.delete')|capitalize}</button>{/if}
			{/if}
			
			{if !isset($context_workers.{$active_worker->id})}<button id="btnTake" title="{$translate->_('display.shortcut.take')}" type="button" onclick="this.form.do_take.value='1';this.form.submit();"><span class="cerb-sprite sprite-hand_paper"></span> {$translate->_('mail.take')|capitalize}</button>{/if}
			{if isset($context_workers.{$active_worker->id})}<button id="btnSurrender" title="{$translate->_('display.shortcut.surrender')}" type="button" onclick="this.form.do_surrender.value='1';this.form.submit();"><span class="cerb-sprite sprite-flag_white"></span> {$translate->_('mail.surrender')|capitalize}</button>{/if}
			
		   	<button id="btnPrint" title="{$translate->_('display.shortcut.print')}" type="button" onclick="document.frmPrint.action='{devblocks_url}c=print&a=ticket&id={$ticket->mask}{/devblocks_url}';document.frmPrint.submit();">&nbsp;<span class="cerb-sprite sprite-printer"></span>&nbsp;</button>
		   	<button type="button" title="{$translate->_('display.shortcut.refresh')}" onclick="document.location='{devblocks_url}c=display&id={$ticket->mask}{/devblocks_url}';">&nbsp;<span class="cerb-sprite sprite-refresh"></span>&nbsp;</button>
		   	<button type="button" onclick="$('#divDisplayToolbarMore').toggle();">{$translate->_('common.more')|lower} &raquo;</button>
			</div>
			
			<div id="divDisplayToolbarMore" style="padding-bottom:5px;display:none;">
				{if $active_worker->hasPriv('core.ticket.view.actions.merge')}<button id="btnMerge" type="button" onclick="genericAjaxPopup('peek','c=display&a=showMergePanel&ticket_id={$ticket->id}',null,false,'500');"><span class="cerb-sprite sprite-folder_gear"></span> {$translate->_('mail.merge')|capitalize}</button>{/if}
			</div>
			
			<div>
			{if !$ticket->is_deleted}
			{if $active_worker->hasPriv('core.ticket.actions.move')}
		   	<select name="bucket_id" onchange="this.form.submit();">
		   		<option value="">-- {$translate->_('common.move_to')|lower} --</option>
		   		{if empty($ticket->category_id)}{assign var=t_or_c value="t"}{else}{assign var=t_or_c value="c"}{/if}
		   		<optgroup label="{$translate->_('common.inboxes')|capitalize}">
		   		{foreach from=$teams item=team}
		   			<option value="t{$team->id}">{$team->name}{if $t_or_c=='t' && $ticket->team_id==$team->id} (*){/if}</option>
		   		{/foreach}
		   		</optgroup>
		   		
		   		{foreach from=$team_categories item=categories key=teamId}
		   			{assign var=team value=$teams.$teamId}
		   			{if !empty($active_worker_memberships.$teamId)}
			   			<optgroup label="-- {$team->name} --">
			   			{foreach from=$categories item=category}
			 				<option value="c{$category->id}">{$category->name}{if $t_or_c=='c' && $ticket->category_id==$category->id} (current bucket){/if}</option>
			 			{/foreach}
			 			</optgroup>
			 		{/if}
		  		{/foreach}
		   	</select>
		   	{/if}
		   	{/if}			
			
			{* Plugin Toolbar *}
			{if !empty($ticket_toolbaritems)}
				{foreach from=$ticket_toolbaritems item=renderer}
					{if !empty($renderer)}{$renderer->render($ticket)}{/if}
				{/foreach}
			{/if}
			</div>
			
			{if $pref_keyboard_shortcuts}
			{$translate->_('common.keyboard')|lower}:
			{if $active_worker->hasPriv('core.display.actions.comment')}(<b>o</b>) {$translate->_('common.comment')} {/if}
			{if !$ticket->is_closed && $active_worker->hasPriv('core.ticket.actions.close')}(<b>c</b>) {$translate->_('common.close')|lower} {/if}
			{if !$ticket->spam_trained && $active_worker->hasPriv('core.ticket.actions.spam')}(<b>s</b>) {$translate->_('common.spam')|lower} {/if}
			{if !$ticket->is_deleted && $active_worker->hasPriv('core.ticket.actions.delete')}(<b>x</b>) {$translate->_('common.delete')|lower} {/if}
			{if !isset($context_workers.{$active_worker->id})}(<b>t</b>) {$translate->_('mail.take')|lower} {/if}
			{if isset($context_workers.{$active_worker->id})}(<b>u</b>) {$translate->_('mail.surrender')|lower} {/if}
			{if !$expand_all}(<b>a</b>) {$translate->_('display.button.read_all')|lower} {/if} 
			{if $active_worker->hasPriv('core.display.actions.reply')}(<b>r</b>) {$translate->_('display.ui.reply')|lower} {/if}  
			(<b>p</b>) {$translate->_('common.print')|lower} 
			<br>
			{/if}
			 
		</form>
		<form action="{devblocks_url}{/devblocks_url}" method="post" name="frmPrint" id="frmPrint" target="_blank" style="display:none;"></form>
	</td>
</tr>
</table>

{if empty($requesters)}
<div class="ui-widget">
	<div class="ui-state-error ui-corner-all" style="padding: 0 .7em; margin: 0.2em; "> 
		<p><span class="ui-icon ui-icon-alert" style="float: left; margin-right: .3em;"></span> 
		<strong>Warning:</strong> {$translate->_('ticket.recipients.empty')}</p>
	</div>
</div>
{/if}

<div id="displayTabs">
	<ul>
		<li><a href="{devblocks_url}ajax.php?c=display&a=showConversation&ticket_id={$ticket->id}{if $expand_all}&expand_all=1{/if}{/devblocks_url}">{$translate->_('display.tab.conversation')}</a></li>
		<li><a href="{devblocks_url}ajax.php?c=internal&a=showTabContextLinks&context=cerberusweb.contexts.ticket&id={$ticket->id}{/devblocks_url}">{$translate->_('common.links')}</a></li>
		<li><a href="{devblocks_url}ajax.php?c=display&a=showContactHistory&ticket_id={$ticket->id}{/devblocks_url}">{'display.tab.history'|devblocks_translate}</a></li>

		{$tabs = [conversation,links,history]}

		{foreach from=$tab_manifests item=tab_manifest}
			{$tabs[] = $tab_manifest->params.uri}
			<li><a href="{devblocks_url}ajax.php?c=display&a=showTab&ext_id={$tab_manifest->id}&ticket_id={$ticket->id}{/devblocks_url}"><i>{$tab_manifest->params.title|devblocks_translate}</i></a></li>
		{/foreach}
	</ul>
</div> 
<br>

{$tab_selected_idx=0}
{foreach from=$tabs item=tab_label name=tabs}
	{if $tab_label==$tab_selected}{$tab_selected_idx = $smarty.foreach.tabs.index}{/if}
{/foreach}

<script type="text/javascript">
	$(function() {
		var tabs = $("#displayTabs").tabs( { selected:{$tab_selected_idx} } );
		
		$('#btnDisplayTicketEdit').bind('click', function() {
			$popup = genericAjaxPopup('peek','c=tickets&a=showPreview&tid={$ticket->id}&edit=1',null,false,'650');
			$popup.one('ticket_save', function(event) {
				event.stopPropagation();
				document.location.href = '{devblocks_url}c=display&mask={$ticket->mask}{/devblocks_url}';
			});
		})
	});
</script>

<script type="text/javascript">
{if $pref_keyboard_shortcuts}
$(document).keypress(function(event) {
	if(event.altKey || event.ctrlKey || event.shiftKey || event.metaKey)
		return;
	
	if($(event.target).is(':input'))
		return;
	
	event.preventDefault();
	
	switch(event.which) {
		case 97:  // (A) read all
			try {
				$('#btnReadAll').click();
			} catch(ex) { } 
			break;
		case 99:  // (C) close
			try {
				$('#btnClose').click();
			} catch(ex) { } 
			break;
		case 101:  // (E) edit
			try {
				$('#btnDisplayTicketEdit').click();
			} catch(ex) { } 
			break;
		case 111:  // (O) comment
			try {
				$('#btnComment').click();
			} catch(ex) { } 
			break;
		case 112:  // (P) print
			try {
				$('#btnPrint').click();
			} catch(ex) { } 
			break;
		case 114:  // (R) reply to first message
			try {
				{if $expand_all}$('BUTTON.reply').last().click();{else}$('BUTTON.reply').first().click();{/if}
			} catch(ex) { } 
			break;
		case 115:  // (S) spam
			try {
				$('#btnSpam').click();
			} catch(ex) { } 
			break;
		case 116:  // (T) take/assign
			try {
				$('#btnTake').click();
			} catch(ex) { } 
			break;
		case 117:  // (U) surrender/unassign
			try {
				$('#btnSurrender').click();
			} catch(ex) { } 
			break;
		case 120:  // (X) delete
			try {
				$('#btnDelete').click();
			} catch(ex) { } 
			break;
		default:
			// We didn't find any obvious keys, try other codes
			break;
	}
});
{/if}
</script>
