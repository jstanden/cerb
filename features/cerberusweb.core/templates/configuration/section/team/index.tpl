<div class="cerb-ui-header">
	<div>
		<div class="cerb-ui-header--title">{{'common.team'|devblocks_translate|capitalize}}</div>
		<div class="cerb-ui-header--subtitle"></div>
	</div>
</div>

<ul id="tabsSetupTeam">
	<li data-alias="config"><a href="c=config&a=invoke&module=team&action=renderTabConfig">{'common.configure'|devblocks_translate|capitalize}</a></li>
	<li data-alias="roles"><a href="c=config&a=invoke&module=team&action=renderTabRoles">{'common.roles'|devblocks_translate|capitalize}</a></li>
	<li data-alias="groups"><a href="c=config&a=invoke&module=team&action=renderTabGroups">{'common.groups'|devblocks_translate|capitalize}</a></li>
	<li data-alias="workers"><a href="c=config&a=invoke&module=team&action=renderTabWorkers">{'common.workers'|devblocks_translate|capitalize}</a></li>
</ul>

<script nonce="{DevblocksPlatform::getRequestNonce()}">
(function() {
	const ul = document.getElementById('tabsSetupTeam');
	if(!ul || !(window.CerbUI && CerbUI.Tabs)) return;

	// A server-requested tab ({$tab}) wins over the remembered one; otherwise CerbUI.Tabs
	// restores the last-selected tab from localStorage via `remember`.
	let active;
	const requested = '{$tab}';
	if(requested) {
		const li = ul.querySelector('li[data-alias="' + requested + '"]');
		if(li) active = [...ul.querySelectorAll(':scope > li')].indexOf(li);
	}

	new CerbUI.Tabs(ul, { remember: 'tabsSetupTeam', active: active });
})();
</script>