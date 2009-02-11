<div id="headerSubMenu">
	<div style="padding-bottom:5px;">
	</div>
</div>

<form action="{devblocks_url}{/devblocks_url}" method="POST" style="margin-bottom:5px;">
<button type="button" onclick="genericAjaxPanel('c=home&a=showAddWorkspacePanel',this,false,'550px');"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/document_plain_new.png{/devblocks_url}" align="top"> {$translate->_('dashboard.add_view')|capitalize}</button>
<button type="button" onclick="autoRefreshTimer.start('{devblocks_url full=true}c=home{/devblocks_url}',this.form.reloadSecs.value);"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/refresh.gif{/devblocks_url}" align="top"> Auto-Refresh</button><!-- 
--><select name="reloadSecs">
	<option value="600">10m</option>
	<option value="300" selected="selected">5m</option>
	<option value="240">4m</option>
	<option value="180">3m</option>
	<option value="120">2m</option>
	<option value="60">1m</option>
	<option value="30">30s</option>
	<option value="10">10s</option>
</select>
</form>

<div id="homeOptions"></div> 
<br>

<script type="text/javascript">
var tabView = new YAHOO.widget.TabView();

{literal}
tabView.addTab( new YAHOO.widget.Tab({
    label: '{/literal}{'home.tab.my_notifications'|devblocks_translate|escape:'quotes'}{literal}',
    dataSrc: '{/literal}{devblocks_url}ajax.php?c=home&a=showMyEvents{/devblocks_url}{literal}',
    cacheData: false,
    {/literal}active: {if empty($selected_tab) || 'events'==$selected_tab}true{else}false{/if}{literal}
}));
{/literal}

{if empty($workspaces)}
{literal}
tabView.addTab( new YAHOO.widget.Tab({
    label: '{/literal}{'home.tab.workspaces_intro'|devblocks_translate|escape:'quotes'}{literal}',
    dataSrc: '{/literal}{devblocks_url}ajax.php?c=home&a=showWorkspacesIntroTab{/devblocks_url}{literal}',
    cacheData: false,
    active: false
}));
{/literal}
{/if}

{foreach from=$tab_manifests item=tab_manifest}
{literal}tabView.addTab( new YAHOO.widget.Tab({{/literal}
    label: '{$tab_manifest->params.title|devblocks_translate|escape:'quotes'}',
    dataSrc: '{devblocks_url}ajax.php?c=home&a=showTab&ext_id={$tab_manifest->id}{/devblocks_url}',
    {if $selected_tab==$tab_manifest->params.uri}active: true,{/if}
    cacheData: false
{literal}}));{/literal}
{/foreach}

{foreach from=$workspaces item=workspace}
{literal}tabView.addTab( new YAHOO.widget.Tab({{/literal}
    label: '<i>{$workspace|escape}</i>',
    dataSrc: '{devblocks_url}ajax.php?c=home&a=showWorkspaceTab&workspace={$workspace|escape:'url'}{/devblocks_url}',
    cacheData: false,
    active:{if substr($selected_tab,2)==$workspace}true{else}false{/if}
{literal}}));{/literal}
{/foreach}

tabView.appendTo('homeOptions');
</script>


