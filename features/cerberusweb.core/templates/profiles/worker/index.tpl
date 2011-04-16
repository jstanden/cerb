<ul class="submenu">
</ul>
<div style="clear:both;"></div>

<form>
<fieldset style="">
	<div style="float:left;">
		<img src="{if $is_ssl}https://secure.{else}http://www.{/if}gravatar.com/avatar/{$worker->email|trim|lower|md5}?s=64&d=mm" border="0" style="margin:0px 5px 5px 0px;">
	</div>
	<div style="float:left;">
		<h1 style="color:rgb(0,120,0);font-weight:bold;font-size:150%;margin:0px;">{$worker->getName()}</h1>
		{if !empty($worker->title)}{$worker->title}<br>{/if}
		{if !empty($worker->email)}{$worker->email}<br>{/if}
		
		{$memberships = $worker->getMemberships()}
		{if !empty($memberships)}
		<ul class="bubbles">
			{foreach from=$memberships item=member key=group_id name=groups}
				{$group = $groups.{$group_id}}
				<li><a href="{devblocks_url}c=profiles&k=group&id={$group->id}-{$group->name|devblocks_permalink}{/devblocks_url}" style="{if $member->is_manager}font-weight:bold;{/if}">{$group->name}</a></li>
			{/foreach}
		</ul>
		{/if}
		
	</div>
	{if $active_worker->is_superuser}
	<div style="float:right;">
		<button type="button" id="btnProfileWorkerEdit"><span class="cerb-sprite sprite-document_edit"></span> Edit</button>
	</div>
	{/if}
</fieldset>
</form>

<div id="profileTabs">
	<ul>
		{$tabs = []}
		{$point = "cerberusweb.profiles.worker.{$worker->id}"}
		
		{if $worker->id == $active_worker->id}
		{$tabs[] = 'notifications'}
		<li><a href="{devblocks_url}ajax.php?c=preferences&a=showMyNotificationsTab{/devblocks_url}">{'home.tab.my_notifications'|devblocks_translate}</a></li>
		{/if}
		
		{* [TODO] Show read-only for all others *}
		{if $worker->id == $active_worker->id}
		{$tabs[] = 'attendant'}
		<li><a href="{devblocks_url}ajax.php?c=internal&a=showAttendantTab&point={$point}&context={CerberusContexts::CONTEXT_WORKER}&context_id={$active_worker->id}{/devblocks_url}">Virtual Attendant</a></li>
		{/if}

		{$tabs[] = 'activity'}
		<li><a href="{devblocks_url}ajax.php?c=internal&a=showTabActivityLog&scope=actor&point={$point}&context={CerberusContexts::CONTEXT_WORKER}&context_id={$worker->id}{/devblocks_url}">{'common.activity_log'|devblocks_translate|capitalize}</a></li>

		{$tabs[] = 'links'}
		<li><a href="{devblocks_url}ajax.php?c=internal&a=showTabContextLinks&context=cerberusweb.contexts.worker&point={$point}&id={$worker->id}{/devblocks_url}">Watching ({$watching_total})</a></li>
		
		{*
		{foreach from=$tab_manifests item=tab_manifest}
			{$tabs[] = $tab_manifest->params.uri}
			<li><a href="{devblocks_url}ajax.php?c=preferences&a=showTab&ext_id={$tab_manifest->id}{/devblocks_url}"><i>{$tab_manifest->params.title|devblocks_translate}</i></a></li>
		{/foreach}
		*}
		
		{if $worker->id == $active_worker->id}
		{if $active_worker->hasPriv('core.home.workspaces')}
			{$enabled_workspaces = DAO_Workspace::getByEndpoint($point, $active_worker->id)}
			{foreach from=$enabled_workspaces item=enabled_workspace}
				{$tabs[] = 'w_'|cat:$enabled_workspace->id}
				<li><a href="{devblocks_url}ajax.php?c=internal&a=showWorkspaceTab&id={$enabled_workspace->id}&point={$point}&request={$response_uri|escape:'url'}{/devblocks_url}"><i>{$enabled_workspace->name}</i></a></li>
			{/foreach}
			
			{$tabs[] = "+"}
			<li><a href="{devblocks_url}ajax.php?c=internal&a=showAddTab&point={$point}&request={$response_uri|escape:'url'}{/devblocks_url}"><i>+</i></a></li>
		{/if}
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
		var tabs = $("#profileTabs").tabs({ selected:{$selected_tab_idx} });
		
		{if $active_worker->is_superuser}
		$('#btnProfileWorkerEdit').bind('click', function() {
			$popup = genericAjaxPopup('peek','c=config&a=showWorkerPeek&id={$worker->id}',null,false,'550');
			$popup.one('worker_save', function(event) {
				event.stopPropagation();
				document.location.href = '{devblocks_url}c=profiles&k=worker&id={$worker->id}-{$worker->getName()|devblocks_permalink}{/devblocks_url}';
			});
		});
		{/if}
	});
</script>