<div id="headerSubMenu">
	<div style="padding-bottom:5px;">
	</div>
</div> 

<h1>{$translate->_('header.preferences')|capitalize}</h1>

{if is_array($pref_errors) && !empty($pref_errors)}
	<div class="error">
		<ul style="margin:2px;">
		{foreach from=$pref_errors item=error}
			<li>{$error}</li>
		{/foreach}
		</ul>
	</div>
{elseif is_array($pref_success) && !empty($pref_success)}
	<div class="success">
		<ul style="margin:2px;">
		{foreach from=$pref_success item=success}
			<li>{$success}</li>
		{/foreach}
		</ul>
	</div>
{else}
	<br>
{/if}

<div id="prefTabs">
	<ul>
		<li><a href="{devblocks_url}ajax.php?c=preferences&a=showGeneral{/devblocks_url}">General</a></li>
		<li><a href="{devblocks_url}ajax.php?c=preferences&a=showRss{/devblocks_url}">RSS Notifications</a></li>

		{$tabs = [general,rss]}

		{foreach from=$tab_manifests item=tab_manifest}
			{$tabs[] = $tab_manifest->params.uri}
			<li><a href="{devblocks_url}ajax.php?c=preferences&a=showTab&ext_id={$tab_manifest->id}{/devblocks_url}"><i>{$tab_manifest->params.title|devblocks_translate|escape:'quotes'}</i></a></li>
		{/foreach}
	</ul>
</div> 
<br>

{$tab_selected_idx=0}
{foreach from=$tabs item=tab_label name=tabs}
	{if $tab_label==$tab}{$tab_selected_idx = $smarty.foreach.tabs.index}{/if}
{/foreach}

<script type="text/javascript">
	$(function() {
		var tabs = $("#prefTabs").tabs( { selected:{$tab_selected_idx} } );
	});
</script>
