<h2 style="margin:0;">Community Portal</h2>

<div class="cerb-properties" style="margin-bottom:10px;">
	{if !empty($tool->name)}
		<div>
			<label>{'common.name'|devblocks_translate|capitalize}:</label> {$tool->name}
		</div>
	{/if}
	
	{$tool_extid = $tool->extension_id}
	{if isset($tool_manifests.$tool_extid)}
		<div>
			<label>{'common.extension'|devblocks_translate|capitalize}:</label> {$tool_manifests.$tool_extid->name}
		</div>
	{/if}
	
	<div>
		<label>{'portal.cfg.profile_id'|devblocks_translate}</label> {$tool->code}
	</div>
</div>

<div id="communityToolTabs">
	<ul>
		<li><a href="{devblocks_url}ajax.php?c=config&a=handleSectionAction&section=portal&action=showTabSettings&id={$tool->id}{/devblocks_url}">{'Settings'|devblocks_translate}</a></li>
		<li><a href="{devblocks_url}ajax.php?c=config&a=handleSectionAction&section=portal&action=showTabTemplates&id={$tool->id}{/devblocks_url}">{'Custom Templates'|devblocks_translate}</a></li>
		<li><a href="{devblocks_url}ajax.php?c=config&a=handleSectionAction&section=portal&action=showTabInstallation&id={$tool->id}{/devblocks_url}">{'portal.cfg.installation'|devblocks_translate}</a></li>
		
		{$tabs = [settings,templates,installation]}
	</ul>
</div> 
<br>

<script type="text/javascript">
$(function() {
	var tabOptions = Devblocks.getDefaultjQueryUiTabOptions();
	tabOptions.active = Devblocks.getjQueryUiTabSelected('communityToolTabs');
	
	var tabs = $("#communityToolTabs").tabs(tabOptions);
});
</script>