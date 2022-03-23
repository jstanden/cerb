<h2>{'common.custom_fields'|devblocks_translate|capitalize}</h2>

<div id="cfTabs">
	<ul>
		{$tabs = [fields,fieldsets]}
		{$point = 'setup.fields.tab'}
		
		<li>
			<a href="{devblocks_url}ajax.php?c=config&a=invoke&module=fields&action=showFieldsTab&point={$point}{/devblocks_url}">{'common.records'|devblocks_translate|capitalize}</a>
		</li>
		<li>
			<a href="{devblocks_url}ajax.php?c=config&a=invoke&module=fields&action=showFieldsetsTab&point={$point}{/devblocks_url}">{'common.fieldsets'|devblocks_translate|capitalize}</a>
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
