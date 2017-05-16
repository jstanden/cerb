{$page_context = CerberusContexts::CONTEXT_ATTACHMENT}
{$page_context_id = $attachment->id}
{$is_downloadable = Context_Attachment::isDownloadableByActor($attachment, $active_worker)}
{$is_writeable = Context_Attachment::isWriteableByActor($attachment, $active_worker)}

<div style="float:left">
	<h1>{$attachment->name}</h1>
</div>

<div style="float:right;">
{$ctx = Extension_DevblocksContext::get($page_context)}
{include file="devblocks:cerberusweb.core::search/quick_search.tpl" view=$ctx->getSearchView() return_url="{devblocks_url}c=search&context={$ctx->manifest->params.alias}{/devblocks_url}"}
</div>

<div style="clear:both;"></div>

<div class="cerb-profile-toolbar">
	<form class="toolbar" action="{devblocks_url}{/devblocks_url}" onsubmit="return false;" style="margin-bottom:5px;">
		{if $is_downloadable}
			<button type="button" id="btnDisplayAttachmentDownload"><span class="glyphicons glyphicons-cloud-download"></span> {'common.download'|devblocks_translate|capitalize}</button>
		{/if}
		{if $is_writeable}
			<button type="button" id="btnDisplayAttachmentEdit" title="{'common.edit'|devblocks_translate|capitalize} (E)" class="cerb-peek-trigger" data-context="{$page_context}" data-context-id="{$page_context_id}" data-edit="true"><span class="glyphicons glyphicons-cogwheel"></span></button>
		{/if}
	</form>
	
	{if $pref_keyboard_shortcuts}
		<small>
		{$translate->_('common.keyboard')|lower}:
		(<b>e</b>) {'common.edit'|devblocks_translate|lower}
		(<b>1-9</b>) change tab
		</small>
	{/if}
</div>

<fieldset class="properties">
	<legend>{'common.attachment'|devblocks_translate|capitalize}</legend>

	<div style="margin-left:15px;">
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
	
	{if $context_counts}
		<div style="margin-top:5px;">
			{foreach from=$context_counts item=count key=context_ext_id}
				{$context = $contexts.$context_ext_id}
				{if $context}
					<button type="button" class="cerb-search-trigger" data-context="{$context_ext_id}" data-query="attachments:(id:{$page_context_id})"><div class="badge-count">{$count|default:0}</div> {$context->name}</button>
				{/if}
			{/foreach}
		</div>
	{/if}
	</div>
</fieldset>

{include file="devblocks:cerberusweb.core::internal/custom_fieldsets/profile_fieldsets.tpl" properties=$properties_custom_fieldsets}

{include file="devblocks:cerberusweb.core::internal/profiles/profile_record_links.tpl" properties=$properties_links}

<div>
{include file="devblocks:cerberusweb.core::internal/notifications/context_profile.tpl" context=$page_context context_id=$page_context_id}
</div>

<div>
{include file="devblocks:cerberusweb.core::internal/macros/behavior/scheduled_behavior_profile.tpl" context=$page_context context_id=$page_context_id}
</div>

<div id="attachmentTabs">
	<ul>
		{$tabs = [activity,comments]}

		<li><a href="{devblocks_url}ajax.php?c=internal&a=showTabActivityLog&scope=target&point={$point}&context={$page_context}&context_id={$page_context_id}{/devblocks_url}">{'common.log'|devblocks_translate|capitalize}</a></li>
		<li><a href="{devblocks_url}ajax.php?c=internal&a=showTabContextComments&point={$point}&context={$page_context}&id={$page_context_id}{/devblocks_url}">{$translate->_('common.comments')|capitalize} <div class="tab-badge">{DAO_Comment::count($page_context, $page_context_id)|default:0}</div></a></li>

		{foreach from=$tab_manifests item=tab_manifest}
			{$tabs[] = $tab_manifest->params.uri}
			<li><a href="{devblocks_url}ajax.php?c=profiles&a=showTab&ext_id={$tab_manifest->id}&point={$point}&context={$page_context}&context_id={$page_context_id}{/devblocks_url}"><i>{$tab_manifest->params.title|devblocks_translate}</i></a></li>
		{/foreach}
	</ul>
</div>
<br>

<script type="text/javascript">
$(function() {
	var tabOptions = Devblocks.getDefaultjQueryUiTabOptions();
	tabOptions.active = Devblocks.getjQueryUiTabSelected('attachmentTabs');

	var tabs = $("#attachmentTabs").tabs(tabOptions);
	
	// Edit
	
	{if $is_writeable}
	$('#btnDisplayAttachmentEdit')
		.cerbPeekTrigger()
		.on('cerb-peek-opened', function(e) {
		})
		.on('cerb-peek-saved', function(e) {
			e.stopPropagation();
			document.location.reload();
		})
		.on('cerb-peek-deleted', function(e) {
			document.location.href = '{devblocks_url}{/devblocks_url}';
			
		})
		.on('cerb-peek-closed', function(e) {
		})
		;
	{/if}
	
	// Download button
	{if $is_downloadable}
	$('#btnDisplayAttachmentDownload')
		.on('click', function(e) {
			window.open('{devblocks_url}c=files&id={$attachment->id}&name={$attachment->name|devblocks_permalink}{/devblocks_url}', '_blank');
		});
	{/if}
});
</script>

{if $pref_keyboard_shortcuts}
<script type="text/javascript">
$(function() {
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
					$tabs = $("#attachmentTabs").tabs();
					$tabs.tabs('option', 'active', idx);
				} catch(ex) { }
				break;
			case 101:  // (E) edit
				try {
					$('#btnDisplayAttachmentEdit').click();
				} catch(ex) { }
				break;
			case 109:  // (M) macros
				try {
					$('#btnDisplayMacros').click();
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
});
</script>
{/if}

{include file="devblocks:cerberusweb.core::internal/profiles/profile_common_scripts.tpl"}
