<form action="#" method="post" id="frmEditWorkspacePage" onsubmit="return false;">
<input type="hidden" name="c" value="pages">
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
			<b>{'common.type'|devblocks_translate|capitalize}:</b>
		</td>
		<td width="99%">
			{if !empty($workspace_page)}
				{if !empty($workspace_page->extension_id)}
					{$page_extension = DevblocksPlatform::getExtension($workspace_page->extension_id, false)}
					{if $page_extension}
						{$page_extension->params.label|devblocks_translate|capitalize}
					{/if}
				{else}
					Workspace
				{/if}
			{else}
				<select name="extension_id">
					<option value="">Workspace</option>
					{if !empty($page_extensions)}
						{foreach from=$page_extensions item=page_extension}
							<option value="{$page_extension->id}">{$page_extension->params.label|devblocks_translate|capitalize}</option>
						{/foreach}
					{/if}
				</select>
			{/if}
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
			{/if}
			
			<select name="owner">
				{if !empty($workspace_page)}<option value="">- change -</option>{/if}
				
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
		</td>
	</tr>
</table>

<fieldset class="delete" style="display:none;">
	<legend>Are you sure you want to delete this workspace page?</legend>
	
	<p>
		This will also delete all of the page's tabs and worklists.
	</p>
	
	<button type="button" class="red" onclick="$('#frmEditWorkspacePage').find('input:hidden[name=do_delete]').val('1');genericAjaxPopupPostCloseReloadView(null,'frmEditWorkspacePage','',false,'workspace_delete');">{'common.yes'|devblocks_translate|capitalize}</button>
	<button type="button" onclick="$(this).closest('fieldset').fadeOut().siblings('div.toolbar').fadeIn();">{'common.no'|devblocks_translate|capitalize}</button>
</fieldset>

<div class="toolbar">
	<button type="button" onclick="genericAjaxPopupPostCloseReloadView(null,'frmEditWorkspacePage','{$view_id}',false,'workspace_save');"><span class="cerb-sprite2 sprite-tick-circle"></span> {$translate->_('common.save_changes')}</button>
	{if !empty($workspace_page)}<button type="button" onclick="$(this).closest('div.toolbar').fadeOut().siblings('fieldset.delete').fadeIn();"><span class="cerb-sprite2 sprite-cross-circle"></span> {'common.delete'|devblocks_translate|capitalize}</button>{/if}
</div>

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
