<div id="portal{$portal->id}ConfigTabs">
	<ul>
		{foreach from=$config_tabs item=config_tab_label key=config_tab_id}
		<li><a href="c=profiles&a=invoke&module=community_portal&action=showConfigTab&config_tab={$config_tab_id}&portal_id={$portal->id}">{$config_tab_label}</a></li>
		{/foreach}
	</ul>
</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	$("#portal{$portal->id}ConfigTabs > ul").each(function() { if(window.CerbUI && CerbUI.Tabs) new CerbUI.Tabs(this); });
});
</script>