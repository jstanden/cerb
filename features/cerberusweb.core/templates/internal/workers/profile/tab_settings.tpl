<div id="worker{$worker->id}SettingsTabs">
	<ul>
		{$tabs = []}
		
		{$list = ['profile','pages','localization','search','availability','mail','security','sessions','watchers']}
		
		{foreach from=$list item=tab}
		{$tabs[] = $tab}
		<li data-alias="{$tab}"><a href="{devblocks_url}ajax.php?c=profiles&a=handleSectionAction&section=worker&action=showSettingsSectionTab&tab={$tab}&worker_id={$worker->id}{/devblocks_url}">{$tab|devblocks_translate|capitalize}</a></li>
		{/foreach}
	</ul>
</div>

<script type="text/javascript">
$(function() {
	var $tabs = $('#worker{$worker->id}SettingsTabs');
	$tabs.tabs();
});
</script>