{include file="devblocks:cerberusweb.crm::crm/submenu.tpl"}

<h2>{'crm.common.opportunity'|devblocks_translate|capitalize}</h2>

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
					&lt;<a href="javascript:;" onclick="genericAjaxPopup('peek','c=contacts&a=showAddressPeek&email={$v.address->email|escape:'url'}',null,false,'500');">{$v.address->email}</a>&gt;
					<button id="btnOppAddyPeek" type="button" onclick="genericAjaxPopup('peek','c=contacts&a=showAddressPeek&email={$v.address->email|escape:'url'}&view_id=',null,false,'500');" style="visibility:false;display:none;"></button>
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
		{$object_watchers = DAO_ContextLink::getContextLinks(CerberusContexts::CONTEXT_OPPORTUNITY, array($opp->id), CerberusContexts::CONTEXT_WORKER)}
		{include file="devblocks:cerberusweb.core::internal/watchers/context_follow_button.tpl" context=CerberusContexts::CONTEXT_OPPORTUNITY context_id=$opp->id full=true}
		</span>		
		
		{if !empty($macros)}
		<button type="button" class="split-left" onclick="$(this).next('button').click();"><span class="cerb-sprite sprite-gear"></span> Macros</button><!--  
		--><button type="button" class="split-right" id="btnDisplayMacros"><span class="cerb-sprite sprite-arrow-down-white"></span></button>
		<ul class="cerb-popupmenu cerb-float" id="menuDisplayMacros">
			<li style="background:none;">
				<input type="text" size="16" class="input_search filter">
			</li>
			{devblocks_url assign=return_url full=true}c=crm&tab=opps&id={$opp->id}{/devblocks_url}
			{foreach from=$macros item=macro key=macro_id}
			<li><a href="{devblocks_url}c=internal&a=applyMacro{/devblocks_url}?macro={$macro->id}&context={CerberusContexts::CONTEXT_OPPORTUNITY}&context_id={$opp->id}&return_url={$return_url|escape:'url'}">{$macro->title}</a></li>
			{/foreach}
		</ul>
		{/if}
		
		<button type="button" id="btnDisplayOppEdit"><span class="cerb-sprite sprite-document_edit"></span> Edit</button>
		
		{$toolbar_exts = DevblocksPlatform::getExtensions('cerberusweb.crm.opp.toolbaritem', true)}
		{foreach from=$toolbar_exts item=ext}
			{$ext->render($opp)}
		{/foreach}
	</form>
	
	{if $pref_keyboard_shortcuts}
	<small>
		{$translate->_('common.keyboard')|lower}:
		(<b>e</b>) {'common.edit'|devblocks_translate|lower}
		{if !empty($macros)}(<b>m</b>) {'common.macros'|devblocks_translate|lower} {/if}
	</small> 
	{/if}
</fieldset>

<div id="oppTabs">
	{$tabs = []}
	{$point = Extension_CrmOpportunityTab::POINT}
	
	<ul>
		{$tabs = [activity,notes,links,mail]}
		
		<li><a href="{devblocks_url}ajax.php?c=internal&a=showTabActivityLog&scope=target&point={$point}&context={CerberusContexts::CONTEXT_OPPORTUNITY}&context_id={$opp->id}{/devblocks_url}">{'common.activity_log'|devblocks_translate|capitalize}</a></li>
		<li><a href="{devblocks_url}ajax.php?c=internal&a=showTabContextComments&context={CerberusContexts::CONTEXT_OPPORTUNITY}&point={$point}&id={$opp->id}{/devblocks_url}">{$translate->_('common.comments')|capitalize}</a></li>		
		<li><a href="{devblocks_url}ajax.php?c=internal&a=showTabContextLinks&context={CerberusContexts::CONTEXT_OPPORTUNITY}&point={$point}&id={$opp->id}{/devblocks_url}">{$translate->_('common.links')}</a></li>		
		<li><a href="{devblocks_url}ajax.php?c=crm&a=showOppMailTab&id={$opp->id}{/devblocks_url}">{'crm.opp.tab.mail_history'|devblocks_translate}</a></li>

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
			$popup = genericAjaxPopup('peek','c=crm&a=showOppPanel&id={$opp->id}',null,false,'550');
			$popup.one('opp_save', function(event) {
				event.stopPropagation();
				document.location.href = '{devblocks_url}c=crm&a=display&id={$opp->id}{/devblocks_url}';
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
		case 97:  // (A) E-mail Peek
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