{$page_context = CerberusContexts::CONTEXT_OPPORTUNITY}
{$page_context_id = $opp->id}

{include file="devblocks:cerberusweb.crm::crm/submenu.tpl"}

<div style="float:left;">
	<h2>{'crm.common.opportunity'|devblocks_translate|capitalize}</h2>
</div>

<div style="float:right;">
	{include file="devblocks:cerberusweb.crm::crm/quick_search.tpl"}
</div>

<br clear="all">

<fieldset class="properties">
	<legend>{$opp->name|truncate:128}</legend>
	
	<form action="{devblocks_url}{/devblocks_url}" onsubmit="return false;" style="margin-bottom:5px;">

		{foreach from=$properties item=v key=k name=props}
			<div class="property">
				{if $k == 'status'}
					<b>{$v.label|capitalize}:</b>
					{if $v.is_closed}
						{if $v.is_won}
							<img src="{devblocks_url}c=resource&p=cerberusweb.crm&f=images/up_plus_gray.gif{/devblocks_url}" align="top" title="Won"> {'crm.opp.status.closed.won'|devblocks_translate}
						{else}
							<img src="{devblocks_url}c=resource&p=cerberusweb.crm&f=images/down_minus_gray.gif{/devblocks_url}" align="top" title="Won"> {'crm.opp.status.closed.lost'|devblocks_translate}
						{/if}
					{else}
						{'crm.opp.status.open'|devblocks_translate}
					{/if}
				{elseif $k == 'lead'}
					<b>{$v.label|capitalize}:</b>
					{$v.address->getName()}
					&lt;<a href="javascript:;" onclick="genericAjaxPopup('peek2','c=contacts&a=showAddressPeek&email={$v.address->email|escape:'url'}',null,false,'600');">{$v.address->email}</a>&gt;
					<button id="btnOppAddyPeek" type="button" onclick="genericAjaxPopup('peek2','c=contacts&a=showAddressPeek&email={$v.address->email|escape:'url'}&view_id=',null,false,'600');" style="visibility:false;display:none;"></button>
				{elseif $k == 'org'}
					<b>{$v.label|capitalize}:</b>
					<a href="javascript:;" onclick="genericAjaxPopup('peek2','c=contacts&a=showOrgPeek&id={$v.org->id}',null,false,'600');">{$v.org->name}</a>
					<button id="btnOppOrgPeek" type="button" onclick="genericAjaxPopup('peek2','c=contacts&a=showOrgPeek&id={$v.org->id}&view_id=',null,false,'600');" style="visibility:false;display:none;"></button>
				{else}
					{include file="devblocks:cerberusweb.core::internal/custom_fields/profile_cell_renderer.tpl"}
				{/if}
			</div>
			{if $smarty.foreach.props.iteration % 3 == 0 && !$smarty.foreach.props.last}
				<br clear="all">
			{/if}
		{/foreach}
		<br clear="all">
	
		<!-- Toolbar -->
		<span>
		{$object_watchers = DAO_ContextLink::getContextLinks($page_context, array($page_context_id), CerberusContexts::CONTEXT_WORKER)}
		{include file="devblocks:cerberusweb.core::internal/watchers/context_follow_button.tpl" context=$page_context context_id=$page_context_id full=true}
		</span>		
		
		<!-- Macros -->
		{devblocks_url assign=return_url full=true}c=crm&tab=opps&id={$page_context_id}{/devblocks_url}
		{include file="devblocks:cerberusweb.core::internal/macros/display/button.tpl" context=$page_context context_id=$page_context_id macros=$macros return_url=$return_url}		
		
		<!-- Edit -->
		{if $active_worker->hasPriv('crm.opp.actions.update_all')}	
		<button type="button" id="btnDisplayOppEdit"><span class="cerb-sprite sprite-document_edit"></span> Edit</button>
		{/if}
		
		{$toolbar_exts = DevblocksPlatform::getExtensions('cerberusweb.crm.opp.toolbaritem', true)}
		{foreach from=$toolbar_exts item=ext}
			{$ext->render($opp)}
		{/foreach}
	</form>
	
	{if $pref_keyboard_shortcuts}
	<small>
		{$translate->_('common.keyboard')|lower}:
		(<b>a</b>) show contact
		(<b>e</b>) {'common.edit'|devblocks_translate|lower}
		{if !empty($macros)}(<b>m</b>) {'common.macros'|devblocks_translate|lower} {/if}
		(<b>o</b>) show organization
		(<b>1-9</b>) change tab
	</small> 
	{/if}
</fieldset>

<div>
{include file="devblocks:cerberusweb.core::internal/notifications/context_profile.tpl" context=$page_context context_id=$page_context_id}
</div>

<div>
{include file="devblocks:cerberusweb.core::internal/macros/behavior/scheduled_behavior_profile.tpl" context=$page_context context_id=$page_context_id}
</div>

<div id="oppTabs">
	<ul>
		{$point = Extension_CrmOpportunityTab::POINT}
		{$tabs = [activity,notes,links,mail]}
		
		<li><a href="{devblocks_url}ajax.php?c=internal&a=showTabActivityLog&scope=target&point={$point}&context={$page_context}&context_id={$page_context_id}{/devblocks_url}">{'common.activity_log'|devblocks_translate|capitalize}</a></li>
		<li><a href="{devblocks_url}ajax.php?c=internal&a=showTabContextComments&context={$page_context}&point={$point}&id={$page_context_id}{/devblocks_url}">{$translate->_('common.comments')|capitalize}</a></li>		
		<li><a href="{devblocks_url}ajax.php?c=internal&a=showTabContextLinks&context={$page_context}&point={$point}&id={$page_context_id}{/devblocks_url}">{$translate->_('common.links')}</a></li>
		<li><a href="{devblocks_url}ajax.php?c=contacts&a=showTabMailHistory&point={$point}&address_ids={$opp->primary_email_id}{/devblocks_url}">{$translate->_('addy_book.org.tabs.mail_history')}</a></li>

		{$tab_manifests = DevblocksPlatform::getExtensions($point, false)}
		{foreach from=$tab_manifests item=tab_manifest}
			{$tabs[] = $tab_manifest->params.uri}
			<li><a href="{devblocks_url}ajax.php?c=crm&a=showOppTab&ext_id={$tab_manifest->id}&point={$point}{/devblocks_url}"><i>{$tab_manifest->params.title|devblocks_translate}</i></a></li>
		{/foreach}
	</ul>
</div> 
<br>

{$selected_tab_idx=0}
{foreach from=$tabs item=tab_label name=tabs}
	{if $tab_label==$selected_tab}{$selected_tab_idx = $smarty.foreach.tabs.index}{/if}
{/foreach}

<script type="text/javascript">
	$(function() {
		var tabs = $("#oppTabs").tabs( { selected:{$selected_tab_idx} } );
		
		$('#btnDisplayOppEdit').bind('click', function() {
			$popup = genericAjaxPopup('peek','c=crm&a=showOppPanel&id={$page_context_id}',null,false,'550');
			$popup.one('opp_save', function(event) {
				event.stopPropagation();
				document.location.href = '{devblocks_url}c=crm&a=display&id={$page_context_id}{/devblocks_url}';
			});
		})
	});

	{include file="devblocks:cerberusweb.core::internal/macros/display/menu_script.tpl"}
	
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
				$tabs = $("#oppTabs").tabs();
				$tabs.tabs('select', idx);
			} catch(ex) { } 
			break;
		case 97:  // (A) Email Peek
			try {
				$('#btnOppAddyPeek').click();
			} catch(e) { } 
			break;
		case 101:  // (E) edit
			try {
				$('#btnDisplayOppEdit').click();
			} catch(ex) { } 
			break;
		case 109:  // (M) macros
			try {
				$('#btnDisplayMacros').click();
			} catch(ex) { } 
			break;
		case 111:  // (O) Org peek
			try {
				$('#btnOppOrgPeek').click();
			} catch(e) { } 
			break;
		case 113:  // (Q) quick compose
			try {
				$('#btnQuickCompose').click();
			} catch(e) { } 
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