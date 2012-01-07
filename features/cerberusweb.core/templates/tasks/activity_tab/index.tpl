<table cellspacing="0" cellpadding="0" border="0" width="100%">
	<tr>
		<td width="99%" valign="middle">
		{if $active_worker->hasPriv('core.tasks.actions.create')}
		<form action="{devblocks_url}{/devblocks_url}" style="margin-bottom:5px;">
			<button type="button" onclick="genericAjaxPopup('peek','c=tasks&a=showTaskPeek&id=0&view_id={$view->id}',null,false,'500');"><span class="cerb-sprite2 sprite-plus-circle-frame"></span> {'tasks.add'|devblocks_translate}</button>
		</form>
		{/if}
		</td>
		<td width="1%" nowrap="nowrap" valign="middle" align="right">
			{include file="devblocks:cerberusweb.core::tasks/activity_tab/quick_search.tpl"}
		</td>
	</tr>
</table>

{include file="devblocks:cerberusweb.core::internal/views/search_and_view.tpl" view=$view}

{include file="devblocks:cerberusweb.core::internal/views/view_workflow_keyboard_shortcuts.tpl" view=$view}

