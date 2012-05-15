<form action="{devblocks_url}{/devblocks_url}" id="frmWorkspaceTab{$tab->id}" method="POST" class="toolbar">
	<input type="hidden" name="c" value="internal">
	<input type="hidden" name="a" value="">

	{if $page->isWriteableByWorker($active_worker)}
		<button type="button" class="edit toolbar-item"><span class="cerb-sprite2 sprite-ui-tab-gear"></span> Edit Tab</button>
		&nbsp;
	{/if}
</form>

<div style="margin-top:5px;" id="divWorkspaceTab{$tab->id}">
	{$tab_extension->renderTab($page, $tab)}
</div>

<script type="text/javascript">
	// Edit workspace actions
	$workspace = $('#frmWorkspaceTab{$tab->id}');
	$workspace.find('button.edit').click(function(e) {
		$popup = genericAjaxPopup('peek','c=pages&a=showEditWorkspaceTab&id={$tab->id}',null,true,'600');
		$popup.one('workspace_save',function(e) {
			$tabs = $('#frmWorkspaceTab{$tab->id}').closest('div.ui-tabs');
			if(0 != $tabs) {
				$tabs.tabs('load', $tabs.tabs('option','selected'));
			}
		});
		$popup.one('workspace_delete',function(e) {
			$tabs = $('#frmWorkspaceTab{$tab->id}').closest('div.ui-tabs');
			if(0 != $tabs) {
				$tabs.tabs('remove', $tabs.tabs('option','selected'));
			}
		});
	});
</script>