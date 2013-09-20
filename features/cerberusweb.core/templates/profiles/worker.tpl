{$page_context = CerberusContexts::CONTEXT_WORKER}
{$page_context_id = $worker->id}

{$gravatar_plugin = DevblocksPlatform::getPlugin('cerberusweb.gravatar')}
{$gravatar_enabled = $gravatar_plugin && $gravatar_plugin->enabled}

<table cellpadding="0" cellspacing="0" border="0" width="100%">
	<tr>
		<td width="1%" nowrap="nowrap" rowspan="2" valign="top" style="padding-left:10px;">
			{if $gravatar_enabled}
			<img src="{if $is_ssl}https://secure.{else}http://www.{/if}gravatar.com/avatar/{$worker->email|trim|lower|md5}?s=64&d=http://cerbweb.com/gravatar/gravatar_nouser.jpg" height="64" width="64" border="0" style="margin:0px 5px 5px 0px;">
			{/if}
		</td>
		<td width="98%" valign="top">
			<h1 style="color:rgb(0,120,0);font-weight:bold;font-size:150%;margin:0px;">{$worker->getName()}</h1>
			{if !empty($worker->title)}<div>{$worker->title}</div>{/if}
		</td>
		<td width="1%" nowrap="nowrap" align="right">
			{$ctx = Extension_DevblocksContext::get($page_context)}
			{include file="devblocks:cerberusweb.core::search/quick_search.tpl" view=$ctx->getSearchView() return_url="{devblocks_url}c=search&context={$ctx->manifest->params.alias}{/devblocks_url}" reset=true}
		</td>
	</tr>
	<tr>
		<td colspan="2">
			{$memberships = $worker->getMemberships()}
			{if !empty($memberships)}
			<ul class="bubbles">
				{foreach from=$memberships item=member key=group_id name=groups}
					{$group = $groups.{$group_id}}
					<li><a href="{devblocks_url}c=profiles&k=group&id={$group->id}-{$group->name|devblocks_permalink}{/devblocks_url}" style="{if $member->is_manager}font-weight:bold;{/if}">{$group->name}</a></li>
				{/foreach}
			</ul>
			{/if}
		</td>
	</tr>
</table>

<div style="clear:both;"></div>

<div class="cerb-profile-toolbar" style="margin-top:5px;">
	<form class="toolbar" action="javascript:;" method="POST" style="margin:0px 0px 5px 5px;" onsubmit="return false;">
		<!-- Macros -->
		{if $worker->id == $active_worker->id || $active_worker->is_superuser}
			{if !empty($page_context) && !empty($page_context_id) && !empty($macros)}
				{devblocks_url assign=return_url full=true}c=profiles&tab=worker&id={$page_context_id}-{$worker->getName()|devblocks_permalink}{/devblocks_url}
				{include file="devblocks:cerberusweb.core::internal/macros/display/button.tpl" context=$page_context context_id=$page_context_id macros=$macros return_url=$return_url}
			{/if}
		{/if}
	
		{if $active_worker->is_superuser}
			{if $worker->id != $active_worker->id}<button type="button" id="btnProfileWorkerPossess"><span class="cerb-sprite2 sprite-user-silhouette"></span> Impersonate</button>{/if}
			<button type="button" id="btnProfileWorkerEdit" title="{'common.edit'|devblocks_translate|capitalize}">&nbsp;<span class="cerb-sprite2 sprite-gear"></span>&nbsp;</button>
			<button type="button" title="{'display.shortcut.refresh'|devblocks_translate}" onclick="document.location='{devblocks_url}c=profiles&type=worker&id={$worker->id}{/devblocks_url}-{$worker->getName()|devblocks_permalink}';">&nbsp;<span class="cerb-sprite sprite-refresh"></span>&nbsp;</button>
		{/if}
	</form>
</div>

<fieldset class="properties">
	<legend>Worker</legend>
	
	<div style="margin-left:15px;">
	
	{foreach from=$properties item=v key=k name=props}
		<div class="property">
			{if $k == '...'}
				<b>{'...'|devblocks_translate|capitalize}:</b>
				...
			{else}
				{include file="devblocks:cerberusweb.core::internal/custom_fields/profile_cell_renderer.tpl"}
			{/if}
		</div>
		{if $smarty.foreach.props.iteration % 3 == 0 && !$smarty.foreach.props.last}
			<br clear="all">
		{/if}
	{/foreach}
	</div>
	
	<br clear="all">
</fieldset>

{include file="devblocks:cerberusweb.core::internal/custom_fieldsets/profile_fieldsets.tpl" properties=$properties_custom_fieldsets}

<div>
{include file="devblocks:cerberusweb.core::internal/notifications/context_profile.tpl" context=$page_context context_id=$page_context_id}
</div>

<div>
{include file="devblocks:cerberusweb.core::internal/macros/behavior/scheduled_behavior_profile.tpl" context=$page_context context_id=$page_context_id}
</div>

<div id="profileTabs">
	<ul>
		{$tabs = []}
		{$point = "cerberusweb.profiles.worker.{$worker->id}"}
		
		{if $worker->id == $active_worker->id}
		{$tabs[] = 'notifications'}
		<li><a href="{devblocks_url}ajax.php?c=preferences&a=showMyNotificationsTab{/devblocks_url}">{'home.tab.my_notifications'|devblocks_translate}</a></li>
		{/if}
		
		{$tabs[] = 'availability'}
		<li><a href="{devblocks_url}ajax.php?c=internal&a=handleSectionAction&section=calendars&action=showCalendarAvailabilityTab&point={$point}&context={$page_context}&context_id={$page_context_id}&id={$profile_worker_prefs.availability_calendar_id}{/devblocks_url}">Availability</a></li>
		
		{$tabs[] = 'activity'}
		<li><a href="{devblocks_url}ajax.php?c=internal&a=showTabActivityLog&scope=both&point={$point}&context={$page_context}&context_id={$page_context_id}{/devblocks_url}">{'common.activity_log'|devblocks_translate|capitalize}</a></li>
		
		{$tabs[] = 'comments'}
		<li><a href="{devblocks_url}ajax.php?c=internal&a=showTabContextComments&context={$page_context}&id={$page_context_id}{/devblocks_url}">{'common.comments'|devblocks_translate|capitalize} <div class="tab-badge">{DAO_Comment::count($page_context, $page_context_id)|default:0}</div></a></li>

		{if $active_worker->is_superuser || $worker->id == $active_worker->id}
		{$tabs[] = 'attendants'}
		<li><a href="{devblocks_url}ajax.php?c=internal&a=showAttendantsTab&point={$point}&context={$page_context}&context_id={$page_context_id}{/devblocks_url}">Virtual Attendants</a></li>
		{/if}
		
		{$tabs[] = 'links'}
		<li><a href="{devblocks_url}ajax.php?c=internal&a=showTabContextLinks&context={$page_context}&point={$point}&id={$page_context_id}{/devblocks_url}">Watchlist <div class="tab-badge">{DAO_ContextLink::count($page_context, $page_context_id)|default:0}</div></a></li>
		
		{if $active_worker->is_superuser || $worker->id == $active_worker->id}
		{$tabs[] = 'snippets'}
		<li><a href="{devblocks_url}ajax.php?c=internal&a=showTabSnippets&point={$point}&context={$page_context}&context_id={$page_context_id}{/devblocks_url}">{'common.snippets'|devblocks_translate|capitalize}</a></li>
		{/if}
		
		{foreach from=$tab_manifests item=tab_manifest}
			{$tabs[] = $tab_manifest->params.uri}
			<li><a href="{devblocks_url}ajax.php?c=profiles&a=showTab&ext_id={$tab_manifest->id}&point={$point}&context={$page_context}&context_id={$page_context_id}{/devblocks_url}"><i>{$tab_manifest->params.title|devblocks_translate}</i></a></li>
		{/foreach}
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
	
	{include file="devblocks:cerberusweb.core::internal/macros/display/menu_script.tpl" selector_button=null selector_menu=null}
</script>

{$profile_scripts = Extension_ContextProfileScript::getExtensions(true, $page_context)}
{if !empty($profile_scripts)}
{foreach from=$profile_scripts item=renderer}
	{if method_exists($renderer,'renderScript')}
		{$renderer->renderScript($page_context, $page_context_id)}
	{/if}
{/foreach}
{/if}
