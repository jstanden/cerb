<ul class="submenu">
</ul>
<div style="clear:both;"></div>

{$tabs = [orgs,people,addresses,lists]}
<div id="addyBookTabs">
	<ul>
		<li><a href="{devblocks_url}ajax.php?c=contacts&a=showOrgsTab{/devblocks_url}">{$translate->_('addy_book.tab.organizations')}</a></li>
		<li><a href="{devblocks_url}ajax.php?c=contacts&a=showPeopleTab{/devblocks_url}">{$translate->_('addy_book.tab.people')}</a></li>
		<li><a href="{devblocks_url}ajax.php?c=contacts&a=showAddysTab{/devblocks_url}">{$translate->_('addy_book.tab.addresses')}</a></li>
		{*<li><a href="{devblocks_url}ajax.php?c=contacts&a=showListsTab{/devblocks_url}">{$translate->_('addy_book.tab.lists')}</a></li>*}
		
		{if $active_worker->hasPriv('core.addybook.import')}
			{$tabs[] = import}
			<li><a href="{devblocks_url}ajax.php?c=contacts&a=showImportTab{/devblocks_url}">{$translate->_('addy_book.tab.import')}</a></li>
		{/if}
		
		{if $active_worker->hasPriv('core.home.workspaces')}
			{$enabled_workspaces = DAO_Workspace::getByEndpoint('cerberusweb.contacts.tab', $active_worker->id)}
			{foreach from=$enabled_workspaces item=enabled_workspace}
				{$tabs[] = 'w_'|cat:$enabled_workspace->id}
				<li><a href="{devblocks_url}ajax.php?c=internal&a=showWorkspaceTab&id={$enabled_workspace->id}&request={$response_uri|escape:'url'}{/devblocks_url}"><i>{$enabled_workspace->name}</i></a></li>
			{/foreach}
			
			{$tabs[] = "+"}
			<li><a href="{devblocks_url}ajax.php?c=internal&a=showAddTab&point=cerberusweb.contacts.tab&request={$response_uri|escape:'url'}{/devblocks_url}"><i>+</i></a></li>
		{/if}
	</ul>
</div> 
<br>

{$tab_selected_idx=0}
{foreach from=$tabs item=tab_label name=tabs}
	{if $tab_label==$selected_tab}{$tab_selected_idx = $smarty.foreach.tabs.index}{/if}
{/foreach}

<script type="text/javascript">
	$(function() {
		var tabs = $("#addyBookTabs").tabs( { selected:{$tab_selected_idx} } );
	});
</script>
