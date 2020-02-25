<div id="worker{$worker->id}SettingsTabs">
	<ul>
		{$tabs = []}
		
		{$list = ['profile','pages','localization','search','mail','records','availability','security','sessions','watchers']}

		{foreach from=$list item=tab_name}
		{$tabs[] = $tab_name}
		<li data-alias="{$tab_name}"><a href="{devblocks_url}ajax.php?c=profiles&a=invokeTab&tab_id={$tab->id}&action=showSettingsSectionTab&worker_id={$worker->id}&tab={$tab_name}{/devblocks_url}">{$tab_name|devblocks_translate|capitalize}</a></li>
		{/foreach}
	</ul>
</div>

<script type="text/javascript">
$(function() {
	$('#worker{$worker->id}SettingsTabs').tabs();
});
</script>