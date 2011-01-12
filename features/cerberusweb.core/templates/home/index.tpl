<ul class="submenu">
</ul>
<div style="clear:both;"></div>

<form action="{devblocks_url}{/devblocks_url}" method="POST" style="margin-bottom:5px;">
{if $active_worker->hasPriv('core.home.auto_refresh')}<button type="button" onclick="autoRefreshTimer.start('{devblocks_url full=true}c=home{/devblocks_url}',this.form.reloadSecs.value);"><span class="cerb-sprite sprite-refresh"></span> {'common.refresh.auto'|devblocks_translate|capitalize}</button><!-- 
--><select name="reloadSecs">
	<option value="600">{'common.time.mins.num'|devblocks_translate:'10'}</option>
	<option value="300" selected="selected">{'common.time.mins.num'|devblocks_translate:'5'}</option>
	<option value="240">{'common.time.mins.num'|devblocks_translate:'4'}</option>
	<option value="180">{'common.time.mins.num'|devblocks_translate:'3'}</option>
	<option value="120">{'common.time.mins.num'|devblocks_translate:'2'}</option>
	<option value="60">{'common.time.mins.num'|devblocks_translate:'1'}</option>
	<option value="30">{'common.time.secs.num'|devblocks_translate:'30'}</option>
</select>{/if}
</form>

<div id="homeTabs">
	<ul>
		{$point = Extension_HomeTab::POINT}
		{$tabs = [events,links]}

		<li><a href="{devblocks_url}ajax.php?c=home&a=showMyEvents{/devblocks_url}">{'home.tab.my_notifications'|devblocks_translate}</a></li>
		<li><a href="{devblocks_url}ajax.php?c=internal&a=showTabContextLinks&context=cerberusweb.contexts.worker&point={$point}&id={$active_worker->id}&filter_open=1{/devblocks_url}">{'My Work'|devblocks_translate}</a></li>

		{$tab_manifests = DevblocksPlatform::getExtensions($point, false)}
		{foreach from=$tab_manifests item=tab_manifest}
			{$tabs[] = $tab_manifest->params.uri}
			<li><a href="{devblocks_url}ajax.php?c=home&a=showTab&ext_id={$tab_manifest->id}{/devblocks_url}">{$tab_manifest->params.title|devblocks_translate}</a></li>
		{/foreach}

		{if $active_worker->hasPriv('core.home.workspaces')}
			{$enabled_workspaces = DAO_Workspace::getByEndpoint($point, $active_worker->id)}
			{foreach from=$enabled_workspaces item=enabled_workspace}
				{$tabs[] = 'w_'|cat:$enabled_workspace->id}
				<li><a href="{devblocks_url}ajax.php?c=internal&a=showWorkspaceTab&id={$enabled_workspace->id}&point={$point}&request={$response_uri|escape:'url'}{/devblocks_url}"><i>{$enabled_workspace->name}</i></a></li>
			{/foreach}
			
			{if empty($enabled_workspaces) && $active_worker->hasPriv('core.home.workspaces')}
				{$tabs[] = intro}		
				<li><a href="{devblocks_url}ajax.php?c=home&a=showWorkspacesIntroTab{/devblocks_url}">{'home.tab.workspaces_intro'|devblocks_translate}</a></li>
			{/if}
		{/if}
		
		{$tabs[] = "+"}
		<li><a href="{devblocks_url}ajax.php?c=internal&a=showAddTab&point={$point}&request={$response_uri|escape:'url'}{/devblocks_url}"><i>+</i></a></li>
	</ul>
</div> 
<br>

{$selected_tab_idx=null}
{if isset($selected_tab)}
{foreach from=$tabs item=tab_label name=tabs}
	{if $tab_label==$selected_tab}{$selected_tab_idx = $smarty.foreach.tabs.index}{/if}
{/foreach}
{/if}

<script type="text/javascript">
	$(function() {
		var tabs = $("#homeTabs").tabs({ 
			cookie:{ name:'homeTabs', path:'/' }
			{if !is_null($selected_tab_idx)},selected:{$selected_tab_idx}{/if}
		});
	});
</script>
