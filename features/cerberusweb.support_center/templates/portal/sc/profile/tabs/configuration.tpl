<div id="portal{$portal->id}ConfigTabs">
	<ul>
		{foreach from=$config_tabs item=config_tab_label key=config_tab_id}
		<li><a href="{devblocks_url}ajax.php?c=profiles&a=handleSectionAction&section=community_portal&action=showConfigTab&config_tab={$config_tab_id}&portal_id={$portal->id}{/devblocks_url}">{$config_tab_label}</a></li>
		{/foreach}
	</ul>
</div>

<script type="text/javascript">
$(function() {
	$("#portal{$portal->id}ConfigTabs").tabs();
});
</script>