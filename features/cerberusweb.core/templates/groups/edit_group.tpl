<div id="groupTabs">
	<ul>
		{$tabs = [settings,buckets,attendant,snippets,members,fields]}
		
		<li><a href="{devblocks_url}ajax.php?c=groups&a=showTabMail&id={$group->id}{/devblocks_url}">{'common.mail'|devblocks_translate|capitalize}</a></li>
		<li><a href="{devblocks_url}ajax.php?c=groups&a=showTabBuckets&id={$group->id}{/devblocks_url}">{'common.buckets'|devblocks_translate|capitalize}</a></li>
		<li><a href="{devblocks_url}ajax.php?c=internal&a=showAttendantTab&point=cerberusweb.page.group&context={CerberusContexts::CONTEXT_GROUP}&context_id={$group->id}{/devblocks_url}">Virtual Attendant</a></li>
		<li><a href="{devblocks_url}ajax.php?c=internal&a=showTabSnippets&point={$point}&context={CerberusContexts::CONTEXT_GROUP}&context_id={$group->id}{/devblocks_url}">{$translate->_('common.snippets')|capitalize}</a></li>
		<li><a href="{devblocks_url}ajax.php?c=groups&a=showTabMembers&id={$group->id}{/devblocks_url}">{'common.members'|devblocks_translate|capitalize}</a></li>
		<li><a href="{devblocks_url}ajax.php?c=groups&a=showTabFields&id={$group->id}{/devblocks_url}">{'common.custom_fields'|devblocks_translate|capitalize}</a></li>
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
