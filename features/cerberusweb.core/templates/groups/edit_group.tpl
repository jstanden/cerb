<div id="groupTabs">
	<ul>
		{$tabs = [settings,buckets,members]}
		{$point = 'cerberusweb.groups.tab'}
		
		<li><a href="{devblocks_url}ajax.php?c=groups&a=showTabMail&id={$group->id}{/devblocks_url}">{'common.mail'|devblocks_translate|capitalize}</a></li>
		<li><a href="{devblocks_url}ajax.php?c=groups&a=showTabBuckets&id={$group->id}{/devblocks_url}">{'common.buckets'|devblocks_translate|capitalize}</a></li>
		<li><a href="{devblocks_url}ajax.php?c=groups&a=showTabMembers&id={$group->id}{/devblocks_url}">{'common.members'|devblocks_translate|capitalize}</a></li>
		
		{if $active_worker->isGroupManager($group->id) || $active_worker->is_superuser}
		{$tabs[] = 'custom_fieldsets'}
		<li><a href="{devblocks_url}ajax.php?c=internal&a=handleSectionAction&section=custom_fieldsets&action=showTabCustomFieldsets&context={CerberusContexts::CONTEXT_GROUP}&context_id={$group->id}&point={$point}{/devblocks_url}">{$translate->_('common.custom_fields')|capitalize}</a></li>
		{/if}
		
		{if $active_worker->isGroupManager($group->id) || $active_worker->is_superuser}
		{$tabs[] = 'attendant'}
		<li><a href="{devblocks_url}ajax.php?c=internal&a=showAttendantTab&context={CerberusContexts::CONTEXT_GROUP}&context_id={$group->id}&point={$point}{/devblocks_url}">Virtual Attendant</a></li>
		{/if}

		{if $active_worker->isGroupManager($group->id) || $active_worker->is_superuser}
		{$tabs[] = 'behavior'}
		<li><a href="{devblocks_url}ajax.php?c=internal&a=showScheduledBehaviorTab&context={CerberusContexts::CONTEXT_GROUP}&context_id={$group->id}&point={$point}{/devblocks_url}">Scheduled Behavior</a></li>
		{/if}

		{if $active_worker->isGroupManager($group->id) || $active_worker->is_superuser}
		{$tabs[] = 'snippets'}
		<li><a href="{devblocks_url}ajax.php?c=internal&a=showTabSnippets&context={CerberusContexts::CONTEXT_GROUP}&context_id={$group->id}&point={$point}{/devblocks_url}">{$translate->_('common.snippets')|capitalize}</a></li>
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
		var tabs = $("#groupTabs").tabs( { selected:{$selected_tab_idx} } );
	});
</script>
