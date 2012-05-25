<div style="margin-top:5px;" id="divWorkspaceTab{$tab->id}">
	{if $tab_extension instanceof Extension_WorkspaceTab}
		{$tab_extension->renderTab($page, $tab)}
	{/if}
</div>