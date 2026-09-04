<div class="cerb-ui-header">
	<div>
		<div class="cerb-ui-header--title">AI Agents</div>
		<div class="cerb-ui-header--subtitle">Configure models, instructions, tools, and knowledge</div>
	</div>
</div>

<ul id="tabsSetupAi">
	<li data-alias="agents"><a href="c=config&a=invoke&module=ai&action=renderTabAgents">Agents</a></li>
	<li data-alias="models"><a href="c=config&a=invoke&module=ai&action=renderTabModels">Models</a></li>
	<li data-alias="tools"><a href="c=config&a=invoke&module=ai&action=renderTabTools">Tools</a></li>
	<li data-alias="filesystems"><a href="c=config&a=invoke&module=ai&action=renderTabFilesystems">Filesystems</a></li>
	<li data-alias="files"><a href="c=config&a=invoke&module=ai&action=renderTabFiles">Files</a></li>
	<li data-alias="surfaces"><a href="c=config&a=invoke&module=ai&action=renderTabSurfaces">Surfaces</a></li>
</ul>

<script nonce="{DevblocksPlatform::getRequestNonce()}">
(function() {
	const ul = document.getElementById('tabsSetupAi');
	if(!ul || !(window.CerbUI && CerbUI.Tabs)) return;

	// A server-requested tab ({$tab}) wins over the remembered one; otherwise CerbUI.Tabs
	// restores the last-selected tab from localStorage via `remember`.
	let active;
	const requested = '{$tab}';
	if(requested) {
		const li = ul.querySelector('li[data-alias="' + requested + '"]');
		if(li) active = [...ul.querySelectorAll(':scope > li')].indexOf(li);
	}

	new CerbUI.Tabs(ul, { remember: 'tabsSetupAi', active: active });
})();
</script>
