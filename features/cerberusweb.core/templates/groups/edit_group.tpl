<h2>{$team->name|escape}</h2>

<div id="groupTabs">
	<ul>
		<li><a href="{devblocks_url}ajax.php?c=groups&a=showTabBuckets&id={$team->id}{/devblocks_url}">Workflow</a></li>
		<li><a href="{devblocks_url}ajax.php?c=groups&a=showTabMail&id={$team->id}{/devblocks_url}">Mail Preferences</a></li>
		<li><a href="{devblocks_url}ajax.php?c=groups&a=showTabInbox&id={$team->id}{/devblocks_url}">Inbox Routing</a></li>
		<li><a href="{devblocks_url}ajax.php?c=groups&a=showTabMembers&id={$team->id}{/devblocks_url}">Members</a></li>
		<li><a href="{devblocks_url}ajax.php?c=groups&a=showTabFields&id={$team->id}{/devblocks_url}">Ticket Fields</a></li>

		{$tabs = [buckets,settings,inbox,members,fields]}

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
		var tabs = $("#groupTabs").tabs( { selected:{$tab_selected_idx} } );
	});
</script>
