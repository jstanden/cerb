{include file="file:$core_tpl/tickets/submenu.tpl"}

<table cellspacing="0" cellpadding="0" border="0" width="100%" style="padding-bottom:5px;">
<tr>
	<td width="1%" nowrap="nowrap" valign="top" style="padding-right:5px;">
		<form action="{devblocks_url}{/devblocks_url}" method="POST">
			{if $active_worker->hasPriv('core.mail.send')}<button type="button" onclick="document.location.href='{devblocks_url}c=tickets&a=compose{/devblocks_url}';"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/export2.png{/devblocks_url}" align="top"> {$translate->_('mail.send_mail')|capitalize}</button>{/if}
			{if $active_worker->hasPriv('core.mail.log_ticket')}<button type="button" onclick="document.location.href='{devblocks_url}c=tickets&a=create{/devblocks_url}';"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/import1.png{/devblocks_url}" align="top"> {$translate->_('mail.log_message')|capitalize}</button>{/if}
			{if $active_worker->hasPriv('core.mail.actions.auto_refresh')}<button type="button" onclick="autoRefreshTimer.start('{devblocks_url full=true}c=tickets{/devblocks_url}',this.form.reloadSecs.value);"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/refresh.gif{/devblocks_url}" align="top"> {'common.refresh.auto'|devblocks_translate|capitalize}</button>
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

<div id="mailTabs"></div> 
<br>

<script type="text/javascript">
{counter name=tab_idx assign=tab_idx start=0 print=false}
{assign var=tab_idx_workflow value=$tab_idx}
{literal}
var tabView = new YAHOO.widget.TabView();

tabView.addTab( new YAHOO.widget.Tab({
    label: '{/literal}{$translate->_('mail.workflow')|capitalize|escape:'quotes'}{literal}',
    dataSrc: '{/literal}{devblocks_url}ajax.php?c=tickets&a=showWorkflowTab&request={$request_path|escape:'url'}{/devblocks_url}{literal}',
    cacheData: false
}));

{/literal}{if $active_worker->hasPriv('core.mail.overview')}
	{counter name=tab_idx assign=tab_idx print=false}
	{assign var=tab_idx_overview value=$tab_idx}
{literal}
tabView.addTab( new YAHOO.widget.Tab({
    label: '{/literal}{$translate->_('mail.overview')|capitalize|escape:'quotes'}{literal}',
    dataSrc: '{/literal}{devblocks_url}ajax.php?c=tickets&a=showOverviewTab&request={$request_path|escape:'url'}{/devblocks_url}{literal}',
    cacheData: false
}));
{/literal}{/if}{literal}

{/literal}{if $active_worker->hasPriv('core.mail.search')}
	{counter name=tab_idx assign=tab_idx print=false}
	{assign var=tab_idx_search value=$tab_idx}
{literal}
tabView.addTab( new YAHOO.widget.Tab({
    label: '{/literal}{$translate->_('common.search')|capitalize|escape:'quotes'}{literal}',
    dataSrc: '{/literal}{devblocks_url}ajax.php?c=tickets&a=showSearchTab&request={$request_path|escape:'url'}{/devblocks_url}{literal}',
    cacheData: true
}));{/literal}
{/if}

{foreach from=$workspaces item=workspace}
{literal}tabView.addTab( new YAHOO.widget.Tab({{/literal}
    label: '<i>{$workspace|escape}</i>',
    dataSrc: '{devblocks_url}ajax.php?c=home&a=showWorkspaceTab&workspace={$workspace|escape:'url'}{/devblocks_url}',
    cacheData: false,
    active:{if substr($selected_tab,2)==$workspace}true{else}false{/if}
{literal}}));{/literal}
{/foreach}

// Initialize the tabs
tabView.appendTo('mailTabs');

// Hotkeys
{literal}
tabView.addListener('activeTabChange', function(e) {
	switch(tabView.get('activeIndex')) {
		// Workflow keys
		{/literal}
		{if is_numeric($tab_idx_workflow)}
		case {$tab_idx_workflow}:
		{literal}
			CreateKeyHandler(function (e) {
				var mykey = getKeyboardKey(e);
				
				switch(mykey) {
					case "a":  // list all
					case "A":
						try {
							document.getElementById('btnWorkflowListAll').click();
						} catch(e){}
						break;
					case "b":  // bulk update
					case "B":
						try {
							document.getElementById('btnmail_workflowBulkUpdate').click();
						} catch(e){}
						break;
					case "c":  // close
					case "C":
						try {
							document.getElementById('btnmail_workflowClose').click();
						} catch(e){}
						break;
					case "m":  // my tickets
					case "M":
						try {
							document.getElementById('btnMyTickets').click();
						} catch(e){}
						break;
					case "s":  // spam
					case "S":
						try {
							document.getElementById('btnmail_workflowSpam').click();
						} catch(e){}
						break;
					case "t":  // take
					case "T":
						try {
							document.getElementById('btnmail_workflowTake').click();
						} catch(e){}
						break;
					case "u":  // surrender
					case "U":
						try {
							document.getElementById('btnmail_workflowSurrender').click();
						} catch(e){}
						break;
					case "x":  // delete
					case "X":
						try {
							document.getElementById('btnmail_workflowDelete').click();
						} catch(e){}
						break;
				}
			});
			break;
			{/literal}{/if}{literal}
			
		// Overview keys
		{/literal}
		{if is_numeric($tab_idx_overview)}
		case {$tab_idx_overview}:
		{literal}
			CreateKeyHandler(function (e) {
				var mykey = getKeyboardKey(e);
				
				switch(mykey) {
					case "a":  // list all
					case "A":
						try {
							document.getElementById('btnOverviewListAll').click();
						} catch(e){}
						break;
					case "b":  // bulk update
					case "B":
						try {
							document.getElementById('btnoverview_allBulkUpdate').click();
						} catch(e){}
						break;
					case "c":  // close
					case "C":
						try {
							document.getElementById('btnoverview_allClose').click();
						} catch(e){}
						break;
					case "m":  // my tickets
					case "M":
						try {
							document.getElementById('btnMyTickets').click();
						} catch(e){}
						break;
					case "s":  // spam
					case "S":
						try {
							document.getElementById('btnoverview_allSpam').click();
						} catch(e){}
						break;
					case "t":  // take
					case "T":
						try {
							document.getElementById('btnoverview_allTake').click();
						} catch(e){}
						break;
					case "u":  // surrender
					case "U":
						try {
							document.getElementById('btnoverview_allSurrender').click();
						} catch(e){}
						break;
					case "x":  // delete
					case "X":
						try {
							document.getElementById('btnoverview_allDelete').click();
						} catch(e){}
						break;
				}
			});
			break;
			{/literal}{/if}{literal}
			
		// Search keys
		{/literal}
		{if is_numeric($tab_idx_search)}
		case {$tab_idx_search}:
		{literal}
			CreateKeyHandler(function (e) {
				var mykey = getKeyboardKey(e);
				
				switch(mykey) {
					case "b":  // bulk update
					case "B":
						try {
							document.getElementById('btnsearchBulkUpdate').click();
						} catch(e){}
						break;
					case "c":  // close
					case "C":
						try {
							document.getElementById('btnsearchClose').click();
						} catch(e){}
						break;
					case "s":  // spam
					case "S":
						try {
							document.getElementById('btnsearchSpam').click();
						} catch(e){}
						break;
					case "t":  // take
					case "T":
						try {
							document.getElementById('btnsearchTake').click();
						} catch(e){}
						break;
					case "u":  // surrender
					case "U":
						try {
							document.getElementById('btnsearchSurrender').click();
						} catch(e){}
						break;
					case "x":  // delete
					case "X":
						try {
							document.getElementById('btnsearchDelete').click();
						} catch(e){}
						break;
				}
			});
			break;
			{/literal}{/if}{literal}
			
		default:
			CreateKeyHandler(function (e) {});
			break;
	}
});
{/literal}

// Select the appropriate tab
{assign var=tabIdx value=null}
{counter assign=counter name="mailTabs" start=0}{if empty($selected_tab) || 'workflow'==$selected_tab}{assign var=tabIdx value=$counter}{/if}
{if $active_worker->hasPriv('core.mail.overview')}{counter assign=counter name="mailTabs"}{if 'overview'==$selected_tab}{assign var=tabIdx value=$counter}{/if}{/if}
{if $active_worker->hasPriv('core.mail.search')}{counter assign=counter name="mailTabs"}{if 'search'==$selected_tab}{assign var=tabIdx value=$counter}{/if}{/if}

{foreach from=$workspaces item=workspace}
	{counter assign=counter name="mailTabs"}{if 'w_'==substr($selected_tab,0,2) && substr($selected_tab,2)==$workspace}{assign var=tabIdx value=$counter}{/if}
{/foreach}

{if is_null($tabIdx)}{assign var=tabIdx value=0}{/if}
tabView.set('activeIndex', {$tabIdx});
</script>
