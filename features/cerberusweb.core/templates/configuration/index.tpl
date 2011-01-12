<ul class="submenu">
</ul>
<div style="clear:both;"></div>

<h1>{$translate->_('header.config')|capitalize}</h1>

{if $install_dir_warning}
<div class="ui-widget">
	<div class="ui-state-error ui-corner-all" style="padding: 0 .7em; margin: 0.2em; "> 
		<p><span class="ui-icon ui-icon-alert" style="float: left; margin-right: .3em;"></span> 
		<strong>Warning:</strong> The 'install' directory still exists.  This is a potential security risk.  Please delete it.</p>
	</div>
</div>
{/if}

<div id="tourConfigMenu"></div>

<div id="configTabs">
	<ul>
		{$tabs = []}
		{$point = Extension_ConfigTab::POINT}
		
		{$tabs[] = 'settings'}
		<li><a href="{devblocks_url}ajax.php?c=config&a=showTabSettings{/devblocks_url}">System</a></li>
		
		{$tabs[] = 'plugins'}
		<li><a href="{devblocks_url}ajax.php?c=config&a=showTabPlugins{/devblocks_url}">Plugins &amp; Features</a></li>
		
		{if !$smarty.const.ONDEMAND_MODE}
			{$tabs[] = 'storage'}
			<li><a href="{devblocks_url}ajax.php?c=config&a=showTabStorage{/devblocks_url}">Storage</a></li>
		{/if}
		
		{$tabs[] = 'mail'}
		<li><a href="{devblocks_url}ajax.php?c=config&a=showTabMail{/devblocks_url}">Mail Setup</a></li>
		
		{$tabs[] = 'preparser'}
		<li><a href="{devblocks_url}ajax.php?c=config&a=showTabPreParser{/devblocks_url}">Mail Filtering</a></li>
		
		{$tabs[] = 'parser'}
		<li><a href="{devblocks_url}ajax.php?c=config&a=showTabParser{/devblocks_url}">Mail Routing</a></li>
		
		{$tabs[] = 'queue'}
		<li><a href="{devblocks_url}ajax.php?c=config&a=showTabQueue{/devblocks_url}">Mail Queue</a></li>
		
		{$tabs[] = 'attachments'}
		<li><a href="{devblocks_url}ajax.php?c=config&a=showTabAttachments{/devblocks_url}">Attachments</a></li>
		
		{if !$smarty.const.ONDEMAND_MODE}
			{$tabs[] = 'scheduler'}
			<li><a href="{devblocks_url}ajax.php?c=config&a=showTabScheduler{/devblocks_url}">Scheduler</a></li>
		{/if}
		
		{$tabs[] = 'groups'}
		<li><a href="{devblocks_url}ajax.php?c=config&a=showTabGroups{/devblocks_url}">Groups</a></li>
		
		{$tabs[] = 'workers'}
		<li><a href="{devblocks_url}ajax.php?c=config&a=showTabWorkers{/devblocks_url}">Workers</a></li>
		
		{$tabs[] = 'acl'}
		<li><a href="{devblocks_url}ajax.php?c=config&a=showTabPermissions{/devblocks_url}">Permissions</a></li>
		
		{$tabs[] = 'fields'}
		<li><a href="{devblocks_url}ajax.php?c=config&a=showTabFields{/devblocks_url}">Custom Fields</a></li>

		{foreach from=$tab_manifests item=tab_manifest}
			{$tabs[] = $tab_manifest->params.uri}
			<li><a href="{devblocks_url}ajax.php?c=config&a=showTab&ext_id={$tab_manifest->id}{/devblocks_url}"><i>{$tab_manifest->params.title|devblocks_translate}</i></a></li>
		{/foreach}
		
		{if $active_worker->hasPriv('core.home.workspaces')}
			{$enabled_workspaces = DAO_Workspace::getByEndpoint($point, $active_worker->id)}
			{foreach from=$enabled_workspaces item=enabled_workspace}
				{$tabs[] = 'w_'|cat:$enabled_workspace->id}
				<li><a href="{devblocks_url}ajax.php?c=internal&a=showWorkspaceTab&point={$point}&id={$enabled_workspace->id}&request={$response_uri|escape:'url'}{/devblocks_url}"><i>{$enabled_workspace->name}</i></a></li>
			{/foreach}
			
			{$tabs[] = "+"}
			<li><a href="{devblocks_url}ajax.php?c=internal&a=showAddTab&point={$point}&request={$response_uri|escape:'url'}{/devblocks_url}"><i>+</i></a></li>
		{/if}
	</ul>
</div> 
<br>

{$selected_tab_idx=0}
{foreach from=$tabs item=tab_label name=tabs}
	{if $tab_label==$selected_tab}{$selected_tab_idx = $smarty.foreach.tabs.index}{/if}
{/foreach}

<script type="text/javascript">
	$(function() {
		var tabs = $("#configTabs").tabs( { selected:{$selected_tab_idx} } );
	});
</script>

<br>