{include file="devblocks:cerberusweb.core::tasks/display/submenu.tpl"}

<table cellspacing="0" cellpadding="0" border="0" width="100%" style="margin-bottom:5px;">
<tr>
	<td valign="top" style="padding-right:5px;">
		<h2>Task</h2>
		 
		<fieldset class="properties">
			<legend>{$task->title|truncate:128}</legend>
			
			<form action="{devblocks_url}{/devblocks_url}" method="post" style="margin-bottom:5px;">
				<input type="hidden" name="c" value="tasks">
				<input type="hidden" name="a" value="">
				<input type="hidden" name="id" value="{$task->id}">
			
				{foreach from=$properties item=v key=k name=props}
					<div class="property">
						{if $k == '...'}
							<b>{$translate->_('...')|capitalize}:</b>
							...
						{elseif $k == 'due_date'}
							<b>{$translate->_('task.due_date')|capitalize}:</b>
							<abbr title="{$task->due_date|devblocks_date}" style="{if !$task->is_completed && $task->due_date < time()}font-weight:bold;color:rgb(150,0,0);{/if}">{$task->due_date|devblocks_prettytime}</abbr>
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
				{$object_watchers = DAO_ContextLink::getContextLinks(CerberusContexts::CONTEXT_TASK, array($task->id), CerberusContexts::CONTEXT_WORKER)}
				{include file="devblocks:cerberusweb.core::internal/watchers/context_follow_button.tpl" context=CerberusContexts::CONTEXT_TASK context_id=$task->id full=true}
				</span>		
		
				{if !empty($macros)}
				<button type="button" class="split-left" onclick="$(this).next('button').click();"><span class="cerb-sprite sprite-gear"></span> Macros</button><!--  
				--><button type="button" class="split-right" id="btnDisplayMacros"><span class="cerb-sprite sprite-arrow-down-white"></span></button>
				<ul class="cerb-popupmenu cerb-float" id="menuDisplayMacros">
					<li style="background:none;">
						<input type="text" size="16" class="input_search filter">
					</li>
					{devblocks_url assign=return_url full=true}c=tasks&tab=display&id={$task->id}{/devblocks_url}
					{foreach from=$macros item=macro key=macro_id}
					<li><a href="{devblocks_url}c=internal&a=applyMacro{/devblocks_url}?macro={$macro->id}&context={CerberusContexts::CONTEXT_TASK}&context_id={$task->id}&return_url={$return_url|escape:'url'}">{$macro->title}</a></li>
					{/foreach}
				</ul>
				{/if}
		
				<button type="button" id="btnDisplayTaskEdit"><span class="cerb-sprite sprite-document_edit"></span> Edit</button>
		
				{$toolbar_extensions = DevblocksPlatform::getExtensions('cerberusweb.task.toolbaritem',true)}
				{foreach from=$toolbar_extensions item=toolbar_extension}
					{$toolbar_extension->render($task)}
				{/foreach}
				
				<button type="button" title="{$translate->_('display.shortcut.refresh')}" onclick="document.location='{devblocks_url}c=tasks&tab=display&id={$task->id}-{$task->title|devblocks_permalink}{/devblocks_url}';">&nbsp;<span class="cerb-sprite sprite-refresh"></span>&nbsp;</button>
			
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
		
		{include file="devblocks:cerberusweb.core::internal/notifications/context_profile.tpl" context=CerberusContexts::CONTEXT_TASK context_id=$task->id}		
		
	</td>
	<td align="right" valign="top">
		{*
		<form action="{devblocks_url}{/devblocks_url}" method="post">
		<input type="hidden" name="c" value="contacts">
		<input type="hidden" name="a" value="doOrgQuickSearch">
		<span><b>{$translate->_('common.quick_search')|capitalize}:</b></span> <select name="type">
			<option value="name">{$translate->_('contact_org.name')|capitalize}</option>
			<option value="phone">{$translate->_('contact_org.phone')|capitalize}</option>
		</select><input type="text" name="query" class="input_search" size="24"><button type="submit">{$translate->_('common.search_go')|lower}</button>
		</form>
		*}
	</td>
</tr>
</table>

<div id="tasksTabs">
	<ul>
		{$tabs = [activity, comments, links]}
		{$point = 'core.page.tasks'}

		<li><a href="{devblocks_url}ajax.php?c=internal&a=showTabActivityLog&scope=target&point={$point}&context={CerberusContexts::CONTEXT_TASK}&context_id={$task->id}{/devblocks_url}">{'common.activity_log'|devblocks_translate|capitalize}</a></li>
		<li><a href="{devblocks_url}ajax.php?c=internal&a=showTabContextComments&context={CerberusContexts::CONTEXT_TASK}&id={$task->id}{/devblocks_url}">{'common.comments'|devblocks_translate|capitalize}</a></li>
		<li><a href="{devblocks_url}ajax.php?c=internal&a=showTabContextLinks&context={CerberusContexts::CONTEXT_TASK}&id={$task->id}{/devblocks_url}">{'common.links'|devblocks_translate}</a></li>
	</ul>
</div> 
<br>

{$tab_selected_idx=0}
{foreach from=$tabs item=tab_label name=tabs}
	{if $tab_label==$tab_selected}{$tab_selected_idx = $smarty.foreach.tabs.index}{/if}
{/foreach}

<script type="text/javascript">
$(function() {
	var tabs = $("#tasksTabs").tabs( { selected:{$tab_selected_idx} } );

	$('#btnDisplayTaskEdit').bind('click', function() {
		$popup = genericAjaxPopup('peek','c=tasks&a=showTaskPeek&id={$task->id}',null,false,'550');
		$popup.one('task_save', function(event) {
			event.stopPropagation();
			document.location.href = '{devblocks_url}c=tasks&a=display&id={$task->id}{/devblocks_url}';
		});
	})
	
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
				$tabs = $("#tasksTabs").tabs();
				$tabs.tabs('select', idx);
			} catch(ex) { } 
			break;
		case 101:  // (E) edit
			try {
				$('#btnDisplayTaskEdit').click();
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
