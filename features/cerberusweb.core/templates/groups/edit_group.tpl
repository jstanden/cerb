<div id="groupTabs">
	<ul>
		<li><a href="{devblocks_url}ajax.php?c=groups&a=showTabMail&id={$team->id}{/devblocks_url}">{'common.mail'|devblocks_translate|capitalize}</a></li>
		<li><a href="{devblocks_url}ajax.php?c=groups&a=showTabBuckets&id={$team->id}{/devblocks_url}">{'common.buckets'|devblocks_translate|capitalize}</a></li>
		<li><a href="{devblocks_url}ajax.php?c=internal&a=showAssistantTab&point=cerberusweb.page.group&point=cerberusweb.groups.tab&context={CerberusContexts::CONTEXT_GROUP}&context_id={$team->id}{/devblocks_url}">Virtual Attendant</a></li>
		<li><a href="{devblocks_url}ajax.php?c=groups&a=showTabInbox&id={$team->id}{/devblocks_url}">{'common.inbox'|devblocks_translate|capitalize}</a></li>
		<li><a href="{devblocks_url}ajax.php?c=groups&a=showTabMembers&id={$team->id}{/devblocks_url}">{'common.members'|devblocks_translate|capitalize}</a></li>
		<li><a href="{devblocks_url}ajax.php?c=groups&a=showTabFields&id={$team->id}{/devblocks_url}">{'common.custom_fields'|devblocks_translate|capitalize}</a></li>

		{$tabs = [settings,buckets,assistant,inbox,members,fields]}

		{foreach from=$tab_manifests item=tab_manifest}
			{$tabs[] = $tab_manifest->params.uri}
			<li><a href="{devblocks_url}ajax.php?c=config&a=showTab&ext_id={$tab_manifest->id}{/devblocks_url}"><i>{$tab_manifest->params.title|devblocks_translate}</i></a></li>
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
		var tabs = $("#groupTabs").tabs( { selected:{$selected_tab_idx} } );
	});
</script>
