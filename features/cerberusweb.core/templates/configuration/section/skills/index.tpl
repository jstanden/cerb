<h2>{'common.skills'|devblocks_translate|capitalize}</h2>

<div id="skillsTabs">
	<ul>
		{$tabs = [skillsets,skills]}
		{$point = 'setup.plugins.tab'}
		
		<li data-alias="skillsets"><a href="{devblocks_url}ajax.php?c=config&a=handleSectionAction&section=skills&action=showSkillsetsTab&point={$point}&request={$response_uri|escape:'url'}{/devblocks_url}">{'common.skillsets'|devblocks_translate|capitalize}</a></li>
		<li data-alias="skills"><a href="{devblocks_url}ajax.php?c=config&a=handleSectionAction&section=skills&action=showSkillsTab&point={$point}&request={$response_uri|escape:'url'}{/devblocks_url}">{'common.skills'|devblocks_translate|capitalize}</a></li>
	</ul>
</div> 
<br>

<script type="text/javascript">
$(function() {
	var tabOptions = Devblocks.getDefaultjQueryUiTabOptions();
	tabOptions.active = Devblocks.getjQueryUiTabSelected('skillsTabs', '{$tab}');
	
	var tabs = $("#skillsTabs").tabs(tabOptions);
});
</script>

