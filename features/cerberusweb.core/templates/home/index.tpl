<div id="headerSubMenu">
	<div style="padding-bottom:5px;">
	</div>
</div>

<form action="{devblocks_url}{/devblocks_url}" method="POST" style="margin-bottom:5px;">
{if $active_worker->hasPriv('core.home.workspaces')}<button type="button" onclick="genericAjaxPanel('c=home&a=showAddWorkspacePanel',null,false,'550');"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/document_plain_new.png{/devblocks_url}" align="top"> {$translate->_('dashboard.add_view')|capitalize}</button>{/if}
{if $active_worker->hasPriv('core.home.auto_refresh')}<button type="button" onclick="autoRefreshTimer.start('{devblocks_url full=true}c=home{/devblocks_url}',this.form.reloadSecs.value);"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/refresh.gif{/devblocks_url}" align="top"> {'common.refresh.auto'|devblocks_translate|capitalize}</button><!-- 
--><select name="reloadSecs">
	<option value="600">{'common.time.mins.num'|devblocks_translate:'10'}</option>
	<option value="300" selected="selected">{'common.time.mins.num'|devblocks_translate:'5'}</option>
	<option value="240">{'common.time.mins.num'|devblocks_translate:'4'}</option>
	<option value="180">{'common.time.mins.num'|devblocks_translate:'3'}</option>
	<option value="120">{'common.time.mins.num'|devblocks_translate:'2'}</option>
	<option value="60">{'common.time.mins.num'|devblocks_translate:'1'}</option>
	<option value="30">{'common.time.secs.num'|devblocks_translate:'30'}</option>
</select>{/if}
</form>

<div id="homeTabs">
	<ul>
		{$tabs = [events]}

		<li><a href="{devblocks_url}ajax.php?c=home&a=showMyEvents{/devblocks_url}">{'home.tab.my_notifications'|devblocks_translate|escape:'quotes'}</a></li>

		{if empty($workspaces) && $active_worker->hasPriv('core.home.workspaces')}
			{$tabs[] = intro}		
			<li><a href="{devblocks_url}ajax.php?c=home&a=showWorkspacesIntroTab{/devblocks_url}">{'home.tab.workspaces_intro'|devblocks_translate|escape:'quotes'}</a></li>
		{/if}

		{foreach from=$tab_manifests item=tab_manifest}
			{$tabs[] = $tab_manifest->params.uri}
			<li><a href="{devblocks_url}ajax.php?c=home&a=showTab&ext_id={$tab_manifest->id}{/devblocks_url}">{$tab_manifest->params.title|devblocks_translate|escape:'quotes'}</a></li>
		{/foreach}

		{if $active_worker->hasPriv('core.home.workspaces')}
		{foreach from=$workspaces item=workspace}
			{$tabs[] = "w_{$workspace}"}
			<li><a href="{devblocks_url}ajax.php?c=home&a=showWorkspaceTab&workspace={$workspace|escape:'url'}{/devblocks_url}"><i>{$workspace|escape}</i></a></li>
		{/foreach}
		{/if}
	</ul>
</div> 
<br>

{$tab_selected_idx=0}
{foreach from=$tabs item=tab_label name=tabs}
	{if $tab_label==$selected_tab}{$tab_selected_idx = $smarty.foreach.tabs.index}{/if}
{/foreach}

<script type="text/javascript">
	$(function() {
		var tabs = $("#homeTabs").tabs( { selected:{$tab_selected_idx} } );
	});
</script>
