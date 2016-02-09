<h2>Plugins</h2>

<div id="pluginTabs">
	<ul>
		{$tabs = [installed]}
		{$point = 'setup.plugins.tab'}
		
		<li data-alias="installed"><a href="{devblocks_url}ajax.php?c=config&a=handleSectionAction&section=plugins&action=showTab&point={$point}&request={$response_uri|escape:'url'}{/devblocks_url}">Installed Plugins</a></li>
		
		{if $smarty.const.CERB_FEATURES_PLUGIN_LIBRARY}
			{$tabs[] = 'plugin'}
			<li data-alias="library"><a href="{devblocks_url}ajax.php?c=config&a=handleSectionAction&section=plugin_library&action=showTab&point={$point}&request={$response_uri|escape:'url'}{/devblocks_url}">Plugin Library</a></li>
		{/if}
	</ul>
</div> 
<br>

<script type="text/javascript">
$(function() {
	var tabOptions = Devblocks.getDefaultjQueryUiTabOptions();
	tabOptions.active = Devblocks.getjQueryUiTabSelected('pluginTabs', '{$tab}');
	
	var tabs = $("#pluginTabs").tabs(tabOptions);
});
</script>
