{include file="devblocks:cerberusweb.feed_reader::feeds/item/display/submenu.tpl"}

<h2>{'feeds.item'|devblocks_translate|capitalize}</h2>

<fieldset class="properties">
	<legend>{$item->title}</legend>
	
	<form action="{devblocks_url}{/devblocks_url}" method="post" style="margin-bottom:5px;">

		<div style="margin-bottom:0.25em;">
			<b>{'common.url'|devblocks_translate}:</b>
			<a href="{$item->url}" target="_blank">{$item->url}</a>
		</div>

		{foreach from=$properties item=v key=k name=props}
			<div class="property">
				{if $k == 'feed'}
					<b>{$v.label|capitalize}:</b>
					{$v.feed->name}
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
		{$object_watchers = DAO_ContextLink::getContextLinks('cerberusweb.contexts.feed.item', array($item->id), CerberusContexts::CONTEXT_WORKER)}
		{include file="devblocks:cerberusweb.core::internal/watchers/context_follow_button.tpl" context='cerberusweb.contexts.feed.item' context_id=$item->id full=true}
		</span>		
		
		<button type="button" id="btnDisplayFeedItemEdit"><span class="cerb-sprite sprite-document_edit"></span> Edit</button>
		
		{$toolbar_exts = DevblocksPlatform::getExtensions('cerberusweb.feed_reader.item.toolbaritem', true)}
		{foreach from=$toolbar_exts item=ext}
			{$ext->render($opp)}
		{/foreach}
	</form>
	
	{if $pref_keyboard_shortcuts}
	<small>
		{$translate->_('common.keyboard')|lower}:
		(<b>e</b>) {'common.edit'|devblocks_translate|lower}
		(<b>1-9</b>) change tab
	</small> 
	{/if}
</fieldset>

<div id="feedItemTabs">
	<ul>
		{$tabs = [activity,notes,links]}
		
		<li><a href="{devblocks_url}ajax.php?c=internal&a=showTabActivityLog&scope=target&point={$point}&context=cerberusweb.contexts.feed.item&context_id={$item->id}{/devblocks_url}">{'common.activity_log'|devblocks_translate|capitalize}</a></li>
		<li><a href="{devblocks_url}ajax.php?c=internal&a=showTabContextComments&context=cerberusweb.contexts.feed.item&id={$item->id}{/devblocks_url}">{$translate->_('common.comments')|capitalize}</a></li>		
		<li><a href="{devblocks_url}ajax.php?c=internal&a=showTabContextLinks&context=cerberusweb.contexts.feed.item&id={$item->id}{/devblocks_url}">{$translate->_('common.links')}</a></li>		

		{foreach from=$tab_manifests item=tab_manifest}
			{$tabs[] = $tab_manifest->params.uri}
			<li><a href="{devblocks_url}ajax.php?c=feeds&a=showTab&ext_id={$tab_manifest->id}{/devblocks_url}"><i>{$tab_manifest->params.title|devblocks_translate}</i></a></li>
		{/foreach}
	</ul>
</div> 
<br>

{$tab_selected_idx=0}
{foreach from=$tabs item=tab_label name=tabs}
	{if $tab_label==$tab_selected}{$tab_selected_idx = $smarty.foreach.tabs.index}{/if}
{/foreach}

<script type="text/javascript">
	$(function() {
		var tabs = $("#feedItemTabs").tabs( { selected:{$tab_selected_idx} } );
		
		$('#btnDisplayFeedItemEdit').bind('click', function() {
			$popup = genericAjaxPopup('peek','c=feeds&a=showFeedItemPopup&id={$item->id}',null,false,'550');
			$popup.one('feeditem_save', function(event) {
				event.stopPropagation();
				document.location.href = '{devblocks_url}c=feeds&i=item&id={$item->id}{/devblocks_url}';
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
		case 49:  // (1) tab cycle
		case 50:  // (2) tab cycle
		case 51:  // (3) tab cycle
		case 52:  // (4) tab cycle
		case 53:  // (5) tab cycle
		case 54:  // (6) tab cycle
		case 55:  // (7) tab cycle
		case 56:  // (8) tab cycle
		case 57:  // (9) tab cycle
		case 58:  // (0) tab cycle
			try {
				idx = event.which-49;
				$tabs = $("#feedItemTabs").tabs();
				$tabs.tabs('select', idx);
			} catch(ex) { } 
			break;
		case 101:  // (E) edit
			try {
				$('#btnDisplayFeedItemEdit').click();
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