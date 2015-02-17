<h2>Custom Fields</h2>

<div id="cfTabs">
	<ul>
		{$tabs = [fields,fieldsets]}
		{$point = 'setup.fields.tab'}
		
		<li>
			<a href="{devblocks_url}ajax.php?c=config&a=handleSectionAction&section=fields&action=showFieldsTab&point={$point}&request={$response_uri|escape:'url'}{/devblocks_url}">Global</a>
		</li>
		<li>
			<a href="{devblocks_url}ajax.php?c=config&a=handleSectionAction&section=fields&action=showFieldsetsTab&point={$point}&request={$response_uri|escape:'url'}{/devblocks_url}">Fieldsets</a>
		</li>
	</ul>
</div> 
<br>

<script type="text/javascript">
$(function() {
	var tabOptions = Devblocks.getDefaultjQueryUiTabOptions();
	tabOptions.active = Devblocks.getjQueryUiTabSelected('cfTabs');
	
	var tabs = $("#cfTabs").tabs(tabOptions);
});
</script>
