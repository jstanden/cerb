{include file="devblocks:cerberusweb.datacenter.domains::domain/display/submenu.tpl"}

<h2>{'cerberusweb.datacenter.domain'|devblocks_translate|capitalize}</h2>

<fieldset class="properties">
	<legend>{$domain->name}</legend>
	
	<form action="{devblocks_url}{/devblocks_url}" method="post" style="margin-bottom:5px;">

		{foreach from=$properties item=v key=k name=props}
			<div class="property">
				{if $k == 'server'}
					<b>{$v.label|capitalize}:</b>
					<a href="javascript:;" onclick="genericAjaxPopup('peek','c=datacenter&a=showServerPeek&view_id=&id={$server->id}', null, false, '500');">{$v.server->name}</a>
				{else}
					{include file="devblocks:cerberusweb.core::internal/custom_fields/profile_cell_renderer.tpl"}
				{/if}
			</div>
			{if $smarty.foreach.props.iteration % 3 == 0 && !$smarty.foreach.props.last}
				<br clear="all">
			{/if}
		{/foreach}
		<br clear="all">
	
		<!-- Toolbar -->
		<div>
			<span>
			{$object_watchers = DAO_ContextLink::getContextLinks('cerberusweb.contexts.datacenter.domain', array($domain->id), CerberusContexts::CONTEXT_WORKER)}
			{include file="devblocks:cerberusweb.core::internal/watchers/context_follow_button.tpl" context='cerberusweb.contexts.datacenter.domain' context_id=$domain->id full=true}
			</span>		
		
			<button type="button" id="btnDatacenterDomainEdit"><span class="cerb-sprite sprite-document_edit"></span> Edit</button>
		</div>
	
	</form>
	
	{if $pref_keyboard_shortcuts}
	<small>
		{$translate->_('common.keyboard')|lower}:
		(<b>e</b>) {'common.edit'|devblocks_translate|lower}
	</small> 
	{/if}
</fieldset>

<div id="datacenterDomainTabs">
	<ul>
		{$tabs = [activity, comments, links]}
		<li><a href="{devblocks_url}ajax.php?c=internal&a=showTabActivityLog&scope=target&point={$point}&context=cerberusweb.contexts.datacenter.domain&context_id={$domain->id}{/devblocks_url}">{'common.activity_log'|devblocks_translate|capitalize}</a></li>		
		<li><a href="{devblocks_url}ajax.php?c=internal&a=showTabContextComments&context=cerberusweb.contexts.datacenter.domain&id={$domain->id}{/devblocks_url}">{'common.comments'|devblocks_translate|capitalize}</a></li>
		<li><a href="{devblocks_url}ajax.php?c=internal&a=showTabContextLinks&context=cerberusweb.contexts.datacenter.domain&id={$domain->id}{/devblocks_url}">{'common.links'|devblocks_translate}</a></li>
	</ul>
</div> 
<br>

{$tab_selected_idx=0}
{foreach from=$tabs item=tab_label name=tabs}
	{if $tab_label==$tab_selected}{$tab_selected_idx = $smarty.foreach.tabs.index}{/if}
{/foreach}

<script type="text/javascript">
	$(function() {
		var tabs = $("#datacenterDomainTabs").tabs( { selected:{$tab_selected_idx} } );
		
		$('#btnDatacenterDomainEdit').bind('click', function() {
			$popup = genericAjaxPopup('peek','c=datacenter.domains&a=showDomainPeek&id={$domain->id}',null,false,'550');
			$popup.one('datacenter_domain_save', function(event) {
				event.stopPropagation();
				document.location.href = '{devblocks_url}c=datacenter.domains&a=domain&id={$domain->id}{/devblocks_url}';
			});
		})
	});
</script>

<script type="text/javascript">
{if $pref_keyboard_shortcuts}
$(document).keypress(function(event) {
	if(event.altKey || event.ctrlKey || event.shiftKey || event.metaKey)
		return;
	
	if($(event.target).is(':input'))
		return;

	hotkey_activated = true;
	
	switch(event.which) {
		case 101:  // (E) edit
			try {
				$('#btnDatacenterDomainEdit').click();
			} catch(ex) { } 
			break;
		default:
			// We didn't find any obvious keys, try other codes
			hotkey_activated = false;
			break;
	}
	
	if(hotkey_activated)
		event.preventDefault();
});
{/if}
</script>
