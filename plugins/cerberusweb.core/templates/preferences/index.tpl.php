<div id="headerSubMenu">
	<div style="padding-bottom:5px;">
	{*
	<div style="padding:5px;">
	<a href="{devblocks_url}c=tickets&a=overview{/devblocks_url}">overview</a>
	*} 
	</div>
</div> 

<h1>My Account</h1>

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

<div id="prefOptions"></div> 
<br>

<script type="text/javascript" language="javascript">
{literal}
var tabView = new YAHOO.widget.TabView();

tabView.addTab( new YAHOO.widget.Tab({
    label: 'General',
    dataSrc: '{/literal}{devblocks_url}ajax.php?c=preferences&a=showGeneral{/devblocks_url}{literal}',
    {/literal}{if empty($tab) || $tab=='general'}active: true,{/if}{literal}
    cacheData: false
}));

tabView.addTab( new YAHOO.widget.Tab({
    label: 'RSS Notifications',
    dataSrc: '{/literal}{devblocks_url}ajax.php?c=preferences&a=showRss{/devblocks_url}{literal}',
    {/literal}{if $tab=='rss'}active: true,{/if}{literal}
    cacheData: false
}));
{/literal}

{foreach from=$tab_manifests item=tab_manifest}
{literal}tabView.addTab( new YAHOO.widget.Tab({{/literal}
    label: '{$tab_manifest->params.title}',
    dataSrc: '{devblocks_url}ajax.php?c=preferences&a=showTab&ext_id={$tab_manifest->id}{/devblocks_url}',
    {if $tab==$tab_manifest->params.uri}active: true,{/if}
    cacheData: false
{literal}}));{/literal}
{/foreach}

tabView.appendTo('prefOptions');
</script>
