<ul class="submenu">
</ul>
<div style="clear:both;"></div>

<h2>{'header.preferences'|devblocks_translate|capitalize}</h2>

{if is_array($pref_errors) && !empty($pref_errors)}
	<div class="ui-widget">
		<div class="ui-state-error ui-corner-all" style="padding: 0.7em; margin:0.2em; ">
		<span style="float:right;">(<a href="javascript:;" onclick="$(this).closest('.ui-widget').fadeOut();">dismiss</a>)</span>
		{foreach from=$pref_errors item=error}
			<span class="ui-icon ui-icon-alert" style="float: left; margin-right: .3em;"></span> {$error}<br>
		{/foreach}
		</div>
	</div>
{elseif is_array($pref_success) && !empty($pref_success)}
	<div class="ui-widget">
		<div class="ui-state-highlight ui-corner-all" style="padding: 0.7em; margin:0.2em; ">
		<span style="float:right;">(<a href="javascript:;" onclick="$(this).closest('.ui-widget').fadeOut();">dismiss</a>)</span>
		{foreach from=$pref_success item=success}
			<span class="ui-icon ui-icon-info" style="float: left; margin-right: .3em;"></span> {$success}<br>
		{/foreach}
		</div>
	</div>
{/if}

<div id="prefTabs">
	<ul>
		{$tabs = [general,security,sessions,watcher]}
		{$point = Extension_PreferenceTab::POINT}

		<li><a href="{devblocks_url}ajax.php?c=preferences&a=showGeneralTab{/devblocks_url}">{'common.settings'|devblocks_translate|capitalize}</a></li>
		<li><a href="{devblocks_url}ajax.php?c=preferences&a=showSecurityTab{/devblocks_url}">{'common.security'|devblocks_translate|capitalize}</a></li>
		<li><a href="{devblocks_url}ajax.php?c=preferences&a=showSessionsTab{/devblocks_url}">{'common.sessions'|devblocks_translate|capitalize}</a></li>
		<li><a href="{devblocks_url}ajax.php?c=preferences&a=showWatcherTab{/devblocks_url}">{'common.notifications'|devblocks_translate|capitalize}</a></li>

		{foreach from=$tab_manifests item=tab_manifest}
			{$tabs[] = $tab_manifest->params.uri}
			<li><a href="{devblocks_url}ajax.php?c=preferences&a=showTab&ext_id={$tab_manifest->id}{/devblocks_url}">{$tab_manifest->params.title|devblocks_translate}</a></li>
		{/foreach}
	</ul>
</div>
<br>

<script type="text/javascript">
$(function() {
	var tabOptions = Devblocks.getDefaultjQueryUiTabOptions();
	tabOptions.active = Devblocks.getjQueryUiTabSelected('prefTabs');
	
	var tabs = $("#prefTabs").tabs(tabOptions);
});
</script>
