{$page_context = CerberusContexts::CONTEXT_WORKER}
{$page_context_id = $worker->id}

<ul class="submenu">
</ul>
<div style="clear:both;"></div>

<div style="margin-left:10px;">
	<div style="float:left;"><img src="{if $is_ssl}https://secure.{else}http://www.{/if}gravatar.com/avatar/{$worker->email|trim|lower|md5}?s=64&d={devblocks_url full=true}c=resource&p=cerberusweb.core&f=images/wgm/gravatar_nouser.jpg{/devblocks_url}" height="64" width="64" border="0" style="margin:0px 5px 5px 0px;"></div>
	<h1 style="color:rgb(0,120,0);font-weight:bold;font-size:150%;margin:0px;">{$worker->getName()}</h1>
	{if !empty($worker->title)}{$worker->title}<br>{/if}
	
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

<div style="clear:both;"></div>

<form action="javascript:;">
<fieldset class="properties">
	{foreach from=$properties item=v key=k name=props}
		<div class="property">
			{if $k == '...'}
				<b>{$translate->_('...')|capitalize}:</b>
				...
			{else}
				{include file="devblocks:cerberusweb.core::internal/custom_fields/profile_cell_renderer.tpl"}
			{/if}
		</div>
		{if $smarty.foreach.props.iteration % 3 == 0 && !$smarty.foreach.props.last}
			<br clear="all">
		{/if}
	{/foreach}
	<br clear="all">
	
	<div style="margin-top:5px;">
		<!-- Macros -->
		{if $worker->id == $active_worker->id || $active_worker->is_superuser}
			{if !empty($page_context) && !empty($page_context_id) && !empty($macros)}
				{devblocks_url assign=return_url full=true}c=profiles&tab=worker&id={$page_context_id}-{$worker->getName()|devblocks_permalink}{/devblocks_url}
				{include file="devblocks:cerberusweb.core::internal/macros/display/button.tpl" context=$page_context context_id=$page_context_id macros=$macros return_url=$return_url}
			{/if}
		{/if}
	
		{if $active_worker->is_superuser}			
			{if $worker->id != $active_worker->id}<button type="button" id="btnProfileWorkerPossess"><span class="cerb-sprite2 sprite-user-silhouette"></span> Impersonate</button>{/if}
			<button type="button" id="btnProfileWorkerEdit"><span class="cerb-sprite sprite-document_edit"></span> {'common.edit'|devblocks_translate|capitalize}</button>
		{/if}
	</div>
</fieldset>
	
<div>
{include file="devblocks:cerberusweb.core::internal/notifications/context_profile.tpl" context=$page_context context_id=$page_context_id}
</div>

<div>
{include file="devblocks:cerberusweb.core::internal/macros/behavior/scheduled_behavior_profile.tpl" context=$page_context context_id=$page_context_id}
</div>

</form>

<div id="profileTabs">
	<ul>
		{$tabs = []}
		{$point = "cerberusweb.profiles.worker.{$worker->id}"}
		
		{if $worker->id == $active_worker->id}
		{$tabs[] = 'notifications'}
		<li><a href="{devblocks_url}ajax.php?c=preferences&a=showMyNotificationsTab{/devblocks_url}">{'home.tab.my_notifications'|devblocks_translate}</a></li>
		{/if}
		
		{$tabs[] = 'calendar'}
		<li><a href="{devblocks_url}ajax.php?c=internal&a=showCalendarTab&point={$point}&context={$page_context}&context_id={$page_context_id}{/devblocks_url}">Calendar</a></li>
		
		{$tabs[] = 'activity'}
		<li><a href="{devblocks_url}ajax.php?c=internal&a=showTabActivityLog&scope=actor&point={$point}&context={$page_context}&context_id={$page_context_id}{/devblocks_url}">{'common.activity_log'|devblocks_translate|capitalize}</a></li>

		{if $active_worker->is_superuser || $worker->id == $active_worker->id}
		{$tabs[] = 'attendant'}
		<li><a href="{devblocks_url}ajax.php?c=internal&a=showAttendantTab&point={$point}&context={$page_context}&context_id={$page_context_id}{/devblocks_url}">Virtual Attendant</a></li>
		{/if}
		
		{if $active_worker->is_superuser || $worker->id == $active_worker->id}
		{$tabs[] = 'behavior'}
		<li><a href="{devblocks_url}ajax.php?c=internal&a=showScheduledBehaviorTab&point={$point}&context={$page_context}&context_id={$page_context_id}{/devblocks_url}">Scheduled Behavior</a></li>
		{/if}

		{$tabs[] = 'links'}
		<li><a href="{devblocks_url}ajax.php?c=internal&a=showTabContextLinks&context={$page_context}&point={$point}&id={$page_context_id}{/devblocks_url}">Watchlist ({$watching_total})</a></li>
		
		{if $active_worker->is_superuser || $worker->id == $active_worker->id}
		{$tabs[] = 'snippets'}
		<li><a href="{devblocks_url}ajax.php?c=internal&a=showTabSnippets&point={$point}&context={$page_context}&context_id={$page_context_id}{/devblocks_url}">{$translate->_('common.snippets')|capitalize}</a></li>
		{/if}
		
		{*
		{foreach from=$tab_manifests item=tab_manifest}
			{$tabs[] = $tab_manifest->params.uri}
			<li><a href="{devblocks_url}ajax.php?c=preferences&a=showTab&ext_id={$tab_manifest->id}{/devblocks_url}"><i>{$tab_manifest->params.title|devblocks_translate}</i></a></li>
		{/foreach}
		*}
		
		{if $worker->id == $active_worker->id}
		{if $active_worker->hasPriv('core.home.workspaces')}
			{$enabled_workspaces = DAO_Workspace::getByEndpoint($point, $active_worker)}
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
			$popup = genericAjaxPopup('peek','c=config&a=handleSectionAction&section=workers&action=showWorkerPeek&id={$worker->id}',null,false,'550');
			$popup.one('worker_save', function(event) {
				event.stopPropagation();
				window.location.reload();
			});
		});
		$('#btnProfileWorkerPossess').bind('click', function() {
			genericAjaxGet('','c=internal&a=su&worker_id={$worker->id}',function(o) {
				window.location.reload();
			});
		});
		{/if}
	});
	
	{include file="devblocks:cerberusweb.core::internal/macros/display/menu_script.tpl"}
</script>