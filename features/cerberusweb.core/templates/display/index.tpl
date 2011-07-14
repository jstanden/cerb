{include file="devblocks:cerberusweb.core::tickets/submenu.tpl"}

<table cellpadding="0" cellspacing="0" width="100%" border="0" id="displayProperties">
<tr>
	<td valign="top">
		<table cellpadding="0" cellspacing="0" width="100%">
			<tr>
				<td valign="top">
					<div style="float:right">
					{include file="devblocks:cerberusweb.core::tickets/quick_search_box.tpl"}
					</div>
					
					<h1>{$ticket->subject}</h1>
				</td>
			<tr>
				<td valign="top">
					{assign var=ticket_team_id value=$ticket->team_id}
					{assign var=ticket_team value=$teams.$ticket_team_id}
					{assign var=ticket_category_id value=$ticket->category_id}
					{assign var=ticket_team_category_set value=$team_categories.$ticket_team_id}
					{assign var=ticket_category value=$ticket_team_category_set.$ticket_category_id}
					
					<div class="cerb-properties">
						{if !empty($ticket->owner_id) && isset($workers.{$ticket->owner_id})}
						{$owner = $workers.{$ticket->owner_id}}
						<div>
							<label>{$translate->_('common.owner')|capitalize}:</label>
							<a href="{devblocks_url}c=profiles&p=worker&id={$owner->id}-{$owner->getName()|devblocks_permalink}{/devblocks_url}" target="_blank">{$owner->getName()}</a>
						</div>
						{/if}
					
						<div>
							<label>{$translate->_('ticket.status')|capitalize}:</label>
							{if $ticket->is_deleted}
								{$translate->_('status.deleted')}
							{elseif $ticket->is_closed}
								{$translate->_('status.closed')}
								{if !empty($ticket->due_date)}
									(<abbr title="{$ticket->due_date|devblocks_date}">{$ticket->due_date|devblocks_prettytime}</abbr>)
								{/if}
							{elseif $ticket->is_waiting}
								{$translate->_('status.waiting')}
								{if !empty($ticket->due_date)}
									(<abbr title="{$ticket->due_date|devblocks_date}">{$ticket->due_date|devblocks_prettytime}</abbr>)
								{/if}
							{else}
								{$translate->_('status.open')}
							{/if} 
						</div>
						
						<div>
							<label>{$translate->_('common.bucket')|capitalize}:</label>
							{$teams.$ticket_team_id->name}: 
							{if !empty($ticket_category_id)}{$ticket_category->name}{else}{$translate->_('common.inbox')|capitalize}{/if}
						</div>
						
						<div>
							<label>{$translate->_('ticket.mask')|capitalize}:</label>
							{$ticket->mask}
						</div>
						
						<div>
							<label>{$translate->_('ticket.id')}:</label>
							{$ticket->id}
						</div>
					</div>

					<div class="cerb-properties">
						<div>
							<label>{$translate->_('ticket.requesters')|capitalize}:</label>
							<span id="displayTicketRequesterBubbles">
								{include file="devblocks:cerberusweb.core::display/rpc/requester_list.tpl" ticket_id=$ticket->id}
							</span>
							(<a href="javascript:;" onclick="genericAjaxPopup('peek','c=display&a=showRequestersPanel&ticket_id={$ticket->id}',null,true,'500');">{$translate->_('common.edit')|lower}</a>)
						</div>
					</div>
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
			
			<div style="padding-bottom:5px;">
			
			<span>
			{$object_watchers = DAO_ContextLink::getContextLinks(CerberusContexts::CONTEXT_TICKET, array($ticket->id), CerberusContexts::CONTEXT_WORKER)}
			{include file="devblocks:cerberusweb.core::internal/watchers/context_follow_button.tpl" context=CerberusContexts::CONTEXT_TICKET context_id=$ticket->id full=true}
			</span>		
			
			{if !empty($macros)}
			<button type="button" class="split-left" onclick="$(this).next('button').click();"><span class="cerb-sprite sprite-gear"></span> Macros</button><!--  
			--><button type="button" class="split-right" id="btnDisplayMacros"><span class="cerb-sprite sprite-arrow-down-white"></span></button>
			<ul class="cerb-popupmenu cerb-float" id="menuDisplayMacros">
				<li style="background:none;">
					<input type="text" size="16" class="input_search filter">
				</li>
				{devblocks_url assign=return_url full=true}c=display&mask={$ticket->mask}{/devblocks_url}
				{foreach from=$macros item=macro key=macro_id}
				<li><a href="{devblocks_url}c=internal&a=applyMacro{/devblocks_url}?macro={$macro->id}&context={CerberusContexts::CONTEXT_TICKET}&context_id={$ticket->id}&return_url={$return_url|escape:'url'}">{$macro->title}</a></li>
				{/foreach}
			</ul>
			{/if}
			
			<button type="button" id="btnDisplayTicketEdit"><span class="cerb-sprite sprite-document_edit"></span> Edit</button>
			
			{if !$ticket->is_deleted}
				{if $ticket->is_closed}
					<button type="button" onclick="this.form.closed.value='0';this.form.submit();"><span class="cerb-sprite sprite-folder_out"></span> {$translate->_('common.reopen')|capitalize}</button>
				{else}
					{if $active_worker->hasPriv('core.ticket.actions.close')}<button title="{$translate->_('display.shortcut.close')}" id="btnClose" type="button" onclick="this.form.closed.value=1;this.form.submit();"><span class="cerb-sprite2 sprite-folder-tick-circle"></span> {$translate->_('common.close')|capitalize}</button>{/if}
				{/if}
				
				{if empty($ticket->spam_training)}
					{if $active_worker->hasPriv('core.ticket.actions.spam')}<button title="{$translate->_('display.shortcut.spam')}" id="btnSpam" type="button" onclick="this.form.spam.value='1';this.form.submit();"><span class="cerb-sprite sprite-spam"></span> {$translate->_('common.spam')|capitalize}</button>{/if}
				{/if}
			{/if}
			
			{if $ticket->is_deleted}
				<button type="button" onclick="this.form.deleted.value='0';this.form.closed.value=0;this.form.submit();"><span class="cerb-sprite2 sprite-cross-circle-frame-gray"></span> {$translate->_('common.undelete')|capitalize}</button>
			{else}
				{if $active_worker->hasPriv('core.ticket.actions.delete')}<button title="{$translate->_('display.shortcut.delete')}" id="btnDelete" type="button" onclick="this.form.deleted.value=1;this.form.closed.value=1;this.form.submit();"><span class="cerb-sprite2 sprite-cross-circle-frame"></span> {$translate->_('common.delete')|capitalize}</button>{/if}
			{/if}
			
		   	<button id="btnPrint" title="{$translate->_('display.shortcut.print')}" type="button" onclick="document.frmPrint.action='{devblocks_url}c=print&a=ticket&id={$ticket->mask}{/devblocks_url}';document.frmPrint.submit();">&nbsp;<span class="cerb-sprite sprite-printer"></span>&nbsp;</button>
		   	<button type="button" title="{$translate->_('display.shortcut.refresh')}" onclick="document.location='{devblocks_url}c=display&id={$ticket->mask}{/devblocks_url}';">&nbsp;<span class="cerb-sprite sprite-refresh"></span>&nbsp;</button>
		   	<button type="button" onclick="$('#divDisplayToolbarMore').toggle();">{$translate->_('common.more')|lower} &raquo;</button>
			</div>
			
			<div id="divDisplayToolbarMore" style="padding-bottom:5px;display:none;">
				{if $active_worker->hasPriv('core.ticket.view.actions.merge')}<button id="btnMerge" type="button" onclick="genericAjaxPopup('peek','c=display&a=showMergePanel&ticket_id={$ticket->id}',null,false,'500');"><span class="cerb-sprite2 sprite-folder-gear"></span> {$translate->_('mail.merge')|capitalize}</button>{/if}
			</div>
			
			<div>
			{* Plugin Toolbar *}
			{if !empty($ticket_toolbaritems)}
				{foreach from=$ticket_toolbaritems item=renderer}
					{if !empty($renderer)}{$renderer->render($ticket)}{/if}
				{/foreach}
			{/if}
			</div>
			
			{if $pref_keyboard_shortcuts}
			{$translate->_('common.keyboard')|lower}:
			(<b>e</b>) {'common.edit'|devblocks_translate|lower} 
			{if $active_worker->hasPriv('core.display.actions.comment')}(<b>o</b>) {$translate->_('common.comment')} {/if}
			{if !empty($macros)}(<b>m</b>) {'common.macros'|devblocks_translate|lower} {/if}
			{if !$ticket->is_closed && $active_worker->hasPriv('core.ticket.actions.close')}(<b>c</b>) {$translate->_('common.close')|lower} {/if}
			{if !$ticket->spam_trained && $active_worker->hasPriv('core.ticket.actions.spam')}(<b>s</b>) {$translate->_('common.spam')|lower} {/if}
			{if !$ticket->is_deleted && $active_worker->hasPriv('core.ticket.actions.delete')}(<b>x</b>) {$translate->_('common.delete')|lower} {/if}
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
		{$tabs = [conversation,activity,links,history]}

		<li><a href="{devblocks_url}ajax.php?c=display&a=showConversation&ticket_id={$ticket->id}{if $expand_all}&expand_all=1{/if}{/devblocks_url}">{$translate->_('display.tab.conversation')}</a></li>
		<li><a href="{devblocks_url}ajax.php?c=internal&a=showTabActivityLog&scope=target&point={$point}&context={CerberusContexts::CONTEXT_TICKET}&context_id={$ticket->id}{/devblocks_url}">{'common.activity_log'|devblocks_translate|capitalize}</a></li>		
		<li><a href="{devblocks_url}ajax.php?c=internal&a=showTabContextLinks&context=cerberusweb.contexts.ticket&id={$ticket->id}{/devblocks_url}">{$translate->_('common.links')}</a></li>
		<li><a href="{devblocks_url}ajax.php?c=display&a=showContactHistory&ticket_id={$ticket->id}{/devblocks_url}">{'display.tab.history'|devblocks_translate}</a></li>

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

	$menu = $('#menuDisplayMacros');
	$menu.appendTo('body');
	$menu.find('> li')
		.click(function(e) {
			e.stopPropagation();
			if(!$(e.target).is('li'))
				return;

			$link = $(this).find('a:first');
			
			if($link.length > 0)
				window.location.href = $link.attr('href');
		})
		;

	$menu.find('> li > input.filter').keyup(
		function(e) {
			$menu = $(this).closest('ul.cerb-popupmenu');
			
			if(27 == e.keyCode) {
				$(this).val('');
				$menu.hide();
				$(this).blur();
				return;
			}
			
			term = $(this).val().toLowerCase();
			$menu.find('> li a').each(function(e) {
				if(-1 != $(this).html().toLowerCase().indexOf(term)) {
					$(this).parent().show();
				} else {
					$(this).parent().hide();
				}
			});
		})
		;
	
	$('#btnDisplayMacros')
		.click(function(e) {
			$menu = $('#menuDisplayMacros');

			if($menu.is(':visible')) {
				$menu.hide();
				return;
			}
			
			$menu
				.css('position','absolute')
				.css('top',$(this).offset().top+($(this).height())+'px')
				.css('left',$(this).prev('button').offset().left+'px')
				.show()
				.find('> li input:text')
				.focus()
				.select()
			;
		});

	$menu
		.hover(
			function(e) {},
			function(e) {
				$('#menuDisplayMacros')
					.hide()
				;
			}
		)
		;	
</script>

<script type="text/javascript">
{if $pref_keyboard_shortcuts}
$(document).keypress(function(event) {
	if(event.altKey || event.ctrlKey || event.shiftKey || event.metaKey)
		return;
	
	if($(event.target).is(':input'))
		return;

	hotkey_activated = true;
	
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
		case 109:  // (M) macros
			try {
				$('#btnDisplayMacros').click();
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
		case 120:  // (X) delete
			try {
				$('#btnDelete').click();
			} catch(ex) { } 
			break;
		default:
			// We didn't find any obvious keys, try other codes
			hotkey_activated = false;
			break;
	}

	if(hotkey_activated)
		event.preventDefault();
});
{/if}
</script>
