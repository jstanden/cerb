<h2>Scheduled Behavior</h2>

<div id="setupVaSchBehTabs">
	<ul>
		{$tabs = []}
		{$point = 'setup.scheduled_behavior.tab'}
		
		{$tabs[] = 'behavior'}
		<li><a href="{devblocks_url}ajax.php?c=internal&a=showScheduledBehaviorTab&point={$point}{/devblocks_url}">All Scheduled Behavior</a></li>
	</ul>
</div> 
<br>

{$selected_tab_idx=0}
{foreach from=$tabs item=tab_label name=tabs}
	{if $tab_label==$selected_tab}{$selected_tab_idx = $smarty.foreach.tabs.index}{/if}
{/foreach}

<script type="text/javascript">
$(function() {
	$tabs = $("#setupVaSchBehTabs");
	var tabs = $tabs.tabs({ 
		selected:{$selected_tab_idx}
	});
});
</script>
