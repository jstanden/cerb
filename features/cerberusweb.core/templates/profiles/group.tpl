{$page_context = CerberusContexts::CONTEXT_GROUP}
{$page_context_id = $group->id}

{$members = $group->getMembers()}
{$reply_to = $group->getReplyTo()}

<table cellpadding="0" cellspacing="0" border="0" width="100%">
	<tr>
		<td width="1%" nowrap="nowrap" rowspan="2" valign="top" style="padding-left:10px;">
			<img src="{if $is_ssl}https://secure.{else}http://www.{/if}gravatar.com/avatar/{$reply_to->email|trim|lower|md5}?s=64&d={devblocks_url full=true}c=resource&p=cerberusweb.core&f=images/wgm/gravatar_nouser.jpg{/devblocks_url}" height="64" width="64" border="0" style="margin:0px 5px 5px 0px;">
		</td>
		<td width="98%" valign="top">
			<h1 style="color:rgb(0,120,0);font-weight:bold;font-size:150%;margin:0px;">{$group->name}</h1>
			{$reply_to->email}<br>
		</td>
		<td width="1%" nowrap="nowrap" align="right">
			{$ctx = Extension_DevblocksContext::get($page_context)}
			{include file="devblocks:cerberusweb.core::search/quick_search.tpl" view=$ctx->getSearchView() return_url="{devblocks_url}c=search&context={$ctx->manifest->params.alias}{/devblocks_url}" reset=true}
		</td>
	</tr>
	<tr>
		<td colspan="2">
			{if !empty($members)}
			<ul class="bubbles">
				{$member_count = $members|count}
				<li><span style="font-weight:bold;">{$member_count} {if $member_count==1}member{else}members{/if}</span></li>
			</ul>
			{/if}
		</td>
	</tr>
</table>

<div style="clear:both;"></div>

<form action="javascript:;">
<fieldset class="properties">
	<legend>Group</legend>
	
	{if !empty($properties)}
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
	{/if}
	
	<div style="margin-top:5px;">
		<!-- Macros -->
		{if $active_worker->isGroupManager($group->id) || $active_worker->is_superuser}
			{if !empty($page_context) && !empty($page_context_id) && !empty($macros)}
				{devblocks_url assign=return_url full=true}c=profiles&tab=group&id={$page_context_id}-{$group->name|devblocks_permalink}{/devblocks_url}
				{include file="devblocks:cerberusweb.core::internal/macros/display/button.tpl" context=$page_context context_id=$page_context_id macros=$macros return_url=$return_url}
			{/if}
		{/if}
	
		{if $active_worker->is_superuser}			
			<button type="button" id="btnProfileGroupEdit"><span class="cerb-sprite sprite-document_edit"></span> {'common.edit'|devblocks_translate|capitalize}</button>
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
		{$point = "cerberusweb.profiles.group.{$group->id}"}
		
		{$tabs[] = 'comments'}
		<li><a href="{devblocks_url}ajax.php?c=internal&a=showTabContextComments&context={$page_context}&id={$page_context_id}{/devblocks_url}">{'common.comments'|devblocks_translate|capitalize}</a></li>
		
		{$tabs[] = 'members'}
		<li><a href="#members">Members</a></li>

		{if $active_worker->isGroupManager($group->id) || $active_worker->is_superuser}
		{$tabs[] = 'attendant'}
		<li><a href="{devblocks_url}ajax.php?c=internal&a=showAttendantTab&point={$point}&context={$page_context}&context_id={$page_context_id}{/devblocks_url}">Virtual Attendant</a></li>
		{/if}

		{if $active_worker->isGroupManager($group->id) || $active_worker->is_superuser}
		{$tabs[] = 'behavior'}
		<li><a href="{devblocks_url}ajax.php?c=internal&a=showScheduledBehaviorTab&point={$point}&context={$page_context}&context_id={$page_context_id}{/devblocks_url}">Scheduled Behavior</a></li>
		{/if}

		{if $active_worker->isGroupManager($group->id) || $active_worker->is_superuser}
		{$tabs[] = 'snippets'}
		<li><a href="{devblocks_url}ajax.php?c=internal&a=showTabSnippets&point={$point}&context={$page_context}&context_id={$page_context_id}{/devblocks_url}">{$translate->_('common.snippets')|capitalize}</a></li>
		{/if}

		{* [TODO] Group managers can add, any member can see 
		{if $active_worker->hasPriv('core.home.workspaces')}
			{$enabled_workspaces = DAO_Workspace::getByEndpoint($point, $active_worker)}
			{foreach from=$enabled_workspaces item=enabled_workspace}
				{$tabs[] = 'w_'|cat:$enabled_workspace->id}
				<li><a href="{devblocks_url}ajax.php?c=internal&a=showWorkspaceTab&id={$enabled_workspace->id}&point={$point}&request={$response_uri|escape:'url'}{/devblocks_url}"><i>{$enabled_workspace->name}</i></a></li>
			{/foreach}
			
			{$tabs[] = "+"}
			<li><a href="{devblocks_url}ajax.php?c=internal&a=showAddTab&point={$point}&request={$response_uri|escape:'url'}{/devblocks_url}"><i>+</i></a></li>
		{/if}
		*}
	</ul>
	
	<div id="members">
		{foreach from=$members item=member}
		{if isset($workers.{$member->id})}
			{$worker = $workers.{$member->id}}
			<fieldset>
				<div style="float:left;">
					<img src="{if $is_ssl}https://secure.{else}http://www.{/if}gravatar.com/avatar/{$worker->email|trim|lower|md5}?s=64&d={devblocks_url full=true}c=resource&p=cerberusweb.core&f=images/wgm/gravatar_nouser.jpg{/devblocks_url}" height="64" width="64" border="0" style="margin:0px 5px 5px 0px;">
				</div>
				<div style="float:left;">
					<a href="{devblocks_url}c=profiles&k=worker&id={$worker->id}-{$worker->getName()|devblocks_permalink}{/devblocks_url}" style="color:rgb(0,120,0);font-weight:bold;font-size:150%;margin:0px;">{$worker->getName()}</a><br>
					{if !empty($worker->title)}{$worker->title}<br>{/if}

					{if $member->is_manager}
					<ul class="bubbles">
						<li style="font-weight:bold;">Manager</li>
					</ul>
					{/if}
				</div>
			</fieldset>
		{/if}
		{/foreach}		
	</div>
</div> 

<br>

{$selected_tab_idx=0}
{foreach from=$tabs item=tab_label name=tabs}
	{if $tab_label==$selected_tab}{$selected_tab_idx = $smarty.foreach.tabs.index}{/if}
{/foreach}

<script type="text/javascript">
$(function() {
	var tabs = $("#profileTabs").tabs( { selected:{$selected_tab_idx} } );

	{if $active_worker->is_superuser}
	$('#btnProfileGroupEdit').bind('click', function() {
		$popup = genericAjaxPopup('peek','c=internal&a=showPeekPopup&context={$page_context}&context_id={$page_context_id}',null,false,'550');
		$popup.one('group_save', function(event) {
			event.stopPropagation();
			document.location.href = '{devblocks_url}c=profiles&k=group&id={$group->id}-{$group->name|devblocks_permalink}{/devblocks_url}';
		});
	});
	{/if}
	
	{include file="devblocks:cerberusweb.core::internal/macros/display/menu_script.tpl"}
});
</script>
