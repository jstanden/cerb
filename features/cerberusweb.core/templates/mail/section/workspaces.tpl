<table cellspacing="0" cellpadding="0" border="0" width="100%" style="padding-bottom:5px;">
<tr>
	<td width="99%" valign="middle">
		<h2>{'common.workspaces'|devblocks_translate|capitalize}</h2>
	</td>
	<td width="1%" valign="middle" nowrap="nowrap">
		{*include file="devblocks:cerberusweb.core::search/quick_search.tpl"*}
	</td>
</tr>
</table>

<div id="mailTabs">
	<ul>
		{$tabs = [workflow]}
		{$point = Extension_MailTab::POINT}
		
		<li><a href="{devblocks_url}ajax.php?c=mail&a=handleSectionAction&section=workspaces&action=showWorkflowTab&request={$response_uri|escape:'url'}{/devblocks_url}">{$translate->_('mail.workflow')|capitalize}</a></li>
		
		{if $active_worker->hasPriv('core.home.workspaces')}
			{$enabled_workspaces = DAO_Workspace::getByEndpoint($point, $active_worker)}
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
		// Allow these special keys
		switch(event.which) {
			case 42: // (*)
			case 126: // (~)
				break;
			default:
				if(event.altKey || event.ctrlKey || event.shiftKey || event.metaKey)
					return;
				break;
		}

		switch(event.which) {
			case 42: // (*) reset filters
				$('#viewCustomFiltersmail_workflow TABLE TBODY.full TD:first FIELDSET SELECT[name=_preset]').val('reset').trigger('change');
				break;
			case 45: // (-) remove last filter
				$('#viewCustomFiltersmail_workflow TABLE TBODY.summary UL.bubbles LI:last A.delete').click();
				break;
//			case 49: // 1
//			case 50: // 2
//			case 51: // 3
//			case 52: // 4
//			case 53: // 5
//			case 54: // 6
//			case 55: // 7
//			case 56: // 8
//			case 57: // 9
//				digit = parseInt(event.which)-49;
//				$('#viewmail_workflow_sidebar FIELDSET TABLE:first TR:nth('+digit+') TD:first A').click();
//				break;
			case 96: // (`)
				$('#viewmail_workflow_sidebar FIELDSET:first TABLE:first TD:first A:first').focus();
				break;
			case 97:  // (A) select all
				try {
					$('#viewmail_workflow TABLE.worklist input:checkbox').each(function(e) {
						is_checked = !this.checked;
						checkAll('viewFormmail_workflow',is_checked);
						$rows=$('#viewFormmail_workflow').find('table.worklistBody').find('tbody > tr');
						if(is_checked) { 
							$rows.addClass('selected'); 
							$(this).attr('checked','checked');
						} else { 
							$rows.removeClass('selected');
							$(this).removeAttr('checked');
						}
					});
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
			case 101:  // (E) explore
				try {
					$('#btnExploremail_workflow').click();
				} catch(e) { } 
				break;
			case 102:  // (f) follow
				try {
					$('#btnmail_workflowFollow').click();
				} catch(e) { } 
				break;
			case 109:  // (M) move
				try {
					event.preventDefault();
					$('#btnmail_workflowMove').click();
				} catch(e) { } 
				break;
			case 115:  // (S) spam
				try {
					$('#btnmail_workflowSpam').click();
				} catch(e) { } 
				break;
			case 117:  // (U) unfollow
				try {
					$('#btnmail_workflowUnfollow').click();
				} catch(e) { } 
				break;
			case 120:  // (X) delete
				try {
					$('#btnmail_workflowDelete').click();
				} catch(e) { } 
				break;
			case 126: // (~)
				$('#viewmail_workflow_sidebar FIELDSET UL.cerb-popupmenu').toggle().find('a:first').focus();
				break;
		}
	}
	
	function doSearchKeys(event) {
		// Allow these special keys
		switch(event.which) {
			case 42: // (*)
			case 126: // (~)
				break;
			default:
				if(event.altKey || event.ctrlKey || event.shiftKey || event.metaKey)
					return;
				break;
		}
		
		switch(event.which) {
			case 42: // (*) reset filters
				$('#viewCustomFilterssearch TABLE TBODY.full TD:first FIELDSET SELECT[name=_preset]').val('reset').trigger('change');
				break;
			case 45: // (-) remove last filter
				$('#viewCustomFilterssearch TABLE TBODY.summary UL.bubbles LI:last A.delete').click();
				break;
			case 96: // (`)
				$('#viewsearch_sidebar FIELDSET:first TABLE:first TD:first A:first').focus();
				break;
			case 97:  // (A) select all
				try {
					$('#viewsearch TABLE.worklist input:checkbox').each(function(e) {
						is_checked = !this.checked;
						checkAll('viewFormsearch',is_checked);
						$rows=$('#viewFormsearch').find('table.worklistBody').find('tbody > tr');
						if(is_checked) { 
							$rows.addClass('selected'); 
							$(this).attr('checked','checked');
						} else { 
							$rows.removeClass('selected');
							$(this).removeAttr('checked');
						}
					});
				} catch(e) { } 
				break;		
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
			case 101:  // (E) explore
				try {
					$('#btnExploresearch').click();
				} catch(e) { } 
				break;
			case 102:  // (f) follow
				try {
					$('#btnsearchFollow').click();
				} catch(e) { } 
				break;
			case 109:  // (m) move
				try {
					event.preventDefault();
					$('#btnsearchMove').click();
				} catch(e) { } 
				break;
			case 115:  // (S) spam
				try {
					$('#btnsearchSpam').click();
				} catch(e) { } 
				break;
			case 117:  // (U) unfollow
				try {
					$('#btnsearchUnfollow').click();
				} catch(e) { } 
				break;
			case 120:  // (X) delete
				try {
					$('#btnsearchDelete').click();
				} catch(e) { } 
				break;
			case 126: // (~)
				$('#viewsearch_sidebar FIELDSET UL.cerb-popupmenu').toggle().find('a:first').focus();
				break;
		}
	}	
</script>
