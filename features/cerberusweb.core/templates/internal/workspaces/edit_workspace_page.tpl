<form action="#" method="post" id="frmEditWorkspacePage" onsubmit="return false;">
<input type="hidden" name="c" value="internal">
<input type="hidden" name="a" value="doEditWorkspacePage">
<input type="hidden" name="id" value="{$workspace_page->id|default:0}">
<input type="hidden" name="view_id" value="{$view_id}">
{if !empty($workspace_page)}<input type="hidden" name="do_delete" value="0">{/if}

<table cellpadding="2" cellspacing="0" border="0" width="100%" style="margin-bottom:5px;">
	<tr>
		<td width="1%" nowrap="nowrap" align="right" valign="top">
			<b>{'common.name'|devblocks_translate|capitalize}:</b>
		</td>
		<td width="99%">
			<input type="text" name="name" value="{$workspace_page->name}" size="35" style="width:100%;">
		</td>
	</tr>

	<tr>
		<td width="1%" nowrap="nowrap" align="right" valign="top">
			<b>{'common.owner'|devblocks_translate|capitalize}:</b>
		</td>
		<td width="99%">
			{if !empty($workspace_page)}
				{$context = Extension_DevblocksContext::get($workspace_page->owner_context)}
				{if !empty($context)}
					{$meta = $context->getMeta({$workspace_page->owner_context_id})}
					<div class="bubble"><b>{$meta.name}</b> ({$context->manifest->name})</div>
				{/if}
			{else}
				<select name="owner">
					<option value="w_{$active_worker->id}">me</option>
					
					{if !empty($owner_groups)}
					{foreach from=$owner_groups item=group key=group_id}
						<option value="g_{$group_id}">Group: {$group->name}</option>
					{/foreach}
					{/if}
					
					{if !empty($owner_roles)}
					{foreach from=$owner_roles item=role key=role_id}
						<option value="r_{$role_id}">Role: {$role->name}</option>
					{/foreach}
					{/if}
					
					{if $active_worker->is_superuser}
					{foreach from=$workers item=worker key=worker_id}
						{if empty($worker->is_disabled)}
						<option value="w_{$worker_id}">Worker: {$worker->getName()}</option>
						{/if}
					{/foreach}
					{/if}
				</select>
			{/if}
		</td>
	</tr>
	
</table>

<button type="button" onclick="genericAjaxPopupPostCloseReloadView(null,'frmEditWorkspacePage','{$view_id}',false,'workspace_save');"><span class="cerb-sprite2 sprite-tick-circle-frame"></span> {$translate->_('common.save_changes')}</button>
{if !empty($workspace_page)}<button type="button" onclick="if(!confirm('Are you sure you want to delete this workspace?')) { return false; }; $('#frmEditWorkspacePage').find('input:hidden[name=do_delete]').val('1');genericAjaxPopupPostCloseReloadView(null,'frmEditWorkspacePage','',false,'workspace_delete');"><span class="cerb-sprite2 sprite-cross-circle-frame"></span> {'common.delete'|devblocks_translate|capitalize}</button>{/if}
</form>

<script type="text/javascript">
	$popup = genericAjaxPopupFetch('peek');
	$popup.one('popup_open', function(event,ui) {
		$(this).dialog('option','title',"{if !empty($workspace_page)}Edit Page{else}Add Page{/if}");
		$('#frmEditWorkspacePage').sortable({ items: 'DIV.column', placeholder:'ui-state-highlight' });
		
		$frm = $('#frmEditWorkspacePage');
		$frm.find('input:text:first').focus().select();
	});
</script>
