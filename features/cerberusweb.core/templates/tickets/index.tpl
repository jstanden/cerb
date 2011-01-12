{include file="devblocks:cerberusweb.core::tickets/submenu.tpl"}

<table cellspacing="0" cellpadding="0" border="0" width="100%" style="padding-bottom:5px;">
<tr>
	<td width="1%" nowrap="nowrap" valign="top" style="padding-right:5px;">
		<form action="{devblocks_url}{/devblocks_url}" method="POST">
			{if $active_worker->hasPriv('core.mail.send')}<button type="button" onclick="document.location.href='{devblocks_url}c=tickets&a=compose{/devblocks_url}';"><span class="cerb-sprite sprite-export"></span> {$translate->_('mail.send_mail')|capitalize}</button>{/if}
			{if $active_worker->hasPriv('core.mail.log_ticket')}<button type="button" onclick="document.location.href='{devblocks_url}c=tickets&a=create{/devblocks_url}';"><span class="cerb-sprite sprite-import"></span> {$translate->_('mail.log_message')|capitalize}</button>{/if}
			{if $active_worker->hasPriv('core.mail.actions.auto_refresh')}<button type="button" onclick="autoRefreshTimer.start('{devblocks_url full=true}c=tickets{/devblocks_url}',this.form.reloadSecs.value);"><span class="cerb-sprite sprite-refresh"></span> {'common.refresh.auto'|devblocks_translate|capitalize}</button>
			<select name="reloadSecs">
				<option value="600">{'common.time.mins.num'|devblocks_translate:'10'}</option>
				<option value="300" selected="selected">{'common.time.mins.num'|devblocks_translate:'5'}</option>
				<option value="240">{'common.time.mins.num'|devblocks_translate:'4'}</option>
				<option value="180">{'common.time.mins.num'|devblocks_translate:'3'}</option>
				<option value="120">{'common.time.mins.num'|devblocks_translate:'2'}</option>
				<option value="60">{'common.time.mins.num'|devblocks_translate:'1'}</option>
				<option value="30">{'common.time.secs.num'|devblocks_translate:'30'}</option>
			</select>{/if}
		</form>
	</td>
	<td width="98%" valign="middle">
	</td>
	<td width="1%" valign="middle" nowrap="nowrap">
		{include file="devblocks:cerberusweb.core::tickets/quick_search_box.tpl"}
	</td>
</tr>
</table>

<div id="mailTabs">
	<ul>
		{$tabs = [workflow]}
		{$point = Extension_MailTab::POINT}
		
		<li><a href="{devblocks_url}ajax.php?c=tickets&a=showWorkflowTab&request={$response_uri|escape:'url'}{/devblocks_url}">{$translate->_('mail.workflow')|capitalize}</a></li>
		
		{if $active_worker->hasPriv('core.mail.search')}
			{$tabs[] = search}
			<li><a href="{devblocks_url}ajax.php?c=tickets&a=showSearchTab&request={$response_uri|escape:'url'}{/devblocks_url}">{$translate->_('mail.search.tickets')|capitalize}</a></li>
		{/if}

		{if 1 || $active_worker->hasPriv('core.mail.messages')}
			{$tabs[] = messages}
			<li><a href="{devblocks_url}ajax.php?c=tickets&a=showMessagesTab&request={$response_uri|escape:'url'}{/devblocks_url}">{$translate->_('mail.search.messages')|capitalize}</a></li>
		{/if}

		{$tabs[] = drafts}
		<li><a href="{devblocks_url}ajax.php?c=tickets&a=showDraftsTab&request={$response_uri|escape:'url'}{/devblocks_url}">{$translate->_('mail.drafts')|capitalize}</a></li>

		{$tabs[] = snippets}
		<li><a href="{devblocks_url}ajax.php?c=tickets&a=showSnippetsTab&request={$response_uri|escape:'url'}{/devblocks_url}">{$translate->_('common.snippets')|capitalize}</a></li>
		
		{$tab_manifests = DevblocksPlatform::getExtensions($point, false)}
		{foreach from=$tab_manifests item=tab_manifest}
			{$tabs[] = $tab_manifest->params.uri}
			<li><a href="{devblocks_url}ajax.php?c=tickets&a=showTab&point={$point}&ext_id={$tab_manifest->id}&request={$response_uri|escape:'url'}{/devblocks_url}"><i>{$tab_manifest->params.title|devblocks_translate}</i></a></li>
		{/foreach}
		
		{if $active_worker->hasPriv('core.home.workspaces')}
			{$enabled_workspaces = DAO_Workspace::getByEndpoint($point, $active_worker->id)}
			{foreach from=$enabled_workspaces item=enabled_workspace}
				{$tabs[] = 'w_'|cat:$enabled_workspace->id}
				<li><a href="{devblocks_url}ajax.php?c=internal&a=showWorkspaceTab&point={$point}&id={$enabled_workspace->id}&request={$response_uri|escape:'url'}{/devblocks_url}"><i>{$enabled_workspace->name}</i></a></li>
			{/foreach}
			
			{$tabs[] = "+"}
			<li><a href="{devblocks_url}ajax.php?c=internal&a=showAddTab&point={$point}&request={$response_uri|escape:'url'}{/devblocks_url}"><i>+</i></a></li>
		{/if}
	</ul>
</div> 
<br>

{$tab_selected_idx=0}
{foreach from=$tabs item=tab_label name=tabs}
	{if $tab_label==$selected_tab}{$tab_selected_idx = $smarty.foreach.tabs.index}{/if}
{/foreach}

<script type="text/javascript">
	$(function() {
		var tabs = $("#mailTabs").tabs( { 
			selected: {$tab_selected_idx}
		});
	});
	
	{if $pref_keyboard_shortcuts}
	$(document).keypress(function(event) {
		// Don't trigger on forms
		if($(event.target).is(':input'))
			return;
		
		idx = $("#mailTabs").tabs('option', 'selected');
		{$case = 0}
		switch(idx) {
			case {$case}:
				doWorkflowKeys(event);
				break;
			{if $active_worker->hasPriv('core.mail.search')}
				{$case = $case + 1}
				case {$case}:
					doSearchKeys(event);
					break;
			{/if}
			default:
				doNullKeys(event);							
				break;
		}
	});
	{/if}
	
	function doNullKeys(event) {
	}

	function doWorkflowKeys(event) {
		if(event.altKey || event.ctrlKey || event.shiftKey || event.metaKey)
			return;

		switch(event.which) {
			case 97:  // (A) list all
				try {
					$('#btnWorkflowListAll').click();
				} catch(e) { } 
				break;
			case 98:  // (B) bulk update
				try {
					$('#btnmail_workflowBulkUpdate').click();
				} catch(e) { } 
				break;
			case 99:  // (C) close
				try {
					$('#btnmail_workflowClose').click();
				} catch(e) { } 
				break;
			case 109:  // (M) my tickets
				try {
					$('#btnMyTickets').click();
				} catch(e) { } 
				break;
			case 115:  // (S) spam
				try {
					$('#btnmail_workflowSpam').click();
				} catch(e) { } 
				break;
			case 116:  // (T) take
				try {
					$('#btnmail_workflowTake').click();
				} catch(e) { } 
				break;
			case 117:  // (U) surrender
				try {
					$('#btnmail_workflowSurrender').click();
				} catch(e) { } 
				break;
			case 120:  // (X) delete
				try {
					$('#btnmail_workflowDelete').click();
				} catch(e) { } 
				break;
		}
	}
	
	function doSearchKeys(event) {
		if(event.altKey || event.ctrlKey || event.shiftKey)
			return;

		switch(event.which) {
			case 98:  // (B) bulk update
				try {
					$('#btnsearchBulkUpdate').click();
				} catch(e) { } 
				break;
			case 99:  // (C) close
				try {
					$('#btnsearchClose').click();
				} catch(e) { } 
				break;
			case 115:  // (S) spam
				try {
					$('#btnsearchSpam').click();
				} catch(e) { } 
				break;
			case 116:  // (T) take
				try {
					$('#btnsearchTake').click();
				} catch(e) { } 
				break;
			case 117:  // (S) surrender
				try {
					$('#btnsearchSurrender').click();
				} catch(e) { } 
				break;
			case 120:  // (X) delete
				try {
					$('#btnsearchDelete').click();
				} catch(e) { } 
				break;
		}
	}	
</script>
