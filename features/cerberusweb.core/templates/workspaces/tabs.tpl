{if !empty($workspace_title)}<h2>{$workspace_title}</h2>{/if}

<div id="workspaceTabs">
	<ul>
		{$tabs = []}
		
		{$enabled_workspaces = DAO_Workspace::getByEndpoint($point, $active_worker)}
		{foreach from=$enabled_workspaces item=enabled_workspace}
			{$tabs[] = 'w_'|cat:$enabled_workspace->id}
			<li><a href="{devblocks_url}ajax.php?c=internal&a=showWorkspaceTab&point={$point}&id={$enabled_workspace->id}&request={$response_uri|escape:'url'}{/devblocks_url}">{$enabled_workspace->name}</a></li>
		{/foreach}
		
		{$tabs[] = "+"}
		<li><a href="{devblocks_url}ajax.php?c=internal&a=showAddTab&point={$point}&request={$response_uri|escape:'url'}{/devblocks_url}"><i>+</i></a></li>
	</ul>
</div> 
<br>

{$tab_selected_idx=0}
{foreach from=$tabs item=tab_label name=tabs}
	{if $tab_label==$selected_tab}{$tab_selected_idx = $smarty.foreach.tabs.index}{/if}
{/foreach}

<script type="text/javascript">
	$(function() {
		var tabs = $("#workspaceTabs").tabs( { 
			selected: {$tab_selected_idx}
		});
	});
</script>
