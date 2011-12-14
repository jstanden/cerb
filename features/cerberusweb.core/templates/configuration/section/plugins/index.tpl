<h2>Manage Plugins</h2>

<div id="pluginTabs">
	<ul>
		{$tabs = []}
		{$point = 'setup.plugins.tab'}
		
		<li><a href="{devblocks_url}ajax.php?c=config&a=handleSectionAction&section=plugins&action=showTab&point={$point}&request={$response_uri|escape:'url'}{/devblocks_url}">Installed Plugins</a></li>
		<li><a href="{devblocks_url}ajax.php?c=config&a=handleSectionAction&section=plugin_library&action=showTab&point={$point}&request={$response_uri|escape:'url'}{/devblocks_url}">Plugin Library</a></li>
	</ul>
</div> 
<br>

{$selected_tab_idx=0}
{foreach from=$tabs item=tab_label name=tabs}
	{if $tab_label==$selected_tab}{$selected_tab_idx = $smarty.foreach.tabs.index}{/if}
{/foreach}

<script type="text/javascript">
	$(function() {
		var tabs = $("#pluginTabs").tabs( { selected:{$selected_tab_idx} } );
	});
</script>

