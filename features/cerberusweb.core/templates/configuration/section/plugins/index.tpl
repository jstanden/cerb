<h2>Plugins</h2>

<div id="pluginTabs">
	<ul>
		{$tabs = [installed,library]}
		{$point = 'setup.plugins.tab'}
		
		<li><a href="{devblocks_url}ajax.php?c=config&a=handleSectionAction&section=plugins&action=showTab&point={$point}&request={$response_uri|escape:'url'}{/devblocks_url}">Installed Plugins</a></li>
		<li><a href="{devblocks_url}ajax.php?c=config&a=handleSectionAction&section=plugin_library&action=showTab&point={$point}&request={$response_uri|escape:'url'}{/devblocks_url}">Plugin Library</a></li>
	</ul>
</div> 
<br>

<script type="text/javascript">
$(function() {
	var tabOptions = Devblocks.getDefaultjQueryUiTabOptions();
	tabOptions.active = Devblocks.getjQueryUiTabSelected('pluginTabs');
	
	var tabs = $("#pluginTabs").tabs(tabOptions);
});
</script>

