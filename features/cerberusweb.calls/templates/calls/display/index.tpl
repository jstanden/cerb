{include file="devblocks:cerberusweb.calls::calls/submenu.tpl"}

<h2>{'calls.common.call'|devblocks_translate|capitalize}</h2> 

<fieldset class="properties">
	<legend>{$call->subject|truncate:128}</legend>
	
	<form action="{devblocks_url}{/devblocks_url}" onsubmit="return false;" style="margin-bottom:5px;">

		{foreach from=$properties item=v key=k name=props}
			<div class="property">
				{if $k == '...'}
					<b>{$translate->_('...')|capitalize}:</b>
					...
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
		{$object_watchers = DAO_ContextLink::getContextLinks(CerberusContexts::CONTEXT_CALL, array($call->id), CerberusContexts::CONTEXT_WORKER)}
		{include file="devblocks:cerberusweb.core::internal/watchers/context_follow_button.tpl" context=CerberusContexts::CONTEXT_CALL context_id=$call->id full=true}
		</span>		
		
		{if !empty($macros)}
		<button type="button" class="split-left" onclick="$(this).next('button').click();"><span class="cerb-sprite sprite-gear"></span> Macros</button><!--  
		--><button type="button" class="split-right" id="btnDisplayMacros"><span class="cerb-sprite sprite-arrow-down-white"></span></button>
		<ul class="cerb-popupmenu cerb-float" id="menuDisplayMacros">
			<li style="background:none;">
				<input type="text" size="16" class="input_search filter">
			</li>
			{devblocks_url assign=return_url full=true}c=calls&id={$call->id}-{$call->subject|devblocks_permalink}{/devblocks_url}
			{foreach from=$macros item=macro key=macro_id}
			<li><a href="{devblocks_url}c=internal&a=applyMacro{/devblocks_url}?macro={$macro->id}&context={CerberusContexts::CONTEXT_CALL}&context_id={$call->id}&return_url={$return_url|escape:'url'}">{$macro->title}</a></li>
			{/foreach}
		</ul>
		{/if}
		
		<button type="button" id="btnDisplayCallEdit"><span class="cerb-sprite sprite-document_edit"></span> Edit</button>
		
		{$toolbar_exts = DevblocksPlatform::getExtensions('cerberusweb.calls.call.toolbaritem', true)}
		{foreach from=$toolbar_exts item=ext}
			{$ext->render($opp)}
		{/foreach}
		
	</form>
	
	{if $pref_keyboard_shortcuts}
	<small>
		{$translate->_('common.keyboard')|lower}:
		(<b>e</b>) {'common.edit'|devblocks_translate|lower}
		{if !empty($macros)}(<b>m</b>) {'common.macros'|devblocks_translate|lower} {/if}
		(<b>1-9</b>) change tab
	</small> 
	{/if}
</fieldset>

{include file="devblocks:cerberusweb.core::internal/notifications/context_profile.tpl" context=CerberusContexts::CONTEXT_CALL context_id=$call->id}

<div id="callTabs">
	<ul>
		{$tabs = [activity,notes,links]}

		<li><a href="{devblocks_url}ajax.php?c=internal&a=showTabActivityLog&scope=target&point={$point}&context={CerberusContexts::CONTEXT_CALL}&context_id={$call->id}{/devblocks_url}">{'common.activity_log'|devblocks_translate|capitalize}</a></li>
		<li><a href="{devblocks_url}ajax.php?c=internal&a=showTabContextComments&context=cerberusweb.contexts.call&id={$call->id}{/devblocks_url}">{$translate->_('common.comments')|capitalize}</a></li>		
		<li><a href="{devblocks_url}ajax.php?c=internal&a=showTabContextLinks&context=cerberusweb.contexts.call&id={$call->id}{/devblocks_url}">{$translate->_('common.links')}</a></li>		

		{foreach from=$tab_manifests item=tab_manifest}
			{$tabs[] = $tab_manifest->params.uri}
			<li><a href="{devblocks_url}ajax.php?c=calls&a=showTab&ext_id={$tab_manifest->id}{/devblocks_url}"><i>{$tab_manifest->params.title|devblocks_translate}</i></a></li>
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
		var tabs = $("#callTabs").tabs( { selected:{$tab_selected_idx} } );
		
		$('#btnDisplayCallEdit').bind('click', function() {
			$popup = genericAjaxPopup('peek','c=calls&a=showEntry&id={$call->id}',null,false,'550');
			$popup.one('call_save', function(event) {
				event.stopPropagation();
				document.location.reload();
			});
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
	});
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
				$tabs = $("#callTabs").tabs();
				$tabs.tabs('select', idx);
			} catch(ex) { } 
			break;
		case 101:  // (E) edit
			try {
				$('#btnDisplayCallEdit').click();
			} catch(ex) { } 
			break;
		case 109:  // (M) macros
			try {
				$('#btnDisplayMacros').click();
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
