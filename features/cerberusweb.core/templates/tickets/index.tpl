{include file="file:$core_tpl/tickets/submenu.tpl"}

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
		{include file="file:$core_tpl/tickets/quick_search_box.tpl"}
	</td>
</tr>
</table>

<div id="mailTabs">
	<ul>
		{$tabs = [workflow]}
		
		<li><a href="{devblocks_url}ajax.php?c=tickets&a=showWorkflowTab&request={$request_path|escape:'url'}{/devblocks_url}">{$translate->_('mail.workflow')|capitalize|escape:'quotes'}</a></li>
		
		{if $active_worker->hasPriv('core.mail.overview')}
			{$tabs[] = overview}
			<li><a href="{devblocks_url}ajax.php?c=tickets&a=showOverviewTab&request={$request_path|escape:'url'}{/devblocks_url}">{$translate->_('mail.overview')|capitalize|escape:'quotes'}</a></li>
		{/if}

		{if $active_worker->hasPriv('core.mail.search')}
			{$tabs[] = search}
			<li><a href="{devblocks_url}ajax.php?c=tickets&a=showSearchTab&request={$request_path|escape:'url'}{/devblocks_url}">{$translate->_('common.search')|capitalize|escape:'quotes'}</a></li>
		{/if}
		
		{foreach from=$tab_manifests item=tab_manifest}
			{$tabs[] = $tab_manifest->params.uri}
			<li><a href="{devblocks_url}ajax.php?c=config&a=showTab&ext_id={$tab_manifest->id}{/devblocks_url}"><i>{$tab_manifest->params.title|devblocks_translate|escape:'quotes'}</i></a></li>
		{/foreach}
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
			selected: {$tab_selected_idx},
			show: function(event, ui) {
				idx = tabs.tabs('option', 'selected');

				{if $pref_keyboard_shortcuts}
					{$case = 0}
					switch(idx) {
						case {$case}:
							doWorkflowKeys();
							break;
						{if $active_worker->hasPriv('core.mail.overview')}
							{$case = $case + 1}
							case {$case}:
								doOverviewKeys();
								break;
						{/if}
						{if $active_worker->hasPriv('core.mail.search')}
							{$case = $case + 1}
							case {$case}:
								doSearchKeys();
								break;
						{/if}
						default:
							doNullKeys();
							break;
					}
				{/if}
			} 
		} );
	});

	function doNullKeys() {
		CreateKeyHandler(function (e) { } );
	}

	function doWorkflowKeys() {
		CreateKeyHandler(function (e) {
			var mycode = getKeyboardKey(e, true);
			
			switch(mycode) {
				case 65:  // (A) list all
					try {
						document.getElementById('btnWorkflowListAll').click();
					} catch(e) { } 
					break;
				case 66:  // (B) bulk update
					try {
						document.getElementById('btnmail_workflowBulkUpdate').click();
					} catch(e) { } 
					break;
				case 67:  // (C) close
					try {
						document.getElementById('btnmail_workflowClose').click();
					} catch(e) { } 
					break;
				case 77:  // (M) my tickets
					try {
						document.getElementById('btnMyTickets').click();
					} catch(e) { } 
					break;
				case 83:  // (S) spam
					try {
						document.getElementById('btnmail_workflowSpam').click();
					} catch(e) { } 
					break;
				case 84:  // (T) take
					try {
						document.getElementById('btnmail_workflowTake').click();
					} catch(e) { } 
					break;
				case 85:  // (U) surrender
					try {
						document.getElementById('btnmail_workflowSurrender').click();
					} catch(e) { } 
					break;
				case 88:  // (X) delete
					try {
						document.getElementById('btnmail_workflowDelete').click();
					} catch(e) { } 
					break;
			}
		} );		
	}
	
	function doOverviewKeys() {
		CreateKeyHandler(function (e) {
			var mycode = getKeyboardKey(e, true);
			
			switch(mycode) {
				case 65: // (A) list all
					try {
						document.getElementById('btnOverviewListAll').click();
					} catch(e) { } 
					break;
				case 66:  // (B) bulk update
					try {
						document.getElementById('btnoverview_allBulkUpdate').click();
					} catch(e) { } 
					break;
				case 67:  // (C) close
					try {
						document.getElementById('btnoverview_allClose').click();
					} catch(e) { } 
					break;
				case 77:  // (M) my tickets
					try {
						document.getElementById('btnMyTickets').click();
					} catch(e) { } 
					break;
				case 83:  // (S) spam
					try {
						document.getElementById('btnoverview_allSpam').click();
					} catch(e) { } 
					break;
				case 84:  // (T) take
					try {
						document.getElementById('btnoverview_allTake').click();
					} catch(e) { } 
					break;
				case 85:  // (U) surrender
					try {
						document.getElementById('btnoverview_allSurrender').click();
					} catch(e) { } 
					break;
				case 88:  // (X) delete
					try {
						document.getElementById('btnoverview_allDelete').click();
					} catch(e) { } 
					break;
			}
		});		
	}
	
	function doSearchKeys() {
		CreateKeyHandler(function (e) {
			var mycode = getKeyboardKey(e, true);
			
			switch(mycode) {
				case 66:  // (B) bulk update
					try {
						document.getElementById('btnsearchBulkUpdate').click();
					} catch(e) { } 
					break;
				case 67:  // close
					try {
						document.getElementById('btnsearchClose').click();
					} catch(e) { } 
					break;
				case 83:  // spam
					try {
						document.getElementById('btnsearchSpam').click();
					} catch(e) { } 
					break;
				case 84:  // take
					try {
						document.getElementById('btnsearchTake').click();
					} catch(e) { } 
					break;
				case 85:  // surrender
					try {
						document.getElementById('btnsearchSurrender').click();
					} catch(e) { } 
					break;
				case 88:  // delete
					try {
						document.getElementById('btnsearchDelete').click();
					} catch(e) { } 
					break;
			}
		});		
	}	
</script>
