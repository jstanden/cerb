<div id="headerSubMenu">
	<div style="padding-bottom:5px;"></div>
</div> 

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
		<li><a href="{devblocks_url}ajax.php?c=config&a=showTabSettings{/devblocks_url}">System</a></li>
		<li><a href="{devblocks_url}ajax.php?c=config&a=showTabPlugins{/devblocks_url}">Plugins &amp; Features</a></li>
		<li><a href="{devblocks_url}ajax.php?c=config&a=showTabMail{/devblocks_url}">Mail Setup</a></li>
		<li><a href="{devblocks_url}ajax.php?c=config&a=showTabPreParser{/devblocks_url}">Mail Filtering</a></li>
		<li><a href="{devblocks_url}ajax.php?c=config&a=showTabParser{/devblocks_url}">Mail Routing</a></li>
		<li><a href="{devblocks_url}ajax.php?c=config&a=showTabAttachments{/devblocks_url}">Attachments</a></li>
		<li><a href="{devblocks_url}ajax.php?c=config&a=showTabScheduler{/devblocks_url}">Scheduler</a></li>
		<li><a href="{devblocks_url}ajax.php?c=config&a=showTabGroups{/devblocks_url}">Groups</a></li>
		<li><a href="{devblocks_url}ajax.php?c=config&a=showTabWorkers{/devblocks_url}">Workers</a></li>
		<li><a href="{devblocks_url}ajax.php?c=config&a=showTabPermissions{/devblocks_url}">Permissions</a></li>
		<li><a href="{devblocks_url}ajax.php?c=config&a=showTabFields{/devblocks_url}">Custom Fields</a></li>

		{$tabs = [settings,plugins,mail,preparser,parser,attachments,scheduler,groups,workers,acl,fields]}

		{foreach from=$tab_manifests item=tab_manifest}
			{$tabs[] = $tab_manifest->params.uri}
			<li><a href="{devblocks_url}ajax.php?c=config&a=showTab&ext_id={$tab_manifest->id}{/devblocks_url}"><i>{$tab_manifest->params.title|devblocks_translate|escape:'quotes'}</i></a></li>
		{/foreach}
	</ul>
</div> 
<br>

{$tab_selected_idx=0}
{foreach from=$tabs item=tab_label name=tabs}
	{if $tab_label==$tab_selected}{$tab_selected_idx = $smarty.foreach.tabs.index}{/if}
{/foreach}

<script type="text/javascript">
	$(function() {
		var tabs = $("#configTabs").tabs( { selected:{$tab_selected_idx} } );
	});
</script>

<br>