{include file="devblocks:cerberusweb.core::tasks/display/submenu.tpl"}

<table cellspacing="0" cellpadding="0" border="0" width="100%" style="padding-bottom:5px;">
<tr>
	<td valign="top" style="padding-right:5px;">
		<h1>{if $task->is_completed}<span class="cerb-sprite2 sprite-tick-circle-frame"></span>{/if} {$task->title}</h1> 
		<form action="{devblocks_url}{/devblocks_url}" method="post">
		<input type="hidden" name="c" value="tasks">
		<input type="hidden" name="a" value="">
		<input type="hidden" name="id" value="{$task->id}">
		
		<b>{'task.is_completed'|devblocks_translate|capitalize}:</b> {if $task->is_completed}{'common.yes'|devblocks_translate|capitalize}{else}{'common.no'|devblocks_translate|capitalize}{/if} &nbsp;
		{if !empty($task->updated_date)}
		<b>{'task.updated_date'|devblocks_translate|capitalize}:</b> <abbr title="{$task->updated_date|devblocks_date}">{$task->updated_date|devblocks_prettytime}</abbr> &nbsp;
		{/if}
		{if !empty($task->due_date)}
		<b>{'task.due_date'|devblocks_translate|capitalize}:</b> <abbr title="{$task->due_date|devblocks_date}">{$task->due_date|devblocks_prettytime}</abbr> &nbsp;
		{/if}

		<br>
		
		<!-- Toolbar -->

		<span>
		{$object_watchers = DAO_ContextLink::getContextLinks(CerberusContexts::CONTEXT_TASK, array($task->id), CerberusContexts::CONTEXT_WORKER)}
		{include file="devblocks:cerberusweb.core::internal/watchers/context_follow_button.tpl" context=CerberusContexts::CONTEXT_TASK context_id=$task->id full=true}
		</span>		

		<div style="display:inline-block;">
			<button type="button" id="btnDisplayTaskActions"><span class="cerb-sprite sprite-gear"></span> Actions &#x25be;</button>
			<ul class="cerb-popupmenu cerb-float" id="menuDisplayTaskActions">
				<li><a href="javascript:;" class="edit">Edit</a></li>
				{if !$task->is_completed}
				<li><a href="javascript:;" onclick="$frm=$(this).closest('form');$frm.find('input:hidden[name=a]').val('doDisplayTaskComplete');$frm.submit();">Mark Completed</a></li>
				{/if}
			</ul>
		</div>
		
		{$toolbar_extensions = DevblocksPlatform::getExtensions('cerberusweb.task.toolbaritem',true)}
		{foreach from=$toolbar_extensions item=toolbar_extension}
			{$toolbar_extension->render($task)}
		{/foreach}
		
		</form>
		<br>
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

		var $menu = $('#menuDisplayTaskActions');
		$menu
			.closest('div')
			.hover(
				function(e) {},
				function(e) {
					$menu.slideUp('fast');
				}
			)
		;
		$menu
			.find('> li')
			.click(function(e) {
				e.stopPropagation();
				if(!$(e.target).is('li'))
					return;
	
				$(this).find('a').trigger('click');
				$menu.slideUp('fast');
			})
		;
		
		$('#btnDisplayTaskActions').click(function() {
			$menu
				.css('position','absolute')
				.css('top',($(this).offset().top+20)+'px')
				.css('left',$(this).offset().left+'px')
				.slideDown('fast');
		});
		
		$menu.find('> LI > A.edit').bind('click', function() {
			$popup = genericAjaxPopup('peek','c=tasks&a=showTaskPeek&id={$task->id}',null,false,'550');
			$popup.one('task_save', function(event) {
				event.stopPropagation();
				document.location.href = '{devblocks_url}c=tasks&a=display&id={$task->id}{/devblocks_url}';
			});
		})
	});
</script>
