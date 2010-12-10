<ul class="submenu">
	<li><a href="{devblocks_url}c=config&a=communities{/devblocks_url}">{'community portals'|devblocks_translate|lower}</a></li>
</ul>
<div style="clear:both;"></div>

{if !empty($tool->name)}
	<h1>{$tool->name}</h1>
{else}
	{$tool_extid = $tool->extension_id}
	{if isset($tool_manifests.$tool_extid)}
		<h1>{$tool_manifests.$tool_extid->name}</h1>
	{else}
		<h1>Community Portal</h1>
	{/if}
{/if}

{$translate->_('usermeet.ui.community.cfg.profile_id')} <b>{$tool->code}</b><br>
<br>

<div id="communityToolTabs">
	<ul>
		<li><a href="{devblocks_url}ajax.php?c=community&a=showTabSettings&id={$tool->id}{/devblocks_url}">{'Settings'|devblocks_translate}</a></li>
		<li><a href="{devblocks_url}ajax.php?c=community&a=showTabTemplates&id={$tool->id}{/devblocks_url}">{'Custom Templates'|devblocks_translate}</a></li>
		<li><a href="{devblocks_url}ajax.php?c=community&a=showTabInstallation&id={$tool->id}{/devblocks_url}">{'Installation'|devblocks_translate}</a></li>

		{$tabs = [settings,templates,installation]}
	</ul>
</div> 
<br>

{$tab_selected_idx=0}
{foreach from=$tabs item=tab_label name=tabs}
	{if $tab_label==$tab_selected}{$tab_selected_idx = $smarty.foreach.tabs.index}{/if}
{/foreach}

<script type="text/javascript">
	$(function() {
		var tabs = $("#communityToolTabs").tabs( { selected:{$tab_selected_idx} } );
	});
</script>
