{$page_context = CerberusContexts::CONTEXT_TICKET}
{$page_context_id = $dict->id}
{$is_writeable = Context_Ticket::isWriteableByActor($dict, $active_worker)}

{if !empty($merge_parent)}
	<div class="help-box">
	<h1>This ticket was merged</h1>
	
	<p>
	You can find the new ticket here: <a href="{devblocks_url}c=profiles&w=ticket&mask={$merge_parent->mask}{/devblocks_url}"><b>[#{$merge_parent->mask}] {$merge_parent->subject}</b></a>
	</p>
	</div>
{/if}

{if $smarty.const.APP_OPT_DEPRECATED_PROFILE_QUICK_SEARCH}
<div style="float:right">
	{$ctx = Extension_DevblocksContext::get($page_context)}
	{include file="devblocks:cerberusweb.core::search/quick_search.tpl" view=$ctx->getSearchView() return_url="{devblocks_url}c=search&context={$ctx->manifest->params.alias}{/devblocks_url}"}
</div>
{/if}

<h1>{$dict->subject}</h1>

<div style="clear:both;"></div>

<div class="cerb-profile-toolbar">
	<form class="toolbar" action="{devblocks_url}{/devblocks_url}" method="post" style="margin-top:5px;margin-bottom:5px;">
		<input type="hidden" name="c" value="display">
		<input type="hidden" name="a" value="updateProperties">
		<input type="hidden" name="id" value="{$dict->id}">
		<input type="hidden" name="status_id" value="{$dict->status_id}">
		<input type="hidden" name="spam" value="0">
		<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">
		
		<span id="spanInteractions">
		{include file="devblocks:cerberusweb.core::events/interaction/interactions_menu.tpl"}
		</span>
		
		<!-- Card -->
		<button type="button" id="btnProfileCard" title="{'common.card'|devblocks_translate|capitalize}" data-context="{$dict->_context}" data-context-id="{$dict->id}"><span class="glyphicons glyphicons-nameplate"></span></button>
		
		<!-- Edit -->
		{if $is_writeable && $active_worker->hasPriv("contexts.{$page_context}.update")}
		<button type="button" id="btnDisplayTicketEdit" title="{'common.edit'|devblocks_translate|capitalize} (E)" class="cerb-peek-trigger" data-context="{CerberusContexts::CONTEXT_TICKET}" data-context-id="{$dict->id}" data-edit="true"><span class="glyphicons glyphicons-cogwheel"></span></button>
		{/if}

		<span id="spanWatcherToolbar">
		{$object_watchers = DAO_ContextLink::getContextLinks($page_context, array($page_context_id), CerberusContexts::CONTEXT_WORKER)}
		{include file="devblocks:cerberusweb.core::internal/watchers/context_follow_button.tpl" context=$page_context context_id=$page_context_id full=true watchers_group_id=$dict->group_id watchers_bucket_id=$dict->bucket_id}
		</span>
		
		{if $is_writeable && $active_worker->hasPriv("contexts.{$page_context}.update")}
		
		{if $dict->status_id != Model_Ticket::STATUS_DELETED}
			{if $dict->status_id == Model_Ticket::STATUS_CLOSED}
				<button type="button" title="{'common.reopen'|devblocks_translate|capitalize}" onclick="this.form.status_id.value='{Model_Ticket::STATUS_OPEN}';this.form.submit();"><span class="glyphicons glyphicons-upload"></span></button>
			{else}
				{if $active_worker->hasPriv('core.ticket.actions.close')}<button title="{'display.shortcut.close'|devblocks_translate|capitalize}" id="btnClose" type="button" onclick="this.form.status_id.value='{Model_Ticket::STATUS_CLOSED}';this.form.submit();"><span class="glyphicons glyphicons-circle-ok"></span></button>{/if}
			{/if}
			
			{if empty($dict->spam_training)}
				{if $active_worker->hasPriv('core.ticket.actions.spam')}<button title="{'display.shortcut.spam'|devblocks_translate|capitalize}" id="btnSpam" type="button" onclick="this.form.spam.value='1';this.form.submit();"><span class="glyphicons glyphicons-ban"></span></button>{/if}
			{/if}
		{/if}
		
		{if $dict->status_id == Model_Ticket::STATUS_DELETED}
			<button type="button" title="{'common.undelete'|devblocks_translate|capitalize}" onclick="this.form.status_id.value='{Model_Ticket::STATUS_OPEN}';this.form.submit();"><span class="glyphicons glyphicons-upload"></span></button>
		{else}
			{if $active_worker->hasPriv("contexts.{$page_context}.delete")}<button title="{'display.shortcut.delete'|devblocks_translate}" id="btnDelete" type="button" onclick="this.form.status_id.value='{Model_Ticket::STATUS_DELETED}';this.form.submit();"><span class="glyphicons glyphicons-circle-remove"></span></button>{/if}
		{/if}
		
		{if $active_worker->hasPriv("contexts.{$page_context}.merge")}<button id="btnMerge" type="button"><span class="glyphicons glyphicons-git-merge"></span></button>{/if}
		
		{/if}
		
		<button id="btnPrint" title="{'display.shortcut.print'|devblocks_translate}" type="button" onclick="document.frmPrint.action='{devblocks_url}c=print&a=ticket&id={$dict->mask}{/devblocks_url}';document.frmPrint.submit();"><span class="glyphicons glyphicons-print"></span></button>
		<button type="button" title="{'common.refresh'|devblocks_translate|capitalize}" onclick="document.location='{devblocks_url}c=profiles&type=ticket&id={$dict->mask}{/devblocks_url}';"><span class="glyphicons glyphicons-refresh"></span></button>
	</form>
	
	<form action="{devblocks_url}{/devblocks_url}" method="post" name="frmPrint" id="frmPrint" target="_blank" style="display:none;"></form>
	
	{if $pref_keyboard_shortcuts}
	<small>
		{'common.keyboard'|devblocks_translate|lower}:
		(<b>i</b>) {'common.interactions'|devblocks_translate|lower} 
		(<b>e</b>) {'common.edit'|devblocks_translate|lower} 
		(<b>w</b>) {'common.watch'|devblocks_translate|lower} 
		(<b>o</b>) {'common.comment'|devblocks_translate} 
		{if $dict->status_id != Model_Ticket::STATUS_CLOSED && $active_worker->hasPriv('core.ticket.actions.close')}(<b>c</b>) {'common.close'|devblocks_translate|lower} {/if}
		{if !$dict->spam_training && $active_worker->hasPriv('core.ticket.actions.spam')}(<b>s</b>) {'common.spam'|devblocks_translate|lower} {/if}
		{if $dict->status_id != Model_Ticket::STATUS_DELETED && $active_worker->hasPriv("contexts.{$page_context}.delete")}(<b>x</b>) {'common.delete'|devblocks_translate|lower} {/if}
		{if empty($dict->owner_id)}(<b>t</b>) {'common.assign'|devblocks_translate|lower} {/if}
		{if !empty($dict->owner_id)}(<b>u</b>) {'common.unassign'|devblocks_translate|lower} {/if}
		{if !$expand_all}(<b>a</b>) {'display.button.read_all'|devblocks_translate|lower} {/if} 
		{if $active_worker->hasPriv('core.display.actions.reply')}(<b>r</b>) {'common.reply'|devblocks_translate|lower} {/if}  
		(<b>p</b>) {'common.print'|devblocks_translate|lower} 
		(<b>1-9</b>) change tab 
	</small>
	{/if}
</div>

<fieldset class="properties">
	<legend>{'common.conversation'|devblocks_translate|capitalize}</legend>

	<div style="margin-left:15px;">

	{foreach from=$properties item=v key=k name=props}
		<div class="property">
			{if $k == 'mask'}
				<b id="tour-profile-ticket-mask">{'ticket.mask'|devblocks_translate|capitalize}:</b>
				{$dict->mask} 
				(#{$dict->id})
			{elseif $k == 'status'}
				<b>{'common.status'|devblocks_translate|capitalize}:</b>
				{if $dict->status_id == Model_Ticket::STATUS_DELETED}
					<span style="font-weight:bold;color:rgb(150,0,0);">{'status.deleted'|devblocks_translate}</span>
				{elseif $dict->status_id == Model_Ticket::STATUS_CLOSED}
					<span style="font-weight:bold;color:rgb(50,115,185);">{'status.closed'|devblocks_translate}</span>
					{if !empty($dict->reopen_date)}
						(opens {if $dict->reopen_date > time()}in {/if}<abbr title="{$dict->reopen_date|devblocks_date}">{$dict->reopen_date|devblocks_prettytime}</abbr>)
					{/if}
				{elseif $dict->status_id == Model_Ticket::STATUS_WAITING}
					<span style="font-weight:bold;color:rgb(50,115,185);">{'status.waiting'|devblocks_translate}</span>
					{if !empty($dict->reopen_date)}
						(opens {if $dict->reopen_date > time()}in {/if}<abbr title="{$dict->reopen_date|devblocks_date}">{$dict->reopen_date|devblocks_prettytime}</abbr>)
					{/if}
				{else}
					{'status.open'|devblocks_translate}
				{/if} 
			{elseif $k == 'importance'}
				<b>{'common.importance'|devblocks_translate|capitalize}:</b>
				<div style="display:inline-block;margin-left:5px;width:40px;height:8px;background-color:rgb(220,220,220);border-radius:8px;">
					<div style="position:relative;margin-left:-5px;top:-1px;left:{$dict->importance}%;width:10px;height:10px;border-radius:10px;background-color:{if $dict->importance < 50}rgb(0,200,0);{elseif $dict->importance > 50}rgb(230,70,70);{else}rgb(175,175,175);{/if}"></div>
				</div>
			{elseif $k == 'bucket'}
				<b>{'common.bucket'|devblocks_translate|capitalize}:</b>
				<ul class="bubbles">
					<li class="bubble-gray"><img src="{devblocks_url}c=avatars&context=group&context_id={$dict->group_id}{/devblocks_url}?v={$dict->group_updated}" style="height:16px;width:16px;vertical-align:middle;border-radius:16px;"> <a href="javascript:;" class="cerb-peek-trigger no-underline" data-context="{CerberusContexts::CONTEXT_GROUP}" data-context-id="{$dict->group_id}">{$dict->group_name}</a></li> 
				{if !empty($dict->bucket_id)}
					<li class="bubble-gray"><a href="javascript:;" class="cerb-peek-trigger no-underline" data-context="{CerberusContexts::CONTEXT_BUCKET}" data-context-id="{$dict->bucket_id}">{$dict->bucket_name}</a></li>
				{/if}
				</ul>
			{else}
				{include file="devblocks:cerberusweb.core::internal/custom_fields/profile_cell_renderer.tpl"}
			{/if}
		</div>
		{if $smarty.foreach.props.iteration % 3 == 0 && !$smarty.foreach.props.last}
			<br clear="all">
		{/if}
	{/foreach}
	<br clear="all">
	
	<div>
		<b>{'common.participants'|devblocks_translate|capitalize}</b>:
		<span id="displayTicketRequesterBubbles">
			{include file="devblocks:cerberusweb.core::display/rpc/requester_list.tpl" ticket_id=$dict->id}
		</span>
	</div>
	
	<div style="margin-top:5px;">
		<button type="button" class="cerb-search-trigger" data-context="{CerberusContexts::CONTEXT_MESSAGE}" data-query="ticket.id:{$dict->id}"><div class="badge-count">{$profile_counts.messages|default:0}</div> {'common.messages'|devblocks_translate|capitalize}</button>
		<button type="button" class="cerb-search-trigger" data-context="{CerberusContexts::CONTEXT_COMMENT}" data-query="on.ticket:(id:{$dict->id})"><div class="badge-count">{$profile_counts.comments|default:0}</div> {'common.comments'|devblocks_translate|capitalize}</button>
		<button type="button" class="cerb-search-trigger" data-context="{CerberusContexts::CONTEXT_ATTACHMENT}" data-query="(on.msgs:(ticket.id:{$dict->id}) OR on.comments:(on.ticket:(id:{$dict->id})) OR on.comments:(on.msgs:(ticket.id:{$dict->id})))"><div class="badge-count">{$profile_counts.attachments|default:0}</div> {'common.attachments'|devblocks_translate|capitalize}</button>
		{*<button type="button" class="cerb-search-trigger" data-context="{CerberusContexts::CONTEXT_ADDRESS}" data-query="ticket.id:{$dict->id}"><div class="badge-count">{$profile_counts.participants|default:0}</div> {'common.participants'|devblocks_translate|capitalize}</button>*}
	</div>
	
	</div>
</fieldset>

{include file="devblocks:cerberusweb.core::internal/custom_fieldsets/profile_fieldsets.tpl" properties=$properties_custom_fieldsets}

{include file="devblocks:cerberusweb.core::internal/profiles/profile_record_links.tpl" properties=$properties_links}

<div>
{include file="devblocks:cerberusweb.core::internal/notifications/context_profile.tpl" context=$page_context context_id=$page_context_id}
</div>

<div>
{include file="devblocks:cerberusweb.core::internal/macros/behavior/scheduled_behavior_profile.tpl" context=$page_context context_id=$page_context_id}
</div>

<div id="profileTicketTabs">
	<ul>
		{$tabs = []}

		{$tabs[] = 'conversation'}
		<li data-alias="conversation"><a href="{devblocks_url}ajax.php?c=display&a=showConversation&point={$point}&ticket_id={$dict->id}{if $convo_focus_ctx && $convo_focus_ctx_id}&focus_ctx={$convo_focus_ctx}&focus_ctx_id={$convo_focus_ctx_id}{/if}{if $expand_all}&expand_all=1{/if}{/devblocks_url}">{'display.tab.timeline'|devblocks_translate|capitalize}</a></li>

		{$tabs[] = 'activity'}
		<li data-alias="activity"><a href="{devblocks_url}ajax.php?c=internal&a=showTabActivityLog&scope=target&point={$point}&context={$page_context}&context_id={$page_context_id}{/devblocks_url}">{'common.log'|devblocks_translate|capitalize}</a></li>

		{$tabs[] = 'history'}
		<li data-alias="history"><a href="{devblocks_url}ajax.php?c=display&a=showContactHistory&point={$point}&ticket_id={$dict->id}{/devblocks_url}">{'display.tab.history'|devblocks_translate} <div class="tab-badge">{DAO_Ticket::getViewCountForRequesterHistory('contact_history', $dict, $visit->get('display.history.scope', ''))|default:0}</div></a></li>

		{foreach from=$tab_manifests item=tab_manifest}
			{$tabs[] = $tab_manifest->params.uri}
			<li><a href="{devblocks_url}ajax.php?c=profiles&a=showTab&ext_id={$tab_manifest->id}&point={$point}&context={$page_context}&context_id={$page_context_id}{/devblocks_url}"><i>{$tab_manifest->params.title|devblocks_translate}</i></a></li>
		{/foreach}
	</ul>
</div>
<br>

<script type="text/javascript">
$(function() {
	// Tabs
	
	var tabOptions = Devblocks.getDefaultjQueryUiTabOptions();
	tabOptions.active = Devblocks.getjQueryUiTabSelected('profileTicketTabs', '{$tab}');
	
	var tabs = $("#profileTicketTabs").tabs(tabOptions);
	
	// Card
	$('#btnProfileCard')
		.cerbPeekTrigger()
	;
	
	// Edit
	
	$('#btnDisplayTicketEdit')
		.cerbPeekTrigger()
		.on('cerb-peek-opened', function(e) {
		})
		.on('cerb-peek-saved', function(e) {
			e.stopPropagation();
			document.location.reload();
		})
		.on('cerb-peek-deleted', function(e) {
			document.location.href = '{devblocks_url}{/devblocks_url}';
			
		})
		.on('cerb-peek-closed', function(e) {
		})
		;
	
	// Merge
	
	$('#btnMerge')
		.on('click', function(e) {
			var $merge_popup = genericAjaxPopup('peek','c=internal&a=showRecordsMergePopup&context={$page_context}&ids={$page_context_id}',null,false,'50%');
			
			$merge_popup.on('record_merged', function(e) {
				e.stopPropagation();
				document.location.reload();
			});
		})
		;
	
	// Interactions
	var $interaction_container = $('#spanInteractions');
	{include file="devblocks:cerberusweb.core::events/interaction/interactions_menu.js.tpl"}
});

// Page title
document.title = "{$dict->_label|escape:'javascript' nofilter} - {$settings->get('cerberusweb.core','helpdesk_title')|escape:'javascript' nofilter}";
</script>

{include file="devblocks:cerberusweb.core::internal/profiles/profile_common_scripts.tpl"}

<script type="text/javascript">
{if $pref_keyboard_shortcuts}
$(document).keypress(function(event) {
	if(event.altKey || event.ctrlKey || event.metaKey)
		return;
	
	if($(event.target).is(':input'))
		return;

	// We only want shift on the Shift+R shortcut right now
	if(event.shiftKey && event.which != 82)
		return;
	
	hotkey_activated = true;
	
	switch(event.which) {
		case 49:  // (1) tab cycle
		case 50:  // (2) tab cycle
		case 51:  // (3) tab cycle
		case 52:  // (4) tab cycle
		case 53:  // (5) tab cycle
		case 54:  // (6) tab cycle
		case 55:  // (7) tab cycle
		case 56:  // (8) tab cycle
		case 57:  // (9) tab cycle
		case 58:  // (0) tab cycle
			try {
				idx = event.which-49;
				$tabs = $("#profileTicketTabs").tabs();
				$tabs.tabs('option', 'active', idx);
			} catch(ex) { } 
			break;
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
		case 105:  // (I) interactions
			try {
				$('#spanInteractions').find('> button').click();
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
		case 82:   // (r)
		case 114:  // (R) reply to first message
			try {
				{if $expand_all}$btn = $('BUTTON.reply').last();{else}$btn = $('BUTTON.reply').first();{/if}
				if(event.shiftKey) {
					$btn.next('BUTTON.split-right').click();
				} else {
					$btn.click();
				}
			} catch(ex) { } 
			break;
		case 115:  // (S) spam
			try {
				$('#btnSpam').click();
			} catch(ex) { } 
			break;
		{if empty($dict->owner_id)}
		case 116:  // (T) take
			try {
				genericAjaxGet('','c=display&a=doTake&ticket_id={$dict->id}',function(e) {
					document.location.href = '{devblocks_url}c=profiles&type=ticket&id={$dict->mask}{/devblocks_url}';
				});
			} catch(ex) { } 
			break;
		{else}
		case 117:  // (U) unassign
			try {
				genericAjaxGet('','c=display&a=doSurrender&ticket_id={$dict->id}',function(e) {
					document.location.href = '{devblocks_url}c=profiles&type=ticket&id={$dict->mask}{/devblocks_url}';
				});
			} catch(ex) { } 
			break;
		{/if}
		case 119:  // (W) watch
			try {
				$('#spanWatcherToolbar button:first').click();
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
