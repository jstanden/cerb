<ul class="submenu">
	<li><a href="{devblocks_url}c=contacts&a=orgs{/devblocks_url}">{$translate->_('addy_book.tab.organizations')|lower}</a></li>
</ul>
<div style="clear:both;"></div>

<div style="float:left;">
	<h2>{'contact_org.name'|devblocks_translate|capitalize}</h2>
</div>

<div style="float:right;">
<form action="{devblocks_url}{/devblocks_url}" method="post">
<input type="hidden" name="c" value="contacts">
<input type="hidden" name="a" value="doOrgQuickSearch">
<span><b>{$translate->_('common.quick_search')|capitalize}:</b></span> <select name="type">
	<option value="name">{$translate->_('contact_org.name')|capitalize}</option>
	<option value="phone">{$translate->_('contact_org.phone')|capitalize}</option>
</select><input type="text" name="query" class="input_search" size="24"><button type="submit">{$translate->_('common.search_go')|lower}</button>
</form>
</div>

<div style="clear:both;"></div>

<fieldset class="properties">
	<legend>{$contact->name|truncate:128}</legend>
	
	<form action="{devblocks_url}{/devblocks_url}" method="post" style="margin-bottom:5px;">

		{foreach from=$properties item=v key=k name=props}
			<div class="property">
				{if $k == '...'}
					<b>{$translate->_('...')|capitalize}:</b>
					...
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
		<span>
		{$object_watchers = DAO_ContextLink::getContextLinks(CerberusContexts::CONTEXT_ORG, array($contact->id), CerberusContexts::CONTEXT_WORKER)}
		{include file="devblocks:cerberusweb.core::internal/watchers/context_follow_button.tpl" context=CerberusContexts::CONTEXT_ORG context_id=$contact->id full=true}
		</span>		
		
		<button type="button" id="btnDisplayOrgEdit"><span class="cerb-sprite sprite-document_edit"></span> Edit</button>
	
	</form>
	
	{if $pref_keyboard_shortcuts}
	<small>
		{$translate->_('common.keyboard')|lower}:
		(<b>e</b>) {'common.edit'|devblocks_translate|lower}
	</small> 
	{/if}
	
</fieldset>

<div style="clear:both;" id="contactTabs">
	<ul>
		{$tabs = [activity,notes,links,history,people]}
		{$point = ''}
		
		<li><a href="{devblocks_url}ajax.php?c=internal&a=showTabActivityLog&scope=target&point={$point}&context={CerberusContexts::CONTEXT_ORG}&context_id={$contact->id}{/devblocks_url}">{'common.activity_log'|devblocks_translate|capitalize}</a></li>
		<li><a href="{devblocks_url}ajax.php?c=internal&a=showTabContextComments&context=cerberusweb.contexts.org&id={$contact->id}{/devblocks_url}">{$translate->_('common.comments')|capitalize}</a></li>
		<li><a href="{devblocks_url}ajax.php?c=internal&a=showTabContextLinks&context=cerberusweb.contexts.org&id={$contact->id}{/devblocks_url}">{$translate->_('common.links')}</a></li>
		<li><a href="{devblocks_url}ajax.php?c=contacts&a=showTabHistory&org={$contact->id}{/devblocks_url}">{$translate->_('addy_book.org.tabs.mail_history')}</a></li>
		<li><a href="{devblocks_url}ajax.php?c=contacts&a=showTabPeople&org={$contact->id}{/devblocks_url}">{'addy_book.org.tabs.people'|devblocks_translate:$people_total}</a></li>

		{foreach from=$tab_manifests item=tab_manifest}
			{$tabs[] = $tab_manifest->params.uri}
			<li><a href="{devblocks_url}ajax.php?c=contacts&a=showOrgTab&ext_id={$tab_manifest->id}&org_id={$contact->id}{/devblocks_url}"><i>{$tab_manifest->params.title|devblocks_translate}</i></a></li>
		{/foreach}
	</ul>
</div> 
<br>

{$tab_selected_idx=0}
{foreach from=$tabs item=tab_label name=tabs}
	{if $tab_label==$selected_tab}{$tab_selected_idx = $smarty.foreach.tabs.index}{/if}
{/foreach}

<script type="text/javascript">
	$(function() {
		var tabs = $("#contactTabs").tabs( { selected:{$tab_selected_idx} } );
	
		$('#btnDisplayOrgEdit').bind('click', function() {
			$popup = genericAjaxPopup('peek','c=contacts&a=showOrgPeek&id={$contact->id}',null,false,'550');
			$popup.one('org_save', function(event) {
				event.stopPropagation();
				document.location.href = '{devblocks_url}c=contacts&a=orgs&m=display&id={$contact->id}{/devblocks_url}';
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
				$('#btnDisplayOrgEdit').click();
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